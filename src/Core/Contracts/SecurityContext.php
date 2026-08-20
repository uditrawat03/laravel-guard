<?php

namespace LaravelGuard\Core\Contracts;

use Illuminate\Contracts\Foundation\Application;

final readonly class SecurityContext
{
    public function __construct(
        public Application $app,
        public array $config,
        public ?string $module = null,
    ) {}

    public function path(string $path = ''): string
    {
        return $this->app->basePath($path);
    }
}
