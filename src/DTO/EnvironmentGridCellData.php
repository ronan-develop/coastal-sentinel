<?php

declare(strict_types=1);

namespace App\DTO;

use App\Enum\EnvironmentVariable;

final class EnvironmentGridCellData
{
    public function __construct(
        public readonly EnvironmentVariable $variable,
        public readonly float $lat,
        public readonly float $lon,
        public readonly ?float $value,
        public readonly \DateTimeImmutable $measuredAt,
    ) {
    }
}
