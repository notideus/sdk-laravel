<?php

declare(strict_types=1);

return [
    'api_key' => env('NOTIDEUS_API_KEY'),
    'base_url' => env('NOTIDEUS_BASE_URL', 'https://api.notideus.io'),
    'timeout' => (float) env('NOTIDEUS_TIMEOUT', 30.0),
    'max_retries' => (int) env('NOTIDEUS_MAX_RETRIES', 2),
    'from_address' => env('NOTIDEUS_FROM_ADDRESS'),
    'from_name' => env('NOTIDEUS_FROM_NAME'),
];
