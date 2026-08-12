<?php

declare(strict_types=1);

namespace App\Tests\Service\Geometry;

use App\Service\Geometry\WktPolygonParser;
use PHPUnit\Framework\TestCase;

final class WktPolygonParserTest extends TestCase
{
    public function testParsesSingleRingPolygon(): void
    {
        $wkt = 'MULTIPOLYGON(((-4.5 48.3, -4.3 48.3, -4.3 48.4, -4.5 48.4, -4.5 48.3)))';

        $rings = WktPolygonParser::parse($wkt);

        self::assertCount(1, $rings);
        self::assertCount(5, $rings[0]);
        self::assertSame([-4.5, 48.3], $rings[0][0]);
    }

    public function testParsesMultipleSubstantialRings(): void
    {
        $wkt = 'MULTIPOLYGON(((-4.5 48.3, -4.3 48.3, -4.3 48.4, -4.5 48.4, -4.5 48.3)), '
            . '((-4.6 48.2, -4.55 48.2, -4.55 48.25, -4.6 48.2)))';

        $rings = WktPolygonParser::parse($wkt);

        self::assertCount(2, $rings);
        self::assertCount(5, $rings[0]);
        self::assertCount(4, $rings[1]);
    }

    public function testIgnoresDegenerateTwoPointRings(): void
    {
        // Artefact observé dans les données source (Sandre/data.gouv.fr) :
        // anneaux à 2 points identiques, sans surface — à ignorer, pas de
        // sens géométrique (cf. ticket #29).
        $wkt = 'MULTIPOLYGON(((-4.5 48.3, -4.3 48.3, -4.3 48.4, -4.5 48.4, -4.5 48.3)), '
            . '((-4.35 48.32, -4.35 48.32)))';

        $rings = WktPolygonParser::parse($wkt);

        self::assertCount(1, $rings);
    }

    public function testReturnsEmptyArrayWhenNoSubstantialRing(): void
    {
        $wkt = 'MULTIPOLYGON(((-4.35 48.32, -4.35 48.32)))';

        $rings = WktPolygonParser::parse($wkt);

        self::assertSame([], $rings);
    }
}
