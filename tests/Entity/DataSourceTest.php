<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\DataSource;
use App\Enum\DataSourceType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV7;

final class DataSourceTest extends TestCase
{
    public function testIdIsUuidV7AfterConstruction(): void
    {
        $dataSource = new DataSource('copernicus', DataSourceType::Forecast);

        self::assertInstanceOf(UuidV7::class, $dataSource->getId());
    }
}
