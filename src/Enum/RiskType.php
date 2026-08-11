<?php

declare(strict_types=1);

namespace App\Enum;

enum RiskType: string
{
    case Thermal = 'thermal';
    case Hypoxia = 'hypoxia';
    case Bacterial = 'bacterial';
}
