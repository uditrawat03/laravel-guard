<?php

namespace LaravelGuard\Tests\Fixtures;

final class ResourceController
{
    public function __construct()
    {
        $this->authorizeResource('App\\Models\\Document', 'document');
    }

    public function destroy(): void {}

    private function authorizeResource(string $model, string $parameter): void {}
}
