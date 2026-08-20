<?php

namespace LaravelGuard\Core\Findings;

use Countable;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

final class FindingCollection implements Countable, IteratorAggregate, JsonSerializable
{
    /** @var list<SecurityFinding> */
    private array $items = [];

    public function add(SecurityFinding $finding): self
    {
        $this->items[] = $finding;

        return $this;
    }

    public function all(): array
    {
        return $this->items;
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function getIterator(): Traversable
    {
        yield from $this->items;
    }

    public function jsonSerialize(): array
    {
        return $this->items;
    }

    public function atOrAbove(Severity $severity): self
    {
        $result = new self;
        foreach ($this->items as $finding) {
            if ($finding->severity->value >= $severity->value) {
                $result->add($finding);
            }
        }

        return $result;
    }

    public function withoutFingerprints(array $fingerprints): self
    {
        $result = new self;
        foreach ($this->items as $finding) {
            if (! in_array($finding->fingerprint(), $fingerprints, true)) {
                $result->add($finding);
            }
        }

        return $result;
    }

    public function counts(): array
    {
        $counts = array_fill_keys(['critical', 'high', 'medium', 'low'], 0);
        foreach ($this->items as $finding) {
            $counts[strtolower($finding->severity->name)]++;
        }

        return $counts;
    }
}
