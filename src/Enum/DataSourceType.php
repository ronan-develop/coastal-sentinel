<?php

declare(strict_types=1);

namespace App\Enum;

enum DataSourceType: string
{
    case Forecast = 'forecast';
    case Observation = 'observation';
}
