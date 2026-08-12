<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Entity\DataSource;
use App\Entity\EnvironmentReading;
use App\Entity\EnvironmentReadingCell;
use App\Entity\Zone;
use App\Enum\DataSourceType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class IngestCommandTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get('doctrine')->getManager();
    }

    public function testIngestsReadingsForKnownSourceAndZone(): void
    {
        $zone = new Zone('rade-de-brest-cmd', 'Rade de Brest (cmd)', 'MULTIPOLYGON(((-4.5 48.3, -4.3 48.3, -4.3 48.4, -4.5 48.4, -4.5 48.3)))');
        $dataSource = new DataSource('test-fixture-source', DataSourceType::Forecast);
        $this->em->persist($zone);
        $this->em->persist($dataSource);
        $this->em->flush();

        $application = new Application(self::$kernel);
        $tester = new CommandTester($application->find('app:ingest'));

        $exitCode = $tester->execute([
            '--source' => 'test-fixture-source',
            '--zone' => 'rade-de-brest-cmd',
        ]);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('1 mesure', $tester->getDisplay());
        self::assertCount(1, $this->em->getRepository(EnvironmentReading::class)->findBy(['zone' => $zone]));
        // Le volet grille (diagnostic de couverture, ticket #33) tourne en
        // complément, avec la même source si elle expose aussi
        // EnvironmentGridSourceInterface — cf. TestFixtureGridSource.
        self::assertCount(1, $this->em->getRepository(EnvironmentReadingCell::class)->findBy(['zone' => $zone]));
    }

    public function testReturnsFailureAndKeepsPreviousDataWhenSourceFails(): void
    {
        $zone = new Zone('rade-de-brest-cmd-fail', 'Rade de Brest (cmd fail)', 'MULTIPOLYGON(((-4.5 48.3, -4.3 48.3, -4.3 48.4, -4.5 48.4, -4.5 48.3)))');
        $dataSource = new DataSource('test-failing-source', DataSourceType::Forecast);
        $this->em->persist($zone);
        $this->em->persist($dataSource);
        $this->em->flush();

        $application = new Application(self::$kernel);
        $tester = new CommandTester($application->find('app:ingest'));

        $exitCode = $tester->execute([
            '--source' => 'test-failing-source',
            '--zone' => 'rade-de-brest-cmd-fail',
        ]);

        self::assertNotSame(0, $exitCode);
        self::assertCount(0, $this->em->getRepository(EnvironmentReading::class)->findBy(['zone' => $zone]));
    }
}
