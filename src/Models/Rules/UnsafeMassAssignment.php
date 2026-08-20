<?php

namespace LaravelGuard\Models\Rules;

use LaravelGuard\Core\Contracts\SecurityContext;
use LaravelGuard\Core\Findings\Confidence;
use LaravelGuard\Core\Findings\SecurityFinding;
use LaravelGuard\Core\Findings\Severity;
use LaravelGuard\Core\Rules\AbstractGuardRule;
use LaravelGuard\Models\ModelInspector;

final class UnsafeMassAssignment extends AbstractGuardRule
{
    private const SENSITIVE = ['is_admin', 'admin', 'role', 'permissions', 'password', 'tenant_id', 'organization_id', 'account_id', 'balance'];

    public function __construct(private readonly ModelInspector $models) {}

    public function id(): string
    {
        return 'LG-MODEL-001';
    }

    public function name(): string
    {
        return 'Potentially unsafe mass assignment';
    }

    public function category(): string
    {
        return 'models';
    }

    public function severity(): Severity
    {
        return Severity::High;
    }

    public function scan(SecurityContext $context): iterable
    {
        foreach ($this->models->models($context) as $model) {
            $fillable = [...$this->models->propertyStrings($model->node, 'fillable'), ...$this->models->attributeStrings($model->node, 'Fillable')];
            $sensitive = array_values(array_intersect(array_map('strtolower', $fillable), self::SENSITIVE));
            $unguarded = $this->models->hasEmptyArrayProperty($model->node, 'guarded');
            if (! $unguarded && $sensitive === []) {
                continue;
            }

            $detail = $unguarded ? 'declares an empty guarded array' : 'allows sensitive fields: '.implode(', ', $sensitive);
            yield SecurityFinding::fromRule($this, "{$model->symbol()} {$detail}.", 'Untrusted request data could modify privileged or tenant-bound attributes.', 'Use explicit validated DTOs or a narrow fillable list and assign privileged attributes separately.', $sensitive === [] ? Confidence::Medium : Confidence::High, $model->file->path, $model->node->getStartLine(), ['symbol' => $model->symbol(), 'code' => $model->file->snippet($model->node)]);
        }
    }
}
