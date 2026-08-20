<?php

namespace LaravelGuard\Models;

use LaravelGuard\Core\Contracts\SecurityContext;
use LaravelGuard\Core\Source\Ast;
use LaravelGuard\Core\Source\SourceIndex;
use PhpParser\Node;
use PhpParser\NodeFinder;

final readonly class ModelInspector
{
    public function __construct(private SourceIndex $sources) {}

    /** @return iterable<ModelDefinition> */
    public function models(SecurityContext $context): iterable
    {
        foreach ($this->sources->files($context) as $file) {
            $classes = (new NodeFinder)->findInstanceOf($file->statements, Node\Stmt\Class_::class);
            foreach ($classes as $class) {
                $extends = $class->extends?->toString();
                if ($extends !== null && ($extends === 'Model' || str_ends_with($extends, '\\Model'))) {
                    yield new ModelDefinition($class, $file);
                }
            }
        }
    }

    /** @return list<string> */
    public function propertyStrings(Node\Stmt\Class_ $class, string $property): array
    {
        foreach ($class->getProperties() as $statement) {
            foreach ($statement->props as $prop) {
                if ($prop->name->toString() === $property && $prop->default instanceof Node\Expr\Array_) {
                    return Ast::strings($prop->default);
                }
            }
        }

        return [];
    }

    public function hasEmptyArrayProperty(Node\Stmt\Class_ $class, string $property): bool
    {
        foreach ($class->getProperties() as $statement) {
            foreach ($statement->props as $prop) {
                if ($prop->name->toString() === $property && $prop->default instanceof Node\Expr\Array_ && $prop->default->items === []) {
                    return true;
                }
            }
        }

        return false;
    }

    /** @return list<string> */
    public function attributeStrings(Node\Stmt\Class_ $class, string $attribute): array
    {
        foreach ($class->attrGroups as $group) {
            foreach ($group->attrs as $attr) {
                if (str_ends_with($attr->name->toString(), $attribute)) {
                    return Ast::strings($attr);
                }
            }
        }

        return [];
    }
}
