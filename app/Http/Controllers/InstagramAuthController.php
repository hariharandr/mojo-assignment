<?php

namespace App\Http\Controllers;

use App\Models\InstagramAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Carbon\Carbon;

class InstagramAuthController extends Controller
{
    public function redirect()
    {
        $client = config('services.instagram.client_id');
        $redirect = config('services.instagram.redirect');
        $scope = urlencode('instagram_business_basic,instagram_business_manage_messages,instagram_business_manage_comments,instagram_content_publish');
        $force = 'true';
        $url = "https://www.instagram.com/oauth/authorize?force_reauth={$force}&client_id={$client}&redirect_uri={$redirect}&response_type=code&scope={$scope}";
        return redirect()->away($url);
    }

    public function callback(Request $request)
    {
        $code = $request->query('code');
        if (!$code) {
            return redirect('/')->with('error', 'No code received');
        }

        // Exchange code for short-lived token
        $response = Http::asForm()->post('https://api.instagram.com/oauth/access_token', [
            'client_id' => config('services.instagram.client_id'),
            'client_secret' => config('services.instagram.client_secret'),
            'grant_type' => 'authorization_code',
            'redirect_uri' => config('services.instagram.redirect'),
            'code' => $code,
        ]);

        if ($response->failed()) {
            return redirect('/')->with('error', 'Token exchange failed: '.$response->body());
        }

        $body = $response->json();

        // short-lived access_token and user_id received
        $accessToken = $body['access_token'] ?? null;
        $igUserId = $body['user_id'] ?? null;

        // Exchange for long-lived token (server-side)
        $exchange = Http::get('https://graph.instagram.com/access_token', [
            'grant_type' => 'ig_exchange_token',
            'client_secret' => config('services.instagram.client_secret'),
            'access_token' => $accessToken,
        ]);

        $long = $exchange->json();
        $longToken = $long['access_token'] ?? $accessToken;
        $expiresIn = $long['expires_in'] ?? null;
        $expiresAt = $expiresIn ? Carbon::now()->addSeconds($expiresIn) : null;

        // Get profile fields
        $profile = Http::get("https://graph.instagram.com/{$igUserId}", [
            'fields' => 'id,username,account_type,media_count,profile_picture_url',
            'access_token' => $longToken,
        ])->json();

        // Save to DB
        $account = InstagramAccount::updateOrCreate(
            ['instagram_user_id' => $igUserId],
            [
                'username' => $profile['username'] ?? null,
                'access_token' => $longToken,
                'token_expires_at' => $expiresAt,
                'profile_json' => $profile,
            ]
        );

        // Redirect to dashboard
        return redirect('/dashboard')->with('success', 'Instagram connected.');
    }
}
