<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\EnvironmentReadingCell;
use App\Interface\EnvironmentGridSourceInterface;
use App\Repository\DataSourceRepository;
use App\Repository\ZoneRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Distinct de IngestionOrchestrator : persiste un instantané brut de la
 * grille (diagnostic de couverture, ticket #33), pas la moyenne journalière
 * structurée qui sert au RiskEngine.
 */
final class GridIngestionOrchestrator
{
    /**
     * @param iterable<EnvironmentGridSourceInterface> $gridSources
     */
    public function __construct(
        private readonly iterable $gridSources,
        private readonly EntityManagerInterface $em,
        private readonly ZoneRepository $zoneRepository,
        private readonly DataSourceRepository $dataSourceRepository,
    ) {
    }

    public function ingest(string $sourceName, string $zoneCode): int
    {
        $adapter = $this->findAdapter($sourceName);

        $zone = $this->zoneRepository->findOneBy(['code' => $zoneCode])
            ?? throw new \InvalidArgumentException(\sprintf('Zone inconnue : "%s".', $zoneCode));

        $dataSource = $this->dataSourceRepository->findOneBy(['name' => $sourceName])
            ?? throw new \InvalidArgumentException(\sprintf('Source de données non enregistrée en base : "%s".', $sourceName));

        $count = 0;
        foreach ($adapter->fetch($zone) as $cell) {
            $this->em->persist(new EnvironmentReadingCell(
                $zone,
                $dataSource,
                $cell->variable,
                $cell->lat,
                $cell->lon,
                $cell->value,
                $cell->measuredAt,
            ));
            ++$count;
        }

        $this->em->flush();

        return $count;
    }

    private function findAdapter(string $sourceName): EnvironmentGridSourceInterface
    {
        foreach ($this->gridSources as $gridSource) {
            if ($gridSource->getSourceName() === $sourceName) {
                return $gridSource;
            }
        }

        throw new \InvalidArgumentException(\sprintf('Source de grille inconnue : "%s".', $sourceName));
    }
}
