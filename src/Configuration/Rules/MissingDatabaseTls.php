<?php

namespace LaravelGuard\Configuration\Rules;

use LaravelGuard\Core\Contracts\SecurityContext;
use LaravelGuard\Core\Findings\Confidence;
use LaravelGuard\Core\Findings\SecurityFinding;
use LaravelGuard\Core\Findings\Severity;
use LaravelGuard\Core\Rules\AbstractGuardRule;

final class MissingDatabaseTls extends AbstractGuardRule
{
    public function id(): string
    {
        return 'LG-CONFIG-007';
    }

    public function name(): string
    {
        return 'Database transport encryption is not explicit';
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
        $name = $config->get('database.default');
        $connection = $config->get("database.connections.{$name}", []);
        $driver = $connection['driver'] ?? null;
        if (! in_array($driver, ['mysql', 'mariadb', 'pgsql', 'sqlsrv'], true)) {
            return;
        }
        $tls = ($connection['sslmode'] ?? null) && ! in_array($connection['sslmode'], ['disable', 'allow', 'prefer'], true);
        $tls = $tls || ($connection['encrypt'] ?? false) === true || ! empty($connection['options'] ?? []);
        if (! $tls) {
            yield SecurityFinding::fromRule($this, "Database connection [{$name}] does not explicitly require TLS.", 'Credentials and application data may traverse the network without verified encryption.', 'Require TLS, certificate validation, and a trusted CA in the production connection.', Confidence::Medium, metadata: ['connection' => $name, 'driver' => $driver]);
        }
    }
}
