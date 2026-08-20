<?php

namespace LaravelGuard\Configuration\Rules;

use LaravelGuard\Core\Contracts\SecurityContext;
use LaravelGuard\Core\Findings\Confidence;
use LaravelGuard\Core\Findings\SecurityFinding;
use LaravelGuard\Core\Findings\Severity;
use LaravelGuard\Core\Rules\AbstractGuardRule;

final class InsecureMailTransport extends AbstractGuardRule
{
    public function id(): string
    {
        return 'LG-CONFIG-008';
    }

    public function name(): string
    {
        return 'Insecure production mail transport';
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
        $default = $config->get('mail.default');
        $mailer = $config->get("mail.mailers.{$default}", []);
        $scheme = strtolower((string) ($mailer['scheme'] ?? $mailer['encryption'] ?? ''));
        if (in_array($default, ['log', 'array'], true) || (($mailer['transport'] ?? null) === 'smtp' && ! in_array($scheme, ['tls', 'ssl', 'smtps'], true))) {
            yield SecurityFinding::fromRule($this, "Production mailer [{$default}] does not provide an explicit encrypted transport.", 'Password resets, invitations, and patient notifications may leak or never leave local logs.', 'Use a production mail provider over verified TLS and avoid logging message bodies.', Confidence::High, metadata: ['mailer' => $default]);
        }
    }
}
