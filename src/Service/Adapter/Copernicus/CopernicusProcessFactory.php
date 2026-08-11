<?php

declare(strict_types=1);

namespace App\Service\Adapter\Copernicus;

use App\Service\Geometry\BoundingBox;
use Symfony\Component\Process\Process;

final class CopernicusProcessFactory
{
    public function __construct(
        private readonly string $pythonBinary,
        private readonly string $scriptPath,
    ) {
    }

    /**
     * @return list<string>
     */
    public function buildCommand(BoundingBox $bbox, \DateTimeImmutable $since): array
    {
        return [
            $this->pythonBinary,
            $this->scriptPath,
            '--lon-min', (string) $bbox->lonMin,
            '--lon-max', (string) $bbox->lonMax,
            '--lat-min', (string) $bbox->latMin,
            '--lat-max', (string) $bbox->latMax,
            '--since', $since->format('Y-m-d'),
        ];
    }

    public function createProcess(BoundingBox $bbox, \DateTimeImmutable $since): Process
    {
        return new Process($this->buildCommand($bbox, $since));
    }
}
