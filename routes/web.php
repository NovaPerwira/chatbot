<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
});

require __DIR__.'/settings.php';

use App\Http\Controllers\ChatController;
// use Illuminate\Support\Facades\Route;

Route::get('/c', function () {
    return view('chat');
});
use App\Http\Controllers\GeminiAgentController;

// Jalur 1: Untuk Chat Biasa / Lama
// Route::post('/chat', [ChatController::class, 'sendMessage']);

Route::get('/playground', function () {
    return view('chat_playground');
});
// Jalur 2: Untuk Chat dengan AI (Ganti URL-nya, misal jadi /chat-ai)
// Route::post('/chat-ai', [GeminiAgentController::class, 'chat']);

Route::post('/chat', [ChatController::class, 'sendMessage']);
Route::post('/chat-ai', [GeminiAgentController::class, 'chat']);