<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Entity\DataSource;
use App\Entity\EnvironmentReading;
use App\Entity\Zone;
use App\Enum\DataSourceType;
use App\Enum\EnvironmentVariable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class PurgeReadingsCommandTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get('doctrine')->getManager();
    }

    public function testPurgesRawPayloadOlderThanKeepDaysOption(): void
    {
        $zone = new Zone('zone-purge-cmd', 'Zone purge cmd', 'MULTIPOLYGON(((-4.5 48.3, -4.3 48.3, -4.3 48.4, -4.5 48.4, -4.5 48.3)))');
        $dataSource = new DataSource('source-purge-cmd', DataSourceType::Forecast);
        $this->em->persist($zone);
        $this->em->persist($dataSource);

        $old = new EnvironmentReading($zone, $dataSource, EnvironmentVariable::WaterTemperature, 21.0, 'celsius', new \DateTimeImmutable('2026-08-14'), 3, ['raw' => 'old']);
        $this->em->persist($old);
        $this->em->flush();

        $this->em->getConnection()->executeStatement(
            'UPDATE environment_readings SET ingested_at = ? WHERE id = ?',
            ['2026-01-01 00:00:00', $old->getId()->toBinary()],
        );
        $this->em->clear();

        $application = new Application(self::$kernel);
        $tester = new CommandTester($application->find('app:purge-readings'));

        $exitCode = $tester->execute(['--keep-days' => '30']);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('1', $tester->getDisplay());

        $reloaded = $this->em->getRepository(EnvironmentReading::class)->find($old->getId());
        self::assertNull($reloaded->getRawPayload());
    }

    public function testDefaultsToNinetyKeepDays(): void
    {
        $application = new Application(self::$kernel);
        $command = $application->find('app:purge-readings');
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([]);

        self::assertSame(0, $exitCode);
        self::assertSame('90', $command->getDefinition()->getOption('keep-days')->getDefault());
    }
}
