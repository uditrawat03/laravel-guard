<?php

return [
    'enabled' => env('LARAVEL_GUARD_ENABLED', true),
    'modules' => [
        'tenant' => true,
        'routes' => true,
        'configuration' => true,
        'uploads' => true,
    ],
    'minimum_severity' => 'low',
    'ci' => ['fail_on' => 'high'],
    'paths' => [app_path(), base_path('routes')],
    'exclude_paths' => [base_path('vendor'), base_path('storage'), base_path('bootstrap/cache')],
    'tenant' => [
        'column' => 'tenant_id',
        'resolver' => null,
        'models' => [],
    ],
    'routes' => [
        'sensitive_methods' => ['POST', 'PUT', 'PATCH', 'DELETE'],
        'sensitive_patterns' => ['admin/*', 'api/admin/*', '*delete*', '*destroy*', '*export*'],
        'public' => [],
        'authorization_middleware' => ['can', 'permission', 'role'],
    ],
    'ignore' => [],
    'custom_rules' => [],
    'baseline' => base_path('.laravel-guard-baseline.json'),
];
