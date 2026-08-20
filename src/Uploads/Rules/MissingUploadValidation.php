<?php

namespace LaravelGuard\Uploads\Rules;

use LaravelGuard\Core\Contracts\SecurityContext;
use LaravelGuard\Core\Findings\Confidence;
use LaravelGuard\Core\Findings\SecurityFinding;
use LaravelGuard\Core\Findings\Severity;
use LaravelGuard\Core\Rules\AbstractGuardRule;
use LaravelGuard\Uploads\UploadAnalysis;

final class MissingUploadValidation extends AbstractGuardRule
{
    public function __construct(private readonly UploadAnalysis $analysis) {}

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

    public function scan(SecurityContext $c): iterable
    {
        foreach ($this->analysis->sources($c) as $file => $source) {
            if (! preg_match_all('/(?:->file\s*\(|UploadedFile\b)/', $source, $hits, PREG_OFFSET_CAPTURE)) {
                continue;
            }$validated = (bool) preg_match('/(?:validate|rules)\s*\([^;]*(?:file|image|mimes|mimetypes|max)\b/s', $source);
            if (! $validated) {
                foreach ($hits[0] as [$match,$offset]) {
                    yield SecurityFinding::fromRule($this, 'File upload use was detected without recognizable file validation in the same source file.', 'Unvalidated files can contain unexpected types or oversized content.', 'Validate file type, MIME, extension, and size before storage.', Confidence::Medium, $file, $this->analysis->line($source, $offset), ['symbol' => $match]);
                }
            }
        }
    }
}
