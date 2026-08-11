<?php

declare(strict_types=1);

namespace App\Service\Adapter\Copernicus;

use App\DTO\EnvironmentReadingData;
use App\Entity\Zone;
use App\Interface\EnvironmentDataSourceInterface;
use App\Service\Geometry\BoundingBox;

final class CopernicusEnvironmentDataSource implements EnvironmentDataSourceInterface
{
    public function __construct(
        private readonly CopernicusProcessFactory $processFactory,
        private readonly CopernicusReadingMapper $mapper,
    ) {
    }

    public function getSourceName(): string
    {
        return 'copernicus';
    }

    /**
     * @return iterable<EnvironmentReadingData>
     */
    public function fetch(Zone $zone, \DateTimeImmutable $since): iterable
    {
        $bbox = BoundingBox::fromWkt($zone->getGeometry());
        $process = $this->processFactory->createProcess($bbox, $since);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException(\sprintf(
                'Échec de l\'ingestion Copernicus pour la zone "%s" : %s',
                $zone->getCode(),
                trim($process->getErrorOutput()),
            ));
        }

        $decoded = json_decode($process->getOutput(), true, 512, \JSON_THROW_ON_ERROR);

        yield from $this->mapper->map($decoded, $since);
    }
}
