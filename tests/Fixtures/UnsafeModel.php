<?php

namespace LaravelGuard\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

final class UnsafeModel extends Model
{
    protected $guarded = [];

    protected $fillable = ['name', 'api_token', 'is_admin'];
}
