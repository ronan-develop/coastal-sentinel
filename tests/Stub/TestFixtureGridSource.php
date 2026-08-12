<?php

declare(strict_types=1);

namespace App\Tests\Stub;

use App\DTO\EnvironmentGridCellData;
use App\Entity\Zone;
use App\Enum\EnvironmentVariable;
use App\Interface\EnvironmentGridSourceInterface;

/**
 * Adaptateur de test enregistré uniquement en environnement `test`
 * (config/services.yaml, bloc when@test) — permet de tester le câblage
 * de IngestCommand (volet grille) sans dépendre de Python ni du réseau.
 */
final class TestFixtureGridSource implements EnvironmentGridSourceInterface
{
    public function getSourceName(): string
    {
        return 'test-fixture-source';
    }

    public function fetch(Zone $zone): iterable
    {
        yield new EnvironmentGridCellData(
            EnvironmentVariable::WaterTemperature,
            48.3,
            -4.4,
            21.5,
            new \DateTimeImmutable('2026-08-11'),
        );
    }
}
