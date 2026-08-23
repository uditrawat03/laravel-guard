<?php

namespace LaravelGuard\Commands;

use DateTimeImmutable;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use LaravelGuard\Core\Baseline\BaselineDocument;
use LaravelGuard\Core\Baseline\BaselineEntry;
use LaravelGuard\LaravelGuard;

final class BaselineCommand extends Command
{
    protected $signature = 'guard:baseline
        {--force : Replace an existing baseline}
        {--reason= : Security acceptance reason}
        {--owner= : Person or team responsible for the accepted findings}
        {--expires= : Expiration date or relative date, such as +90 days}
        {--list : List baseline entries and their governance status}
        {--explain= : Explain entries matching a fingerprint or rule ID}
        {--prune : Remove expired and resolved entries}';

    protected $description = 'Create and govern the Laravel Guard finding baseline';

    public function handle(Filesystem $files, LaravelGuard $guard): int
    {
        $path = (string) config('laravel-guard.baseline');
        $modes = array_filter(['list' => $this->option('list'), 'explain' => $this->option('explain'), 'prune' => $this->option('prune')]);
        if (count($modes) > 1) {
            $this->error('Use only one of --list, --explain, or --prune at a time.');

            return self::INVALID;
        }

        if ($this->option('list')) {
            return $this->listEntries($files, $path);
        }
        if (is_string($this->option('explain')) && $this->option('explain') !== '') {
            return $this->explainEntry($files, $path, $this->option('explain'));
        }
        if ($this->option('prune')) {
            return $this->prune($files, $guard, $path);
        }

        return $this->create($files, $guard, $path);
    }

    private function create(Filesystem $files, LaravelGuard $guard, string $path): int
    {
        if ($files->exists($path) && ! $this->option('force')) {
            $this->error('Baseline already exists. Use --force to replace it.');

            return self::FAILURE;
        }

        $findings = $guard->scan();
        $reason = $this->cleanOption('reason');
        if ($findings->count() > 0 && config('laravel-guard.baseline_governance.require_reason', true) && $reason === null) {
            $this->error('A baseline acceptance reason is required. Pass --reason="...".');

            return self::INVALID;
        }

        try {
            $expiresAt = $this->expiration();
        } catch (\InvalidArgumentException $error) {
            $this->error($error->getMessage());

            return self::INVALID;
        }

        $document = BaselineDocument::fromFindings($findings, $this->owner(), $reason, $expiresAt);
        $this->write($files, $path, $document);
        $this->info("Saved {$findings->count()} governed finding(s) to {$path}.");

        return self::SUCCESS;
    }

    private function listEntries(Filesystem $files, string $path): int
    {
        $document = $this->load($files, $path);
        if ($document === null) {
            return self::FAILURE;
        }

        $this->table(['Fingerprint', 'Rule', 'Severity', 'Owner', 'Expires', 'Status', 'Reason'], array_map(
            fn (BaselineEntry $entry) => $this->row($entry),
            $document->entries,
        ));
        $this->info(count($document->entries).' baseline entr'.(count($document->entries) === 1 ? 'y' : 'ies').'.');

        return self::SUCCESS;
    }

    private function explainEntry(Filesystem $files, string $path, string $query): int
    {
        $document = $this->load($files, $path);
        if ($document === null) {
            return self::FAILURE;
        }
        $matches = $document->matching($query);
        if ($matches === []) {
            $this->error("No baseline entry matches [{$query}].");

            return self::FAILURE;
        }

        foreach ($matches as $entry) {
            $this->table(['Field', 'Value'], [
                ['Fingerprint', $entry->fingerprint], ['Rule', $entry->ruleId], ['Title', $entry->title],
                ['Severity', $entry->severity], ['File', $entry->file ?? '-'], ['Owner', $entry->owner ?? '-'],
                ['Reason', $entry->reason ?? '-'], ['Created', $entry->createdAt ?? '-'], ['Expires', $entry->expiresAt ?? 'Never'],
                ['Status', $entry->isExpired() ? 'expired' : 'active'],
            ]);
        }

        return self::SUCCESS;
    }

    private function prune(Filesystem $files, LaravelGuard $guard, string $path): int
    {
        $document = $this->load($files, $path);
        if ($document === null) {
            return self::FAILURE;
        }
        $current = array_map(fn ($finding) => $finding->fingerprint(), $guard->scan()->all());
        $pruned = $document->pruned($current);
        $removed = count($document->entries) - count($pruned->entries);
        $this->write($files, $path, $pruned);
        $this->info("Pruned {$removed} expired or resolved baseline entr".($removed === 1 ? 'y.' : 'ies.'));

        return self::SUCCESS;
    }

    private function load(Filesystem $files, string $path): ?BaselineDocument
    {
        if (! $files->exists($path)) {
            $this->error("No baseline exists at {$path}.");

            return null;
        }
        try {
            return BaselineDocument::fromJson($files->get($path));
        } catch (\Throwable $error) {
            $this->error("Baseline is invalid: {$error->getMessage()}");

            return null;
        }
    }

    private function write(Filesystem $files, string $path, BaselineDocument $document): void
    {
        $files->put($path, json_encode($document, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL);
    }

    private function owner(): string
    {
        return $this->cleanOption('owner')
            ?? config('laravel-guard.baseline_governance.owner')
            ?? getenv('GITHUB_ACTOR') ?: getenv('GITLAB_USER_LOGIN') ?: getenv('USERNAME') ?: getenv('USER') ?: get_current_user();
    }

    private function expiration(): ?string
    {
        $value = $this->cleanOption('expires');
        $days = config('laravel-guard.baseline_governance.default_ttl_days', 90);
        if ($value === null && is_int($days) && $days > 0) {
            $value = "+{$days} days";
        }
        if ($value === null) {
            return null;
        }
        try {
            $expiration = new DateTimeImmutable($value);
        } catch (\Exception) {
            throw new \InvalidArgumentException("Invalid expiration [{$value}]. Use an ISO date or relative date such as +90 days.");
        }
        if ($expiration <= new DateTimeImmutable) {
            throw new \InvalidArgumentException('Baseline expiration must be in the future.');
        }

        return $expiration->format(DATE_ATOM);
    }

    private function cleanOption(string $name): ?string
    {
        $value = $this->option($name);

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    private function row(BaselineEntry $entry): array
    {
        return [substr($entry->fingerprint, 0, 12), $entry->ruleId, $entry->severity, $entry->owner ?? '-', $entry->expiresAt ?? 'Never', $entry->isExpired() ? 'expired' : 'active', $entry->reason ?? '-'];
    }
}
