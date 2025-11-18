<?php

use App\Http\Controllers\InstagramAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/health', function () {
    return response('OK', 200)->header('Content-Type', 'text/plain');
});

Route::get('/auth/instagram', [InstagramAuthController::class, 'redirect'])->name('instagram.redirect');
Route::get('/auth/instagram/callback', [InstagramAuthController::class, 'callback'])->name('instagram.callback');