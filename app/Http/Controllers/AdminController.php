<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Exceptions\BookingRuleViolation;
use App\Http\Requests\StoreStaffRequest;
use App\Http\Requests\UpdateBookingRequest;
use App\Mail\BookingCancelled;
use App\Mail\InquiryReply;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\BookingHistory;
use App\Models\Inquiry;
use App\Models\Report;
use App\Models\Staycation;
use App\Models\User;
use App\Services\BookingArchiveService;
use App\Services\BookingAvailabilityService;
use App\Services\BookingInventoryService;
use App\Services\RevenueReportingService;
use App\Support\Money;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AdminController extends Controller
{
    public function dashboard(RevenueReportingService $revenue)
    {
        // Finished stays are marked completed by the scheduled
        // `bookings:complete-past` command. Rendering this dashboard must not
        // change booking state.

        // ✅ 2. Overall Stats
        $totalUsers = User::count();
        $totalBookings = Booking::count();
        $totalRevenue = $revenue->totalVerifiedAmount(Booking::query())->toDecimalString();

        $bookings = Booking::query()
            ->with(['user', 'staycation'])
            ->whereIn('payment_status', [
                PaymentStatus::Unpaid->value,
                PaymentStatus::Pending->value,
            ])
            ->latest()
            ->take(10)
            ->get();

        // ✅ 3. Monthly Analytics
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        $monthlyBookings = Booking::whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->count();

        $monthlyRevenue = $revenue->totalVerifiedAmount(
            Booking::query()
                ->whereMonth('created_at', $currentMonth)
                ->whereYear('created_at', $currentYear)
        )->toDecimalString();

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
        ? round(($bookedDays / ($totalDays * $totalStaycations)) * 100).'%' : '0%';

        // ✅ 4. Chart Data (last 6 months)
        $months = collect(range(0, 5))
            ->map(fn ($i) => Carbon::now()->subMonths($i)->format('M'))
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
            ->map(function ($i) use ($revenue) {
                $month = Carbon::now()->subMonths($i);

                // Float only at the presentation boundary, for the chart.
                return $revenue->totalVerifiedAmount(
                    Booking::query()
                        ->whereMonth('created_at', $month->month)
                        ->whereYear('created_at', $month->year)
                )->toFloat();
            })
            ->reverse()
            ->values();

        // ✅ 5. Return Single View with all data
        return view('admin.dashboard', [
            'totalUsers' => $totalUsers,
            'totalBookings' => $totalBookings,
            'totalRevenue' => $totalRevenue,
            'bookings' => $bookings,
            'monthlyBookings' => $monthlyBookings,
            'monthlyRevenue' => $monthlyRevenue,
            'newUsers' => $newUsers,
            'averageOccupancy' => $averageOccupancy,
            'months' => $months,
            'totals' => $totals,
            'revenues' => $revenues,
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

    public function analytics(RevenueReportingService $revenue)
    {
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        // --- Cards ---
        $monthlyBookings = Booking::whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->count();

        $monthlyRevenue = $revenue->totalVerifiedAmount(
            Booking::query()
                ->whereMonth('created_at', $currentMonth)
                ->whereYear('created_at', $currentYear)
        )->toDecimalString();

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

        $averageOccupancy = round(($bookedDays / ($totalDays * 1)) * 100).'%'; // assuming 1 property

        // --- Charts (last 6 months) ---
        $months = collect(range(0, 5))
            ->map(fn ($i) => Carbon::now()->subMonths($i)->format('M'))
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
            ->map(function ($i) use ($revenue) {
                $month = Carbon::now()->subMonths($i);

                // Float only at the presentation boundary, for the chart.
                return $revenue->totalVerifiedAmount(
                    Booking::query()
                        ->whereMonth('created_at', $month->month)
                        ->whereYear('created_at', $month->year)
                )->toFloat();
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

    public function bookings()
    {
        return view('admin.bookings');
    }

    public function reports()
    {
        $reports = Report::all();

        // Count bookings for the current month
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;
        $monthlyBookings = Booking::whereYear('created_at', $currentYear)
            ->whereMonth('created_at', $currentMonth)
            ->count();

        return view('admin.reports', compact('reports', 'monthlyBookings'));
    }

    public function generateReport(Request $request, RevenueReportingService $revenue)
    {
        $request->validate([
            'report_type' => 'required',
            'report_year' => 'required|integer|min:2000|max:'.date('Y'),
        ]);

        $year = $request->input('report_year');
        $type = $request->input('report_type');

        // Only include paid and half_paid bookings
        $bookings = Booking::with('staycation')
            ->whereYear('created_at', $year)
            ->whereIn('payment_status', ['paid', 'half_paid'])
            ->get();

        // Accumulated in centavos. Adding floats month by month and then summing
        // the months would drift, and a report is exactly where that shows up.
        $months = collect(range(1, 12))->mapWithKeys(function ($m) {
            return [Carbon::create()->month($m)->format('F') => ['bookings' => 0, 'revenue' => Money::zero()]];
        })->toArray();

        $totalRevenue = Money::zero();

        foreach ($bookings as $b) {
            $monthName = Carbon::parse($b->created_at)->format('F');
            $verified = $revenue->verifiedAmountFor($b);

            $months[$monthName]['bookings'] += 1;
            $months[$monthName]['revenue'] = $months[$monthName]['revenue']->plus($verified);
            $totalRevenue = $totalRevenue->plus($verified);
        }

        $totalBookings = array_sum(array_column($months, 'bookings'));

        // Formatted only now that every addition is done.
        $months = array_map(
            fn (array $month): array => [
                'bookings' => $month['bookings'],
                'revenue' => $month['revenue']->toDecimalString(),
            ],
            $months
        );

        $totalRevenue = $totalRevenue->toDecimalString();

        $pdf = Pdf::loadView('admin.reports_pdf', [
            'bookings' => $bookings,
            'months' => $months,
            'totalRevenue' => $totalRevenue,
            'totalBookings' => $totalBookings,
            'type' => $type,
            'year' => $year,
        ]);

        return $pdf->download('Annual_Report_'.$year.'.pdf');
    }

    public function downloadReport($id)
    {
        $report = Report::findOrFail($id);
        $path = storage_path('app/reports/'.$report->file_name);

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

    public function addProduct()
    {
        return view('admin.addproduct');
    }

    public function view_bookings()
    {
        $bookings = Booking::orderByDesc('id')->get();

        return view('admin.view_bookings', compact('bookings'));
    }

    /**
     * Calendar events for booked and blocked nights.
     */
    public function getEvents(int $staycationId): \Illuminate\Http\JsonResponse
    {
        return response()->json(
            app(BookingAvailabilityService::class)->calendarEvents($staycationId)
        );
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

    /**
     * Move an existing booking, holding it to the same domain rules a customer
     * booking must satisfy: a real staycation that is open, at least one night,
     * a legal guest count, no overlap, and a server-recalculated price.
     *
     * The lock, the checks and the write all happen in one transaction inside
     * BookingInventoryService, using the same staycation-row mutex that booking
     * creation takes.
     */
    public function updateBooking(UpdateBookingRequest $request, $id): RedirectResponse
    {
        $booking = Booking::findOrFail($id);

        try {
            app(BookingInventoryService::class)->rescheduleBooking(
                $booking,
                (int) $request->validated('staycation_id'),
                (int) $request->validated('guest_number'),
                CarbonImmutable::parse($request->validated('start_date'))->startOfDay(),
                CarbonImmutable::parse($request->validated('end_date'))->startOfDay(),
                $request->safe()->only(['name', 'phone']),
            );
        } catch (BookingRuleViolation $exception) {
            return redirect()->back()
                ->withInput()
                ->with('error', $exception->getMessage());
        }

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
        if ($inquiry->status === 'unread') {
            $inquiry->status = 'read';
            $inquiry->save();
        }
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'Replied to Inquiry',
            'description' => "Admin replied to inquiry from {$inquiry->name} ({$inquiry->email}).",
            'ip_address' => request()->ip(),
        ]);

        return redirect()->back()->with('success', 'Your reply has been sent successfully!');
    }

    public function viewBookings($id)
    {
        $customer = User::findOrFail($id);
        $bookings = Booking::query()
            ->with('staycation')
            ->where('user_id', $id)
            ->get();

        return view('admin.customer_bookings', compact('customer', 'bookings'));
    }

    /**
     * Open or close a staycation for booking.
     *
     * Takes the same staycation lock booking creation does: closing a property
     * while a booking is being created for it would otherwise let the booking's
     * "is it open?" check pass against a value that is being changed underneath
     * it. Reading and writing under the lock makes the toggle atomic too, so two
     * simultaneous clicks cannot both flip from the same starting value.
     */
    public function toggleAvailability($id): RedirectResponse
    {
        app(BookingInventoryService::class)->withStaycationLock((int) $id, function (Staycation $staycation): void {
            $staycation->update([
                'house_availability' => $staycation->house_availability === 'available'
                    ? 'unavailable'
                    : 'available',
            ]);
        });

        return redirect()->back()->with('success', 'Staycation availability updated!');
    }

    /**
     * Archive a booking and remove it from the live table.
     *
     * The archive write and the delete are one transaction inside
     * BookingArchiveService, so the booking can never end up recorded twice or
     * erased without a record.
     */
    public function deleteBooking($id, BookingArchiveService $archive): RedirectResponse
    {
        $booking = Booking::with('user')->findOrFail($id);

        try {
            // The authoritative eligibility check lives inside the service,
            // against the locked row. Checking it here as well would only be a
            // convenience, and a stale one.
            $archive->archiveAndDelete($booking);
        } catch (BookingRuleViolation $exception) {
            return redirect()->back()->with('error', $exception->getMessage());
        }

        $recipient = $booking->user->email ?? $booking->email;

        if (filled($recipient)) {
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
        $cancelledBookings = Booking::withTrashed()
            ->where(function ($query) {
                $query->where('payment_status', 'cancelled')
                    ->orWhereNotNull('deleted_at');
            })
            ->get();

        // Return the view
        return view('admin.cancelled', compact('cancelledBookings'));
    }

    public function addStaff()
    {
        return view('admin.add_staff');
    }

    public function createStaff(StoreStaffRequest $request): RedirectResponse
    {
        $staff = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => Hash::make($request->validated('password')),
            'usertype' => 'staff',
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'Staff Account Created',
            'description' => "Staff account {$staff->email} was created.",
            'ip_address' => request()->ip(),
        ]);

        return redirect()->back()->with('success', 'Staff account created successfully!');
    }

    public function index()
    {
        $staff = User::where('usertype', 'staff')->get();

        return view('admin.staff.list', compact('staff'));
    }

    /**
     * Delete a staff account.
     *
     * The target is resolved as a staff account rather than as any user, so this
     * endpoint cannot be pointed at a customer or another administrator. A
     * non-staff id is simply not found here.
     */
    public function destroy($id): RedirectResponse
    {
        $staff = User::query()
            ->where('usertype', 'staff')
            ->findOrFail($id);

        $staff->delete();

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'Staff Account Deleted',
            'description' => "Staff account {$staff->email} was deleted.",
            'ip_address' => request()->ip(),
        ]);

        return redirect()->back()->with('success', 'Staff account deleted successfully.');
    }
}
