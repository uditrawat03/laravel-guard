<?php

namespace LaravelGuard\Uploads\Rules;

use LaravelGuard\Core\Contracts\SecurityContext;
use LaravelGuard\Core\Findings\SecurityFinding;
use LaravelGuard\Core\Findings\Severity;
use LaravelGuard\Core\Rules\AbstractGuardRule;
use LaravelGuard\Core\Source\SourceIndex;

final class DangerousUploadExtension extends AbstractGuardRule
{
    public function __construct(private readonly SourceIndex $sources) {}

    public function id(): string
    {
        return 'LG-UPLOAD-003';
    }

    public function name(): string
    {
        return 'Dangerous upload extension is allowed';
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
        $extensions = $context->config['uploads']['dangerous_extensions'] ?? [];
        foreach ($this->sources->calls($context, ['validate', 'rules']) as $call) {
            $code = strtolower($call->code());
            foreach ($extensions as $extension) {
                if (preg_match('/(?:mimes|extensions)[^\n\]]*\b'.preg_quote(strtolower($extension), '/').'\b/', $code)) {
                    yield SecurityFinding::fromRule($this, "Upload validation allows the executable [{$extension}] extension.", 'Uploaded executable content may run in a public or misconfigured storage location.', 'Remove executable formats from the allowlist and store uploads outside the web root.', file: $call->file->path, line: $call->line(), metadata: ['symbol' => $call->symbol, 'extension' => $extension, 'code' => $call->code()]);
                    break;
                }
            }
        }
    }
}
