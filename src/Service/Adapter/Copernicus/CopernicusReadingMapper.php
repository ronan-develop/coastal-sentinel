<?php

declare(strict_types=1);

namespace App\Service\Adapter\Copernicus;

use App\DTO\EnvironmentReadingData;
use App\Enum\EnvironmentVariable;

final class CopernicusReadingMapper
{
    /**
     * @param list<array{date: string, water_temperature_celsius: float}> $decoded
     *
     * @return iterable<EnvironmentReadingData>
     */
    public function map(array $decoded, \DateTimeImmutable $since): iterable
    {
        foreach ($decoded as $entry) {
            $measuredAt = new \DateTimeImmutable($entry['date']);

            yield new EnvironmentReadingData(
                EnvironmentVariable::WaterTemperature,
                (float) $entry['water_temperature_celsius'],
                'celsius',
                $measuredAt,
                $since->diff($measuredAt)->days,
                $entry,
            );
        }
    }
}
