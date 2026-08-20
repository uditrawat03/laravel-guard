<?php

namespace LaravelGuard\Core\Findings;

enum Severity: int
{
    case Low = 1;
    case Medium = 2;
    case High = 3;
    case Critical = 4;

    public static function fromName(string $value): self
    {
        return match (strtolower($value)) {
            'low' => self::Low,
            'medium' => self::Medium,
            'high' => self::High,
            'critical' => self::Critical,
            default => throw new \InvalidArgumentException("Unknown severity [{$value}]."),
        };
    }

    public function label(): string
    {
        return strtoupper($this->name);
    }
}
