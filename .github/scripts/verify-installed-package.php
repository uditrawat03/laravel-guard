<?php

declare(strict_types=1);

if ($argc !== 3) {
    fwrite(STDERR, "Usage: verify-installed-package.php <installed.json> <expected-version>\n");
    exit(2);
}

$document = json_decode((string) file_get_contents($argv[1]), true, flags: JSON_THROW_ON_ERROR);
$packages = $document['packages'] ?? $document;
$package = null;

foreach ($packages as $candidate) {
    if (($candidate['name'] ?? null) === 'laravel-guard/laravel-guard') {
        $package = $candidate;
        break;
    }
}

if ($package === null) {
    fwrite(STDERR, "laravel-guard/laravel-guard is not installed.\n");
    exit(1);
}

$actual = ltrim((string) ($package['version'] ?? ''), 'v');
$expected = ltrim($argv[2], 'v');

if ($actual !== $expected) {
    fwrite(STDERR, "Expected Laravel Guard {$expected}; installed {$actual}.\n");
    exit(1);
}

if (($package['installation-source'] ?? null) !== 'dist') {
    fwrite(STDERR, "Laravel Guard was not installed from a distribution archive.\n");
    exit(1);
}

fwrite(STDOUT, "Verified Laravel Guard {$actual} from a distribution archive.\n");
