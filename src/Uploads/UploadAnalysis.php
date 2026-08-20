<?php

namespace LaravelGuard\Uploads;

use LaravelGuard\Core\Contracts\SecurityContext;
use LaravelGuard\Core\Support\PhpFileScanner;

final readonly class UploadAnalysis
{
    public function __construct(private PhpFileScanner $files) {}

    public function sources(SecurityContext $c): iterable
    {
        return $this->files->files($c->config['paths'] ?? [$c->path('app')], $c->config['exclude_paths'] ?? []);
    }

    public function line(string $source, int $offset): int
    {
        return $this->files->lineOf($source, $offset);
    }
}
