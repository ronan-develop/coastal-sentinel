<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\DTO\EnvironmentGridCellData;
use App\Entity\DataSource;
use App\Entity\EnvironmentReadingCell;
use App\Entity\Zone;
use App\Enum\DataSourceType;
use App\Enum\EnvironmentVariable;
use App\Repository\DataSourceRepository;
use App\Repository\EnvironmentReadingCellRepository;
use App\Repository\ZoneRepository;
use App\Service\GridIngestionOrchestrator;
use App\Tests\Stub\StubEnvironmentGridSource;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class GridIngestionOrchestratorTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get('doctrine')->getManager();
    }

    private function makeOrchestrator(iterable $gridSources): GridIngestionOrchestrator
    {
        return new GridIngestionOrchestrator(
            $gridSources,
            $this->em,
            self::getContainer()->get(ZoneRepository::class),
            self::getContainer()->get(DataSourceRepository::class),
        );
    }

    public function testIngestsCellsFromMatchingAdapter(): void
    {
        $zone = new Zone('rade-de-brest-grid-orch', 'Rade de Brest (grid orch)', 'MULTIPOLYGON(((-4.5 48.3, -4.3 48.3, -4.3 48.4, -4.5 48.4, -4.5 48.3)))');
        $dataSource = new DataSource('copernicus-grid-orch', DataSourceType::Forecast);
        $this->em->persist($zone);
        $this->em->persist($dataSource);
        $this->em->flush();

        $measuredAt = new \DateTimeImmutable();
        $adapter = new StubEnvironmentGridSource('copernicus-grid-orch', [
            new EnvironmentGridCellData(EnvironmentVariable::WaterTemperature, 48.3, -4.4, 21.5, $measuredAt),
            new EnvironmentGridCellData(EnvironmentVariable::WaterTemperature, 48.31, -4.41, null, $measuredAt),
        ]);

        $orchestrator = $this->makeOrchestrator([$adapter]);
        $count = $orchestrator->ingest('copernicus-grid-orch', 'rade-de-brest-grid-orch');

        self::assertSame(2, $count);
        self::assertCount(2, $this->em->getRepository(EnvironmentReadingCell::class)->findBy(['zone' => $zone]));
    }

    public function testThrowsOnUnknownSource(): void
    {
        $orchestrator = $this->makeOrchestrator([]);

        $this->expectException(\InvalidArgumentException::class);

        $orchestrator->ingest('source-inconnue', 'rade-de-brest');
    }

    public function testThrowsOnUnknownZone(): void
    {
        $adapter = new StubEnvironmentGridSource('copernicus-grid-orch-2', []);
        $orchestrator = $this->makeOrchestrator([$adapter]);

        $this->expectException(\InvalidArgumentException::class);

        $orchestrator->ingest('copernicus-grid-orch-2', 'zone-inexistante');
    }
}
