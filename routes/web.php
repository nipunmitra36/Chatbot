<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// ChatController দ্বারা Dashboard
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [ChatController::class, 'index'])->name('dashboard');
    Route::post('/chat/send', [ChatController::class, 'sendMessage'])->name('chat.send')->middleware('auth');


    // Profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
Route::get('/chat', [SchoolChatController::class, 'index'])->name('chat.index');
Route::post('/chat/send', [SchoolChatController::class, 'sendMessage'])->name('chat.send');
require __DIR__ . '/auth.php';
