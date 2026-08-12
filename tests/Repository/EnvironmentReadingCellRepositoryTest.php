<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Entity\DataSource;
use App\Entity\EnvironmentReadingCell;
use App\Entity\Zone;
use App\Enum\DataSourceType;
use App\Enum\EnvironmentVariable;
use App\Repository\EnvironmentReadingCellRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class EnvironmentReadingCellRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private EnvironmentReadingCellRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get('doctrine')->getManager();
        $this->repository = self::getContainer()->get(EnvironmentReadingCellRepository::class);
    }

    public function testFindsLatestCellsForZoneOnly(): void
    {
        $zone = new Zone('zone-cell-test', 'Zone cell test', 'MULTIPOLYGON(((-4.5 48.3, -4.3 48.3, -4.3 48.4, -4.5 48.4, -4.5 48.3)))');
        $otherZone = new Zone('zone-cell-other', 'Autre zone', 'MULTIPOLYGON(((-4.5 48.3, -4.3 48.3, -4.3 48.4, -4.5 48.4, -4.5 48.3)))');
        $dataSource = new DataSource('source-cell-test', DataSourceType::Forecast);
        $this->em->persist($zone);
        $this->em->persist($otherZone);
        $this->em->persist($dataSource);

        $measuredAt = new \DateTimeImmutable('2026-08-12T10:00:00+00:00');
        $cellOk = new EnvironmentReadingCell($zone, $dataSource, EnvironmentVariable::WaterTemperature, 48.3, -4.4, 21.5, $measuredAt);
        $cellMissing = new EnvironmentReadingCell($zone, $dataSource, EnvironmentVariable::WaterTemperature, 48.31, -4.41, null, $measuredAt);
        $otherZoneCell = new EnvironmentReadingCell($otherZone, $dataSource, EnvironmentVariable::WaterTemperature, 48.3, -4.4, 20.0, $measuredAt);
        $this->em->persist($cellOk);
        $this->em->persist($cellMissing);
        $this->em->persist($otherZoneCell);
        $this->em->flush();

        $results = $this->repository->findLatestForZone($zone);

        self::assertCount(2, $results);
    }

    public function testPurgesOlderThanThreshold(): void
    {
        $zone = new Zone('zone-cell-purge', 'Zone cell purge', 'MULTIPOLYGON(((-4.5 48.3, -4.3 48.3, -4.3 48.4, -4.5 48.4, -4.5 48.3)))');
        $dataSource = new DataSource('source-cell-purge', DataSourceType::Forecast);
        $this->em->persist($zone);
        $this->em->persist($dataSource);

        $old = new EnvironmentReadingCell($zone, $dataSource, EnvironmentVariable::WaterTemperature, 48.3, -4.4, 21.5, new \DateTimeImmutable('2026-01-01'));
        $recent = new EnvironmentReadingCell($zone, $dataSource, EnvironmentVariable::WaterTemperature, 48.31, -4.41, 21.6, new \DateTimeImmutable('2026-08-12'));
        $this->em->persist($old);
        $this->em->persist($recent);
        $this->em->flush();

        $this->em->getConnection()->executeStatement(
            'UPDATE environment_reading_cells SET ingested_at = ? WHERE id = ?',
            ['2026-01-01 00:00:00', $old->getId()->toBinary()],
        );
        $this->em->clear();

        $purgedCount = $this->repository->purgeOlderThan(new \DateTimeImmutable('2026-08-01'));

        self::assertSame(1, $purgedCount);
        self::assertNull($this->repository->find($old->getId()));
        self::assertNotNull($this->repository->find($recent->getId()));
    }
}
