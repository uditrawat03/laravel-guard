<?php

namespace LaravelGuard\Tests\Unit;

use LaravelGuard\Core\Findings\Confidence;
use LaravelGuard\Core\Findings\SecurityFinding;
use LaravelGuard\Core\Findings\Severity;
use LaravelGuard\Core\Support\SourceLocation;
use LaravelGuard\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class SecurityFindingTest extends TestCase
{
    public function test_json_serialization_preserves_the_complete_finding_contract(): void
    {
        $finding = $this->finding(
            location: new SourceLocation('C:/project/app/Service.php', 42),
            metadata: ['symbol' => 'Service::run', 'code' => 'unsafe()'],
        );

        $serialized = $finding->jsonSerialize();

        $this->assertSame($finding->fingerprint(), $serialized['fingerprint']);
        $this->assertSame('LG-TEST-001', $serialized['rule_id']);
        $this->assertSame('https://github.com/uditrawat03/laravel-guard/blob/main/docs/RULES.md#lg-test-001', $serialized['documentation_url']);
        $this->assertSame('security', $serialized['category']);
        $this->assertSame('high', $serialized['severity']);
        $this->assertSame('high', $serialized['confidence']);
        $this->assertSame('Test finding', $serialized['title']);
        $this->assertSame('Unsafe operation.', $serialized['description']);
        $this->assertSame('Test risk.', $serialized['risk']);
        $this->assertSame('Test recommendation.', $serialized['recommendation']);
        $this->assertSame('C:/project/app/Service.php', $serialized['file']);
        $this->assertSame(42, $serialized['line']);
        $this->assertSame(['symbol' => 'Service::run', 'code' => 'unsafe()'], $serialized['metadata']);
    }

    #[DataProvider('stableSourcePaths')]
    public function test_fingerprints_are_stable_across_project_roots_and_line_changes(string $first, string $second): void
    {
        $left = $this->finding(location: new SourceLocation($first, 10));
        $right = $this->finding(location: new SourceLocation($second, 999));

        $this->assertSame($left->fingerprint(), $right->fingerprint());
    }

    public static function stableSourcePaths(): array
    {
        return [
            'application' => ['/first/app/Service.php', 'C:\\second\\app\\Service.php'],
            'routes' => ['/first/routes/api.php', 'C:\\second\\routes\\api.php'],
            'configuration' => ['/first/config/app.php', 'C:\\second\\config\\app.php'],
            'database' => ['/first/database/migrations/test.php', 'C:\\second\\database\\migrations\\test.php'],
            'resources' => ['/first/resources/views/test.blade.php', 'C:\\second\\resources\\views\\test.blade.php'],
            'external file' => ['/first/package/Service.php', 'C:\\second\\package\\Service.php'],
        ];
    }

    public function test_code_identity_is_normalized_and_takes_precedence_over_description(): void
    {
        $left = $this->finding(description: 'First description.', metadata: ['code' => "  User::query()\n    ->delete();  "]);
        $right = $this->finding(description: 'Different description.', metadata: ['code' => 'User::query() ->delete();']);

        $this->assertSame($left->fingerprint(), $right->fingerprint());
    }

    public function test_description_identity_is_normalized_when_code_is_absent(): void
    {
        $left = $this->finding(description: "  Unsafe\n operation.  ");
        $right = $this->finding(description: 'Unsafe operation.');

        $this->assertSame($left->fingerprint(), $right->fingerprint());
    }

    public function test_security_identity_changes_when_a_stable_component_changes(): void
    {
        $original = $this->finding(location: new SourceLocation('/project/app/Service.php', 10), metadata: ['symbol' => 'Service::run', 'code' => 'unsafe()']);
        $fingerprint = $original->fingerprint();

        $this->assertNotSame($fingerprint, $this->finding(ruleId: 'LG-TEST-002', location: $original->location, metadata: $original->metadata)->fingerprint());
        $this->assertNotSame($fingerprint, $this->finding(location: new SourceLocation('/project/app/Other.php', 10), metadata: $original->metadata)->fingerprint());
        $this->assertNotSame($fingerprint, $this->finding(location: $original->location, metadata: ['symbol' => 'Service::other', 'code' => 'unsafe()'])->fingerprint());
        $this->assertNotSame($fingerprint, $this->finding(location: $original->location, metadata: ['symbol' => 'Service::run', 'code' => 'safe()'])->fingerprint());
    }

    private function finding(
        string $ruleId = 'LG-TEST-001',
        string $description = 'Unsafe operation.',
        SourceLocation $location = new SourceLocation,
        array $metadata = [],
    ): SecurityFinding {
        return new SecurityFinding(
            $ruleId,
            'security',
            Severity::High,
            Confidence::High,
            'Test finding',
            $description,
            'Test risk.',
            'Test recommendation.',
            $location,
            $metadata,
        );
    }
}
