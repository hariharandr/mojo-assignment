<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class InstagramAccount extends Model
{
    protected $fillable = [
        'user_id',
        'instagram_user_id',
        'username',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'profile_json',
    ];

    protected $casts = [
        'profile_json' => 'array',
        'token_expires_at' => 'datetime',
    ];

    public function isTokenExpired(): bool
    {
        return !$this->token_expires_at || $this->token_expires_at->lt(Carbon::now());
    }
}
