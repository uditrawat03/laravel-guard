<?php

namespace LaravelGuard\Core\Exceptions;

final class SecurityExceptionManager
{
    /** @var list<array{rule: string, reason: string}> */
    private array $active = [];

    /** @var list<SecurityException> */
    private array $audit = [];

    public function allow(string $rule, string $reason, callable $callback): mixed
    {
        if (trim($rule) === '' || trim($reason) === '') {
            throw new \InvalidArgumentException('A scoped security exception requires a rule and reason.');
        }

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? [];
        $this->audit[] = new SecurityException($rule, $reason, $trace['file'] ?? 'unknown', $trace['line'] ?? 0, date(DATE_ATOM));
        $this->active[] = compact('rule', 'reason');

        try {
            return $callback();
        } finally {
            array_pop($this->active);
        }
    }

    public function allows(string $rule): bool
    {
        foreach (array_reverse($this->active) as $exception) {
            if ($exception['rule'] === $rule || $exception['rule'] === '*') {
                return true;
            }
        }

        return false;
    }

    /** @return list<SecurityException> */
    public function audit(): array
    {
        return $this->audit;
    }
}
