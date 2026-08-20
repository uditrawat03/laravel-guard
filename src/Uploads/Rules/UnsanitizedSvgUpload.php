<?php

namespace LaravelGuard\Uploads\Rules;

use LaravelGuard\Core\Contracts\SecurityContext;
use LaravelGuard\Core\Findings\Confidence;
use LaravelGuard\Core\Findings\SecurityFinding;
use LaravelGuard\Core\Findings\Severity;
use LaravelGuard\Core\Rules\AbstractGuardRule;
use LaravelGuard\Core\Source\SourceIndex;

final class UnsanitizedSvgUpload extends AbstractGuardRule
{
    public function __construct(private readonly SourceIndex $sources) {}

    public function id(): string
    {
        return 'LG-UPLOAD-007';
    }

    public function name(): string
    {
        return 'SVG upload may not be sanitized';
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
        $sanitized = [];
        foreach ($this->sources->calls($context, ['sanitize', 'clean', 'purify']) as $call) {
            $sanitized[$call->file->path][$call->symbol ?? '*'] = true;
        }
        foreach ($this->sources->calls($context, ['validate', 'rules']) as $call) {
            if (! preg_match('/\b(svg|image\/svg\+xml)\b/i', $call->code()) || isset($sanitized[$call->file->path][$call->symbol ?? '*'])) {
                continue;
            }
            yield SecurityFinding::fromRule($this, 'SVG is accepted without a recognizable sanitizer call in the same method.', 'SVG content can contain scripts, external references, and active markup.', 'Sanitize SVG with a maintained allowlist library or convert it to a raster format.', Confidence::Medium, $call->file->path, $call->line(), ['symbol' => $call->symbol, 'code' => $call->code()]);
        }
    }
}
