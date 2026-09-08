<?php

namespace LaravelGuard\Core\Diff;

final class GitRepository
{
    public static function mergeBase(string $base, string $workingDirectory): string
    {
        self::assertRef($base);
        [$verifyStatus, $verifyError] = self::run(['git', '-C', $workingDirectory, 'rev-parse', '--verify', "{$base}^{commit}"]);
        if ($verifyStatus !== 0) {
            throw new \RuntimeException(trim($verifyError) ?: "Unable to resolve Git base [{$base}].");
        }

        [$status, $output] = self::run(['git', '-C', $workingDirectory, 'merge-base', $base, 'HEAD']);
        if ($status !== 0 || trim($output) === '') {
            throw new \RuntimeException("Unable to find a merge base between [{$base}] and HEAD. Fetch the comparison history first.");
        }

        return trim($output);
    }

    /** @return list<string> */
    public static function untrackedFiles(string $workingDirectory): array
    {
        [$status, $output] = self::run(['git', '-C', $workingDirectory, 'ls-files', '--others', '--exclude-standard', '-z']);
        if ($status !== 0) {
            throw new \RuntimeException('Unable to enumerate untracked Git files.');
        }

        return array_values(array_filter(explode("\0", $output), fn ($path) => $path !== ''));
    }

    /** @return array{0:int, 1:string} */
    public static function run(array $command): array
    {
        $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (! is_resource($process)) {
            throw new \RuntimeException('Unable to start Git.');
        }
        $output = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $status = proc_close($process);

        return [$status, $status === 0 ? (string) $output : (string) $error];
    }

    private static function assertRef(string $base): void
    {
        if (! preg_match('/^[A-Za-z0-9._\/-]+$/', $base)) {
            throw new \InvalidArgumentException('The Git base contains unsupported characters.');
        }
    }
}
