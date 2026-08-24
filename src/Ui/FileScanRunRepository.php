<?php

namespace LaravelGuard\Ui;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

final readonly class FileScanRunRepository implements ScanRunRepository
{
    public function __construct(private Filesystem $files, private Repository $config) {}

    public function save(array $run): array
    {
        $directory = $this->directory();
        $this->ensureDirectory($directory);
        $run['id'] = (string) Str::ulid();
        $run['schema'] = 'laravel-guard/ui-scan';
        $run['schema_version'] = 1;
        $path = $directory.DIRECTORY_SEPARATOR.$run['id'].'.json';
        $temporary = $path.'.tmp';
        $payload = json_encode($run, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        if ($this->files->put($temporary, $payload, true) === false || ! @rename($temporary, $path)) {
            $this->files->delete($temporary);
            throw new \RuntimeException('Laravel Guard could not persist the UI scan report.');
        }

        $this->purgeExpired();

        return $run;
    }

    public function latest(): ?array
    {
        return $this->all()[0] ?? null;
    }

    public function all(): array
    {
        $directory = $this->directory();
        if (! $this->files->isDirectory($directory)) {
            return [];
        }

        $paths = $this->files->glob($directory.DIRECTORY_SEPARATOR.'*.json');
        rsort($paths, SORT_STRING);

        return array_values(array_filter(array_map(fn (string $path) => $this->read($path), $paths)));
    }

    public function purgeExpired(): int
    {
        $days = max(1, (int) $this->config->get('laravel-guard.ui.retention_days', 30));
        $threshold = time() - ($days * 86400);
        $removed = 0;
        foreach ($this->files->glob($this->directory().DIRECTORY_SEPARATOR.'*.json') as $path) {
            if ($this->files->lastModified($path) < $threshold && $this->files->delete($path)) {
                $removed++;
            }
        }

        return $removed;
    }

    private function directory(): string
    {
        $path = $this->config->get('laravel-guard.ui.storage_path');
        if (! is_string($path) || trim($path) === '') {
            throw new \InvalidArgumentException('laravel-guard.ui.storage_path must be a non-empty path.');
        }

        return rtrim($path, '/\\');
    }

    private function ensureDirectory(string $directory): void
    {
        if (! $this->files->isDirectory($directory)) {
            $this->files->makeDirectory($directory, 0700, true);
        }
        if (! $this->files->isWritable($directory)) {
            throw new \RuntimeException("Laravel Guard UI storage [{$directory}] is not writable.");
        }
    }

    private function read(string $path): ?array
    {
        try {
            $data = json_decode($this->files->get($path), true, flags: JSON_THROW_ON_ERROR);

            return is_array($data) && ($data['schema'] ?? null) === 'laravel-guard/ui-scan' ? $data : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
