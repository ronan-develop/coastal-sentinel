<?php

declare(strict_types=1);

namespace App\Tests\Stub;

use App\DTO\EnvironmentGridCellData;
use App\Entity\Zone;
use App\Interface\EnvironmentGridSourceInterface;

final class StubEnvironmentGridSource implements EnvironmentGridSourceInterface
{
    /**
     * @param list<EnvironmentGridCellData> $cells
     */
    public function __construct(
        private readonly string $sourceName,
        private readonly array $cells,
    ) {
    }

    public function getSourceName(): string
    {
        return $this->sourceName;
    }

    public function fetch(Zone $zone): iterable
    {
        yield from $this->cells;
    }
}
