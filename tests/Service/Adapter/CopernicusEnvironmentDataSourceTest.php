<?php

declare(strict_types=1);

namespace App\Tests\Service\Adapter;

use App\Entity\Zone;
use App\Enum\EnvironmentVariable;
use App\Service\Adapter\Copernicus\CopernicusEnvironmentDataSource;
use App\Service\Adapter\Copernicus\CopernicusProcessFactory;
use App\Service\Adapter\Copernicus\CopernicusReadingMapper;
use PHPUnit\Framework\TestCase;

final class CopernicusEnvironmentDataSourceTest extends TestCase
{
    private const FIXTURES_DIR = __DIR__ . '/../../Fixtures/copernicus';

    public function testGetSourceNameIsCopernicus(): void
    {
        $source = new CopernicusEnvironmentDataSource(
            new CopernicusProcessFactory('python3', self::FIXTURES_DIR . '/fake-success.py'),
            new CopernicusReadingMapper(),
        );

        self::assertSame('copernicus', $source->getSourceName());
    }

    public function testFetchReturnsReadingsFromSuccessfulProcess(): void
    {
        $source = new CopernicusEnvironmentDataSource(
            new CopernicusProcessFactory('python3', self::FIXTURES_DIR . '/fake-success.py'),
            new CopernicusReadingMapper(),
        );
        $zone = new Zone('rade-de-brest-test', 'Rade de Brest (test)', 'MULTIPOLYGON(((-4.5 48.3, -4.3 48.3, -4.3 48.4, -4.5 48.4, -4.5 48.3)))');

        $readings = iterator_to_array($source->fetch($zone, new \DateTimeImmutable('2026-08-11')));

        self::assertCount(2, $readings);
        self::assertSame(EnvironmentVariable::WaterTemperature, $readings[0]->variable);
        self::assertSame(21.63, $readings[0]->value);
        self::assertSame('2026-08-12', $readings[1]->measuredAt->format('Y-m-d'));
    }

    public function testFetchThrowsWhenProcessFails(): void
    {
        $source = new CopernicusEnvironmentDataSource(
            new CopernicusProcessFactory('python3', self::FIXTURES_DIR . '/fake-failure.py'),
            new CopernicusReadingMapper(),
        );
        $zone = new Zone('rade-de-brest-test', 'Rade de Brest (test)', 'MULTIPOLYGON(((-4.5 48.3, -4.3 48.3, -4.3 48.4, -4.5 48.4, -4.5 48.3)))');

        $this->expectException(\RuntimeException::class);

        iterator_to_array($source->fetch($zone, new \DateTimeImmutable('2026-08-11')));
    }
}
