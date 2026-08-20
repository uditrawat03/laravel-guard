<?php

namespace LaravelGuard\Core\Source;

use PhpParser\Node\Expr;

final readonly class CallSite
{
    public function __construct(
        public string $name,
        public Expr $node,
        public SourceFile $file,
        public ?string $symbol,
    ) {}

    public function line(): int
    {
        return $this->node->getStartLine();
    }

    public function code(): string
    {
        return $this->file->snippet($this->node);
    }
}
