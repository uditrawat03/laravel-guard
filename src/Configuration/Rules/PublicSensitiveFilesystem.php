<?php

namespace LaravelGuard\Configuration\Rules;

use LaravelGuard\Core\Contracts\SecurityContext;
use LaravelGuard\Core\Findings\Confidence;
use LaravelGuard\Core\Findings\SecurityFinding;
use LaravelGuard\Core\Findings\Severity;
use LaravelGuard\Core\Rules\AbstractGuardRule;

final class PublicSensitiveFilesystem extends AbstractGuardRule
{
    public function id(): string
    {
        return 'LG-CONFIG-005';
    }

    public function name(): string
    {
        return 'Default filesystem is publicly visible';
    }

    public function category(): string
    {
        return 'configuration';
    }

    public function severity(): Severity
    {
        return Severity::High;
    }

    public function scan(SecurityContext $context): iterable
    {
        if (! $context->app->environment('production')) {
            return;
        }
        $config = $context->app['config'];
        $default = $config->get('filesystems.default');
        $disk = $config->get("filesystems.disks.{$default}", []);
        if ($default === 'public' || ($disk['visibility'] ?? null) === 'public') {
            yield SecurityFinding::fromRule($this, "The default [{$default}] filesystem is publicly visible.", 'Files containing exports, identity documents, or internal records may become directly accessible.', 'Use a private default disk and temporary signed URLs for authorized downloads.', Confidence::Medium, metadata: ['disk' => $default]);
        }
    }
}
