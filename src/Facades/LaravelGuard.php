<?php

namespace LaravelGuard\Facades;

use Illuminate\Support\Facades\Facade;

/** @method static \LaravelGuard\Core\Findings\FindingCollection scan(?string $module = null) @method static \LaravelGuard\LaravelGuard registerRule(string|\LaravelGuard\Core\Contracts\GuardRule $rule) */
final class LaravelGuard extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \LaravelGuard\LaravelGuard::class;
    }
}
