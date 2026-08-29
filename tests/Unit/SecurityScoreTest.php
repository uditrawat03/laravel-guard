<?php

namespace LaravelGuard\Tests\Unit;

use LaravelGuard\Core\Findings\Confidence;
use LaravelGuard\Core\Findings\FindingCollection;
use LaravelGuard\Core\Findings\SecurityFinding;
use LaravelGuard\Core\Findings\Severity;
use LaravelGuard\Core\Scoring\SecurityScore;
use LaravelGuard\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class SecurityScoreTest extends TestCase
{
    public function test_empty_findings_receive_a_perfect_score(): void
    {
        $score = SecurityScore::fromFindings(new FindingCollection);

        $this->assertSame(100, $score->score);
        $this->assertSame('A', $score->grade);
        $this->assertSame([], $score->categories);
    }

    public function test_each_severity_uses_its_documented_weight(): void
    {
        $expected = [
            [Severity::Critical, 75, 'C'],
            [Severity::High, 90, 'A'],
            [Severity::Medium, 96, 'A'],
            [Severity::Low, 99, 'A'],
        ];

        foreach ($expected as [$severity, $value, $grade]) {
            $score = SecurityScore::fromFindings($this->findings($severity));

            $this->assertSame($value, $score->score);
            $this->assertSame($grade, $score->grade);
            $this->assertSame(['score' => $value, 'grade' => $grade], $score->categories['security']);
        }
    }

    public function test_deductions_accumulate_and_scores_never_fall_below_zero(): void
    {
        $findings = $this->findings(Severity::Critical, 5);
        $findings->add($this->finding(Severity::High, 'tenant'));

        $score = SecurityScore::fromFindings($findings);

        $this->assertSame(0, $score->score);
        $this->assertSame('F', $score->grade);
        $this->assertSame(['score' => 0, 'grade' => 'F'], $score->categories['security']);
        $this->assertSame(['score' => 90, 'grade' => 'A'], $score->categories['tenant']);
    }

    #[DataProvider('gradeBoundaries')]
    public function test_grade_boundaries(int $deduction, string $grade): void
    {
        $score = SecurityScore::fromFindings($this->findings(Severity::Low, $deduction));

        $this->assertSame(100 - $deduction, $score->score);
        $this->assertSame($grade, $score->grade);
        $this->assertSame($grade, $score->categories['security']['grade']);
    }

    public static function gradeBoundaries(): array
    {
        return [
            'A lower boundary' => [10, 'A'],
            'B upper boundary' => [11, 'B'],
            'B lower boundary' => [20, 'B'],
            'C upper boundary' => [21, 'C'],
            'C lower boundary' => [30, 'C'],
            'D upper boundary' => [31, 'D'],
            'D lower boundary' => [40, 'D'],
            'F upper boundary' => [41, 'F'],
        ];
    }

    private function findings(Severity $severity, int $count = 1): FindingCollection
    {
        $findings = new FindingCollection;
        for ($index = 0; $index < $count; $index++) {
            $findings->add($this->finding($severity));
        }

        return $findings;
    }

    private function finding(Severity $severity, string $category = 'security'): SecurityFinding
    {
        return new SecurityFinding(
            'LG-TEST-001',
            $category,
            $severity,
            Confidence::High,
            'Test finding',
            'Test description.',
            'Test risk.',
            'Test recommendation.',
        );
    }
}
