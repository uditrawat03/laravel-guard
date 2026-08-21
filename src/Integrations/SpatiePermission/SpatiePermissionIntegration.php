<?php

namespace LaravelGuard\Integrations\SpatiePermission;

final class SpatiePermissionIntegration
{
    public function available(): bool
    {
        return class_exists('Spatie\\Permission\\PermissionServiceProvider');
    }

    public function authorizationMiddleware(): array
    {
        return ['permission', 'role', 'role_or_permission'];
    }
}
