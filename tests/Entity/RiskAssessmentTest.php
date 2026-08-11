<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\RiskAssessment;
use App\Entity\Zone;
use App\Enum\RiskType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV7;

final class RiskAssessmentTest extends TestCase
{
    public function testIdIsUuidV7AfterConstruction(): void
    {
        $zone = new Zone('rade-de-brest', 'Rade de Brest', 'MULTIPOLYGON(((-4.4 48.3, -4.3 48.3, -4.3 48.4, -4.4 48.3)))');

        $assessment = new RiskAssessment(
            $zone,
            RiskType::Thermal,
            0.8,
            new \DateTimeImmutable('2026-08-15'),
            new \DateTimeImmutable('2026-08-19'),
            'Refroidissement des dégorgeoirs',
        );

        self::assertInstanceOf(UuidV7::class, $assessment->getId());
    }
}
