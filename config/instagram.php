<?php

return [
    // TTLs in seconds
    'profile_ttl'   => env('INSTAGRAM_CACHE_PROFILE_TTL', 3600),
    'feed_ttl'      => env('INSTAGRAM_CACHE_FEED_TTL', 300),
    'media_ttl'     => env('INSTAGRAM_CACHE_MEDIA_TTL', 180),

    // default list limit
    'default_limit' => env('INSTAGRAM_DEFAULT_LIMIT', 24),

    // Graph API base
    'graph_base'    => env('INSTAGRAM_GRAPH_BASE', 'https://graph.instagram.com'),

    // OAuth / client
    'client_id'     => env('INSTAGRAM_CLIENT_ID'),
    'client_secret' => env('INSTAGRAM_CLIENT_SECRET'),
    'redirect'      => env('INSTAGRAM_REDIRECT_URI'),

    // scopes default (string)
    'scopes'        => env('INSTAGRAM_SCOPES', 'instagram_basic,instagram_manage_comments,instagram_manage_insights'),
];
