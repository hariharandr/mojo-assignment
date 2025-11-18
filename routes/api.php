<?php
use App\Http\Controllers\InstagramApiController;
use Illuminate\Support\Facades\Route;

Route::get('/instagram/profile', [InstagramApiController::class, 'profile']);
Route::get('/instagram/feed', [InstagramApiController::class, 'feed']);
Route::get('/instagram/media/{mediaId}', [InstagramApiController::class, 'media']);

Route::post('/instagram/media/{mediaId}/comments', [InstagramApiController::class, 'postComment']);
Route::post('/instagram/comments/{commentId}/replies', [InstagramApiController::class, 'replyComment']);
