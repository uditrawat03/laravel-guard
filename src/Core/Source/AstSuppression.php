<?php

namespace LaravelGuard\Core\Source;

final readonly class AstSuppression
{
    public function __construct(
        public string $rule,
        public string $reason,
        public ?string $symbol,
        public int $line,
    ) {}
}
