<?php

declare(strict_types=1);

namespace App\Service\Adapter\Copernicus;

use App\DTO\EnvironmentGridCellData;
use App\Enum\EnvironmentVariable;

final class CopernicusGridCellMapper
{
    /**
     * @param list<array{lat: float, lon: float, value: float|null}> $decoded
     *
     * @return iterable<EnvironmentGridCellData>
     */
    public function map(array $decoded, \DateTimeImmutable $measuredAt): iterable
    {
        foreach ($decoded as $entry) {
            yield new EnvironmentGridCellData(
                EnvironmentVariable::WaterTemperature,
                (float) $entry['lat'],
                (float) $entry['lon'],
                $entry['value'] !== null ? (float) $entry['value'] : null,
                $measuredAt,
            );
        }
    }
}
