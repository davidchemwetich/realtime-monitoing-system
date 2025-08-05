<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


// Add this temporarily for testing
Route::middleware('auth')->get('/test-route', function () {
    return response()->json(['message' => 'Route is working!']);
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Chat routes
Route::middleware('auth')->group(function () {
    Route::get('/chat', function () {
        return view('chat');
    })->name('chat');

    Route::post('/notify', [ChatController::class, 'notify'])->name('notify');
});


Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');


require __DIR__ . '/auth.php';