<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\DataSource;
use App\Entity\EnvironmentReading;
use App\Entity\Zone;
use App\Enum\DataSourceType;
use App\Enum\EnvironmentVariable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV7;

final class EnvironmentReadingTest extends TestCase
{
    public function testIdIsUuidV7AfterConstruction(): void
    {
        $zone = new Zone('rade-de-brest', 'Rade de Brest', 'MULTIPOLYGON(((-4.4 48.3, -4.3 48.3, -4.3 48.4, -4.4 48.3)))');
        $dataSource = new DataSource('copernicus', DataSourceType::Forecast);

        $reading = new EnvironmentReading(
            $zone,
            $dataSource,
            EnvironmentVariable::WaterTemperature,
            21.5,
            'celsius',
            new \DateTimeImmutable('2026-08-15'),
        );

        self::assertInstanceOf(UuidV7::class, $reading->getId());
    }
}
