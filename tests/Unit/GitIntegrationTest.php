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

    public function test_matches_findings_in_untracked_files_including_spaces(): void
    {
        $path = $this->repository.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Untracked Service.php';
        file_put_contents($path, "<?php\n\nreturn 'unsafe';\n");
        $finding = $this->finding($path, 3, 'LG-TEST-003');

        $changed = GitDiff::fromRef('HEAD', $this->repository)
            ->newFindings((new FindingCollection)->add($finding));

        $this->assertCount(1, $changed);
    }

    public function test_matches_modified_lines_after_a_file_rename(): void
    {
        $renamed = $this->repository.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'RenamedService.php';
        $this->git(['mv', 'app/Service.php', 'app/RenamedService.php']);
        file_put_contents($renamed, "<?php\nreturn 'unsafe';\n");

        $changed = GitDiff::fromRef('HEAD', $this->repository)
            ->newFindings((new FindingCollection)->add($this->finding($renamed, 2, 'LG-TEST-004')));

        $this->assertCount(1, $changed);
    }

    public function test_deleted_files_do_not_create_new_line_findings(): void
    {
        unlink($this->sourcePath());

        $changed = GitDiff::fromRef('HEAD', $this->repository)
            ->newFindings((new FindingCollection)->add($this->finding($this->sourcePath(), 2, 'LG-TEST-005')));

        $this->assertCount(0, $changed);
    }

    public function test_baseline_is_loaded_from_the_merge_base_not_the_comparison_tip(): void
    {
        $this->git(['branch', 'base-tip']);
        $this->git(['checkout', '-b', 'feature']);
        $this->git(['checkout', 'base-tip']);
        file_put_contents($this->baselinePath(), json_encode([
            'fingerprints' => ['tip-only'],
            'findings' => [['fingerprint' => 'tip-only', 'rule_id' => 'LG-TEST-999']],
        ], JSON_THROW_ON_ERROR));
        $this->git(['add', '.laravel-guard-baseline.json']);
        $this->git(['commit', '-m', 'Change baseline on comparison branch']);
        $this->git(['checkout', 'feature']);

        $snapshot = GitBaseline::fromRef('base-tip', $this->repository, $this->baselinePath());

        $this->assertNotNull($snapshot);
        $this->assertSame(['historical-fingerprint'], $snapshot->fingerprints);
    }

    private function finding(string $path, int $line, string $ruleId): SecurityFinding
    {
        return new SecurityFinding(
            $ruleId,
            'test',
            Severity::High,
            Confidence::High,
            'Changed risk',
            'A changed line contains a risk.',
            'Test risk',
            'Fix the test risk.',
            new SourceLocation($path, $line),
        );
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
