<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Entity\RiskAssessment;
use App\Entity\Zone;
use App\Enum\RiskType;
use App\Repository\RiskAssessmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class RiskAssessmentRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private RiskAssessmentRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get('doctrine')->getManager();
        $this->repository = self::getContainer()->get(RiskAssessmentRepository::class);
    }

    public function testReturnsNullWhenNoAssessmentExists(): void
    {
        $zone = new Zone('zone-latest-empty', 'Zone vide', 'MULTIPOLYGON(((-4.5 48.3, -4.3 48.3, -4.3 48.4, -4.5 48.4, -4.5 48.3)))');
        $this->em->persist($zone);
        $this->em->flush();

        self::assertNull($this->repository->findLatestForZone($zone));
    }

    public function testReturnsTheMostRecentlyComputedAssessment(): void
    {
        $zone = new Zone('zone-latest', 'Zone latest', 'MULTIPOLYGON(((-4.5 48.3, -4.3 48.3, -4.3 48.4, -4.5 48.4, -4.5 48.3)))');
        $otherZone = new Zone('zone-latest-other', 'Autre zone', 'MULTIPOLYGON(((-4.5 48.3, -4.3 48.3, -4.3 48.4, -4.5 48.4, -4.5 48.3)))');
        $this->em->persist($zone);
        $this->em->persist($otherZone);

        $older = new RiskAssessment($zone, RiskType::Thermal, 0.0, new \DateTimeImmutable('2026-08-01'), new \DateTimeImmutable('2026-08-05'), 'ancienne évaluation');
        $this->em->persist($older);
        $this->em->flush();

        // computedAt est fixé à la construction (now()) — on force un écart réel.
        usleep(1_100_000);

        $newer = new RiskAssessment($zone, RiskType::Thermal, 1.0, new \DateTimeImmutable('2026-08-14'), new \DateTimeImmutable('2026-08-16'), 'évaluation récente');
        $this->em->persist($newer);
        $this->em->persist(new RiskAssessment($otherZone, RiskType::Thermal, 1.0, new \DateTimeImmutable('2026-08-14'), new \DateTimeImmutable('2026-08-16'), 'autre zone'));
        $this->em->flush();

        $latest = $this->repository->findLatestForZone($zone);

        self::assertNotNull($latest);
        self::assertSame('évaluation récente', $latest->getRecommendedAction());
    }
}
