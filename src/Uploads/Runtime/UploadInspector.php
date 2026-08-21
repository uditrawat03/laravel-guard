<?php

namespace LaravelGuard\Uploads\Runtime;

use Illuminate\Contracts\Config\Repository;
use LaravelGuard\Core\Exceptions\SecurityExceptionManager;
use LaravelGuard\Runtime\SecurityEventCollector;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final readonly class UploadInspector
{
    public function __construct(
        private SecurityEventCollector $events,
        private SecurityExceptionManager $exceptions,
        private Repository $config,
    ) {}

    public function inspect(UploadedFile $file): UploadInspectionResult
    {
        $path = $file->getPathname();
        $declared = $this->normalizeMime($file->getClientMimeType());
        $detected = $this->detectedMime($path);
        $extension = strtolower($file->getClientOriginalExtension());
        $violations = [];

        if ($declared && $detected && ! $this->compatible($declared, $detected)) {
            $violations[] = 'mime_mismatch';
            $this->record('LG-UPLOAD-008', 'The declared upload MIME type does not match its detected file signature.', $file, $extension, $detected, ['declared_mime' => $declared]);
        }

        if ($this->containsExecutableMarker($path)) {
            $violations[] = 'executable_content';
            $this->record('LG-UPLOAD-009', 'Executable content was detected inside an uploaded file.', $file, $extension, $detected);
        }

        $allowed = array_map('strtolower', $this->config->get('laravel-guard.uploads.allowed_detected_mimes', []));
        if ($allowed !== [] && $detected && ! in_array($detected, $allowed, true)) {
            $violations[] = 'mime_not_allowed';
            $this->record('LG-UPLOAD-010', 'The detected upload MIME type is not allowlisted.', $file, $extension, $detected);
        }

        return new UploadInspectionResult($file->getClientOriginalName(), $declared, $detected, $extension, array_values(array_unique($violations)));
    }

    public function assertSafe(UploadedFile $file): UploadInspectionResult
    {
        $result = $this->inspect($file);
        if (! $result->safe() && ! $this->exceptions->allows('LG-UPLOAD-RUNTIME')) {
            throw new UnsafeUploadException($result);
        }

        return $result;
    }

    private function record(string $rule, string $message, UploadedFile $file, string $extension, ?string $detected, array $extra = []): void
    {
        $this->events->record($rule, $message, [
            'filename_hash' => hash('sha256', $file->getClientOriginalName()),
            'extension' => $extension,
            'detected_mime' => $detected,
            ...$extra,
        ]);
    }

    private function detectedMime(string $path): ?string
    {
        if (! class_exists(\finfo::class) || ! is_file($path)) {
            return null;
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path);

        return is_string($mime) ? $this->normalizeMime($mime) : null;
    }

    private function normalizeMime(?string $mime): ?string
    {
        return $mime ? strtolower(trim(explode(';', $mime, 2)[0])) : null;
    }

    private function compatible(string $declared, string $detected): bool
    {
        $aliases = ['image/jpg' => 'image/jpeg', 'application/x-pdf' => 'application/pdf', 'text/x-c' => 'text/plain'];

        return ($aliases[$declared] ?? $declared) === ($aliases[$detected] ?? $detected);
    }

    private function containsExecutableMarker(string $path): bool
    {
        $stream = @fopen($path, 'rb');
        if (! is_resource($stream)) {
            return false;
        }
        $bytes = (string) fread($stream, 8192);
        fclose($stream);

        return str_contains($bytes, '<?php') || str_contains($bytes, '<?=')
            || str_starts_with($bytes, '#!/bin/') || str_starts_with($bytes, 'MZ');
    }
}
