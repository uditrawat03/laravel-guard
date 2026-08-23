<?php

namespace LaravelGuard\Core\Suppressions;

use LaravelGuard\Core\Contracts\SecurityContext;
use LaravelGuard\Core\Exceptions\SecurityExceptionManager;
use LaravelGuard\Core\Findings\SecurityFinding;
use LaravelGuard\Core\Source\SourceIndex;

final readonly class ConfigSuppressionResolver
{
    public function __construct(
        private SourceIndex $sources,
        private SecurityExceptionManager $exceptions,
    ) {}

    public function suppressed(SecurityFinding $finding, SecurityContext $context): bool
    {
        if ($this->exceptions->allows($finding->ruleId)) {
            return true;
        }

        $ignore = $context->config['ignore'] ?? [];
        if (! is_array($ignore)) {
            return false;
        }
        if (array_key_exists($finding->ruleId, $ignore)) {
            $targets = $ignore[$finding->ruleId];
            if ($targets === true || $targets === ['*']) {
                return true;
            }

            $identities = array_filter([
                $finding->location->file,
                $finding->metadata['symbol'] ?? null,
                $finding->metadata['route'] ?? null,
                $finding->metadata['route_name'] ?? null,
                $finding->fingerprint(),
                $finding->metadata['uri'] ?? null,
                $finding->metadata['name'] ?? null,
                $finding->metadata['action'] ?? null,
            ], 'is_string');
            foreach ((array) $targets as $target) {
                $value = is_array($target) ? ($target['target'] ?? null) : $target;
                if (is_string($value) && in_array($value, $identities, true)) {
                    return true;
                }
            }
        }

        return $finding->location->file !== null
            && $this->sources->suppressed($context, $finding->location->file, $finding->metadata['symbol'] ?? null, $finding->ruleId);
    }
}
