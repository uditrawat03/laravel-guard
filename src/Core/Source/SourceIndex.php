<?php

namespace LaravelGuard\Core\Source;

use LaravelGuard\Core\Contracts\SecurityContext;
use LaravelGuard\Core\Support\PhpFileScanner;
use PhpParser\Error;
use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use PhpParser\ParserFactory;

final class SourceIndex
{
    /** @var array<string, SourceFile> */
    private array $cache = [];

    private readonly Parser $parser;

    /** @var \WeakMap<SecurityContext, array<string, SourceFile>> */
    private readonly \WeakMap $scanCache;

    public function __construct(private readonly PhpFileScanner $files)
    {
        $this->parser = (new ParserFactory)->createForNewestSupportedVersion();
        $this->scanCache = new \WeakMap;
    }

    /** @return iterable<string, SourceFile> */
    public function files(SecurityContext $context): iterable
    {
        $paths = $context->config['paths'] ?? [$context->path('app'), $context->path('routes')];
        $excluded = $context->config['exclude_paths'] ?? [];
        if (isset($this->scanCache[$context])) {
            yield from $this->scanCache[$context];

            return;
        }

        $indexed = [];
        foreach ($this->files->files($paths, $excluded) as $path => $source) {
            $key = $path.':'.hash('xxh128', $source);
            if (! isset($this->cache[$key])) {
                $this->cache[$key] = $this->parse($path, $source);
            }

            $indexed[$path] = $this->cache[$key];
        }
        $this->scanCache[$context] = $indexed;

        yield from $indexed;
    }

    /** @return iterable<CallSite> */
    public function calls(SecurityContext $context, array $names = []): iterable
    {
        $wanted = array_map('strtolower', $names);
        $finder = new NodeFinder;

        foreach ($this->files($context) as $file) {
            $calls = $finder->find($file->statements, fn (Node $node) => $node instanceof Node\Expr\FuncCall || $node instanceof Node\Expr\MethodCall || $node instanceof Node\Expr\StaticCall);
            foreach ($calls as $call) {
                $name = $this->callName($call);
                if ($name === null || ($wanted !== [] && ! in_array(strtolower($name), $wanted, true))) {
                    continue;
                }

                yield new CallSite($name, $call, $file, $call->getAttribute('laravel_guard_symbol'));
            }
        }
    }

    public function suppressed(SecurityContext $context, string $file, ?string $symbol, string $rule): bool
    {
        foreach ($this->files($context) as $sourceFile) {
            if ($sourceFile->path !== str_replace('\\', '/', $file)) {
                continue;
            }

            foreach ($sourceFile->suppressions as $suppression) {
                if (($suppression->rule === $rule || $suppression->rule === '*') && ($suppression->symbol === null || $symbol === $suppression->symbol || str_starts_with((string) $symbol, $suppression->symbol.'::'))) {
                    return true;
                }
            }
        }

        return false;
    }

    private function parse(string $path, string $source): SourceFile
    {
        try {
            $statements = $this->parser->parse($source) ?? [];
        } catch (Error) {
            return new SourceFile($path, $source, []);
        }

        $traverser = new NodeTraverser;
        $traverser->addVisitor(new NameResolver);
        $traverser->addVisitor(new SymbolAnnotatingVisitor);
        $statements = $traverser->traverse($statements);

        return new SourceFile($path, $source, $statements, $this->suppressions($statements));
    }

    /** @return list<AstSuppression> */
    private function suppressions(array $statements): array
    {
        $result = [];
        $nodes = (new NodeFinder)->find($statements, fn (Node $node) => property_exists($node, 'attrGroups') && $node->attrGroups !== []);

        foreach ($nodes as $node) {
            foreach ($node->attrGroups as $group) {
                foreach ($group->attrs as $attribute) {
                    if (! str_ends_with($attribute->name->toString(), 'GuardIgnore')) {
                        continue;
                    }

                    $values = [];
                    foreach ($attribute->args as $index => $argument) {
                        $key = $argument->name?->toString() ?? $index;
                        if ($argument->value instanceof Node\Scalar\String_) {
                            $values[$key] = $argument->value->value;
                        }
                    }

                    $rule = $values['rule'] ?? $values[0] ?? null;
                    $reason = $values['reason'] ?? $values[1] ?? null;
                    if (is_string($rule) && is_string($reason) && trim($reason) !== '') {
                        $result[] = new AstSuppression($rule, $reason, $node->getAttribute('laravel_guard_symbol'), $node->getStartLine());
                    }
                }
            }
        }

        return $result;
    }

    private function callName(Node\Expr $call): ?string
    {
        $name = $call->name ?? null;

        return $name instanceof Node\Identifier || $name instanceof Node\Name ? $name->toString() : null;
    }
}
