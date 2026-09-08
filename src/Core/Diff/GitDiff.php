<?php

namespace LaravelGuard\Core\Diff;

use LaravelGuard\Core\Findings\FindingCollection;

final class GitDiff
{
    /** @var array<string, list<array{0:int, 1:int}>> */
    private array $ranges = [];

    public static function fromRef(string $base, string $workingDirectory): self
    {
        $mergeBase = GitRepository::mergeBase($base, $workingDirectory);
        [$status, $output] = GitRepository::run([
            'git', '-c', 'core.quotepath=false', '-C', $workingDirectory,
            'diff', '--unified=0', '--no-color', '--find-renames', $mergeBase, '--', '.',
        ]);
        if ($status !== 0) {
            throw new \RuntimeException(trim($output) ?: "Unable to diff against [{$base}].");
        }

        foreach (GitRepository::untrackedFiles($workingDirectory) as $relative) {
            $absolute = rtrim($workingDirectory, '/\\').DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);
            if (! is_file($absolute) || ($contents = file_get_contents($absolute)) === false || str_contains($contents, "\0") || $contents === '') {
                continue;
            }
            $lines = substr_count($contents, "\n") + (str_ends_with($contents, "\n") ? 0 : 1);
            $path = str_replace('\\', '/', $relative);
            $output .= "\n--- /dev/null\n+++ b/{$path}\n@@ -0,0 +1,{$lines} @@\n";
        }

        return new self($output);
    }

    public function __construct(string $diff)
    {
        $file = null;
        foreach (preg_split('/\R/', $diff) as $line) {
            if (str_starts_with($line, '+++ b/')) {
                $file = substr($line, 6);
            } elseif (str_starts_with($line, '+++ /dev/null')) {
                $file = null;
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
