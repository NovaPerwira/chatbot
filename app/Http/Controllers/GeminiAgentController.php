<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Order; // Import Model Order

class GeminiAgentController extends Controller
{
    public function chat(Request $request)
    {
        $userMessage = $request->input('message');
        $apiKey = env('GEMINI_API_KEY'); // Pastikan ada di .env
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$apiKey}";

        // --- 1. DEFINISI TOOLS (Apa yang AI bisa lakukan) ---
        $tools = [
            "function_declarations" => [
                [
                    "name" => "check_order_status",
                    "description" => "Mengecek status pengiriman atau pesanan berdasarkan nomor order (Order Number).",
                    "parameters" => [
                        "type" => "OBJECT",
                        "properties" => [
                            "order_number" => [
                                "type" => "STRING",
                                "description" => "Nomor pesanan, biasanya diawali ORD, contoh: ORD-001"
                            ]
                        ],
                        "required" => ["order_number"]
                    ]
                ]
            ]
        ];

        // --- 2. REQUEST PERTAMA KE GEMINI ---
        // Kita kirim pesan user + definisi tools
        $payload = [
            "contents" => [
                ["role" => "user", "parts" => [["text" => $userMessage]]]
            ],
            "tools" => [$tools]
        ];

        $response = Http::post($url, $payload)->json();

        // Ambil respon kandidat pertama
        $candidatePart = $response['candidates'][0]['content']['parts'][0];

        // --- 3. CEK APAKAH AI MINTA PANGGIL FUNGSI? ---
        if (isset($candidatePart['functionCall'])) {
            
            // AI ingin menjalankan fungsi. Mari kita proses.
            $functionName = $candidatePart['functionCall']['name'];
            $args = $candidatePart['functionCall']['args'];

            $functionResult = null;

            // Mapping nama fungsi ke kode PHP kita
            if ($functionName === 'check_order_status') {
                // Panggil Model Laravel
                $dbResult = Order::checkStatus($args['order_number']);
                $functionResult = ["result" => $dbResult];
            }

            // Jika fungsi tidak dikenal (opsional)
            if (!$functionResult) {
                $functionResult = ["error" => "Fungsi tidak ditemukan"];
            }

            // --- 4. REQUEST KEDUA (FOLLOW-UP) ---
            // Kirim balik hasil database ke Gemini agar dia bisa ngomong ke user
            
            $secondPayload = [
                "contents" => [
                    // A. Pesan awal user
                    ["role" => "user", "parts" => [["text" => $userMessage]]],
                    
                    // B. Respon AI sebelumnya (bahwa dia mau panggil fungsi)
                    ["role" => "model", "parts" => [$candidatePart]],
                    
                    // C. Hasil eksekusi fungsi (Data dari Database)
                    ["role" => "function", "parts" => [[
                        "functionResponse" => [
                            "name" => $functionName,
                            "response" => $functionResult
                        ]
                    ]]]
                ],
                "tools" => [$tools] // Tools tetap disertakan
            ];

            $finalResponse = Http::post($url, $secondPayload)->json();
            
            // Ambil jawaban akhir berupa teks manusiawi
            $finalText = $finalResponse['candidates'][0]['content']['parts'][0]['text'];
            
            return response()->json([
                'reply' => $finalText,
                'debug_action' => 'Database Accessed' // Flag untuk debugging
            ]);

        } 
        
        // --- JIKA AI TIDAK MINTA DATABASE ---
        // Langsung return jawaban teks biasa (misal user cuma tanya "Halo")
        else {
            return response()->json([
                'reply' => $candidatePart['text'],
                'debug_action' => 'Direct Reply'
            ]);
        }
    }
}