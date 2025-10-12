<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class ConsentPopupController extends Controller
{
    public function save(Request $request)
    {
        $choice = $request->input('choice', 'reject'); // default to reject if not sent
        Cookie::queue('user_consent', $choice, 525600); // 1 year in minutes

        return response()->json(['message' => 'Consent saved', 'choice' => $choice]);
    }
}
