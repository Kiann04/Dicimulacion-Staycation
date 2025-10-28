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
use App\Models\BookingHistory;
use App\Mail\BookingCancelled;
use Illuminate\Support\Facades\Hash;
use App\Models\BlockedDate;

class AdminController extends Controller
{
    public function dashboard()
    {
        // ✅ 1. Automatically mark completed bookings
        Booking::whereDate('end_date', '<', Carbon::today())
            ->where('status', '!=', 'completed')
            ->update(['status' => 'completed']);

        // ✅ 2. Overall Stats
        $totalUsers    = User::count();
        $totalBookings = Booking::count();
        $totalRevenue  = Booking::sum(DB::raw("
            CASE 
                WHEN payment_status = 'paid' THEN total_price
                WHEN payment_status = 'half_paid' THEN total_price / 2
                ELSE 0
            END
        "));

        $bookings = Booking::where('payment_status', 'unpaid')
            ->latest()
            ->take(10)
            ->get();

        // ✅ 3. Monthly Analytics
        $currentMonth = Carbon::now()->month;
        $currentYear  = Carbon::now()->year;

        $monthlyBookings = Booking::whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->count();

        $monthlyRevenue = Booking::whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->sum(DB::raw("
                CASE 
                    WHEN payment_status = 'paid' THEN total_price
                    WHEN payment_status = 'half_paid' THEN total_price / 2
                    ELSE 0
                END
            "));

        $newUsers = User::whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->count();

        $totalDays = now()->daysInMonth;
        $totalStaycations = Staycation::count();

        $bookedDays = Booking::whereMonth('start_date', $currentMonth)
            ->whereYear('start_date', $currentYear)
            ->get()
            ->sum(function ($b) {
                return Carbon::parse($b->start_date)->diffInDays(Carbon::parse($b->end_date));
            });

        $averageOccupancy = $totalStaycations > 0
        ? round(($bookedDays / ($totalDays * $totalStaycations)) * 100) . '%': '0%';

        // ✅ 4. Chart Data (last 6 months)
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
                    ->sum(DB::raw("
                        CASE 
                            WHEN payment_status = 'paid' THEN total_price
                            WHEN payment_status = 'half_paid' THEN total_price / 2
                            ELSE 0
                        END
                    "));
            })
            ->reverse()
            ->values();

        // ✅ 5. Return Single View with all data
        return view('admin.dashboard', [
            'totalUsers'        => $totalUsers,
            'totalBookings'     => $totalBookings,
            'totalRevenue'      => $totalRevenue,
            'bookings'          => $bookings,
            'monthlyBookings'   => $monthlyBookings,
            'monthlyRevenue'    => $monthlyRevenue,
            'newUsers'          => $newUsers,
            'averageOccupancy'  => $averageOccupancy,
            'months'            => $months,
            'totals'            => $totals,
            'revenues'          => $revenues,
        ]);
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
            ->orderBy('name', 'asc') // optional, sort by name
            ->paginate(10) // show 10 customers per page
            ->withQueryString(); // keep search term in URL
        
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
            ->sum(DB::raw("
            CASE 
                WHEN payment_status = 'paid' THEN total_price
                WHEN payment_status = 'half_paid' THEN total_price / 2
                ELSE 0
            END
        "));

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
                    ->sum(DB::raw("
                    CASE 
                        WHEN payment_status = 'paid' THEN total_price
                        WHEN payment_status = 'half_paid' THEN total_price / 2
                        ELSE 0
                    END
                "));
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
        // Customer inquiries (paginated)
        $inquiries = Inquiry::latest()->paginate(10); // 10 per page

        // Booking payment proofs (paginated)
       $bookingProofs = Booking::with('user', 'staycation')
                            ->latest()
                            ->paginate(10); // 10 per page

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
        // Bookings
        $bookings = Booking::where('staycation_id', $staycationId)
            ->whereNull('deleted_at')
            ->get();

        $bookingEvents = $bookings->map(function ($booking) {
            return [
                'title' => 'Booked',
                'start' => $booking->start_date,
                'end' => Carbon::parse($booking->end_date)->addDay()->toDateString(),
                'color' => '#f56565', // red
                'display' => 'background', // full background
                'allDay' => true,          // important for all-day
            ];
        });

        // Blocked Dates
        $blockedDates = BlockedDate::where('staycation_id', $staycationId)->get();

        $blockedEvents = $blockedDates->map(function ($blocked) {
            return [
                'title' => $blocked->reason ?? 'Blocked',
                'start' => $blocked->start_date,
                'end' => Carbon::parse($blocked->end_date)->addDay()->toDateString(),
                'color' => '#fcd34d', // yellow
                'display' => 'background',
                'allDay' => true,     // important for all-day
            ];
        });

        $events = $bookingEvents->merge($blockedEvents)->values();

        return response()->json($events);
    }

    public function view_staycation_bookings($staycation_id)
    {
        // Get the bookings for the specific staycation with the housename
        $bookings = Booking::where('staycation_id', $staycation_id)
                        ->orderByDesc('id')
                        ->get();

        // Fetch the staycation data for the given ID (including housename)
        $staycation = Staycation::find($staycation_id);

        return view('admin.view_bookings', compact('bookings', 'staycation', 'staycation_id'));
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
        AuditLog::create([
        'user_id'    => Auth::id(),
        'action'     => 'Replied to Inquiry',
        'description'=> "Admin replied to inquiry from {$inquiry->name} ({$inquiry->email}).",
        'ip_address' => request()->ip(),
        ]);

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

        // Only delete unpaid bookings
        if ($booking->payment_status !== 'unpaid') {
            return redirect()->back()->with('error', 'Only unpaid bookings can be deleted.');
        }

        // ✅ Copy booking data to booking_history before deleting
        DB::table('booking_history')->insert([
            'booking_id'     => $booking->id,
            'user_id'        => $booking->user_id,
            'name'           => $booking->name,
            'staycation_id'  => $booking->staycation_id,
            'start_date'     => $booking->start_date,
            'end_date'       => $booking->end_date,
            'total_price'    => $booking->total_price,
            'payment_status' => $booking->payment_status,
            'payment_proof'  => $booking->payment_proof,
            'action_by'      => Auth::check() ? Auth::user()->name : 'Admin',
            'deleted_at'     => now(), // mark when it was archived
        ]);

        // ✅ Permanently remove the booking (hard delete)
        $booking->forceDelete();

        // ✅ Log action
        AuditLog::create([
            'user_id'    => Auth::id(),
            'action'     => 'Booking Deleted',
            'description'=> "Booking ID: {$booking->id} permanently deleted and copied to history.",
            'ip_address' => request()->ip(),
        ]);
        $recipient = $booking->user->email ?? $booking->email;
        if (!empty($recipient)) {
            Mail::to($recipient)->send(new BookingCancelled($booking));
        }
        return redirect()->route('admin.cancelled')
                        ->with('success', 'Booking permanently deleted and archived to booking history.');
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
    public function messagesAndPayments(Request $request)
    {
        // Load all inquiries
        $inquiries = Inquiry::latest()->get();

        // Handle search input
        $search = $request->input('search');

        // Load booking proofs with optional search
        $bookingProofs = Booking::with(['user', 'staycation'])
            ->whereNotNull('payment_proof')
            ->when($search, function ($query, $search) {
                $query->where('id', 'like', "%{$search}%")
                    ->orWhere('transaction_number', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            })
            ->latest()
            ->get();

        // Keep track of which tab is open (default = inquiries)
        $activeTab = $request->input('tab', 'inquiries');

        return view('admin.message', compact('inquiries', 'bookingProofs', 'activeTab'));
    }
    public function showCancelledBookings()
    {
        // Fetch cancelled bookings with related staycation
        $cancelledBookings = BookingHistory::with('staycation')
            ->orderByDesc('id')
            ->get();

        return view('admin.cancelled', compact('cancelledBookings'));
    }
    public function cancelled()
    {
        // Fetch cancelled bookings (adjust according to your DB)
        $cancelledBookings = Booking::where('payment_status', 'cancelled')
                                    ->orWhereNotNull('deleted_at')
                                    ->get();

        // Return the view
        return view('admin.cancelled', compact('cancelledBookings'));
    }
    public function addStaff()
    {
        return view('admin.add_staff');
    }

    public function createStaff(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email',
            'password' => 'required|min:6',
        ]);

        // Check if email already exists
        if (User::where('email', $request->email)->exists()) {
            return redirect()->back()->with('error', 'A staff account with this email already exists.');
        }

        User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'usertype'  => 'staff',
        ]);

        return redirect()->back()->with('success', 'Staff account created successfully!');
    }
    public function index()
    {
        $staff = User::where('usertype', 'staff')->get();
        return view('admin.staff.list', compact('staff'));
    }

    // Delete a staff account
    public function destroy($id)
    {
        $staff = User::findOrFail($id);

        if ($staff->usertype === 'admin') {
            return redirect()->back()->with('error', 'You cannot delete an admin account.');
        }

        $staff->delete();

        return redirect()->back()->with('success', 'Staff account deleted successfully.');
    }
}


