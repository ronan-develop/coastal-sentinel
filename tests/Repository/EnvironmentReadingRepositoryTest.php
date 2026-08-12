<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Entity\DataSource;
use App\Entity\EnvironmentReading;
use App\Entity\Zone;
use App\Enum\DataSourceType;
use App\Enum\EnvironmentVariable;
use App\Repository\EnvironmentReadingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class EnvironmentReadingRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private EnvironmentReadingRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get('doctrine')->getManager();
        $this->repository = self::getContainer()->get(EnvironmentReadingRepository::class);
    }

    /**
     * Régression : setParameter('zone', $zone) sans type explicite ne
     * convertissait pas l'UUID au format binaire stocké en base — la
     * comparaison ne matchait jamais, alors que findBy(['zone' => $zone])
     * fonctionnait (chemin de code différent).
     */
    public function testFindsReadingsWithinRangeForZoneAndVariable(): void
    {
        $zone = new Zone('zone-repo-test', 'Zone repo test', 'MULTIPOLYGON(((-4.5 48.3, -4.3 48.3, -4.3 48.4, -4.5 48.4, -4.5 48.3)))');
        $otherZone = new Zone('zone-repo-other', 'Autre zone', 'MULTIPOLYGON(((-4.5 48.3, -4.3 48.3, -4.3 48.4, -4.5 48.4, -4.5 48.3)))');
        $dataSource = new DataSource('source-repo-test', DataSourceType::Forecast);
        $this->em->persist($zone);
        $this->em->persist($otherZone);
        $this->em->persist($dataSource);

        $inRange = new EnvironmentReading($zone, $dataSource, EnvironmentVariable::WaterTemperature, 21.0, 'celsius', new \DateTimeImmutable('2026-08-14'), 3);
        $outOfRange = new EnvironmentReading($zone, $dataSource, EnvironmentVariable::WaterTemperature, 21.0, 'celsius', new \DateTimeImmutable('2026-08-25'), 14);
        $otherVariable = new EnvironmentReading($zone, $dataSource, EnvironmentVariable::Salinity, 30.0, 'psu', new \DateTimeImmutable('2026-08-14'), 3);
        $otherZoneReading = new EnvironmentReading($otherZone, $dataSource, EnvironmentVariable::WaterTemperature, 21.0, 'celsius', new \DateTimeImmutable('2026-08-14'), 3);
        $this->em->persist($inRange);
        $this->em->persist($outOfRange);
        $this->em->persist($otherVariable);
        $this->em->persist($otherZoneReading);
        $this->em->flush();

        $results = $this->repository->findByZoneAndVariableWithinRange(
            $zone,
            EnvironmentVariable::WaterTemperature,
            new \DateTimeImmutable('2026-08-14'),
            new \DateTimeImmutable('2026-08-18'),
        );

        self::assertCount(1, $results);
        self::assertSame($inRange->getId()->toRfc4122(), $results[0]->getId()->toRfc4122());
    }

    public function testPurgesRawPayloadOlderThanThresholdOnly(): void
    {
        $zone = new Zone('zone-purge-test', 'Zone purge test', 'MULTIPOLYGON(((-4.5 48.3, -4.3 48.3, -4.3 48.4, -4.5 48.4, -4.5 48.3)))');
        $dataSource = new DataSource('source-purge-test', DataSourceType::Forecast);
        $this->em->persist($zone);
        $this->em->persist($dataSource);

        $old = new EnvironmentReading($zone, $dataSource, EnvironmentVariable::WaterTemperature, 21.0, 'celsius', new \DateTimeImmutable('2026-08-14'), 3, ['raw' => 'old']);
        $recent = new EnvironmentReading($zone, $dataSource, EnvironmentVariable::WaterTemperature, 21.0, 'celsius', new \DateTimeImmutable('2026-08-15'), 4, ['raw' => 'recent']);
        $this->em->persist($old);
        $this->em->persist($recent);
        $this->em->flush();

        // ingestedAt est toujours "maintenant" au constructeur (invariant du
        // domaine) : on l'antidate directement en base pour simuler une
        // lecture ancienne, sans ajouter de setter au seul usage des tests.
        $this->em->getConnection()->executeStatement(
            'UPDATE environment_readings SET ingested_at = ? WHERE id = ?',
            ['2026-01-01 00:00:00', $old->getId()->toBinary()],
        );
        $this->em->clear();

        $threshold = new \DateTimeImmutable('2026-08-01');
        $purgedCount = $this->repository->purgeRawPayloadOlderThan($threshold);

        self::assertSame(1, $purgedCount);

        $oldReloaded = $this->repository->find($old->getId());
        $recentReloaded = $this->repository->find($recent->getId());

        self::assertNull($oldReloaded->getRawPayload());
        self::assertSame(['raw' => 'recent'], $recentReloaded->getRawPayload());
    }
}
