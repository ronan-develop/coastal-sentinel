<?php

declare(strict_types=1);

namespace App\Service\Geometry;

final class WktPolygonParser
{
    /**
     * Extrait les anneaux d'un WKT (MULTI)POLYGON sous forme de listes de
     * points [lon, lat]. Ignore les anneaux dégénérés (< 3 points) — un
     * artefact observé dans les données source Sandre/data.gouv.fr, sans
     * signification géométrique (cf. ticket #29).
     *
     * @return list<list<array{0: float, 1: float}>>
     */
    public static function parse(string $wkt): array
    {
        preg_match_all('/\(([^()]+)\)/', $wkt, $ringMatches);

        $rings = [];
        foreach ($ringMatches[1] as $ringText) {
            preg_match_all('/(-?\d+(?:\.\d+)?)\s+(-?\d+(?:\.\d+)?)/', $ringText, $pointMatches, \PREG_SET_ORDER);

            if (\count($pointMatches) < 3) {
                continue;
            }

            $rings[] = array_map(
                static fn (array $point): array => [(float) $point[1], (float) $point[2]],
                $pointMatches,
            );
        }

        return $rings;
    }
}
