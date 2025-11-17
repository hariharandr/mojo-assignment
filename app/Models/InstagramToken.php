<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstagramToken extends Model
{
    protected $fillable = ['instagram_user_id', 'access_token', 'expires_at'];
    protected $dates = ['expires_at'];
}
