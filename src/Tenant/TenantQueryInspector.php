<?php

namespace LaravelGuard\Tenant;

use Illuminate\Database\Events\QueryExecuted;
use LaravelGuard\Runtime\SecurityEventCollector;

final readonly class TenantQueryInspector
{
    public function __construct(
        private TenantContext $tenant,
        private SecurityEventCollector $events,
    ) {}

    public function inspect(QueryExecuted $query, array $tables, string $column): void
    {
        if (! $this->tenant->active()) {
            return;
        }

        $sql = strtolower($query->sql);
        $table = $this->tenantTable($sql, $tables);
        if ($table === null || str_contains($sql, strtolower($column))) {
            return;
        }

        $operation = strtolower((string) strtok(ltrim($sql), " \t\n"));
        $rule = match ($operation) {
            'update' => 'LG-TENANT-004',
            'delete' => 'LG-TENANT-005',
            default => 'LG-TENANT-006',
        };
        $this->events->record($rule, "A {$operation} query on tenant table [{$table}] had no {$column} constraint.", [
            'table' => $table,
            'operation' => $operation,
            'connection' => $query->connectionName,
            'sql_fingerprint' => hash('sha256', preg_replace('/\s+/', ' ', $sql)),
        ]);
    }

    private function tenantTable(string $sql, array $tables): ?string
    {
        foreach ($tables as $table) {
            if (preg_match('/(?:from|update|into|join)\s+[`"\[]?'.preg_quote(strtolower($table), '/').'\b/', $sql)) {
                return $table;
            }
        }

        return null;
    }
}
