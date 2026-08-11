<?php

declare(strict_types=1);

namespace App\Tests\Stub;

use App\Entity\Zone;
use App\Interface\EnvironmentDataSourceInterface;

/**
 * Adaptateur de test simulant un échec (ex. Copernicus indisponible),
 * enregistré uniquement en environnement `test` (config/services.yaml).
 */
final class FailingTestDataSource implements EnvironmentDataSourceInterface
{
    public function getSourceName(): string
    {
        return 'test-failing-source';
    }

    public function fetch(Zone $zone, \DateTimeImmutable $since): iterable
    {
        throw new \RuntimeException('Échec simulé de la source de données.');

        yield;
    }
}
