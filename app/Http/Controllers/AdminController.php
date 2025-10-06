<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\Staycation;
use App\Models\User;
use App\Models\Inquiry;
use App\Models\Report; 
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Mail\InquiryReply;
use Illuminate\Support\Facades\Mail;
use App\Mail\BookingApproved;
use App\Mail\BookingDeclined;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;


class AdminController extends Controller
{
    public function dashboard()
    {
        // Automatically mark completed bookings
        Booking::whereDate('end_date', '<', Carbon::today())
            ->where('status', '!=', 'completed')
            ->update(['status' => 'completed']);

        // Stats (unchanged)
        $totalUsers    = User::count();
        $totalBookings = Booking::count();
        $totalRevenue  = Booking::where('payment_status', 'paid')->sum('total_price');

        // Only show unpaid bookings on dashboard
        $bookings = Booking::where('payment_status', 'unpaid')
            ->latest()
            ->take(10)
            ->get();

        return view('admin.dashboard', compact(
            'totalUsers',
            'totalBookings',
            'totalRevenue',
            'bookings'
        ));
    }

    
    public function customers(Request $request)
    {
        $search = $request->input('search');

        $customers = \App\Models\User::where('usertype', 'user')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->get();

        return view('admin.customers', compact('customers', 'search'));
    }

    public function analytics()
    {
    $currentMonth = \Carbon\Carbon::now()->month;
    $currentYear = \Carbon\Carbon::now()->year;

    // Get bookings grouped by month
    $bookingsPerMonth = \App\Models\Booking::selectRaw('MONTH(created_at) as month, COUNT(*) as total')
        ->whereYear('created_at', $currentYear)
        ->groupBy('month')
        ->orderBy('month')
        ->pluck('total', 'month')
        ->toArray();

    // Ensure all 12 months are present
    $months = [];
    $totals = [];
    for ($m = 1; $m <= 12; $m++) {
        $months[] = date('F', mktime(0, 0, 0, $m, 1));
        $totals[] = $bookingsPerMonth[$m] ?? 0;
    }

    // Dashboard cards
    $monthlyBookings = \App\Models\Booking::whereMonth('created_at', $currentMonth)->count();
    $monthlyRevenue = \App\Models\Booking::join('staycations', 'bookings.staycation_id', '=', 'staycations.id')
        ->whereMonth('bookings.created_at', $currentMonth)
        ->sum('staycations.house_price');
    $newUsers = \App\Models\User::whereMonth('created_at', $currentMonth)->count();

    return view('admin.analytics', compact(
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
        return view('admin.messages', compact('inquiries'));
    }

    // View a specific inquiry
    public function viewMessage($id)
    {
        $inquiry = Inquiry::findOrFail($id);

        // If unread, mark as read
        if ($inquiry->status === 'unread') {
            $inquiry->status = 'read';
            $inquiry->save();
        }

        return view('admin.view_messages', compact('inquiry'));
    }

    // Delete an inquiry
    public function deleteMessage($id)
    {
        Inquiry::destroy($id);
        return redirect()->route('admin.messages')->with('success', 'Message deleted!');
    }

    public function bookings() {
        return view('admin.bookings');
    }

    public function reports()
    {
    $reports = Report::all();

    // Count bookings for the current month
    $currentMonth = Carbon::now()->month;
    $currentYear  = Carbon::now()->year;
    $monthlyBookings = Booking::whereYear('created_at', $currentYear)
                              ->whereMonth('created_at', $currentMonth)
                              ->count();

    return view('admin.reports', compact('reports', 'monthlyBookings'));
    }   

    public function generateReport(Request $request)
    {
    $request->validate([
        'report_type' => 'required',
        'report-year' => 'required|integer|min:2000|max:' . date('Y'),
    ]);

    $type = $request->input('report_type');
    $year = $request->input('report-year');

    // Get all bookings for the selected year
    $bookings = Booking::with('staycation')
        ->whereYear('created_at', $year)
        ->get();

    // Calculate monthly revenue
    $monthlyRevenue = $bookings->groupBy(function($b) {
    return \Carbon\Carbon::parse($b->created_at)->format('F');
    })->map(function($monthBookings) {
        return $monthBookings->sum('total_price');
    });
    $totalRevenue = Booking::where('payment_status', 'paid')->sum('total_price');


    $pdf = Pdf::loadView('admin.reports_pdf', compact('bookings', 'monthlyRevenue', 'totalRevenue', 'type', 'year'));

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
        // Fetch latest 50 logs
        $auditLogs = AuditLog::latest()->paginate(50);

        return view('admin.settings', compact('auditLogs'));
    }
    public function auditLogs()
{
    // Fetch the latest 50 logs
    $auditLogs = AuditLog::latest()->paginate(50);

    return view('admin.audit_logs', compact('auditLogs'));
}

    public function addProduct() {
        return view('admin.addproduct');
    }
    public function view_bookings()
    {
        $bookings = Booking::orderByDesc('id')->get();
        return view('admin.view_bookings', compact('bookings'));
    }

    // bookings by staycation
    public function getEvents($staycationId)
    {
        $bookings = Booking::where('staycation_id', $staycationId)->get();

        $events = $bookings->map(function ($booking) {
            return [
                'title' => 'Booked',
                'start' => $booking->start_date,
                'end' => Carbon::parse($booking->end_date)->addDay()->toDateString(), // ✅ include last day
                'color' => '#999', // grey background
                'display' => 'background', // block day
            ];
        });

        return response()->json($events);
    }
    public function view_staycation_bookings($staycation_id)
    {
        $bookings = Booking::where('staycation_id', $staycation_id)
                           ->orderByDesc('id')
                           ->get();

        return view('admin.view_bookings', compact('bookings', 'staycation_id'));
    }
    public function editBooking($id)
    {
        $booking = Booking::findOrFail($id);
        $staycations = Staycation::all(); // all available staycations
        return view('admin.update_bookings', compact('booking', 'staycations'));
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
        return view('admin.reply_message', compact('inquiry'));
    }

    public function sendReplyMessage(Request $request, $id)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $inquiry = Inquiry::findOrFail($id);
        Mail::to($inquiry->email)->send(new InquiryReply($request->message, $inquiry));

        // Mark as read
        if($inquiry->status === 'unread'){
            $inquiry->status = 'read';
            $inquiry->save();
        }

        return redirect()->back()->with('success', 'Your reply has been sent successfully!');
    }

    public function viewBookings($id)
    {
        $customer = User::findOrFail($id);
        $bookings = Booking::where('user_id', $id)->get();

        return view('admin.customer_bookings', compact('customer', 'bookings'));
    }
    public function toggleAvailability($id)
    {
    $staycation = Staycation::findOrFail($id);

    // Toggle availability
    $staycation->house_availability = $staycation->house_availability === 'available' ? 'unavailable' : 'available';
    $staycation->save();

    return redirect()->back()->with('success', 'Staycation availability updated!');
    }
    public function deleteBooking($id)
    {
        $booking = Booking::findOrFail($id);

        if ($booking->payment_status !== 'unpaid') {
            return response()->json(['error' => 'Only unpaid bookings can be deleted.'], 403);
        }

        $booking->delete();

        AuditLog::create([
            'user_id'    => Auth::id(),
            'action'     => 'Booking Deleted',
            'description'=> "Booking ID: {$booking->id} deleted by admin.",
            'ip_address' => request()->ip(),
        ]);

        return response()->json(['success' => 'Unpaid booking deleted successfully!']);
    }

}


