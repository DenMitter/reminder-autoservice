<?php

return [
    'driver' => env('SMS_DRIVER', 'log'),

    'from' => env('SMS_FROM', config('app.name')),

    'alphasms' => [
        'endpoint' => env('ALPHASMS_ENDPOINT', 'https://alphasms.ua/api/json.php'),
        'api_key' => env('ALPHASMS_API_KEY'),
        'login' => env('ALPHASMS_LOGIN'),
        'password' => env('ALPHASMS_PASSWORD'),
        'sender' => env('ALPHASMS_SENDER'),
    ],
];
