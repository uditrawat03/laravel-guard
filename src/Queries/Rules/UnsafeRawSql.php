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

final class UnsafeRawSql extends AbstractGuardRule
{
    public function __construct(private readonly SourceIndex $sources) {}

    public function id(): string
    {
        return 'LG-QUERY-002';
    }

    public function name(): string
    {
        return 'Unbound raw SQL';
    }

    public function category(): string
    {
        return 'queries';
    }

    public function severity(): Severity
    {
        return Severity::High;
    }

    public function scan(SecurityContext $context): iterable
    {
        foreach ($this->sources->calls($context, ['select', 'statement', 'unprepared', 'whereRaw', 'orWhereRaw', 'havingRaw', 'orderByRaw', 'selectRaw']) as $call) {
            $sql = $call->node->args[0]->value ?? null;
            if (! $sql instanceof Node\Expr || Ast::containsVariable($sql) || count($call->node->args) > 1) {
                continue;
            }

            $literal = Ast::string($sql);
            if ($literal === null || ! preg_match('/\b(select|insert|update|delete|drop|alter|union|where|order\s+by)\b/i', $literal)) {
                continue;
            }

            yield SecurityFinding::fromRule(
                $this,
                "{$call->name}() executes raw SQL without an explicit bindings argument.",
                'Future edits can accidentally introduce interpolation into a query that bypasses the query builder.',
                'Prefer the query builder or provide an explicit bindings array for raw SQL.',
                Confidence::Medium,
                $call->file->path,
                $call->line(),
                ['symbol' => $call->symbol, 'code' => $call->code()],
            );
        }
    }
}
