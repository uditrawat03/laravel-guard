<?php

namespace LaravelGuard\Ui;

interface ScanRunRepository
{
    public function save(array $run): array;

    public function latest(): ?array;

    /** @return list<array<string, mixed>> */
    public function all(): array;

    public function purgeExpired(): int;
}
