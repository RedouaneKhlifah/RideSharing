<?php

return [
    'secret' => env('JWT_SECRET'),
    'ttl' => 1440, // Time to live for the token in minutes
    'refresh_ttl' => 20160, // Refresh token time to live (in minutes)
    'algo' => 'HS256', // Algorithm to use for signing
    'required_claims' => ['sub', 'iat'],
    'blacklist_enabled' => true,
    'user' => 'App\Models\User', // Your user model
    'identifier' => 'id',
];
