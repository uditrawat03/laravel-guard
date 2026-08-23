<?php

namespace LaravelGuard\Core\Diagnostics;

enum DiagnosticStatus: string
{
    case Pass = 'pass';
    case Warning = 'warning';
    case Error = 'error';
}
