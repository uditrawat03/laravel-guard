<?php

namespace LaravelGuard\Core\Diff;

final class GitBaseline
{
    public static function fromRef(string $base, string $workingDirectory, string $baselinePath): ?BaselineSnapshot
    {
        if (! preg_match('/^[A-Za-z0-9._\/-]+$/', $base)) {
            throw new \InvalidArgumentException('The Git base contains unsupported characters.');
        }
        $root = rtrim(str_replace('\\', '/', realpath($workingDirectory) ?: $workingDirectory), '/');
        $path = str_replace('\\', '/', $baselinePath);
        if (! str_starts_with($path, $root.'/')) {
            throw new \InvalidArgumentException('The baseline must be inside the Git working directory.');
        }
        $relative = substr($path, strlen($root) + 1);
        $process = proc_open(['git', '-C', $workingDirectory, 'show', "{$base}:{$relative}"], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (! is_resource($process)) {
            throw new \RuntimeException('Unable to start Git.');
        }
        $output = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $status = proc_close($process);
        if ($status !== 0) {
            if (str_contains(strtolower($error), 'does not exist') || str_contains(strtolower($error), 'exists on disk')) {
                return null;
            }
            throw new \RuntimeException(trim($error) ?: "Unable to load the baseline from [{$base}].");
        }

        return BaselineSnapshot::fromJson($output);
    }
}
