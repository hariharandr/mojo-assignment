<?php

namespace App\Http\Controllers;

use App\Models\InstagramAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class InstagramAuthController extends Controller
{
    public function redirect()
    {
        $clientId = config('services.instagram.client_id');
        $redirectUri = config('services.instagram.redirect');
        $scopes = config('services.instagram.scopes', 'instagram_business_basic');

        // Build the authorization URL
        $url = "https://www.instagram.com/oauth/authorize" .
            "?client_id=" . urlencode($clientId) .
            "&redirect_uri=" . urlencode($redirectUri) .
            "&response_type=code" .
            "&scope=" . urlencode($scopes);

        Log::info('Instagram OAuth Redirect', [
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'scopes' => $scopes,
            'url' => $url
        ]);

        return redirect()->away($url);
    }

    public function callback(Request $request)
    {
        $code = $request->query('code');
        
        if (!$code) {
            $error = $request->query('error');
            $errorDescription = $request->query('error_description');
            
            Log::error('Instagram OAuth callback error', [
                'error' => $error,
                'error_description' => $errorDescription
            ]);
            
            return redirect('/')->with('error', $errorDescription ?? 'Authorization failed');
        }

        Log::info('Instagram OAuth callback received', ['code' => substr($code, 0, 10) . '...']);

        try {
            // Exchange code for short-lived token
            $response = Http::asForm()->post('https://api.instagram.com/oauth/access_token', [
                'client_id' => config('services.instagram.client_id'),
                'client_secret' => config('services.instagram.client_secret'),
                'grant_type' => 'authorization_code',
                'redirect_uri' => config('services.instagram.redirect'),
                'code' => $code,
            ]);

            if ($response->failed()) {
                Log::error('Instagram token exchange failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return redirect('/')->with('error', 'Token exchange failed: ' . $response->body());
            }

            $body = $response->json();
            Log::info('Instagram token exchange successful', ['user_id' => $body['user_id'] ?? null]);

            $accessToken = $body['access_token'] ?? null;
            $igUserId = $body['user_id'] ?? null;

            if (!$accessToken || !$igUserId) {
                throw new \Exception('Missing access_token or user_id in response');
            }

            // Exchange for long-lived token
            $exchangeResponse = Http::get('https://graph.instagram.com/access_token', [
                'grant_type' => 'ig_exchange_token',
                'client_secret' => config('services.instagram.client_secret'),
                'access_token' => $accessToken,
            ]);

            if ($exchangeResponse->failed()) {
                Log::error('Instagram long-lived token exchange failed', [
                    'status' => $exchangeResponse->status(),
                    'body' => $exchangeResponse->body()
                ]);
                // Continue with short-lived token as fallback
                $longToken = $accessToken;
                $expiresAt = Carbon::now()->addHours(1); // Short token expires in 1 hour
            } else {
                $longData = $exchangeResponse->json();
                $longToken = $longData['access_token'] ?? $accessToken;
                $expiresIn = $longData['expires_in'] ?? 5184000; // 60 days default
                $expiresAt = Carbon::now()->addSeconds($expiresIn);
            }

            // Get profile information
            $profileResponse = Http::get("https://graph.instagram.com/{$igUserId}", [
                'fields' => 'id,username,account_type,media_count,profile_picture_url',
                'access_token' => $longToken,
            ]);

            $profile = $profileResponse->successful() ? $profileResponse->json() : [
                'id' => $igUserId,
                'username' => 'unknown',
            ];

            // Save to database
            $account = InstagramAccount::updateOrCreate(
                ['instagram_user_id' => $igUserId],
                [
                    'username' => $profile['username'] ?? 'unknown',
                    'access_token' => $longToken,
                    'token_expires_at' => $expiresAt,
                    'profile_json' => $profile,
                ]
            );

            Log::info('Instagram account connected successfully', [
                'user_id' => $igUserId,
                'username' => $profile['username'] ?? 'unknown'
            ]);

            return view('instagram.profile', [
                'profile' => $profile,
                'access_token' => $longToken
            ]);

        } catch (\Exception $e) {
            Log::error('Instagram OAuth exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect('/')->with('error', 'Authentication failed: ' . $e->getMessage());
        }
    }
}