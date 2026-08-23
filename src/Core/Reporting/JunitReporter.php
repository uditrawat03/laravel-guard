<?php

namespace LaravelGuard\Core\Reporting;

use LaravelGuard\Core\Findings\FindingCollection;
use LaravelGuard\Core\Support\OutputSchema;

final class JunitReporter
{
    public function render(FindingCollection $findings): string
    {
        $escape = fn (string $value) => htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $cases = '';
        foreach ($findings as $finding) {
            $message = $escape($finding->description.' '.$finding->recommendation);
            $cases .= '<testcase classname="LaravelGuard.'.strtolower($escape($finding->category)).'" name="'.$escape($finding->ruleId).'"><failure type="'.$escape($finding->severity->label()).'" message="'.$message.'">'.$message.'</failure></testcase>';
        }
        $properties = '<properties><property name="laravel-guard.schema" value="'.OutputSchema::JUNIT.'"/><property name="laravel-guard.schema-version" value="'.OutputSchema::JUNIT_VERSION.'"/></properties>';

        return '<?xml version="1.0" encoding="UTF-8"?><testsuites><testsuite name="Laravel Guard" tests="'.$findings->count().'" failures="'.$findings->count().'">'.$properties.$cases.'</testsuite></testsuites>';
    }
}
