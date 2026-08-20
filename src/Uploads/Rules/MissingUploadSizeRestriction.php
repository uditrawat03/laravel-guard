<?php

namespace LaravelGuard\Uploads\Rules;

use LaravelGuard\Core\Contracts\SecurityContext;
use LaravelGuard\Core\Findings\Confidence;
use LaravelGuard\Core\Findings\SecurityFinding;
use LaravelGuard\Core\Findings\Severity;
use LaravelGuard\Core\Rules\AbstractGuardRule;
use LaravelGuard\Core\Source\SourceIndex;

final class MissingUploadSizeRestriction extends AbstractGuardRule
{
    public function __construct(private readonly SourceIndex $sources) {}

    public function id(): string
    {
        return 'LG-UPLOAD-005';
    }

    public function name(): string
    {
        return 'Upload validation has no size restriction';
    }

    public function category(): string
    {
        return 'uploads';
    }

    public function severity(): Severity
    {
        return Severity::Medium;
    }

    public function scan(SecurityContext $context): iterable
    {
        if (! ($context->config['uploads']['require_size_limit'] ?? true)) {
            return;
        }
        foreach ($this->sources->calls($context, ['validate', 'rules']) as $call) {
            $code = $call->code();
            if (! preg_match('/\b(file|image|mimes|mimetypes)\b/i', $code) || preg_match('/\bmax\s*:/i', $code)) {
                continue;
            }
            yield SecurityFinding::fromRule($this, 'File validation was found without a max size rule.', 'Large uploads may exhaust memory, disk, queue workers, or downstream processors.', 'Add an explicit max rule and enforce matching web-server limits.', Confidence::High, $call->file->path, $call->line(), ['symbol' => $call->symbol, 'code' => $code]);
        }
    }
}
