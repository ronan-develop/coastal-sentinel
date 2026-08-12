<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\EnvironmentReadingCell;
use App\Entity\Zone;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EnvironmentReadingCell>
 */
class EnvironmentReadingCellRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EnvironmentReadingCell::class);
    }

    /**
     * @return list<EnvironmentReadingCell>
     */
    public function findLatestForZone(Zone $zone): array
    {
        $latestMeasuredAt = $this->createQueryBuilder('c')
            ->select('MAX(c.measuredAt)')
            ->andWhere('c.zone = :zone')
            ->setParameter('zone', $zone->getId(), 'uuid')
            ->getQuery()
            ->getSingleScalarResult();

        if ($latestMeasuredAt === null) {
            return [];
        }

        return $this->createQueryBuilder('c')
            ->andWhere('c.zone = :zone')
            ->andWhere('c.measuredAt = :measuredAt')
            ->setParameter('zone', $zone->getId(), 'uuid')
            ->setParameter('measuredAt', new \DateTimeImmutable($latestMeasuredAt))
            ->getQuery()
            ->getResult();
    }

    public function purgeOlderThan(\DateTimeImmutable $threshold): int
    {
        return $this->getEntityManager()
            ->createQuery('DELETE FROM App\Entity\EnvironmentReadingCell c WHERE c.ingestedAt < :threshold')
            ->setParameter('threshold', $threshold)
            ->execute();
    }
}
