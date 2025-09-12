<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Booking;
use App\Models\Staycation;
use App\Models\Inquiry;
use App\Models\Report;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Mail\InquiryReply;
use App\Mail\BookingApproved;
use App\Mail\BookingDeclined;

class StaffController extends Controller
{
    public function dashboard()
    {
        Booking::whereDate('end_date', '<', Carbon::today())
            ->where('status', '!=', 'completed')
            ->update(['status' => 'completed']);

        $totalUsers     = User::count();
        $totalBookings  = Booking::count();
        $totalRevenue   = Booking::sum('total_price');
        $bookings       = Booking::latest()->take(10)->get();

        return view('staff.dashboard', compact(
            'totalUsers',
            'totalBookings',
            'totalRevenue',
            'bookings'
        ));
    }

    public function customers(Request $request)
    {
        $search = $request->input('search');

        $customers = User::where('usertype', 'user')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->get();

        return view('staff.customers', compact('customers', 'search'));
    }

    public function analytics()
    {
        $currentMonth = Carbon::now()->month;
        $currentYear  = Carbon::now()->year;

        $bookingsPerMonth = Booking::selectRaw('MONTH(created_at) as month, COUNT(*) as total')
            ->whereYear('created_at', $currentYear)
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month')
            ->toArray();

        $months = [];
        $totals = [];
        for ($m = 1; $m <= 12; $m++) {
            $months[] = date('F', mktime(0, 0, 0, $m, 1));
            $totals[] = $bookingsPerMonth[$m] ?? 0;
        }

        $monthlyBookings = Booking::whereMonth('created_at', $currentMonth)->count();
        $monthlyRevenue = Booking::join('staycations', 'bookings.staycation_id', '=', 'staycations.id')
            ->whereMonth('bookings.created_at', $currentMonth)
            ->sum('staycations.house_price');
        $newUsers = User::whereMonth('created_at', $currentMonth)->count();

        return view('staff.analytics', compact(
            'monthlyBookings',
            'monthlyRevenue',
            'newUsers',
            'months',
            'totals'
        ));
    }

    public function messages()
    {
        $inquiries = Inquiry::latest()->get();
        return view('staff.messages', compact('inquiries'));
    }

    public function viewMessage($id)
    {
        $inquiry = Inquiry::findOrFail($id);

        if ($inquiry->status === 'unread') {
            $inquiry->status = 'read';
            $inquiry->save();
        }

        return view('staff.view_messages', compact('inquiry'));
    }

    public function deleteMessage($id)
    {
        Inquiry::destroy($id);
        return redirect()->route('staff.messages')->with('success', 'Message deleted!');
    }

    public function bookings()
    {
        return view('staff.bookings');
    }

    public function reports()
    {
        $reports = Report::all();
        $currentMonth = Carbon::now()->month;
        $currentYear  = Carbon::now()->year;
        $monthlyBookings = Booking::whereYear('created_at', $currentYear)
                                  ->whereMonth('created_at', $currentMonth)
                                  ->count();

        return view('staff.reports', compact('reports', 'monthlyBookings'));
    }

    public function generateReport(Request $request)
    {
        $request->validate([
            'report_type' => 'required',
            'report-year' => 'required|integer|min:2000|max:' . date('Y'),
        ]);

        $type = $request->input('report_type');
        $year = $request->input('report-year');

        $bookings = Booking::with('staycation')
            ->whereYear('created_at', $year)
            ->get();

        $monthlyRevenue = $bookings->groupBy(function($b) {
            return Carbon::parse($b->created_at)->format('F');
        })->map(function($monthBookings) {
            return $monthBookings->sum(fn($b) => $b->staycation->house_price ?? 0);
        });

        $totalRevenue = $bookings->sum(fn($b) => $b->staycation->house_price ?? 0);

        $pdf = Pdf::loadView('staff.reports_pdf', compact('bookings', 'monthlyRevenue', 'totalRevenue', 'type', 'year'));

        return $pdf->download('Annual_Report_' . $year . '.pdf');
    }

    public function downloadReport($id)
    {
        $report = Report::findOrFail($id);
        $path   = storage_path('app/reports/' . $report->file_name);

        return response()->download($path);
    }

    public function settings()
    {
        return view('staff.settings');
    }

    public function addProduct()
    {
        return view('staff.addproduct');
    }

    public function view_bookings()
    {
        $bookings = Booking::orderByDesc('id')->get();
        return view('staff.view_bookings', compact('bookings'));
    }

    public function view_staycation_bookings($staycation_id)
    {
        $bookings = Booking::where('staycation_id', $staycation_id)
                           ->orderByDesc('id')
                           ->get();

        return view('staff.view_bookings', compact('bookings', 'staycation_id'));
    }

    public function editBooking($id)
    {
        $booking = Booking::findOrFail($id);
        $staycations = Staycation::all();
        return view('staff.update_bookings', compact('booking', 'staycations'));
    }

    public function updateBooking(Request $request, $id)
    {
        $request->validate([
            'staycation_id' => 'required|exists:staycations,id',
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'guest_number' => 'required|integer|min:1',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $booking = Booking::findOrFail($id);
        $booking->update($request->only([
            'staycation_id',
            'name',
            'phone',
            'guest_number',
            'start_date',
            'end_date',
        ]));

        return redirect()->back()->with('success', 'Booking updated successfully!');
    }

    public function replyMessageForm($id)
    {
        $inquiry = Inquiry::findOrFail($id);
        return view('staff.reply_message', compact('inquiry'));
    }

    public function sendReplyMessage(Request $request, $id)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $inquiry = Inquiry::findOrFail($id);
        Mail::to($inquiry->email)->send(new InquiryReply($request->message, $inquiry));

        return redirect()->route('staff.view_messages', $id)->with('success', 'Reply sent successfully!');
    }

    public function approveBooking($id)
    {
        $booking = Booking::with('user', 'staycation')->findOrFail($id);
        $booking->status = 'approved';
        $booking->save();

        if ($booking->user && $booking->user->email) {
            Mail::to($booking->user->email)->send(new BookingApproved($booking));
        }

        return back()->with('success', 'Booking approved and email sent.');
    }

    public function declineBooking($id)
    {
        $booking = Booking::with('user', 'staycation')->findOrFail($id);

        if ($booking->user && $booking->user->email) {
            Mail::to($booking->user->email)->send(new BookingDeclined($booking));
        }

        $booking->delete();

        return back()->with('success', 'Booking declined, email sent, and record deleted.');
    }

    public function viewBookings($id)
    {
        $customer = User::findOrFail($id);
        $bookings = Booking::where('user_id', $id)->get();

        return view('staff.customer_bookings', compact('customer', 'bookings'));
    }
}
