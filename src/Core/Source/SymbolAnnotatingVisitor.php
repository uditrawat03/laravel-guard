<?php

namespace LaravelGuard\Core\Source;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

final class SymbolAnnotatingVisitor extends NodeVisitorAbstract
{
    /** @var list<string> */
    private array $symbols = [];

    public function enterNode(Node $node): null|int|Node
    {
        $symbol = $this->symbolFor($node);
        if ($symbol !== null) {
            $parent = end($this->symbols);
            $this->symbols[] = $parent ? "{$parent}::{$symbol}" : $symbol;
        }

        $active = end($this->symbols);
        if ($active !== false) {
            $node->setAttribute('laravel_guard_symbol', $active);
        }

        return null;
    }

    public function leaveNode(Node $node): null|int|Node|array
    {
        if ($this->symbolFor($node) !== null) {
            array_pop($this->symbols);
        }

        return null;
    }

    private function symbolFor(Node $node): ?string
    {
        if ($node instanceof Node\Stmt\ClassLike && $node->name !== null) {
            return $node->namespacedName?->toString() ?? $node->name->toString();
        }

        if ($node instanceof Node\Stmt\ClassMethod) {
            return $node->name->toString();
        }

        if ($node instanceof Node\Stmt\Function_) {
            return $node->namespacedName?->toString() ?? $node->name->toString();
        }

        return null;
    }
}
