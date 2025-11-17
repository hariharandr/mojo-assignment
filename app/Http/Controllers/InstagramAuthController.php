<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use App\Models\InstagramToken;

class InstagramAuthController extends Controller
{
    public function redirectToInstagram(Request $request)
    {
        $clientId = env('INSTAGRAM_APP_ID');
        $redirect = env('INSTAGRAM_REDIRECT_URI');
        $scope = urlencode(env('INSTAGRAM_SCOPES', 'instagram_business_basic'));

        $url = "https://www.instagram.com/oauth/authorize"
            . "?client_id={$clientId}"
            . "&redirect_uri=" . urlencode($redirect)
            . "&response_type=code"
            . "&scope={$scope}"
            . "&force_reauth=true";

        return redirect($url);
    }

    public function handleCallback(Request $request)
    {
        if ($request->has('error')) {
            return redirect('/')->with('error', $request->get('error_description') ?? 'Authorization declined');
        }

        $code = $request->get('code');
        if (! $code) {
            return redirect('/')->with('error', 'No code returned from Instagram.');
        }

        try {
            // Exchange code for short-lived token
            $tokenResponse = Http::asForm()->post('https://api.instagram.com/oauth/access_token', [
                'client_id' => env('INSTAGRAM_APP_ID'),
                'client_secret' => env('INSTAGRAM_APP_SECRET'),
                'grant_type' => 'authorization_code',
                'redirect_uri' => env('INSTAGRAM_REDIRECT_URI'),
                'code' => $code,
            ]);

            if (! $tokenResponse->successful()) {
                Log::error('IG token exchange failed', ['resp' => $tokenResponse->body()]);
                return redirect('/')->with('error', 'Token exchange failed.');
            }

            $shortData = $tokenResponse->json();
            $shortToken = data_get($shortData, 'access_token') ?? data_get($shortData, 'data.0.access_token');
            $appScopedId = data_get($shortData, 'user_id') ?? data_get($shortData, 'data.0.user_id');

            // Exchange short-lived token for long-lived token
            $longResp = Http::get('https://graph.instagram.com/access_token', [
                'grant_type' => 'ig_exchange_token',
                'client_secret' => env('INSTAGRAM_APP_SECRET'),
                'access_token' => $shortToken,
            ]);

            if (! $longResp->successful()) {
                Log::error('IG long token exchange failed', ['resp' => $longResp->body()]);
                return redirect('/')->with('error', 'Long token exchange failed.');
            }

            $longData = $longResp->json();
            $longToken = data_get($longData, 'access_token');
            $expiresIn = data_get($longData, 'expires_in', 60*60*24*60);

            // Persist token (optional)
            if ($appScopedId && $longToken && class_exists(\App\Models\InstagramToken::class)) {
                InstagramToken::updateOrCreate(
                    ['instagram_user_id' => $appScopedId],
                    [
                        'access_token' => $longToken,
                        'expires_at' => Carbon::now()->addSeconds($expiresIn),
                    ]
                );
            }

            // Fetch profile + media
            $profileResp = Http::get("https://graph.instagram.com/{$appScopedId}", [
                'fields' => 'user_id,username,account_type,profile_picture_url,media_count',
                'access_token' => $longToken,
            ]);

            $mediaResp = Http::get("https://graph.instagram.com/{$appScopedId}/media", [
                'fields' => 'id,caption,media_type,media_url,thumbnail_url,permalink',
                'access_token' => $longToken,
                'limit' => 10,
            ]);

            $profile = $profileResp->successful() ? $profileResp->json() : null;
            $media = $mediaResp->successful() ? ($mediaResp->json()['data'] ?? []) : [];

            return view('instagram.profile', compact('profile', 'media', 'longToken'));

        } catch (\Throwable $e) {
            Log::error('Instagram OAuth exception', ['err' => $e->getMessage()]);
            return redirect('/')->with('error', 'Unexpected error during Instagram OAuth.');
        }
    }
}
