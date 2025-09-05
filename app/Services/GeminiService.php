<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GeminiService
{
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');
    }

    public function askGemini($message)
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent";

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($url . '?key=' . $this->apiKey, [
            'contents' => [
                ['parts' => [['text' => $message]]]
            ]
        ]);

        if ($response->successful()) {
            return $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? 'No response';
        }

        return 'Error: ' . $response->body();
    }
}
