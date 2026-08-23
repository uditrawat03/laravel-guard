<?php

namespace LaravelGuard\Routes;

use Illuminate\Routing\Route;
use Illuminate\Support\Str;

final class RouteAnalysis
{
    public static function middleware(Route $r): array
    {
        return array_map('strval', $r->gatherMiddleware());
    }

    public static function has(array $m, array $needles): bool
    {
        foreach ($m as $v) {
            foreach ($needles as $n) {
                if ($v === $n || str_starts_with($v, $n.':') || str_starts_with($v, $n.'.')) {
                    return true;
                }
            }
        }

        return false;
    }

    public static function sensitive(Route $r, array $c): bool
    {
        if (array_intersect($r->methods(), $c['sensitive_methods'] ?? [])) {
            return true;
        }
        foreach ($c['sensitive_patterns'] ?? [] as $p) {
            if (Str::is($p, $r->uri()) || Str::is($p, (string) $r->getName())) {
                return true;
            }
        }

        return false;
    }

    public static function public(Route $r, array $c): bool
    {
        if (self::ignored($r, $c)) {
            return true;
        }

        foreach ($c['public'] ?? [] as $p) {
            if (Str::is($p, $r->uri()) || Str::is($p, (string) $r->getName())) {
                return true;
            }
        }

        return false;
    }

    public static function ignored(Route $r, array $c): bool
    {
        foreach ($c['ignore'] ?? [] as $pattern) {
            if (Str::is($pattern, $r->uri()) || Str::is($pattern, (string) $r->getName())) {
                return true;
            }
        }

        return false;
    }

    public static function metadata(Route $r): array
    {
        return ['methods' => $r->methods(), 'uri' => $r->uri(), 'name' => $r->getName(), 'action' => $r->getActionName(), 'symbol' => $r->getName() ?: $r->uri()];
    }
}
