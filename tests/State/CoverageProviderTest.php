<?php

declare(strict_types=1);

namespace App\Tests\State;

use ApiPlatform\Metadata\Get;
use App\ApiResource\CoverageOutput;
use App\Entity\DataSource;
use App\Entity\EnvironmentReadingCell;
use App\Entity\RiskThreshold;
use App\Entity\Zone;
use App\Enum\DataSourceType;
use App\Enum\EnvironmentVariable;
use App\Enum\RiskType;
use App\Enum\ThresholdOperator;
use App\Repository\EnvironmentReadingCellRepository;
use App\Repository\RiskThresholdRepository;
use App\Repository\ZoneRepository;
use App\State\CoverageProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CoverageProviderTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private CoverageProvider $provider;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get('doctrine')->getManager();
        $this->provider = new CoverageProvider(
            self::getContainer()->get(ZoneRepository::class),
            self::getContainer()->get(EnvironmentReadingCellRepository::class),
            self::getContainer()->get(RiskThresholdRepository::class),
        );
    }

    public function testReturnsCellsWithStatusRelativeToThreshold(): void
    {
        $zone = new Zone('zone-coverage-ok', 'Zone coverage ok', 'MULTIPOLYGON(((-4.5 48.3, -4.3 48.3, -4.3 48.4, -4.5 48.4, -4.5 48.3)))');
        $dataSource = new DataSource('source-coverage-ok', DataSourceType::Forecast);
        $this->em->persist($zone);
        $this->em->persist($dataSource);
        $this->em->persist(new RiskThreshold(RiskType::Thermal, EnvironmentVariable::WaterTemperature, ThresholdOperator::GreaterThan, 28.0, 'test', 3));

        $measuredAt = new \DateTimeImmutable('2026-08-12T10:00:00+00:00');
        $this->em->persist(new EnvironmentReadingCell($zone, $dataSource, EnvironmentVariable::WaterTemperature, 48.3, -4.4, 21.5, $measuredAt));
        $this->em->persist(new EnvironmentReadingCell($zone, $dataSource, EnvironmentVariable::WaterTemperature, 48.31, -4.41, 29.0, $measuredAt));
        $this->em->persist(new EnvironmentReadingCell($zone, $dataSource, EnvironmentVariable::WaterTemperature, 48.32, -4.42, null, $measuredAt));
        $this->em->flush();

        $output = $this->provider->provide(new Get(), ['zone' => 'zone-coverage-ok']);

        self::assertInstanceOf(CoverageOutput::class, $output);
        self::assertSame('zone-coverage-ok', $output->zone);
        self::assertSame(28.0, $output->threshold);
        self::assertCount(3, $output->cells);

        $statuses = array_column($output->cells, 'status');
        sort($statuses);
        self::assertSame(['absent', 'ok', 'risque'], $statuses);
    }

    public function testThrowsNotFoundForUnknownZone(): void
    {
        $this->expectException(NotFoundHttpException::class);

        $this->provider->provide(new Get(), ['zone' => 'zone-inexistante']);
    }
}
