<?php

namespace LaravelGuard\Uploads\Rules;

use LaravelGuard\Core\Contracts\SecurityContext;
use LaravelGuard\Core\Findings\Confidence;
use LaravelGuard\Core\Findings\SecurityFinding;
use LaravelGuard\Core\Findings\Severity;
use LaravelGuard\Core\Rules\AbstractGuardRule;
use LaravelGuard\Core\Source\Ast;
use LaravelGuard\Core\Source\SourceIndex;
use PhpParser\Node;

final class UploadPathTraversal extends AbstractGuardRule
{
    public function __construct(private readonly SourceIndex $sources) {}

    public function id(): string
    {
        return 'LG-UPLOAD-006';
    }

    public function name(): string
    {
        return 'User-controlled upload path';
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
        foreach ($this->sources->calls($context, ['move', 'storeAs', 'storePubliclyAs', 'putFileAs']) as $call) {
            $argument = $call->node->args[1]->value ?? null;
            if (! $argument instanceof Node\Expr || ! Ast::containsVariable($argument)) {
                continue;
            }
            yield SecurityFinding::fromRule($this, "{$call->name}() receives a dynamic filename or path.", 'Untrusted path segments can overwrite files or escape the intended storage directory.', 'Generate an opaque filename and reject path separators and dot segments.', Confidence::Medium, $call->file->path, $call->line(), ['symbol' => $call->symbol, 'code' => $call->code()]);
        }
    }
}
