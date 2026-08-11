<?php

declare(strict_types=1);

namespace App\Enum;

enum ThresholdOperator: string
{
    case GreaterThan = 'gt';
    case GreaterThanOrEqual = 'gte';
    case LessThan = 'lt';
    case LessThanOrEqual = 'lte';
}
