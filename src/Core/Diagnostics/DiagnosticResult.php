<?php

namespace LaravelGuard\Core\Diagnostics;

use JsonSerializable;

final readonly class DiagnosticResult implements JsonSerializable
{
    public function __construct(
        public DiagnosticStatus $status,
        public string $check,
        public string $message,
        public ?string $remediation = null,
    ) {}

    public function jsonSerialize(): array
    {
        return array_filter([
            'status' => $this->status->value,
            'check' => $this->check,
            'message' => $this->message,
            'remediation' => $this->remediation,
        ], fn ($value) => $value !== null);
    }
}
