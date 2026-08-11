<?php

declare(strict_types=1);

namespace App\Interface;

use App\DTO\EnvironmentReadingData;
use App\Entity\Zone;

interface EnvironmentDataSourceInterface
{
    /**
     * @return iterable<EnvironmentReadingData>
     */
    public function fetch(Zone $zone, \DateTimeImmutable $since): iterable;

    public function getSourceName(): string;
}
