<?php

return [
    'enabled' => env('LARAVEL_GUARD_ENABLED', true),
    'modules' => [
        'tenant' => true, 'routes' => true, 'configuration' => true, 'uploads' => true,
        'queries' => true, 'models' => true, 'secrets' => true, 'api' => true, 'runtime' => false,
    ],
    'minimum_severity' => 'low',
    'ci' => ['fail_on' => 'high'],
    'paths' => [app_path(), base_path('routes'), base_path('config')],
    'exclude_paths' => [base_path('vendor'), base_path('storage'), base_path('bootstrap/cache')],
    'tenant' => ['column' => 'tenant_id', 'resolver' => null, 'models' => [], 'tables' => []],
    'routes' => [
        'sensitive_methods' => ['POST', 'PUT', 'PATCH', 'DELETE'],
        'sensitive_patterns' => ['admin/*', 'api/admin/*', '*delete*', '*destroy*', '*export*'],
        'public' => [],
        'ignore' => ['storage.local.*'],
        'authorization_middleware' => ['can', 'permission', 'role', 'role_or_permission'],
        'policy_models' => [],
    ],
    'uploads' => [
        'dangerous_extensions' => ['php', 'phtml', 'phar', 'cgi', 'pl', 'sh', 'exe', 'bat', 'cmd', 'js', 'html', 'htm'],
        'require_size_limit' => true,
        'allowed_detected_mimes' => [],
    ],
    'secrets' => ['entropy_threshold' => 3.6, 'allow_placeholders' => true],
    'runtime' => ['enabled' => env('LARAVEL_GUARD_RUNTIME', false), 'environments' => ['local', 'testing']],
    'integrations' => ['telescope' => false, 'debugbar' => false],
    'reporters' => [],
    'ignore' => [],
    'custom_rules' => [],
    'baseline' => base_path('.laravel-guard-baseline.json'),
    'baseline_governance' => [
        'require_reason' => true,
        'default_ttl_days' => 90,
        'owner' => env('LARAVEL_GUARD_BASELINE_OWNER'),
    ],
    'cache' => ['enabled' => true],
];
