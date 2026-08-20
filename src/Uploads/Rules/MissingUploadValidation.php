<?php

namespace LaravelGuard\Uploads\Rules;

use LaravelGuard\Core\Contracts\SecurityContext;
use LaravelGuard\Core\Findings\Confidence;
use LaravelGuard\Core\Findings\SecurityFinding;
use LaravelGuard\Core\Findings\Severity;
use LaravelGuard\Core\Rules\AbstractGuardRule;
use LaravelGuard\Core\Source\SourceIndex;

final class MissingUploadValidation extends AbstractGuardRule
{
    public function __construct(private readonly SourceIndex $sources) {}

    public function id(): string
    {
        return 'LG-UPLOAD-001';
    }

    public function name(): string
    {
        return 'Upload may be missing validation';
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
        $validated = [];
        foreach ($this->sources->calls($context, ['validate', 'validated', 'rules']) as $call) {
            if (preg_match('/\b(file|image|mimes|mimetypes)\b/i', $call->code())) {
                $validated[$call->file->path][$call->symbol ?? '*'] = true;
            }
        }

        foreach ($this->sources->calls($context, ['file', 'hasFile', 'store', 'storeAs', 'storePublicly', 'storePubliclyAs', 'move']) as $call) {
            if (isset($validated[$call->file->path][$call->symbol ?? '*'])) {
                continue;
            }
            yield SecurityFinding::fromRule($this, "{$call->name}() is used without recognizable file validation in the same method.", 'Unvalidated files can contain unexpected types or oversized content.', 'Validate file type, MIME, extension, and size before storage.', Confidence::Medium, $call->file->path, $call->line(), ['symbol' => $call->symbol, 'code' => $call->code()]);
        }
    }
}
