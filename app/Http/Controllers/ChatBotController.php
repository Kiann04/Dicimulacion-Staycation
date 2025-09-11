<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ChatBotController extends Controller
{
    public function ask(Request $request)
    {
        $apiKey = env('GEMINI_API_KEY');
        $message = $request->input('message');

        if (!$apiKey) {
            return response()->json(['reply' => 'Missing Gemini API key'], 500);
        }

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post("https://generativelanguage.googleapis.com/v1/models/gemini-1.5-flash:generateContent?key={$apiKey}", [
            'contents' => [
                ['parts' => [['text' => $message]]]
            ]
        ]);

        if ($response->failed()) {
            return response()->json(['reply' => 'Error calling Gemini API'], 500);
        }

        $data = $response->json();

        return response()->json([
            'reply' => $data['candidates'][0]['content']['parts'][0]['text'] ?? 'No response from Gemini'
        ]);
    }
}
