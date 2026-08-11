<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\DTO\EnvironmentReadingData;
use App\Entity\DataSource;
use App\Entity\EnvironmentReading;
use App\Entity\Zone;
use App\Enum\DataSourceType;
use App\Enum\EnvironmentVariable;
use App\Repository\DataSourceRepository;
use App\Repository\EnvironmentReadingRepository;
use App\Repository\ZoneRepository;
use App\Service\IngestionOrchestrator;
use App\Tests\Stub\StubEnvironmentDataSource;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class IngestionOrchestratorTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get('doctrine')->getManager();
    }

    private function makeOrchestrator(iterable $dataSources): IngestionOrchestrator
    {
        return new IngestionOrchestrator(
            $dataSources,
            $this->em,
            self::getContainer()->get(ZoneRepository::class),
            self::getContainer()->get(DataSourceRepository::class),
            self::getContainer()->get(EnvironmentReadingRepository::class),
        );
    }

    public function testIngestsReadingsFromMatchingAdapter(): void
    {
        $zone = new Zone('rade-de-brest-orch', 'Rade de Brest (orch)', 'MULTIPOLYGON(((-4.5 48.3, -4.3 48.3, -4.3 48.4, -4.5 48.4, -4.5 48.3)))');
        $dataSource = new DataSource('copernicus-orch', DataSourceType::Forecast);
        $this->em->persist($zone);
        $this->em->persist($dataSource);
        $this->em->flush();

        $since = new \DateTimeImmutable('2026-08-11');
        $adapter = new StubEnvironmentDataSource('copernicus-orch', [
            new EnvironmentReadingData(EnvironmentVariable::WaterTemperature, 21.63, 'celsius', new \DateTimeImmutable('2026-08-11'), 0),
            new EnvironmentReadingData(EnvironmentVariable::WaterTemperature, 21.29, 'celsius', new \DateTimeImmutable('2026-08-12'), 1),
        ]);

        $orchestrator = $this->makeOrchestrator([$adapter]);
        $count = $orchestrator->ingest('copernicus-orch', 'rade-de-brest-orch', $since);

        self::assertSame(2, $count);

        $readings = $this->em->getRepository(EnvironmentReading::class)->findBy(['zone' => $zone]);
        self::assertCount(2, $readings);
        self::assertNotNull($dataSource->getLastSuccessfulIngestionAt());
    }

    public function testIngestIsIdempotentOnSecondRun(): void
    {
        $zone = new Zone('rade-de-brest-idem', 'Rade de Brest (idem)', 'MULTIPOLYGON(((-4.5 48.3, -4.3 48.3, -4.3 48.4, -4.5 48.4, -4.5 48.3)))');
        $dataSource = new DataSource('copernicus-idem', DataSourceType::Forecast);
        $this->em->persist($zone);
        $this->em->persist($dataSource);
        $this->em->flush();

        $since = new \DateTimeImmutable('2026-08-11');
        $adapter = new StubEnvironmentDataSource('copernicus-idem', [
            new EnvironmentReadingData(EnvironmentVariable::WaterTemperature, 21.63, 'celsius', new \DateTimeImmutable('2026-08-11'), 0),
        ]);

        $orchestrator = $this->makeOrchestrator([$adapter]);
        $orchestrator->ingest('copernicus-idem', 'rade-de-brest-idem', $since);
        $secondRunCount = $orchestrator->ingest('copernicus-idem', 'rade-de-brest-idem', $since);

        self::assertSame(0, $secondRunCount);
        self::assertCount(1, $this->em->getRepository(EnvironmentReading::class)->findBy(['zone' => $zone]));
    }

    public function testThrowsOnUnknownSource(): void
    {
        $orchestrator = $this->makeOrchestrator([]);

        $this->expectException(\InvalidArgumentException::class);

        $orchestrator->ingest('source-inconnue', 'rade-de-brest', new \DateTimeImmutable());
    }
}
