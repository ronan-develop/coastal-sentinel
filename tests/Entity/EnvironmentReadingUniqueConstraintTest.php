<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\DataSource;
use App\Entity\EnvironmentReading;
use App\Entity\Zone;
use App\Enum\DataSourceType;
use App\Enum\EnvironmentVariable;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class EnvironmentReadingUniqueConstraintTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get('doctrine')->getManager();
    }

    public function testDuplicateReadingIsRejectedByUniqueConstraint(): void
    {
        $zone = new Zone('rade-de-brest-test', 'Rade de Brest (test)', 'MULTIPOLYGON(((-4.4 48.3, -4.3 48.3, -4.3 48.4, -4.4 48.3)))');
        $dataSource = new DataSource('copernicus-test', DataSourceType::Forecast);
        $measuredAt = new \DateTimeImmutable('2026-08-15');

        $this->em->persist($zone);
        $this->em->persist($dataSource);
        $this->em->flush();

        $first = new EnvironmentReading($zone, $dataSource, EnvironmentVariable::WaterTemperature, 21.5, 'celsius', $measuredAt, 3);
        $this->em->persist($first);
        $this->em->flush();

        $duplicate = new EnvironmentReading($zone, $dataSource, EnvironmentVariable::WaterTemperature, 22.0, 'celsius', $measuredAt, 3);
        $this->em->persist($duplicate);

        $this->expectException(UniqueConstraintViolationException::class);
        $this->em->flush();
    }
}
