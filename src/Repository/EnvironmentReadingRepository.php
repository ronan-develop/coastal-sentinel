<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\EnvironmentReading;
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
}
