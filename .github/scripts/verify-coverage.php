<?php

declare(strict_types=1);

$report = $argv[1] ?? '';
$minimum = isset($argv[2]) && is_numeric($argv[2]) ? (float) $argv[2] : null;

if ($report === '' || $minimum === null || $minimum < 0 || $minimum > 100) {
    fwrite(STDERR, "Usage: php verify-coverage.php <clover.xml> <minimum-percent>\n");

    exit(2);
}

if (! is_file($report)) {
    fwrite(STDERR, "Coverage report not found: {$report}\n");

    exit(2);
}

libxml_use_internal_errors(true);
$xml = simplexml_load_file($report);
if ($xml === false) {
    fwrite(STDERR, "Coverage report is not valid XML: {$report}\n");

    exit(2);
}

$metrics = $xml->project->metrics;
$statements = (int) ($metrics['statements'] ?? 0);
$covered = (int) ($metrics['coveredstatements'] ?? 0);
if ($statements < 1 || $covered < 0 || $covered > $statements) {
    fwrite(STDERR, "Coverage report does not contain valid project statement metrics.\n");

    exit(2);
}

$percentage = ($covered / $statements) * 100;
$summary = sprintf(
    'Line coverage: %.2f%% (%d/%d statements); required: %.2f%%',
    $percentage,
    $covered,
    $statements,
    $minimum,
);

fwrite(STDOUT, $summary.PHP_EOL);
$githubSummary = getenv('GITHUB_STEP_SUMMARY');
if (is_string($githubSummary) && $githubSummary !== '') {
    file_put_contents($githubSummary, "## Laravel Guard coverage\n\n{$summary}\n", FILE_APPEND);
}

if ($percentage + 0.00001 < $minimum) {
    fwrite(STDERR, "Coverage threshold failed.\n");

    exit(1);
}
