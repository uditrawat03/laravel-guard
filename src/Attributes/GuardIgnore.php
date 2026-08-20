<?php

namespace LaravelGuard\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final readonly class GuardIgnore
{
    public function __construct(public string $rule, public string $reason)
    {
        if (trim($reason) === '') {
            throw new \InvalidArgumentException('GuardIgnore requires a reason.');
        }
    }
}
