<?php

namespace LaravelGuard\Tests\Feature;

use DateTimeImmutable;
use Illuminate\Support\Facades\Artisan;
use LaravelGuard\Core\Baseline\BaselineDocument;
use LaravelGuard\Core\Diff\GitDiff;
use LaravelGuard\Core\Diff\SecurityDiff;
use LaravelGuard\Core\Findings\Confidence;
use LaravelGuard\Core\Findings\FindingCollection;
use LaravelGuard\Core\Findings\SecurityFinding;
use LaravelGuard\Core\Findings\Severity;
use LaravelGuard\Core\Reporting\JsonReporter;
use LaravelGuard\Tests\TestCase;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Validator;

final class OutputSchemaConformanceTest extends TestCase
{
    public function test_every_packaged_schema_accepts_a_package_document(): void
    {
        $findings = $this->findings();
        $documents = [
            'report-v1.json' => json_decode((new JsonReporter)->render($findings), false, flags: JSON_THROW_ON_ERROR),
            'diff-v1.json' => $this->asJsonValue(SecurityDiff::compare($findings, null, new GitDiff("+++ b/app/Test.php\n@@ -0,0 +1 @@\n+unsafe\n"))),
            'baseline-v3.json' => $this->asJsonValue(BaselineDocument::fromFindings(
                $findings,
                'security-team',
                'Reviewed test risk',
                (new DateTimeImmutable('+30 days'))->format(DATE_ATOM),
            )),
            'performance-v1.json' => $this->artisanJson('guard:benchmark', [
                '--runs' => 1,
                '--module' => 'uploads',
                '--path' => [__DIR__.'/../Fixtures/UnsafeQueries.php'],
                '--format' => 'json',
            ]),
            'runtime-performance-v1.json' => $this->asJsonValue([
                'schema' => 'laravel-guard/runtime-performance',
                'schema_version' => 1,
                'scenario' => 'query',
                'runs' => 1,
                'operations_per_run' => 1,
                'average_us' => 1.0,
                'p95_us' => 1.0,
                'peak_memory_mb' => 1.0,
                'status' => 'pass',
                'violations' => [],
            ]),
            'runtime-performance-v2.json' => $this->artisanJson('guard:benchmark-runtime', [
                'scenario' => 'query',
                '--runs' => 1,
                '--operations' => 1,
                '--format' => 'json',
            ]),
        ];

        foreach ($documents as $schemaFile => $document) {
            $this->assertConformsTo($schemaFile, $document);
        }
    }

    private function assertConformsTo(string $schemaFile, mixed $document): void
    {
        $schema = json_decode(
            (string) file_get_contents(dirname(__DIR__, 2).'/resources/schemas/'.$schemaFile),
            false,
            flags: JSON_THROW_ON_ERROR,
        );
        $result = (new Validator)->validate($document, $schema);
        $errors = $result->error() === null
            ? []
            : (new ErrorFormatter)->format($result->error());

        $this->assertTrue(
            $result->isValid(),
            $schemaFile.' rejected its package document: '.json_encode($errors, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
        );
    }

    private function artisanJson(string $command, array $parameters): mixed
    {
        $exitCode = Artisan::call($command, $parameters);
        $output = trim(Artisan::output());

        $this->assertSame(0, $exitCode, $command.' failed while generating a schema fixture.');

        return json_decode($output, false, flags: JSON_THROW_ON_ERROR);
    }

    private function asJsonValue(mixed $value): mixed
    {
        return json_decode(
            json_encode($value, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            false,
            flags: JSON_THROW_ON_ERROR,
        );
    }

    private function findings(): FindingCollection
    {
        return (new FindingCollection)->add(new SecurityFinding(
            'LG-TEST-001',
            'test',
            Severity::High,
            Confidence::High,
            'Test finding',
            'Unsafe operation.',
            'Security risk.',
            'Use a safe operation.',
        ));
    }
}
