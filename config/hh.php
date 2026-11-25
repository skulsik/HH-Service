<?php

return [
    'api_url' => env('HH_API_URL', 'https://api.hh.ru'),
    'user_agent' => env('HH_USER_AGENT'),
    'client_id' => env('HH_CLIENT_ID'),
    'client_secret' => env('HH_CLIENT_SECRET'),
    'search' => [
        'text' => 'php developer',
        'area' => '113',
        'order_by' => 'publication_time',
        'per_page' => 100,
    ],
];
