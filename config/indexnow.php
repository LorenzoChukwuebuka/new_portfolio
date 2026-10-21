<?php

return [
    /*
    | This verification key is public by protocol design. Keep the default
    | stable across releases; INDEXNOW_KEY may override it in the shared .env.
    */
    'key' => env('INDEXNOW_KEY', '944700e31d884d9dec27f46c22872309'),
    'enabled' => (bool) env('INDEXNOW_ENABLED', true),
    'endpoint' => 'https://api.indexnow.org/indexnow',
    'timeout' => (int) env('INDEXNOW_TIMEOUT', 5),
    'connect_timeout' => (int) env('INDEXNOW_CONNECT_TIMEOUT', 2),
    'http_retries' => 2,
    'max_delivery_attempts' => 5,
    'request_batch_size' => min(10000, max(1, (int) env('INDEXNOW_BATCH_SIZE', 100))),
    'lease_minutes' => 2,
];
