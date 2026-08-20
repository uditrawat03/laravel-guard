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
        if (array_key_exists($finding->ruleId, $ignore)) {
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
        }

        return $finding->location->file !== null
            && $this->sources->suppressed($context, $finding->location->file, $finding->metadata['symbol'] ?? null, $finding->ruleId);
    }
}
