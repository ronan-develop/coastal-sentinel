<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\RiskAssessmentProvider;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/risque/{zone}',
            provider: RiskAssessmentProvider::class,
        ),
    ],
)]
final class RiskAssessmentOutput
{
    /**
     * @param list<list<array{0: float, 1: float}>> $zonePolygons Anneaux du polygone de la zone, en [lon, lat].
     */
    public function __construct(
        public readonly string $zone,
        public readonly string $riskType,
        public readonly float $score,
        public readonly string $windowStart,
        public readonly string $windowEnd,
        public readonly string $recommendedAction,
        public readonly string $computedAt,
        public readonly array $zonePolygons,
    ) {
    }
}
