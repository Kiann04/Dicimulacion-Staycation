<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class OfflineChatBotController extends Controller
{
    // Your staycation info / FAQ
    private $faq = [
        'check in' => 'Check-in time is 2:00 PM.',
        'check out' => 'Check-out time is 12:00 PM.',
        'price standard room' => 'Standard Room costs ₱2,500 per night.',
        'price family suite' => 'Family Suite costs ₱4,500 per night.',
        'amenities' => 'We offer Free WiFi, Pool, Kitchen, Parking, and Netflix.',
        'location' => 'We are located in Angono, Rizal.',
        'contact' => 'You can call us at 0912-345-6789.',
    ];

    public function chat(Request $request)
    {
        $message = strtolower($request->message);

        foreach ($this->faq as $keyword => $answer) {
            if (strpos($message, $keyword) !== false) {
                return response()->json(['reply' => $answer]);
            }
        }

        return response()->json(['reply' => 'Sorry, I can only answer questions about Sunset Haven Staycation.']);
    }
}
