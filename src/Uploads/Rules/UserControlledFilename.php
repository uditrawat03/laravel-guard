<?php

namespace LaravelGuard\Uploads\Rules;

use LaravelGuard\Core\Contracts\SecurityContext;
use LaravelGuard\Core\Findings\Confidence;
use LaravelGuard\Core\Findings\SecurityFinding;
use LaravelGuard\Core\Findings\Severity;
use LaravelGuard\Core\Rules\AbstractGuardRule;
use LaravelGuard\Uploads\UploadAnalysis;

final class UserControlledFilename extends AbstractGuardRule
{
    public function __construct(private readonly UploadAnalysis $analysis) {}

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

    public function scan(SecurityContext $c): iterable
    {
        foreach ($this->analysis->sources($c) as $file => $source) {
            if (preg_match_all('/(?:getClientOriginalName|getClientOriginalExtension)\s*\(/', $source, $hits, PREG_OFFSET_CAPTURE)) {
                foreach ($hits[0] as [$match,$offset]) {
                    yield SecurityFinding::fromRule($this, 'An original client filename or extension is used in upload handling.', 'Client-controlled names may enable path traversal, collisions, or dangerous extensions.', 'Generate a server-side name and allowlist the validated extension.', $this->confidence(), $file, $this->analysis->line($source, $offset), ['symbol' => $match]);
                }
            }
        }
    }

    private function confidence(): Confidence
    {
        return Confidence::High;
    }
}
