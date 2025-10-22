<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BlockedDate;

class BlockedDateController extends Controller
{
    public function index()
    {
        $blockedDates = BlockedDate::orderBy('date', 'asc')->get();
        return view('admin.block_dates', compact('blockedDates'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date|unique:blocked_dates,date',
        ]);

        BlockedDate::create([
            'date' => $request->date,
            'reason' => $request->reason,
        ]);

        return redirect()->back()->with('success', 'Date blocked successfully.');
    }
}
