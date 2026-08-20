<?php

namespace LaravelGuard\Queries\Rules;

use LaravelGuard\Core\Contracts\SecurityContext;
use LaravelGuard\Core\Findings\Confidence;
use LaravelGuard\Core\Findings\SecurityFinding;
use LaravelGuard\Core\Findings\Severity;
use LaravelGuard\Core\Rules\AbstractGuardRule;
use LaravelGuard\Core\Source\Ast;
use LaravelGuard\Core\Source\SourceIndex;
use PhpParser\Node;

final class PotentialSqlInjection extends AbstractGuardRule
{
    public function __construct(private readonly SourceIndex $sources) {}

    public function id(): string
    {
        return 'LG-QUERY-001';
    }

    public function name(): string
    {
        return 'Potential SQL injection';
    }

    public function category(): string
    {
        return 'queries';
    }

    public function severity(): Severity
    {
        return Severity::Critical;
    }

    public function scan(SecurityContext $context): iterable
    {
        foreach ($this->sources->calls($context, ['select', 'statement', 'unprepared', 'raw', 'whereRaw', 'orWhereRaw', 'havingRaw', 'orderByRaw', 'selectRaw']) as $call) {
            $sql = $call->node->args[0]->value ?? null;
            if (! $sql instanceof Node\Expr || ! Ast::containsVariable($sql)) {
                continue;
            }

            yield SecurityFinding::fromRule(
                $this,
                "{$call->name}() receives a dynamically composed SQL expression.",
                'User-controlled values may alter SQL structure and expose or modify application data.',
                'Use placeholders and pass values through the binding argument instead of interpolation.',
                Confidence::High,
                $call->file->path,
                $call->line(),
                ['symbol' => $call->symbol, 'code' => $call->code()],
            );
        }
    }
}
