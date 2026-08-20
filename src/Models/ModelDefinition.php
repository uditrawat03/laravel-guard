<?php

namespace LaravelGuard\Models;

use LaravelGuard\Core\Source\SourceFile;
use PhpParser\Node\Stmt\Class_;

final readonly class ModelDefinition
{
    public function __construct(public Class_ $node, public SourceFile $file) {}

    public function symbol(): string
    {
        return $this->node->namespacedName?->toString() ?? $this->node->name?->toString() ?? 'anonymous-model';
    }
}
