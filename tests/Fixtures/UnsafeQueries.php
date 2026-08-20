<?php

namespace LaravelGuard\Tests\Fixtures;

use Illuminate\Support\Facades\DB;

final class UnsafeQueries
{
    public function lookup(string $email): array
    {
        return DB::select("SELECT * FROM users WHERE email = '{$email}'");
    }

    public function purge(): int
    {
        return DB::table('audit_logs')->delete();
    }
}
