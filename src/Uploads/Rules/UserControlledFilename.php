<?php

namespace LaravelGuard\Uploads\Rules;

use LaravelGuard\Core\Contracts\SecurityContext;
use LaravelGuard\Core\Findings\SecurityFinding;
use LaravelGuard\Core\Findings\Severity;
use LaravelGuard\Core\Rules\AbstractGuardRule;
use LaravelGuard\Core\Source\SourceIndex;

final class UserControlledFilename extends AbstractGuardRule
{
    public function __construct(private readonly SourceIndex $sources) {}

    public function id(): string
    {
        return 'LG-UPLOAD-002';
    }

    public function name(): string
    {
        return 'User-controlled upload filename';
    }

    public function category(): string
    {
        return 'uploads';
    }

    public function severity(): Severity
    {
        return Severity::High;
    }

    public function scan(SecurityContext $context): iterable
    {
        foreach ($this->sources->calls($context, ['getClientOriginalName', 'getClientOriginalExtension']) as $call) {
            yield SecurityFinding::fromRule($this, 'An original client filename or extension is used in upload handling.', 'Client-controlled names may enable path traversal, collisions, or dangerous extensions.', 'Generate a server-side name and allowlist the validated extension.', file: $call->file->path, line: $call->line(), metadata: ['symbol' => $call->symbol, 'code' => $call->code()]);
        }
    }
}
