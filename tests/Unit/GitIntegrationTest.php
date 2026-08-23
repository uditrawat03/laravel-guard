<?php

namespace LaravelGuard\Tests\Unit;

use LaravelGuard\Core\Diff\GitBaseline;
use LaravelGuard\Core\Diff\GitDiff;
use LaravelGuard\Core\Findings\Confidence;
use LaravelGuard\Core\Findings\FindingCollection;
use LaravelGuard\Core\Findings\SecurityFinding;
use LaravelGuard\Core\Findings\Severity;
use LaravelGuard\Core\Support\SourceLocation;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

final class GitIntegrationTest extends TestCase
{
    private string $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = sys_get_temp_dir().DIRECTORY_SEPARATOR.'laravel-guard-git-'.bin2hex(random_bytes(5));
        mkdir($this->repository.DIRECTORY_SEPARATOR.'app', recursive: true);

        $this->git(['init']);
        $this->git(['config', 'user.email', 'guard-tests@example.test']);
        $this->git(['config', 'user.name', 'Laravel Guard Tests']);

        file_put_contents($this->baselinePath(), json_encode([
            'fingerprints' => ['historical-fingerprint'],
            'findings' => [['fingerprint' => 'historical-fingerprint', 'rule_id' => 'LG-TEST-001']],
        ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        file_put_contents($this->sourcePath(), "<?php\nreturn 'safe';\n");

        $this->git(['add', '.']);
        $this->git(['commit', '-m', 'Create security baseline']);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->repository);

        parent::tearDown();
    }

    public function test_loads_a_baseline_from_a_real_git_reference(): void
    {
        file_put_contents($this->baselinePath(), '{}');

        $snapshot = GitBaseline::fromRef('HEAD', $this->repository, $this->baselinePath());

        $this->assertNotNull($snapshot);
        $this->assertSame(['historical-fingerprint'], $snapshot->fingerprints);
        $this->assertSame('LG-TEST-001', $snapshot->findings[0]['rule_id']);
    }

    public function test_matches_findings_on_lines_changed_from_a_real_git_reference(): void
    {
        file_put_contents($this->sourcePath(), "<?php\nreturn 'unsafe';\n");
        $finding = new SecurityFinding(
            'LG-TEST-002',
            'test',
            Severity::High,
            Confidence::High,
            'Changed risk',
            'A changed line contains a risk.',
            'Test risk',
            'Fix the test risk.',
            new SourceLocation($this->sourcePath(), 2),
        );

        $changed = GitDiff::fromRef('HEAD', $this->repository)
            ->newFindings((new FindingCollection)->add($finding));

        $this->assertCount(1, $changed);
        $this->assertSame('LG-TEST-002', $changed->all()[0]->ruleId);
    }

    private function git(array $arguments): void
    {
        $command = ['git', '-C', $this->repository, ...$arguments];
        $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);

        if (! is_resource($process)) {
            throw new RuntimeException('Unable to start Git for the integration test.');
        }

        $output = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        if (proc_close($process) !== 0) {
            throw new RuntimeException(trim($error) ?: trim($output));
        }
    }

    private function baselinePath(): string
    {
        return $this->repository.DIRECTORY_SEPARATOR.'.laravel-guard-baseline.json';
    }

    private function sourcePath(): string
    {
        return $this->repository.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Service.php';
    }

    private function removeDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($items as $item) {
            $path = $item->getPathname();
            if ($item->isDir()) {
                @rmdir($path);
            } else {
                @chmod($path, 0666);
                @unlink($path);
            }
        }
        @rmdir($directory);
    }
}
