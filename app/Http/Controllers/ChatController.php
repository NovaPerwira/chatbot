<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    public function sendMessage(Request $request)
    {
        $request->validate(['message' => 'required|string']);
        $userMessage = $request->input('message');
        $apiKey = env('GEMINI_API_KEY');

        if (!$apiKey) return response()->json(['error' => 'API Key belum disetting'], 500);

        // --- BAGIAN YANG DIUBAH ---
        // Kita pakai model yang PASTI ADA di daftar JSON kamu:
        $model = 'gemini-2.0-flash'; 
        // --------------------------

        try {
            // URL endpoint
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

            $response = Http::withHeaders(['Content-Type' => 'application/json'])
                ->post($url, [
                    'contents' => [
                        ['parts' => [['text' => $userMessage]]]
                    ]
                ]);

            if ($response->failed()) {
                Log::error('Gemini Error', $response->json());
                return response()->json([
                    'error' => 'Gagal akses Google AI.',
                    'details' => $response->json()
                ], $response->status());
            }

            $botReply = $response->json('candidates.0.content.parts.0.text');
            
            return response()->json(['reply' => $botReply ?? 'Tidak ada teks balasan.']);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}