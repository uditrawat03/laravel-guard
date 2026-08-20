<?php

namespace LaravelGuard\Queries\Rules;

use LaravelGuard\Core\Contracts\SecurityContext;
use LaravelGuard\Core\Findings\Confidence;
use LaravelGuard\Core\Findings\SecurityFinding;
use LaravelGuard\Core\Findings\Severity;
use LaravelGuard\Core\Rules\AbstractGuardRule;
use LaravelGuard\Core\Source\Ast;
use LaravelGuard\Core\Source\SourceIndex;

final class UnsafeBulkUpdate extends AbstractGuardRule
{
    public function __construct(private readonly SourceIndex $sources) {}

    public function id(): string
    {
        return 'LG-QUERY-003';
    }

    public function name(): string
    {
        return 'Potentially unscoped bulk update';
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
        foreach ($this->sources->calls($context, ['update']) as $call) {
            $chain = Ast::callChain($call->node);
            if (array_intersect($chain, ['where', 'wherekey', 'wherein', 'find', 'first']) || count($chain) === 1) {
                continue;
            }

            yield SecurityFinding::fromRule($this, 'A query update chain has no recognizable constraint.', 'The update may affect every row visible to the query.', 'Add an explicit where constraint and verify tenant scope before updating.', Confidence::Medium, $call->file->path, $call->line(), ['symbol' => $call->symbol, 'code' => $call->code()]);
        }
    }
}
