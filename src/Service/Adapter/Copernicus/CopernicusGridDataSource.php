<?php

declare(strict_types=1);

namespace App\Service\Adapter\Copernicus;

use App\DTO\EnvironmentGridCellData;
use App\Entity\Zone;
use App\Interface\EnvironmentGridSourceInterface;
use App\Service\Geometry\BoundingBox;

final class CopernicusGridDataSource implements EnvironmentGridSourceInterface
{
    public function __construct(
        private readonly CopernicusGridProcessFactory $processFactory,
        private readonly CopernicusGridCellMapper $mapper,
    ) {
    }

    public function getSourceName(): string
    {
        return 'copernicus';
    }

    /**
     * @return iterable<EnvironmentGridCellData>
     */
    public function fetch(Zone $zone): iterable
    {
        $bbox = BoundingBox::fromWkt($zone->getGeometry());
        $process = $this->processFactory->createProcess($bbox);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException(\sprintf(
                'Échec de la récupération de la grille Copernicus pour la zone "%s" : %s',
                $zone->getCode(),
                trim($process->getErrorOutput()),
            ));
        }

        $decoded = json_decode($process->getOutput(), true, 512, \JSON_THROW_ON_ERROR);

        yield from $this->mapper->map($decoded, new \DateTimeImmutable());
    }
}
