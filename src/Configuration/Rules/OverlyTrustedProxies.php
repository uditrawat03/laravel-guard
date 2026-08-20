<?php

namespace LaravelGuard\Configuration\Rules;

use LaravelGuard\Core\Contracts\SecurityContext;
use LaravelGuard\Core\Findings\Confidence;
use LaravelGuard\Core\Findings\SecurityFinding;
use LaravelGuard\Core\Findings\Severity;
use LaravelGuard\Core\Rules\AbstractGuardRule;
use LaravelGuard\Core\Source\SourceIndex;

final class OverlyTrustedProxies extends AbstractGuardRule
{
    public function __construct(private readonly SourceIndex $sources) {}

    public function id(): string
    {
        return 'LG-CONFIG-009';
    }

    public function name(): string
    {
        return 'All proxies may be trusted';
    }

    public function category(): string
    {
        return 'configuration';
    }

    public function severity(): Severity
    {
        return Severity::High;
    }

    public function scan(SecurityContext $context): iterable
    {
        foreach ($this->sources->calls($context, ['trustProxies']) as $call) {
            if (! str_contains($call->code(), "'*'") && ! str_contains($call->code(), '"*"')) {
                continue;
            }
            yield SecurityFinding::fromRule($this, 'The application appears to trust forwarded headers from every proxy.', 'Attackers may spoof client IP, host, scheme, or secure-request detection.', 'Trust only known load-balancer addresses and explicitly select forwarded headers.', Confidence::Medium, $call->file->path, $call->line(), ['symbol' => $call->symbol, 'code' => $call->code()]);
        }
    }
}
