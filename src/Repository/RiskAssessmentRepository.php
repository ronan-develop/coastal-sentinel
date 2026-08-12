<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\RiskAssessment;
use App\Entity\Zone;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RiskAssessment>
 */
class RiskAssessmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RiskAssessment::class);
    }

    public function findLatestForZone(Zone $zone): ?RiskAssessment
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.zone = :zone')
            // Type 'uuid' explicite obligatoire, sinon la comparaison ne
            // matche jamais (cf. EnvironmentReadingRepository, bug #16).
            ->setParameter('zone', $zone->getId(), 'uuid')
            ->orderBy('a.computedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
