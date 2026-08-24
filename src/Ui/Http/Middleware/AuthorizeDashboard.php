<?php

namespace LaravelGuard\Ui\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class AuthorizeDashboard
{
    public function __construct(private Gate $gate) {}

    public function handle(Request $request, Closure $next): Response
    {
        $ability = config('laravel-guard.ui.ability');
        abort_unless(is_string($ability) && trim($ability) !== '', 403);
        abort_unless($this->gate->has($ability) && $this->gate->allows($ability), 403);

        return $next($request);
    }
}
