<?php

namespace LaravelGuard\Uploads\Rules;

use LaravelGuard\Core\Contracts\SecurityContext;
use LaravelGuard\Core\Findings\Confidence;
use LaravelGuard\Core\Findings\SecurityFinding;
use LaravelGuard\Core\Findings\Severity;
use LaravelGuard\Core\Rules\AbstractGuardRule;
use LaravelGuard\Core\Source\SourceIndex;

final class PublicExecutableUpload extends AbstractGuardRule
{
    public function __construct(private readonly SourceIndex $sources) {}

    public function id(): string
    {
        return 'LG-UPLOAD-004';
    }

    public function name(): string
    {
        return 'Upload may be stored in a public executable location';
    }

    public function category(): string
    {
        return 'uploads';
    }

    public function severity(): Severity
    {
        return Severity::Critical;
    }

    public function scan(SecurityContext $context): iterable
    {
        foreach ($this->sources->calls($context, ['move', 'storePublicly', 'storePubliclyAs', 'putFileAs']) as $call) {
            $code = strtolower($call->code());
            if (! str_contains($code, 'public_path') && ! str_contains($code, "disk('public") && ! str_contains($code, 'storepublicly')) {
                continue;
            }
            yield SecurityFinding::fromRule($this, 'An uploaded file is written to a publicly reachable location.', 'A dangerous or incorrectly validated file may be downloaded or executed by an attacker.', 'Use private storage, generated names, content-disposition downloads, and strict allowlist validation.', Confidence::Medium, $call->file->path, $call->line(), ['symbol' => $call->symbol, 'code' => $call->code()]);
        }
    }
}
