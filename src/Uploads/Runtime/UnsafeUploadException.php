<?php

namespace LaravelGuard\Uploads\Runtime;

final class UnsafeUploadException extends \RuntimeException
{
    public function __construct(public readonly UploadInspectionResult $inspection)
    {
        parent::__construct('Laravel Guard rejected an upload that failed content inspection.');
    }
}
