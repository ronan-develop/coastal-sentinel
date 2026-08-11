<?php

declare(strict_types=1);

namespace App\Tests\Stub;

use App\DTO\EnvironmentReadingData;
use App\Entity\Zone;
use App\Enum\EnvironmentVariable;
use App\Interface\EnvironmentDataSourceInterface;

/**
 * Adaptateur de test enregistré uniquement en environnement `test`
 * (config/services.yaml, bloc when@test) — permet de tester le câblage
 * de IngestCommand sans dépendre de Python ni du réseau.
 */
final class TestFixtureDataSource implements EnvironmentDataSourceInterface
{
    public function getSourceName(): string
    {
        return 'test-fixture-source';
    }

    public function fetch(Zone $zone, \DateTimeImmutable $since): iterable
    {
        yield new EnvironmentReadingData(
            EnvironmentVariable::WaterTemperature,
            19.5,
            'celsius',
            new \DateTimeImmutable('2026-08-11'),
            0,
        );
    }
}
