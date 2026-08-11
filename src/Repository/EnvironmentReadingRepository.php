<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\EnvironmentReading;
use App\Entity\Zone;
use App\Enum\EnvironmentVariable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EnvironmentReading>
 */
class EnvironmentReadingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EnvironmentReading::class);
    }

    /**
     * @return list<EnvironmentReading>
     */
    public function findByZoneAndVariableWithinRange(
        Zone $zone,
        EnvironmentVariable $variable,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
    ): array {
        return $this->createQueryBuilder('r')
            ->andWhere('r.zone = :zone')
            ->andWhere('r.variable = :variable')
            ->andWhere('r.measuredAt >= :from')
            ->andWhere('r.measuredAt <= :to')
            // Type 'uuid' explicite obligatoire : sans lui, Doctrine ne convertit
            // pas l'UUID au format binaire stocké en base et la comparaison ne
            // matche jamais (testé, reproduit, cf. EnvironmentReadingRepositoryTest).
            ->setParameter('zone', $zone->getId(), 'uuid')
            ->setParameter('variable', $variable)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('r.measuredAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
