<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BlockedDate;
use App\Models\Staycation; // add this at the top

class BlockedDateController extends Controller
{
    public function index()
    {
        $blockedDates = BlockedDate::orderBy('start_date', 'asc')->get();
        $staycations = Staycation::all(); // fetch all staycations

        return view('admin.block_dates', compact('blockedDates', 'staycations'));
    }


    public function store(Request $request)
    {
        $request->validate([
            'staycation_id' => 'required|integer',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:255',
        ]);

        BlockedDate::create([
            'staycation_id' => $request->staycation_id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'reason' => $request->reason,
        ]);

        return redirect()->back()->with('success', 'Date blocked successfully.');
    }
}
