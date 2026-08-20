<?php

namespace LaravelGuard\Core\Scoring;

use LaravelGuard\Core\Findings\FindingCollection;
use LaravelGuard\Core\Findings\Severity;

final readonly class SecurityScore
{
    public function __construct(public int $score, public string $grade, public array $categories) {}

    public static function fromFindings(FindingCollection $findings): self
    {
        $weights = [Severity::Critical->value => 25, Severity::High->value => 10, Severity::Medium->value => 4, Severity::Low->value => 1];
        $deductions = 0;
        $categories = [];
        foreach ($findings as $finding) {
            $weight = $weights[$finding->severity->value];
            $deductions += $weight;
            $categories[$finding->category] = ($categories[$finding->category] ?? 100) - $weight;
        }
        foreach ($categories as $category => $score) {
            $categories[$category] = ['score' => max(0, $score), 'grade' => self::grade(max(0, $score))];
        }
        $score = max(0, 100 - min(100, $deductions));

        return new self($score, self::grade($score), $categories);
    }

    private static function grade(int $score): string
    {
        return match (true) {
            $score >= 90 => 'A', $score >= 80 => 'B', $score >= 70 => 'C', $score >= 60 => 'D', default => 'F',
        };
    }
}
