<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\DataSource;
use App\Entity\EnvironmentReading;
use App\Entity\RiskThreshold;
use App\Entity\Zone;
use App\Enum\DataSourceType;
use App\Enum\EnvironmentVariable;
use App\Enum\RiskType;
use App\Enum\ThresholdOperator;
use App\Repository\EnvironmentReadingRepository;
use App\Repository\RiskThresholdRepository;
use App\Service\Risk\ExceedanceWindowFinder;
use App\Service\RiskEngine;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class RiskEngineTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private RiskEngine $riskEngine;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get('doctrine')->getManager();
        $this->riskEngine = new RiskEngine(
            self::getContainer()->get(EnvironmentReadingRepository::class),
            self::getContainer()->get(RiskThresholdRepository::class),
            $this->em,
            new ExceedanceWindowFinder(),
        );
    }

    private function seedZoneWithReadings(string $code, \DateTimeImmutable $today, array $valuesByDayOffset): Zone
    {
        $zone = new Zone($code, 'Zone de test', 'MULTIPOLYGON(((-4.5 48.3, -4.3 48.3, -4.3 48.4, -4.5 48.4, -4.5 48.3)))');
        $dataSource = new DataSource('copernicus-risk-' . $code, DataSourceType::Forecast);
        $this->em->persist($zone);
        $this->em->persist($dataSource);

        foreach ($valuesByDayOffset as $offset => $value) {
            $this->em->persist(new EnvironmentReading(
                $zone,
                $dataSource,
                EnvironmentVariable::WaterTemperature,
                $value,
                'celsius',
                $today->modify(\sprintf('+%d days', $offset)),
                $offset,
            ));
        }

        $this->em->flush();

        return $zone;
    }

    public function testTriggersRiskWhenThresholdExceededForMinExposureDaysWithinJ3J7(): void
    {
        $today = new \DateTimeImmutable('2026-08-11');
        $zone = $this->seedZoneWithReadings('zone-triggered', $today, [
            3 => 29.0,
            4 => 29.5,
            5 => 29.2,
            6 => 26.0,
            7 => 25.0,
        ]);
        $this->em->persist(new RiskThreshold(RiskType::Thermal, EnvironmentVariable::WaterTemperature, ThresholdOperator::GreaterThan, 28.0, 'test', 3));
        $this->em->flush();

        $assessment = $this->riskEngine->assess($zone, RiskType::Thermal, $today);

        self::assertSame(1.0, $assessment->getScore());

        self::assertSame('2026-08-14', $assessment->getWindowStart()->format('Y-m-d'));
        self::assertSame('2026-08-16', $assessment->getWindowEnd()->format('Y-m-d'));
        self::assertStringContainsString('thermique', $assessment->getRecommendedAction());
    }

    public function testNoRiskWhenThresholdNeverExceeded(): void
    {
        $today = new \DateTimeImmutable('2026-08-11');
        $zone = $this->seedZoneWithReadings('zone-safe', $today, [
            3 => 20.0,
            4 => 21.0,
            5 => 20.5,
            6 => 19.0,
            7 => 20.0,
        ]);
        $this->em->persist(new RiskThreshold(RiskType::Thermal, EnvironmentVariable::WaterTemperature, ThresholdOperator::GreaterThan, 28.0, 'test', 3));
        $this->em->flush();

        $assessment = $this->riskEngine->assess($zone, RiskType::Thermal, $today);

        self::assertSame(0.0, $assessment->getScore());
        self::assertSame('2026-08-14', $assessment->getWindowStart()->format('Y-m-d'));
        self::assertSame('2026-08-18', $assessment->getWindowEnd()->format('Y-m-d'));
    }

    public function testAssessmentIsPersisted(): void
    {
        $today = new \DateTimeImmutable('2026-08-11');
        $zone = $this->seedZoneWithReadings('zone-persisted', $today, [3 => 20.0]);
        $this->em->persist(new RiskThreshold(RiskType::Thermal, EnvironmentVariable::WaterTemperature, ThresholdOperator::GreaterThan, 28.0, 'test', 3));
        $this->em->flush();

        $assessment = $this->riskEngine->assess($zone, RiskType::Thermal, $today);

        $found = $this->em->getRepository(\App\Entity\RiskAssessment::class)->find($assessment->getId());
        self::assertNotNull($found);
    }
}
