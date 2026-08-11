<?php

declare(strict_types=1);

namespace App\Service\Geometry;

final class BoundingBox
{
    private function __construct(
        public readonly float $lonMin,
        public readonly float $lonMax,
        public readonly float $latMin,
        public readonly float $latMax,
    ) {
    }

    public static function fromWkt(string $wkt): self
    {
        preg_match_all('/(-?\d+(?:\.\d+)?)\s+(-?\d+(?:\.\d+)?)/', $wkt, $matches, PREG_SET_ORDER);

        if ($matches === []) {
            throw new \InvalidArgumentException('Aucune coordonnée trouvée dans le WKT fourni.');
        }

        $longitudes = array_map(static fn (array $pair): float => (float) $pair[1], $matches);
        $latitudes = array_map(static fn (array $pair): float => (float) $pair[2], $matches);

        return new self(
            lonMin: min($longitudes),
            lonMax: max($longitudes),
            latMin: min($latitudes),
            latMax: max($latitudes),
        );
    }
}
