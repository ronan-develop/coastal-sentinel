<?php

declare(strict_types=1);

namespace App\Tests\Stub;

use App\DTO\EnvironmentReadingData;
use App\Entity\Zone;
use App\Interface\EnvironmentDataSourceInterface;

final class StubEnvironmentDataSource implements EnvironmentDataSourceInterface
{
    /**
     * @param list<EnvironmentReadingData> $readings
     */
    public function __construct(
        private readonly string $sourceName,
        private readonly array $readings,
    ) {
    }

    public function getSourceName(): string
    {
        return $this->sourceName;
    }

    public function fetch(Zone $zone, \DateTimeImmutable $since): iterable
    {
        yield from $this->readings;
    }
}
