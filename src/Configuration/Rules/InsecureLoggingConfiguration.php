<?php

namespace LaravelGuard\Configuration\Rules;

use LaravelGuard\Core\Contracts\SecurityContext;
use LaravelGuard\Core\Findings\SecurityFinding;
use LaravelGuard\Core\Findings\Severity;
use LaravelGuard\Core\Rules\AbstractGuardRule;

final class InsecureLoggingConfiguration extends AbstractGuardRule
{
    public function id(): string
    {
        return 'LG-CONFIG-006';
    }

    public function name(): string
    {
        return 'Verbose production logging';
    }

    public function category(): string
    {
        return 'configuration';
    }

    public function severity(): Severity
    {
        return Severity::Medium;
    }

    public function scan(SecurityContext $context): iterable
    {
        if (! $context->app->environment('production')) {
            return;
        }
        $config = $context->app['config'];
        $channel = $config->get('logging.default');
        $level = strtolower((string) ($config->get("logging.channels.{$channel}.level") ?? $config->get('logging.level')));
        if (in_array($level, ['debug', 'notice', 'info'], true)) {
            yield SecurityFinding::fromRule($this, "Production logging level is [{$level}].", 'Verbose logs can retain request data, tokens, identifiers, and sensitive business context.', 'Use warning or error in production and apply structured redaction for sensitive fields.', metadata: ['channel' => $channel, 'level' => $level]);
        }
    }
}
