<?php

namespace LaravelGuard\Api\Rules;

use LaravelGuard\Core\Contracts\SecurityContext;
use LaravelGuard\Core\Findings\Confidence;
use LaravelGuard\Core\Findings\SecurityFinding;
use LaravelGuard\Core\Findings\Severity;
use LaravelGuard\Core\Rules\AbstractGuardRule;
use LaravelGuard\Core\Source\Ast;
use LaravelGuard\Core\Source\SourceIndex;
use PhpParser\Node;

final class UnsafeApiResourceExposure extends AbstractGuardRule
{
    public function __construct(private readonly SourceIndex $sources) {}

    public function id(): string
    {
        return 'LG-API-003';
    }

    public function name(): string
    {
        return 'API may expose models directly';
    }

    public function category(): string
    {
        return 'api';
    }

    public function severity(): Severity
    {
        return Severity::Medium;
    }

    public function scan(SecurityContext $context): iterable
    {
        foreach ($this->sources->calls($context, ['json']) as $call) {
            $argument = $call->node->args[0]->value ?? null;
            if (! $argument instanceof Node\Expr) {
                continue;
            }
            $code = Ast::code($argument);
            if (! preg_match('/(?:->(?:get|all|paginate|first|find)\s*\(|::(?:all|find)\s*\()/i', $code)) {
                continue;
            }
            yield SecurityFinding::fromRule($this, 'A JSON response appears to serialize an Eloquent result directly.', 'Model attributes and future schema changes may be exposed unintentionally.', 'Return an explicit JsonResource or DTO with an allowlisted response shape.', Confidence::Medium, $call->file->path, $call->line(), ['symbol' => $call->symbol, 'code' => $call->code()]);
        }
    }
}
