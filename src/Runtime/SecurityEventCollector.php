<?php

namespace LaravelGuard\Runtime;

final class SecurityEventCollector
{
    /** @var list<SecurityEvent> */
    private array $events = [];

    public function record(string $ruleId, string $message, array $metadata = []): void
    {
        $location = $this->location();
        $this->events[] = new SecurityEvent($ruleId, $message, $metadata, $location['file'] ?? null, $location['line'] ?? null, date(DATE_ATOM));
    }

    /** @return list<SecurityEvent> */
    public function all(): array
    {
        return $this->events;
    }

    /** @return list<SecurityEvent> */
    public function forRule(string $ruleId): array
    {
        return array_values(array_filter($this->events, fn (SecurityEvent $event) => $event->ruleId === $ruleId));
    }

    public function clear(): void
    {
        $this->events = [];
    }

    private function location(): array
    {
        foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS) as $frame) {
            $file = str_replace('\\', '/', $frame['file'] ?? '');
            if ($file !== '' && ! str_contains($file, '/vendor/') && ! str_contains($file, '/src/Runtime/')) {
                return ['file' => $file, 'line' => $frame['line'] ?? null];
            }
        }

        return [];
    }
}
