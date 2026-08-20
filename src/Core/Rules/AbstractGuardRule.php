<?php

namespace LaravelGuard\Core\Rules;

use LaravelGuard\Core\Contracts\GuardRule;

abstract class AbstractGuardRule implements GuardRule
{
    public function description(): string
    {
        return $this->name();
    }
}
