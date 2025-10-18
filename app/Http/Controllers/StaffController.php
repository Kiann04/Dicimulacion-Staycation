<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Booking;
use App\Models\Inquiry;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Mail\InquiryReply;
use Illuminate\Support\Facades\Auth;
use App\Models\AuditLog;

class StaffController extends Controller
{
    public function dashboard(Request $request)
    {
        $search = $request->input('search');

        // Search bookings by guest name, email, or staycation name
        $bookings = Booking::with(['user', 'staycation'])
            ->when($search, function ($query, $search) {
                $query->whereHas('user', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
                })
                ->orWhereHas('staycation', function ($q) use ($search) {
                    $q->where('house_name', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->get();

        $totalBookings = Booking::count();

        return view('staff.dashboard', compact('totalBookings', 'bookings', 'search'));
    }


    public function customers(Request $request)
    {
        $search = $request->input('search');
        $customers = User::where('usertype', 'user')
            ->when($search, fn($q) => $q->where('name', 'like', "%$search%")
                                        ->orWhere('email', 'like', "%$search%"))
            ->get();

        return view('staff.customers', compact('customers', 'search'));
    }

    public function viewCustomerBookings($id)
    {
        $customer = User::findOrFail($id);
        $bookings = Booking::where('user_id', $id)->get();
        return view('staff.customer_bookings', compact('customer', 'bookings'));
    }

    public function bookings()
    {
        $bookings = Booking::orderByDesc('id')->get();
        return view('staff.view_bookings', compact('bookings'));
    }

    // List all inquiries
public function messages()
{
    $inquiries = Inquiry::latest()->get();
    return view('staff.messages', compact('inquiries'));
}

// View single message
public function viewMessage($id)
{
    $inquiry = Inquiry::findOrFail($id);

    if ($inquiry->status === 'unread') {
        $inquiry->status = 'read';
        $inquiry->save();
    }

    return view('staff.view_message', compact('inquiry'));
}

// Show reply form
public function replyMessageForm($id)
{
    $inquiry = Inquiry::findOrFail($id);
    return view('staff.reply_message', compact('inquiry'));
}

// Send reply
public function sendReplyMessage(Request $request, $id)
{
    $request->validate([
        'message' => 'required|string',
    ]);

    $inquiry = Inquiry::findOrFail($id);

    // ✉️ Send reply email
    Mail::to($inquiry->email)->send(new InquiryReply($request->message, $inquiry));

    // ✅ Mark as read if unread
    if ($inquiry->status === 'unread') {
        $inquiry->status = 'read';
        $inquiry->save();
    }

    // 🧾 Add to audit logs
    AuditLog::create([
        'user_id'    => Auth::id(), // the currently logged-in admin
        'action'     => 'Replied to Inquiry',
        'description'=> "Admin replied to inquiry #{$inquiry->id} ({$inquiry->email}).",
        'ip_address' => request()->ip(),
    ]);

    return redirect()->back()->with('success', 'Your reply has been sent successfully!');
}


// Delete message
public function deleteMessage($id)
{
    Inquiry::destroy($id);
    return redirect()->route('staff.messages')->with('success', 'Message deleted!');
}
}