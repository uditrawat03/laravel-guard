<?php

namespace LaravelGuard\Routes\Rules;

use Illuminate\Contracts\Auth\Access\Gate;
use LaravelGuard\Core\Contracts\SecurityContext;
use LaravelGuard\Core\Findings\Confidence;
use LaravelGuard\Core\Findings\SecurityFinding;
use LaravelGuard\Core\Findings\Severity;
use LaravelGuard\Core\Rules\AbstractGuardRule;

final class MissingPolicyRegistration extends AbstractGuardRule
{
    public function __construct(private readonly Gate $gate) {}

    public function id(): string
    {
        return 'LG-ROUTE-007';
    }

    public function name(): string
    {
        return 'Configured model has no authorization policy';
    }

    public function category(): string
    {
        return 'routes';
    }

    public function severity(): Severity
    {
        return Severity::High;
    }

    public function scan(SecurityContext $context): iterable
    {
        foreach ($context->config['routes']['policy_models'] ?? [] as $model) {
            if (! is_string($model) || ! class_exists($model)) {
                continue;
            }
            if ($this->gate->getPolicyFor($model) === null) {
                yield SecurityFinding::fromRule($this, "The configured model [{$model}] has no discoverable policy.", 'Resource routes may rely on authorization that Laravel cannot resolve.', 'Create and register a policy or add a UsePolicy attribute to the model.', Confidence::High, metadata: ['symbol' => $model]);
            }
        }
    }
}
