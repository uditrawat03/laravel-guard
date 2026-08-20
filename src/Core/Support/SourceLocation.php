<?php

namespace LaravelGuard\Core\Support;

final readonly class SourceLocation
{
    public function __construct(public ?string $file = null, public ?int $line = null) {}

    public function toArray(): array
    {
        return array_filter(['file' => $this->file, 'line' => $this->line], fn ($value) => $value !== null);
    }
}
