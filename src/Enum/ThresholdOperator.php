<?php

declare(strict_types=1);

namespace App\Enum;

enum ThresholdOperator: string
{
    case GreaterThan = 'gt';
    case GreaterThanOrEqual = 'gte';
    case LessThan = 'lt';
    case LessThanOrEqual = 'lte';

    public function compare(float $value, float $threshold): bool
    {
        return match ($this) {
            self::GreaterThan => $value > $threshold,
            self::GreaterThanOrEqual => $value >= $threshold,
            self::LessThan => $value < $threshold,
            self::LessThanOrEqual => $value <= $threshold,
        };
    }
}
