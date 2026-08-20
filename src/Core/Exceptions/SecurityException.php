<?php

namespace LaravelGuard\Core\Exceptions;

final readonly class SecurityException
{
    public function __construct(
        public string $rule,
        public string $reason,
        public string $file,
        public int $line,
        public string $createdAt,
    ) {}
}
