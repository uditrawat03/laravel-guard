<?php

namespace LaravelGuard\Core\Diff;

final readonly class BaselineSnapshot
{
    public function __construct(public array $fingerprints, public array $findings) {}

    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true, flags: JSON_THROW_ON_ERROR);

        return new self(
            array_values(array_filter($data['fingerprints'] ?? [], 'is_string')),
            array_values(array_filter($data['findings'] ?? [], 'is_array')),
        );
    }
}
