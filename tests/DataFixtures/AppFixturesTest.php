<?php

declare(strict_types=1);

namespace App\Tests\DataFixtures;

use App\DataFixtures\AppFixtures;
use App\Entity\Zone;
use App\Enum\RiskType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class AppFixturesTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get('doctrine')->getManager();
        (new AppFixtures())->load($this->em);
    }

    public function testZoneRadeDeBrestIsSeeded(): void
    {
        $zone = $this->em->getRepository(Zone::class)->findOneBy(['code' => 'rade-de-brest']);

        self::assertNotNull($zone);
        self::assertSame('Rade de Brest', $zone->getName());
    }

    public function testZoneGeometryIsValidWkt(): void
    {
        $zone = $this->em->getRepository(Zone::class)->findOneBy(['code' => 'rade-de-brest']);

        self::assertNotNull($zone);
        $wkt = $zone->getGeometry();
        self::assertStringStartsWith('MULTIPOLYGON(', $wkt);
        self::assertSame(substr_count($wkt, '('), substr_count($wkt, ')'));
    }

    public function testDataSourceCopernicusIsSeeded(): void
    {
        $dataSource = $this->em->getRepository(\App\Entity\DataSource::class)->findOneBy(['name' => 'copernicus']);

        self::assertNotNull($dataSource);
    }

    public function testThermalThresholdIsSeeded(): void
    {
        $threshold = $this->em->getRepository(\App\Entity\RiskThreshold::class)->findOneBy(['riskType' => RiskType::Thermal]);

        self::assertNotNull($threshold);
        self::assertSame(28.0, $threshold->getValue());
    }
}
