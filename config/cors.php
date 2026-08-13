<?php

$defaults = [
    'http://localhost:5173',
    'https://painel-infinite-loyalty.vercel.app',
];

$fromEnv = array_filter(array_map(
    'trim',
    explode(',', (string) env('CORS_ALLOWED_ORIGINS', ''))
));

$frontend = trim((string) env('FRONTEND_URL', ''));

$origins = array_values(array_unique(array_filter(array_merge(
    $defaults,
    $fromEnv,
    $frontend !== '' ? [$frontend] : []
))));

return [
    'paths' => ['api/*', 'up', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => $origins,
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];
