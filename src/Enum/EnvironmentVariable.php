<?php

declare(strict_types=1);

namespace App\Enum;

enum EnvironmentVariable: string
{
    case WaterTemperature = 'water_temperature';
    case Salinity = 'salinity';
    case DissolvedOxygen = 'dissolved_oxygen';
    case Precipitation = 'precipitation';
}
