<?php

namespace LaravelGuard\Secrets\Rules;

use LaravelGuard\Core\Contracts\SecurityContext;
use LaravelGuard\Core\Findings\Confidence;
use LaravelGuard\Core\Findings\SecurityFinding;
use LaravelGuard\Core\Findings\Severity;
use LaravelGuard\Core\Rules\AbstractGuardRule;
use LaravelGuard\Core\Source\SourceIndex;
use PhpParser\Node;
use PhpParser\NodeFinder;

final class HardcodedSecret extends AbstractGuardRule
{
    public function __construct(private readonly SourceIndex $sources) {}

    public function id(): string
    {
        return 'LG-SECRET-001';
    }

    public function name(): string
    {
        return 'Potential hardcoded secret';
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
        foreach ($this->sources->files($context) as $file) {
            foreach ((new NodeFinder)->findInstanceOf($file->statements, Node\Scalar\String_::class) as $string) {
                $value = $string->value;
                if (! $this->looksSecret($value, $file->source, $string->getStartFilePos())) {
                    continue;
                }

                yield SecurityFinding::fromRule($this, 'A credential-like literal was detected and masked as '.$this->mask($value).'.', 'Committed credentials can grant unauthorized access and remain recoverable from version history.', 'Move the value to a secret manager or environment variable and rotate it if committed.', Confidence::High, $file->path, $string->getStartLine(), ['symbol' => $string->getAttribute('laravel_guard_symbol'), 'secret_type' => $this->type($value), 'masked' => $this->mask($value)]);
            }
        }
    }

    private function looksSecret(string $value, string $source, int $offset): bool
    {
        if (preg_match('/^(?:sk_(?:live|test)_[A-Za-z0-9]{16,}|AKIA[0-9A-Z]{16}|gh[pousr]_[A-Za-z0-9]{20,}|xox[baprs]-[A-Za-z0-9-]{12,})$/', $value)) {
            return true;
        }
        if (str_contains($value, 'BEGIN PRIVATE KEY') || str_contains($value, 'BEGIN RSA PRIVATE KEY')) {
            return true;
        }
        if (strlen($value) < 16 || in_array(strtolower($value), ['password', 'secret', 'changeme', 'example', 'testing'], true)) {
            return false;
        }

        $lineStart = strrpos(substr($source, 0, max(0, $offset)), "\n");
        $prefix = substr($source, $lineStart === false ? 0 : $lineStart, max(0, $offset - ($lineStart ?: 0)));

        return (bool) preg_match('/(?:api[_-]?key|secret|password|token|credential)\s*(?:=>|=)\s*[\'\"]?$/i', trim($prefix));
    }

    private function mask(string $value): string
    {
        return substr($value, 0, min(4, strlen($value))).str_repeat('*', min(12, max(6, strlen($value) - 6))).substr($value, -2);
    }

    private function type(string $value): string
    {
        return match (true) {
            str_starts_with($value, 'AKIA') => 'aws_access_key',
            str_starts_with($value, 'gh') => 'github_token',
            str_starts_with($value, 'sk_') => 'api_key',
            str_starts_with($value, 'xox') => 'slack_token',
            str_contains($value, 'PRIVATE KEY') => 'private_key',
            default => 'credential',
        };
    }
}
