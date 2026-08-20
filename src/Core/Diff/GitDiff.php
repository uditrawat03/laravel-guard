<?php

namespace LaravelGuard\Core\Diff;

use LaravelGuard\Core\Findings\FindingCollection;

final class GitDiff
{
    /** @var array<string, list<array{0:int, 1:int}>> */
    private array $ranges = [];

    public static function fromRef(string $base, string $workingDirectory): self
    {
        if (! preg_match('/^[A-Za-z0-9._\/-]+$/', $base)) {
            throw new \InvalidArgumentException('The Git base contains unsupported characters.');
        }
        $process = proc_open(['git', '-C', $workingDirectory, 'diff', '--unified=0', '--no-color', $base, '--'], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (! is_resource($process)) {
            throw new \RuntimeException('Unable to start Git.');
        }
        $output = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        if (proc_close($process) !== 0) {
            throw new \RuntimeException(trim($error) ?: "Unable to diff against [{$base}].");
        }

        return new self($output);
    }

    public function __construct(string $diff)
    {
        $file = null;
        foreach (preg_split('/\R/', $diff) as $line) {
            if (str_starts_with($line, '+++ b/')) {
                $file = substr($line, 6);
            }
            if ($file && preg_match('/^@@ -\d+(?:,\d+)? \+(\d+)(?:,(\d+))? @@/', $line, $matches)) {
                $start = (int) $matches[1];
                $count = isset($matches[2]) ? (int) $matches[2] : 1;
                if ($count > 0) {
                    $this->ranges[$file][] = [$start, $start + $count - 1];
                }
            }
        }
    }

    public function newFindings(FindingCollection $findings): FindingCollection
    {
        $result = new FindingCollection;
        foreach ($findings as $finding) {
            if (! $finding->location->file || ! $finding->location->line) {
                continue;
            }
            $path = str_replace('\\', '/', $finding->location->file);
            foreach ($this->ranges as $file => $ranges) {
                if (! str_ends_with($path, $file)) {
                    continue;
                }
                foreach ($ranges as [$start, $end]) {
                    if ($finding->location->line >= $start && $finding->location->line <= $end) {
                        $result->add($finding);
                        break 2;
                    }
                }
            }
        }

        return $result;
    }
}
