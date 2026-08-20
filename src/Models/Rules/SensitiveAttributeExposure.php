<?php

namespace LaravelGuard\Models\Rules;

use LaravelGuard\Core\Contracts\SecurityContext;
use LaravelGuard\Core\Findings\Confidence;
use LaravelGuard\Core\Findings\SecurityFinding;
use LaravelGuard\Core\Findings\Severity;
use LaravelGuard\Core\Rules\AbstractGuardRule;
use LaravelGuard\Models\ModelInspector;

final class SensitiveAttributeExposure extends AbstractGuardRule
{
    private const SENSITIVE = ['password', 'remember_token', 'api_token', 'access_token', 'refresh_token', 'secret', 'ssn', 'national_id', 'medical_notes'];

    public function __construct(private readonly ModelInspector $models) {}

    public function id(): string
    {
        return 'LG-MODEL-002';
    }

    public function name(): string
    {
        return 'Sensitive model attribute may be serialized';
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
            $hidden = [...$this->models->propertyStrings($model->node, 'hidden'), ...$this->models->attributeStrings($model->node, 'Hidden')];
            $exposed = array_values(array_diff(array_intersect(array_map('strtolower', $fillable), self::SENSITIVE), array_map('strtolower', $hidden)));
            if ($exposed === []) {
                continue;
            }

            yield SecurityFinding::fromRule($this, "{$model->symbol()} may serialize: ".implode(', ', $exposed).'.', 'Sensitive values can leak through JSON responses, logs, queues, or debugging output.', 'Add these attributes to hidden or expose the model only through an explicit API resource.', Confidence::High, $model->file->path, $model->node->getStartLine(), ['symbol' => $model->symbol(), 'attributes' => $exposed, 'code' => $model->file->snippet($model->node)]);
        }
    }
}
