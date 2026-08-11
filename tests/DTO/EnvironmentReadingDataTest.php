<?php

declare(strict_types=1);

namespace App\Tests\DTO;

use App\DTO\EnvironmentReadingData;
use App\Enum\EnvironmentVariable;
use PHPUnit\Framework\TestCase;

final class EnvironmentReadingDataTest extends TestCase
{
    public function testHoldsIngestionValues(): void
    {
        $measuredAt = new \DateTimeImmutable('2026-08-14');

        $data = new EnvironmentReadingData(
            EnvironmentVariable::WaterTemperature,
            21.63,
            'celsius',
            $measuredAt,
            3,
            ['date' => '2026-08-14', 'water_temperature_celsius' => 21.63],
        );

        self::assertSame(EnvironmentVariable::WaterTemperature, $data->variable);
        self::assertSame(21.63, $data->value);
        self::assertSame('celsius', $data->unit);
        self::assertSame($measuredAt, $data->measuredAt);
        self::assertSame(3, $data->horizon);
        self::assertSame(['date' => '2026-08-14', 'water_temperature_celsius' => 21.63], $data->rawPayload);
    }
}
