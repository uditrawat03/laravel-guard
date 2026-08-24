<?php

namespace LaravelGuard\Tests\Unit;

use LaravelGuard\Core\Contracts\SecurityContext;
use LaravelGuard\Core\Source\SourceIndex;
use LaravelGuard\Tests\TestCase;

final class SourceIndexTest extends TestCase
{
    public function test_files_are_enumerated_once_per_scan_context(): void
    {
        $directory = sys_get_temp_dir().'/laravel-guard-source-index-'.bin2hex(random_bytes(6));
        mkdir($directory);

        try {
            file_put_contents($directory.'/First.php', '<?php final class First {}');
            $index = $this->app->make(SourceIndex::class);
            $context = new SecurityContext($this->app, ['paths' => [$directory], 'exclude_paths' => []]);

            $this->assertCount(1, iterator_to_array($index->files($context)));
            file_put_contents($directory.'/Second.php', '<?php final class Second {}');
            $this->assertCount(1, iterator_to_array($index->files($context)));

            $nextScan = new SecurityContext($this->app, ['paths' => [$directory], 'exclude_paths' => []]);
            $this->assertCount(2, iterator_to_array($index->files($nextScan)));
        } finally {
            @unlink($directory.'/First.php');
            @unlink($directory.'/Second.php');
            @rmdir($directory);
        }
    }
}
