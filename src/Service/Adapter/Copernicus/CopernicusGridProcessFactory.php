<?php

declare(strict_types=1);

namespace App\Service\Adapter\Copernicus;

use App\Service\Geometry\BoundingBox;
use Symfony\Component\Process\Process;

final class CopernicusGridProcessFactory
{
    public function __construct(
        private readonly string $pythonBinary,
        private readonly string $scriptPath,
    ) {
    }

    /**
     * @return list<string>
     */
    public function buildCommand(BoundingBox $bbox): array
    {
        return [
            $this->pythonBinary,
            $this->scriptPath,
            '--lon-min', (string) $bbox->lonMin,
            '--lon-max', (string) $bbox->lonMax,
            '--lat-min', (string) $bbox->latMin,
            '--lat-max', (string) $bbox->latMax,
        ];
    }

    public function createProcess(BoundingBox $bbox): Process
    {
        return new Process($this->buildCommand($bbox));
    }
}
