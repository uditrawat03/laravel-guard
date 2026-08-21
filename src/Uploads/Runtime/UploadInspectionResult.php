<?php

namespace LaravelGuard\Uploads\Runtime;

final readonly class UploadInspectionResult
{
    public function __construct(
        public string $filename,
        public ?string $declaredMime,
        public ?string $detectedMime,
        public string $extension,
        public array $violations,
    ) {}

    public function safe(): bool
    {
        return $this->violations === [];
    }
}
