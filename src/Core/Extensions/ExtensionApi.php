<?php

namespace LaravelGuard\Core\Extensions;

final class ExtensionApi
{
    public const VERSION = '1.0';

    public const CONFIGURATION_VERSION = 1;

    /** @return array<string, int> */
    public static function outputSchemas(): array
    {
        return [
            'laravel-guard/report' => 1,
            'laravel-guard/security-diff' => 1,
            'laravel-guard/baseline' => 4,
            'laravel-guard/scan-performance' => 1,
            'laravel-guard/runtime-performance' => 2,
        ];
    }
}
