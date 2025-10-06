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
use Illuminate\Support\Facades\DB;


class AdminController extends Controller
{
    public function dashboard()
    {
        // Automatically mark completed bookings
        Booking::whereDate('end_date', '<', Carbon::today())
            ->where('status', '!=', 'completed')
            ->update(['status' => 'completed']);

        // Stats
        $totalUsers    = User::count();
        $totalBookings = Booking::count();

        // ✅ Updated Revenue Logic (Full + Half Paid)
        $totalRevenue = Booking::sum(DB::raw("
            CASE 
                WHEN payment_status = 'paid' THEN total_price
                WHEN payment_status = 'half_paid' THEN total_price / 2
                ELSE 0
            END
        "));

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
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        // --- Cards ---
        $monthlyBookings = Booking::whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->count();

        $monthlyRevenue = Booking::whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->sum('total_price');

        $newUsers = User::whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->count();

        // --- Average Occupancy (optional) ---
        $totalDays = now()->daysInMonth;
        $bookedDays = Booking::whereMonth('start_date', $currentMonth)
            ->whereYear('start_date', $currentYear)
            ->get()
            ->sum(function ($b) {
                return Carbon::parse($b->start_date)->diffInDays(Carbon::parse($b->end_date));
            });

        $averageOccupancy = round(($bookedDays / ($totalDays * 1)) * 100) . '%'; // assuming 1 property

        // --- Charts (last 6 months) ---
        $months = collect(range(0, 5))
            ->map(fn($i) => Carbon::now()->subMonths($i)->format('M'))
            ->reverse()
            ->values();

        $totals = collect(range(0, 5))
            ->map(function ($i) {
                $month = Carbon::now()->subMonths($i);
                return Booking::whereMonth('created_at', $month->month)
                    ->whereYear('created_at', $month->year)
                    ->count();
            })
            ->reverse()
            ->values();

        $revenues = collect(range(0, 5))
            ->map(function ($i) {
                $month = Carbon::now()->subMonths($i);
                return Booking::whereMonth('created_at', $month->month)
                    ->whereYear('created_at', $month->year)
                    ->sum('total_price');
            })
            ->reverse()
            ->values();

        return view('admin.analytics', [
            'monthlyBookings' => $monthlyBookings,
            'monthlyRevenue' => $monthlyRevenue,
            'newUsers' => $newUsers,
            'averageOccupancy' => $averageOccupancy,
            'months' => $months,
            'totals' => $totals,
            'revenues' => $revenues,
        ]);
    }

    public function messages()
{
    // Customer inquiries (existing)
    $inquiries = Inquiry::latest()->get();

    // Booking payment proofs (pending bookings with uploaded proof)
    $bookingProofs = Booking::with('user', 'staycation')
                        ->where('status', 'pending')
                        ->whereNotNull('payment_proof')
                        ->latest()
                        ->get();

    return view('admin.messages', compact('inquiries', 'bookingProofs'));
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
            'report_year' => 'required|integer|min:2000|max:' . date('Y'),
        ]);

        $year = $request->input('report_year');
        $type = $request->input('report_type');

        // Only include paid and half_paid bookings
        $bookings = Booking::with('staycation')
            ->whereYear('created_at', $year)
            ->whereIn('payment_status', ['paid', 'half_paid'])
            ->get();

        // Initialize months
        $months = collect(range(1,12))->mapWithKeys(function($m){
            return [Carbon::create()->month($m)->format('F') => ['bookings'=>0, 'revenue'=>0]];
        })->toArray();

        foreach($bookings as $b){
            $monthName = Carbon::parse($b->created_at)->format('F');
            $months[$monthName]['bookings'] += 1;

            // Add full price for 'paid', half price for 'half_paid'
            if($b->payment_status === 'paid'){
                $months[$monthName]['revenue'] += $b->total_price;
            } elseif($b->payment_status === 'half_paid'){
                $months[$monthName]['revenue'] += $b->total_price / 2;
            }
        }

        $totalRevenue = array_sum(array_column($months, 'revenue'));
        $totalBookings = array_sum(array_column($months, 'bookings'));

        $pdf = Pdf::loadView('admin.reports_pdf', [
            'bookings' => $bookings,
            'months' => $months,
            'totalRevenue' => $totalRevenue,
            'totalBookings' => $totalBookings,
            'type' => $type,
            'year' => $year
        ]);

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
    public function viewMessagesAndProofs()
    {
        // Load inquiries
        $inquiries = Inquiry::latest()->get();

        // Load bookings with payment proofs
        $bookingProofs = Booking::with(['user', 'staycation'])
                            ->whereNotNull('payment_proof')
                            ->latest()
                            ->get();

        return view('admin.messages_and_proofs', compact('inquiries', 'bookingProofs'));
    }


}


