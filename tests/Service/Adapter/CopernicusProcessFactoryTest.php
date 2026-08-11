<?php

declare(strict_types=1);

namespace App\Tests\Service\Adapter;

use App\Service\Adapter\Copernicus\CopernicusProcessFactory;
use App\Service\Geometry\BoundingBox;
use PHPUnit\Framework\TestCase;

final class CopernicusProcessFactoryTest extends TestCase
{
    public function testBuildsProcessCommandWithBoundingBoxAndSince(): void
    {
        $factory = new CopernicusProcessFactory('/usr/bin/python3', '/opt/ingest-copernicus.py');
        $bbox = BoundingBox::fromWkt('MULTIPOLYGON(((-4.5 48.3, -4.3 48.3, -4.3 48.4, -4.5 48.4, -4.5 48.3)))');
        $since = new \DateTimeImmutable('2026-08-11');

        $command = $factory->buildCommand($bbox, $since);

        self::assertSame(
            [
                '/usr/bin/python3',
                '/opt/ingest-copernicus.py',
                '--lon-min', '-4.5',
                '--lon-max', '-4.3',
                '--lat-min', '48.3',
                '--lat-max', '48.4',
                '--since', '2026-08-11',
            ],
            $command,
        );
    }

    public function testCreateProcessUsesTheBuiltCommand(): void
    {
        $factory = new CopernicusProcessFactory('/usr/bin/python3', '/opt/ingest-copernicus.py');
        $bbox = BoundingBox::fromWkt('MULTIPOLYGON(((-4.5 48.3, -4.3 48.3, -4.3 48.4, -4.5 48.4, -4.5 48.3)))');
        $since = new \DateTimeImmutable('2026-08-11');

        $process = $factory->createProcess($bbox, $since);

        self::assertStringContainsString('ingest-copernicus.py', $process->getCommandLine());
    }
}
