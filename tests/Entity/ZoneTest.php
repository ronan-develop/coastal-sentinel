<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Zone;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV7;

final class ZoneTest extends TestCase
{
    public function testIdIsUuidV7AfterConstruction(): void
    {
        $zone = new Zone('rade-de-brest', 'Rade de Brest', 'MULTIPOLYGON(((-4.4 48.3, -4.3 48.3, -4.3 48.4, -4.4 48.3)))');

        self::assertInstanceOf(UuidV7::class, $zone->getId());
    }
}
