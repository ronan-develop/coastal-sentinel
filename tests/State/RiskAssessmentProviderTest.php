<?php

declare(strict_types=1);

namespace App\Tests\State;

use ApiPlatform\Metadata\Get;
use App\ApiResource\RiskAssessmentOutput;
use App\Entity\RiskAssessment;
use App\Entity\Zone;
use App\Enum\RiskType;
use App\Repository\RiskAssessmentRepository;
use App\Repository\ZoneRepository;
use App\State\RiskAssessmentProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class RiskAssessmentProviderTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private RiskAssessmentProvider $provider;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get('doctrine')->getManager();
        $this->provider = new RiskAssessmentProvider(
            self::getContainer()->get(ZoneRepository::class),
            self::getContainer()->get(RiskAssessmentRepository::class),
        );
    }

    public function testReturnsOutputForZoneWithAssessment(): void
    {
        $zone = new Zone('zone-provider-ok', 'Zone provider ok', 'MULTIPOLYGON(((-4.5 48.3, -4.3 48.3, -4.3 48.4, -4.5 48.4, -4.5 48.3)))');
        $this->em->persist($zone);
        $this->em->persist(new RiskAssessment(
            $zone,
            RiskType::Thermal,
            1.0,
            new \DateTimeImmutable('2026-08-14'),
            new \DateTimeImmutable('2026-08-16'),
            'Risque thermique détecté.',
        ));
        $this->em->flush();

        $output = $this->provider->provide(new Get(), ['zone' => 'zone-provider-ok']);

        self::assertInstanceOf(RiskAssessmentOutput::class, $output);
        self::assertSame('zone-provider-ok', $output->zone);
        self::assertSame('thermal', $output->riskType);
        self::assertSame(1.0, $output->score);
        self::assertSame('2026-08-14', $output->windowStart);
        self::assertSame('2026-08-16', $output->windowEnd);
        self::assertSame('Risque thermique détecté.', $output->recommendedAction);
        self::assertCount(1, $output->zonePolygons);
        self::assertSame([-4.5, 48.3], $output->zonePolygons[0][0]);
    }

    public function testThrowsNotFoundForUnknownZone(): void
    {
        $this->expectException(NotFoundHttpException::class);

        $this->provider->provide(new Get(), ['zone' => 'zone-inexistante']);
    }

    public function testThrowsNotFoundWhenNoAssessmentYet(): void
    {
        $zone = new Zone('zone-provider-empty', 'Zone provider empty', 'MULTIPOLYGON(((-4.5 48.3, -4.3 48.3, -4.3 48.4, -4.5 48.4, -4.5 48.3)))');
        $this->em->persist($zone);
        $this->em->flush();

        $this->expectException(NotFoundHttpException::class);

        $this->provider->provide(new Get(), ['zone' => 'zone-provider-empty']);
    }
}
