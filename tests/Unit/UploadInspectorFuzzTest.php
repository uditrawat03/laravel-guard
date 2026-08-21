<?php

namespace LaravelGuard\Tests\Unit;

use LaravelGuard\Core\Exceptions\SecurityExceptionManager;
use LaravelGuard\Runtime\SecurityEventCollector;
use LaravelGuard\Tests\TestCase;
use LaravelGuard\Uploads\Runtime\UploadInspector;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class UploadInspectorFuzzTest extends TestCase
{
    #[DataProvider('executableContent')]
    public function test_rejects_executable_signatures_regardless_of_filename(string $name, string $contents): void
    {
        $path = tempnam(sys_get_temp_dir(), 'guard-fuzz-');
        file_put_contents($path, $contents);

        try {
            $inspector = new UploadInspector(new SecurityEventCollector, new SecurityExceptionManager, $this->app['config']);
            $result = $inspector->inspect(new UploadedFile($path, $name, 'application/octet-stream', null, true));

            $this->assertContains('executable_content', $result->violations);
        } finally {
            @unlink($path);
        }
    }

    public static function executableContent(): array
    {
        return [
            'php image' => ['profile.png', "<?php echo 'x';"],
            'short echo tag' => ['report.txt', '<?= getenv("APP_KEY") ?>'],
            'unix executable' => ['notes.txt', "#!/bin/sh\necho unsafe"],
            'windows executable' => ['invoice.pdf', "MZ\x90\x00payload"],
        ];
    }
}
