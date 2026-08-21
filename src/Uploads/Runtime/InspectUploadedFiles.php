<?php

namespace LaravelGuard\Uploads\Runtime;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

final readonly class InspectUploadedFiles
{
    public function __construct(private UploadInspector $inspector) {}

    public function handle(Request $request, Closure $next): Response
    {
        foreach ($this->flatten($request->allFiles()) as $file) {
            $this->inspector->assertSafe($file);
        }

        return $next($request);
    }

    /** @return iterable<UploadedFile> */
    private function flatten(array $files): iterable
    {
        foreach ($files as $file) {
            if (is_array($file)) {
                yield from $this->flatten($file);
            } elseif ($file instanceof UploadedFile) {
                yield $file;
            }
        }
    }
}
