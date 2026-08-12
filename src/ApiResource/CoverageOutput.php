<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\CoverageProvider;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/risque/{zone}/couverture',
            provider: CoverageProvider::class,
        ),
    ],
)]
final class CoverageOutput
{
    /**
     * @param list<array{lat: float, lon: float, value: float|null, status: string}> $cells
     */
    public function __construct(
        public readonly string $zone,
        public readonly string $variable,
        public readonly float $threshold,
        public readonly array $cells,
    ) {
    }
}
