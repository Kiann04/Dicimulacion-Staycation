<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use PDF;
use Carbon\Carbon;

class ReportController extends Controller
{
    // 🔹 Handles form submission
    public function generate(Request $request)
    {
        $reportType = $request->input('report_type');
        $year = $request->input('report_year');
        $month = $request->input('report_month');

        // Redirects to download route with parameters
        return redirect()->route('admin.reports.download', [
            'type' => strtolower($reportType),
            'year' => $year,
            'month' => $month,
        ]);
    }

    // 🔹 Generates and downloads the PDF report
    public function download($type, $year, $month = null)
    {
        if ($type === 'monthly' && $month) {
            $bookings = Booking::whereYear('start_date', $year)
                ->whereMonth('start_date', $month)
                ->get();

            $reportTitle = 'Monthly Report - ' . Carbon::createFromFormat('m', $month)->format('F') . ' ' . $year;
            $fileName = 'monthly_report_' . $year . '_' . $month . '.pdf';
        } else {
            $bookings = Booking::whereYear('start_date', $year)->get();

            $reportTitle = 'Annual Report - ' . $year;
            $fileName = 'annual_report_' . $year . '.pdf';
        }

        $pdf = PDF::loadView('admin.reports.pdf', [
            'bookings' => $bookings,
            'reportTitle' => $reportTitle,
            'year' => $year,
            'month' => $month,
        ]);

        return $pdf->download($fileName);
    }
}
