<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class ConsentPopupController extends Controller
{
    public function save(Request $request)
    {
        $choice = $request->input('choice', 'reject'); // default reject
        // Save cookie for 1 year (525600 minutes)
        Cookie::queue('user_consent', $choice, 525600);

        return response()->json(['message' => 'Consent saved', 'choice' => $choice]);
    }
}
