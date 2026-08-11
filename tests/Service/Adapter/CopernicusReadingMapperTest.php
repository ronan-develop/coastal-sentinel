<?php

declare(strict_types=1);

namespace App\Tests\Service\Adapter;

use App\Enum\EnvironmentVariable;
use App\Service\Adapter\Copernicus\CopernicusReadingMapper;
use PHPUnit\Framework\TestCase;

final class CopernicusReadingMapperTest extends TestCase
{
    public function testMapsDecodedPayloadToReadingData(): void
    {
        $mapper = new CopernicusReadingMapper();
        $since = new \DateTimeImmutable('2026-08-11');
        $decoded = [
            ['date' => '2026-08-11', 'water_temperature_celsius' => 21.63],
            ['date' => '2026-08-14', 'water_temperature_celsius' => 21.13],
        ];

        $readings = iterator_to_array($mapper->map($decoded, $since));

        self::assertCount(2, $readings);

        self::assertSame(EnvironmentVariable::WaterTemperature, $readings[0]->variable);
        self::assertSame(21.63, $readings[0]->value);
        self::assertSame('celsius', $readings[0]->unit);
        self::assertSame('2026-08-11', $readings[0]->measuredAt->format('Y-m-d'));
        self::assertSame(0, $readings[0]->horizon);
        self::assertSame($decoded[0], $readings[0]->rawPayload);

        self::assertSame('2026-08-14', $readings[1]->measuredAt->format('Y-m-d'));
        self::assertSame(3, $readings[1]->horizon);
    }

    public function testMapsEmptyPayloadToNoReadings(): void
    {
        $mapper = new CopernicusReadingMapper();

        $readings = iterator_to_array($mapper->map([], new \DateTimeImmutable('2026-08-11')));

        self::assertSame([], $readings);
    }
}
