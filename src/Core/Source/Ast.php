<?php

namespace LaravelGuard\Core\Source;

use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\PrettyPrinter\Standard;

final class Ast
{
    public static function code(Node $node): string
    {
        $printer = new Standard;

        return $node instanceof Node\Expr ? $printer->prettyPrintExpr($node) : $printer->prettyPrint([$node]);
    }

    public static function containsVariable(Node $node): bool
    {
        return (new NodeFinder)->findFirst($node, fn (Node $child) => $child instanceof Node\Expr\Variable || $child instanceof Node\Expr\PropertyFetch || $child instanceof Node\Expr\ArrayDimFetch) !== null;
    }

    public static function string(Node\Expr $expression): ?string
    {
        return $expression instanceof Node\Scalar\String_ ? $expression->value : null;
    }

    /** @return list<string> */
    public static function strings(Node $node): array
    {
        return array_values(array_map(
            fn (Node\Scalar\String_ $string) => $string->value,
            (new NodeFinder)->findInstanceOf($node, Node\Scalar\String_::class),
        ));
    }

    /** @return list<string> */
    public static function callChain(Node\Expr $expression): array
    {
        $names = [];
        $current = $expression;
        while ($current instanceof Node\Expr\MethodCall || $current instanceof Node\Expr\StaticCall) {
            if ($current->name instanceof Node\Identifier) {
                $names[] = strtolower($current->name->toString());
            }
            $current = $current instanceof Node\Expr\MethodCall ? $current->var : null;
        }

        return array_reverse($names);
    }
}
