<?php

namespace LaravelGuard\Tests\Fixtures;

use Illuminate\Support\Facades\DB;
use LaravelGuard\Attributes\GuardIgnore;

final class SuppressedQuery
{
    #[GuardIgnore(rule: 'LG-QUERY-001', reason: 'Synthetic fixture verifies scoped suppression')]
    public function lookup(string $email): array
    {
        return DB::select("SELECT * FROM users WHERE email = '{$email}'");
    }
}
