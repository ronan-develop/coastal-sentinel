<?php

declare(strict_types=1);

namespace App\Tests\Service\Geometry;

use App\Service\Geometry\BoundingBox;
use PHPUnit\Framework\TestCase;

final class BoundingBoxTest extends TestCase
{
    public function testExtractsMinMaxFromSimplePolygon(): void
    {
        $wkt = 'MULTIPOLYGON(((-4.5 48.3, -4.3 48.3, -4.3 48.4, -4.5 48.4, -4.5 48.3)))';

        $bbox = BoundingBox::fromWkt($wkt);

        self::assertSame(-4.5, $bbox->lonMin);
        self::assertSame(-4.3, $bbox->lonMax);
        self::assertSame(48.3, $bbox->latMin);
        self::assertSame(48.4, $bbox->latMax);
    }

    public function testHandlesMultiplePolygonsAndRings(): void
    {
        $wkt = 'MULTIPOLYGON(((-4.6 48.2, -4.2 48.2, -4.2 48.5, -4.6 48.5, -4.6 48.2)), ((-4.55 48.3, -4.5 48.3, -4.5 48.35, -4.55 48.3)))';

        $bbox = BoundingBox::fromWkt($wkt);

        self::assertSame(-4.6, $bbox->lonMin);
        self::assertSame(-4.2, $bbox->lonMax);
        self::assertSame(48.2, $bbox->latMin);
        self::assertSame(48.5, $bbox->latMax);
    }
}
