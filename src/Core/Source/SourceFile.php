<?php

namespace LaravelGuard\Core\Source;

use PhpParser\Node;

final readonly class SourceFile
{
    /**
     * @param  list<Node>  $statements
     * @param  list<AstSuppression>  $suppressions
     */
    public function __construct(
        public string $path,
        public string $source,
        public array $statements,
        public array $suppressions = [],
    ) {}

    public function snippet(Node $node): string
    {
        $start = max(0, $node->getStartFilePos());
        $end = max($start, $node->getEndFilePos());

        return substr($this->source, $start, $end - $start + 1);
    }
}
