<?php

namespace LaravelGuard\Commands;

use Illuminate\Console\Command;
use LaravelGuard\Core\Rules\RuleReference;
use LaravelGuard\LaravelGuard;

final class ExplainRuleCommand extends Command
{
    protected $signature = 'guard:explain {rule : Rule identifier, for example LG-TENANT-002} {--format=console : console or json}';

    protected $description = 'Explain a Laravel Guard rule, its impact, response, and analysis limits';

    public function handle(LaravelGuard $guard): int
    {
        $requested = strtoupper((string) $this->argument('rule'));
        $rule = collect($guard->rules())->first(fn ($rule) => strtoupper($rule->id()) === $requested);
        if ($rule === null) {
            $matches = collect($guard->rules())
                ->filter(fn ($rule) => str_contains(strtoupper($rule->id().' '.$rule->name()), $requested))
                ->take(5)
                ->map(fn ($rule) => $rule->id())
                ->implode(', ');
            $message = "Unknown Laravel Guard rule [{$requested}].".($matches !== '' ? " Possible matches: {$matches}." : ' Run guard:rules to list available rules.');
            $this->error($message);

            return self::FAILURE;
        }

        $reference = RuleReference::for($rule);
        if (strtolower((string) $this->option('format')) === 'json') {
            $this->line(json_encode($reference, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

            return self::SUCCESS;
        }

        $this->info($reference['rule_id'].' '.$reference['name']);
        $this->table(['Property', 'Value'], [
            ['Module', $reference['category']],
            ['Default severity', strtoupper($reference['default_severity'])],
            ['What it detects', $reference['description']],
            ['Why it matters', $reference['why_it_matters']],
            ['How to respond', $reference['how_to_respond']],
            ['Analysis limits', $reference['analysis_limits']],
            ['Documentation', $reference['documentation']],
        ]);

        return self::SUCCESS;
    }
}
