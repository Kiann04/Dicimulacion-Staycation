<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use Carbon\Carbon;
use PDF;

class ReportController extends Controller
{
    /**
     * 🔹 Handles form submission — redirects to download route
     */
    public function generate(Request $request)
    {
        $reportType = strtolower($request->input('report_type'));
        $year = $request->input('report_year');
        $month = $request->input('report_month');
        $week = $request->input('report_week');

        // Redirect to the correct download route
        return redirect()->route('reports.download', [
            'type' => $reportType,
            'year' => $year,
            'month' => $month,
            'week' => $week,
        ]);
    }

    /**
     * 🔹 Generates and downloads the PDF report
     */
    public function download($type, $year, $month = null, $week = null)
    {
        if ($type === 'weekly' && $week) {
            // Compute start and end dates of the selected week
            $startOfWeek = Carbon::now()->setISODate($year, $week)->startOfWeek(Carbon::MONDAY);
            $endOfWeek = (clone $startOfWeek)->endOfWeek(Carbon::SUNDAY);

            // Fetch bookings for that week
            $bookings = Booking::with(['user', 'staycation'])
                ->whereBetween('start_date', [$startOfWeek, $endOfWeek])
                ->get();

            $reportTitle = "Weekly Report - Week {$week} ({$startOfWeek->format('M d')} – {$endOfWeek->format('M d, Y')})";
            $fileName = "weekly_report_week_{$week}_{$year}.pdf";
            $reportType = 'Weekly';

        } elseif ($type === 'monthly' && $month) {
            // Fetch monthly bookings
            $bookings = Booking::with(['user', 'staycation'])
                ->whereYear('start_date', $year)
                ->whereMonth('start_date', $month)
                ->get();

            $monthName = Carbon::createFromFormat('m', $month)->format('F');
            $reportTitle = "Monthly Report - {$monthName} {$year}";
            $fileName = "monthly_report_{$year}_{$month}.pdf";
            $reportType = 'Monthly';

        } else {
            // Fetch annual bookings
            $bookings = Booking::with(['user', 'staycation'])
                ->whereYear('start_date', $year)
                ->get();

            $reportTitle = "Annual Report - {$year}";
            $fileName = "annual_report_{$year}.pdf";
            $reportType = 'Yearly';
        }

        // Generate PDF
        $pdf = PDF::loadView('admin.reports.pdf', [
            'bookings' => $bookings,
            'reportTitle' => $reportTitle,
            'reportType' => $reportType,
            'year' => $year,
            'month' => $month,
            'week' => $week,
        ]);

        // Download the PDF
        return $pdf->download($fileName);
    }
}
