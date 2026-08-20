<?php

namespace LaravelGuard\Core\Findings;

enum Confidence: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';

    public function label(): string
    {
        return strtoupper($this->value);
    }
}
