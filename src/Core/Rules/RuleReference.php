<?php

namespace LaravelGuard\Core\Rules;

use LaravelGuard\Core\Contracts\GuardRule;

final class RuleReference
{
    private const DOCUMENTATION_URL = 'https://github.com/uditrawat03/laravel-guard/blob/main/docs/RULES.md#{rule}';

    private const GUIDANCE = [
        'configuration' => [
            'why' => 'Insecure framework or environment settings can expose application data before business-level authorization is reached.',
            'respond' => 'Correct the deployed configuration, clear cached configuration, and verify the effective values in the target environment.',
            'limits' => 'The scanner sees Laravel configuration available to the running command; infrastructure controls outside Laravel require separate validation.',
        ],
        'routes' => [
            'why' => 'A reachable route without the expected authentication, authorization, throttling, or request-integrity control can expose privileged operations.',
            'respond' => 'Apply explicit route or controller protections and add a feature test that proves unauthorized callers are rejected.',
            'limits' => 'Custom authorization wrappers and interprocedural checks may not be recognized automatically.',
        ],
        'uploads' => [
            'why' => 'Untrusted files can enable code execution, stored cross-site scripting, data leakage, denial of service, or unsafe public distribution.',
            'respond' => 'Use strict validation, generated filenames, private storage, detected MIME checks, size limits, and format-specific processing.',
            'limits' => 'Laravel Guard is not an antivirus or complete file-format parser; high-risk workloads need quarantine and specialist scanning.',
        ],
        'tenant' => [
            'why' => 'A missing tenant boundary can disclose or modify another customer organization\'s data.',
            'respond' => 'Resolve tenant context explicitly, guard tenant-owned models, constrain bulk/raw queries, and test cross-tenant denial.',
            'limits' => 'Queues, caches, filesystems, broadcasts, and cross-database tenancy need application-specific controls beyond the current Eloquent and SQL checks.',
        ],
        'queries' => [
            'why' => 'Unsafe SQL construction or unscoped bulk operations can alter or expose large amounts of data.',
            'respond' => 'Use parameter binding, query-builder expressions, explicit scopes, transactions, and narrow validated inputs.',
            'limits' => 'The current analysis is syntax-oriented and does not yet follow values across method or file boundaries.',
        ],
        'models' => [
            'why' => 'Broad assignment or serialization can let untrusted clients change privileged fields or receive sensitive attributes.',
            'respond' => 'Use validated DTOs, narrow fillable fields, hidden/visible controls, explicit resources, and tests for serialized output.',
            'limits' => 'Complex accessors, casts, resources, and validation provenance are only partially modeled.',
        ],
        'secrets' => [
            'why' => 'Committed credentials can grant durable access to data and infrastructure even after source code is fixed.',
            'respond' => 'Remove the credential, rotate it, move configuration to an approved secret store, and inspect repository history.',
            'limits' => 'The package does not scan complete Git history or validate credentials with remote providers.',
        ],
        'api' => [
            'why' => 'Public or weakly protected APIs can expose data and high-volume operations to automated callers.',
            'respond' => 'Require an explicit authentication guard, authorization, rate limits, and allowlisted resource representations.',
            'limits' => 'Token abilities, OAuth grants, GraphQL, webhooks, and custom API gateways require additional review.',
        ],
    ];

    public static function for(GuardRule $rule): array
    {
        $guidance = self::GUIDANCE[$rule->category()] ?? [
            'why' => 'The matched pattern may weaken an application security boundary.',
            'respond' => 'Review the finding in its application context and add a regression test for the intended control.',
            'limits' => 'Custom rule behavior is defined by the application or extension that registered it.',
        ];

        return [
            'rule_id' => $rule->id(),
            'name' => $rule->name(),
            'description' => $rule->description(),
            'category' => $rule->category(),
            'default_severity' => strtolower($rule->severity()->name),
            'why_it_matters' => $guidance['why'],
            'how_to_respond' => $guidance['respond'],
            'analysis_limits' => $guidance['limits'],
            'documentation' => 'docs/RULES.md#'.strtolower($rule->id()),
            'documentation_url' => self::documentationUrl($rule->id()),
        ];
    }

    public static function documentationUrl(string $ruleId): string
    {
        $template = trim((string) config('laravel-guard.documentation_url', self::DOCUMENTATION_URL));
        $template = $template !== '' ? $template : self::DOCUMENTATION_URL;
        $url = str_replace(['{rule}', '{RULE}'], [strtolower($ruleId), strtoupper($ruleId)], $template);

        if (! in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)) {
            return str_replace('{rule}', strtolower($ruleId), self::DOCUMENTATION_URL);
        }

        return str_contains($url, strtolower($ruleId)) || str_contains($url, strtoupper($ruleId))
            ? $url
            : rtrim($url, '#').'#'.strtolower($ruleId);
    }
}
