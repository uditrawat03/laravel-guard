<?php

namespace LaravelGuard\Tests\Integration\PHPStan\Fixtures;

use Illuminate\Database\Eloquent\Model;
use LaravelGuard\Tenant\GuardsTenant;

final class SafeTenantModel extends Model
{
    use GuardsTenant;
}
