<?php

namespace LaravelGuard\Core\Support;

use Illuminate\Filesystem\Filesystem;

final readonly class PhpFileScanner
{
    public function __construct(private Filesystem $files) {}

    public function files(array $paths, array $excluded = []): iterable
    {
        $excluded = array_map(fn (string $path) => $this->normalize($path), $excluded);
        foreach ($paths as $path) {
            if (! $this->files->exists($path)) {
                continue;
            }
            if (! $this->files->isDirectory($path)) {
                $real = $this->normalize((string) realpath($path));
                if (pathinfo($real, PATHINFO_EXTENSION) === 'php' && ! $this->excluded($real, $excluded)) {
                    yield $real => $this->files->get($real);
                }

                continue;
            }
            foreach ($this->files->allFiles($path) as $file) {
                $real = $this->normalize($file->getRealPath());
                if ($file->getExtension() !== 'php' || $this->excluded($real, $excluded)) {
                    continue;
                }
                yield $real => $this->files->get($real);
            }
        }
    }

    public function lineOf(string $source, int $offset): int
    {
        return substr_count(substr($source, 0, $offset), "\n") + 1;
    }

    private function normalize(string $path): string
    {
        return str_replace('\\', '/', $path);
    }

    private function excluded(string $file, array $paths): bool
    {
        foreach ($paths as $path) {
            if (str_starts_with($file, rtrim($path, '/').'/')) {
                return true;
            }
        }

        return false;
    }
}
