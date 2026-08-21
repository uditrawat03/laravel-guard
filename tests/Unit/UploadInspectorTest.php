<?php

namespace LaravelGuard\Tests\Unit;

use LaravelGuard\Core\Exceptions\SecurityExceptionManager;
use LaravelGuard\Runtime\SecurityEventCollector;
use LaravelGuard\Tests\TestCase;
use LaravelGuard\Uploads\Runtime\UnsafeUploadException;
use LaravelGuard\Uploads\Runtime\UploadInspector;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class UploadInspectorTest extends TestCase
{
    private array $temporaryFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $path) {
            @unlink($path);
        }
        parent::tearDown();
    }

    public function test_detects_mime_mismatch_and_embedded_executable_content(): void
    {
        $path = $this->temporary('<?php echo "unsafe";');
        $events = new SecurityEventCollector;
        $inspector = new UploadInspector($events, new SecurityExceptionManager, $this->app['config']);
        $result = $inspector->inspect(new UploadedFile($path, 'avatar.png', 'image/png', null, true));

        $this->assertFalse($result->safe());
        $this->assertContains('mime_mismatch', $result->violations);
        $this->assertContains('executable_content', $result->violations);
        $this->assertCount(2, $events->all());
        $this->assertStringNotContainsString('<?php', json_encode($events->all(), JSON_THROW_ON_ERROR));
    }

    public function test_assert_safe_rejects_an_unsafe_upload(): void
    {
        $path = $this->temporary('<?php echo "unsafe";');
        $inspector = new UploadInspector(new SecurityEventCollector, new SecurityExceptionManager, $this->app['config']);

        $this->expectException(UnsafeUploadException::class);
        $inspector->assertSafe(new UploadedFile($path, 'report.pdf', 'application/pdf', null, true));
    }

    public function test_accepts_a_file_with_matching_signature_and_declared_mime(): void
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true);
        $path = $this->temporary($png ?: '');
        $inspector = new UploadInspector(new SecurityEventCollector, new SecurityExceptionManager, $this->app['config']);
        $result = $inspector->inspect(new UploadedFile($path, 'pixel.png', 'image/png', null, true));

        $this->assertTrue($result->safe());
        $this->assertSame('image/png', $result->detectedMime);
    }

    private function temporary(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'guard-upload-');
        file_put_contents($path, $contents);
        $this->temporaryFiles[] = $path;

        return $path;
    }
}
