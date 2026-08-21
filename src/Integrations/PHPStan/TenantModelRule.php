<?php

namespace LaravelGuard\Integrations\PHPStan;

use LaravelGuard\Tenant\Contracts\TenantOwned;
use LaravelGuard\Tenant\GuardsTenant;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/** @implements Rule<Class_> */
final readonly class TenantModelRule implements Rule
{
    /** @param list<class-string> $tenantModelClasses */
    public function __construct(private array $tenantModelClasses = []) {}

    public function getNodeType(): string
    {
        return Class_::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (! $scope->isInClass()) {
            return [];
        }

        $reflection = $scope->getClassReflection();
        if (! in_array($reflection->getName(), $this->tenantModelClasses, true)) {
            return [];
        }

        $native = $reflection->getNativeReflection();
        if ($native->implementsInterface(TenantOwned::class) || in_array(GuardsTenant::class, $this->allTraits($native), true)) {
            return [];
        }

        return [RuleErrorBuilder::message(sprintf(
            'Tenant model %s must implement %s or use %s.',
            $reflection->getName(),
            TenantOwned::class,
            GuardsTenant::class,
        ))->identifier('laravelGuard.tenant.missingConstraint')->build()];
    }

    /** @return list<class-string> */
    private function allTraits(\ReflectionClass $class): array
    {
        $traits = [];
        do {
            foreach ($class->getTraits() as $trait) {
                $traits[$trait->getName()] = $trait->getName();
                $traits += array_combine(array_keys($trait->getTraits()), array_keys($trait->getTraits())) ?: [];
            }
        } while ($class = $class->getParentClass());

        return array_values($traits);
    }
}
