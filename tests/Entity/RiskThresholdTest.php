<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\RiskThreshold;
use App\Enum\EnvironmentVariable;
use App\Enum\RiskType;
use App\Enum\ThresholdOperator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV7;

final class RiskThresholdTest extends TestCase
{
    public function testIdIsUuidV7AfterConstruction(): void
    {
        $threshold = new RiskThreshold(
            RiskType::Thermal,
            EnvironmentVariable::WaterTemperature,
            ThresholdOperator::GreaterThan,
            28.0,
            'Ifremer (provisoire)',
        );

        self::assertInstanceOf(UuidV7::class, $threshold->getId());
    }
}
