<?php

namespace LaravelGuard\Core\Suppressions;

use LaravelGuard\Core\Findings\SecurityFinding;

final class ConfigSuppressionResolver
{
    public function suppressed(SecurityFinding $finding, array $ignore): bool
    {
        if (! array_key_exists($finding->ruleId, $ignore)) {
            return false;
        }
        $targets = $ignore[$finding->ruleId];
        if ($targets === true || $targets === ['*']) {
            return true;
        }
        foreach ((array) $targets as $target) {
            $value = is_array($target) ? ($target['target'] ?? null) : $target;
            if ($value && ($value === $finding->location->file || $value === ($finding->metadata['symbol'] ?? null))) {
                return true;
            }
        }

        return false;
    }
}
