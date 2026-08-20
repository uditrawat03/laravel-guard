<?php

namespace LaravelGuard\Secrets\Rules;

use Illuminate\Filesystem\Filesystem;
use LaravelGuard\Core\Contracts\SecurityContext;
use LaravelGuard\Core\Findings\Confidence;
use LaravelGuard\Core\Findings\SecurityFinding;
use LaravelGuard\Core\Findings\Severity;
use LaravelGuard\Core\Rules\AbstractGuardRule;

final class CommittedCredential extends AbstractGuardRule
{
    public function __construct(private readonly Filesystem $files) {}

    public function id(): string
    {
        return 'LG-SECRET-002';
    }

    public function name(): string
    {
        return 'Credential file is tracked by Git';
    }

    public function category(): string
    {
        return 'secrets';
    }

    public function severity(): Severity
    {
        return Severity::Critical;
    }

    public function scan(SecurityContext $context): iterable
    {
        if (! $this->tracked($context->path(), '.env')) {
            return;
        }
        $path = $context->path('.env');
        if (! $this->files->exists($path)) {
            return;
        }
        $sensitive = [];
        foreach (preg_split('/\R/', $this->files->get($path)) as $line) {
            if (preg_match('/^([A-Z0-9_]*(?:KEY|SECRET|PASSWORD|TOKEN|CREDENTIAL)[A-Z0-9_]*)=(.+)$/', trim($line), $matches) && trim($matches[2], " \t\n\r\0\x0B\"'") !== '') {
                $sensitive[] = $matches[1];
            }
        }
        if ($sensitive !== []) {
            yield SecurityFinding::fromRule($this, 'The tracked .env file contains credential-bearing keys: '.implode(', ', $sensitive).'.', 'Secrets committed to Git remain recoverable after deletion and may grant infrastructure access.', 'Remove .env from version control, rotate every exposed value, and use deployment secrets.', Confidence::High, $path, 1, ['keys' => $sensitive, 'symbol' => '.env']);
        }
    }

    private function tracked(string $directory, string $path): bool
    {
        $process = proc_open(['git', '-C', $directory, 'ls-files', '--error-unmatch', '--', $path], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (! is_resource($process)) {
            return false;
        }
        stream_get_contents($pipes[1]);
        stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        return proc_close($process) === 0;
    }
}
