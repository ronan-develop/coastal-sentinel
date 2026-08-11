<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\EnvironmentReading;
use App\Interface\EnvironmentDataSourceInterface;
use App\Repository\DataSourceRepository;
use App\Repository\EnvironmentReadingRepository;
use App\Repository\ZoneRepository;
use Doctrine\ORM\EntityManagerInterface;

final class IngestionOrchestrator
{
    /**
     * @param iterable<EnvironmentDataSourceInterface> $dataSources
     */
    public function __construct(
        private readonly iterable $dataSources,
        private readonly EntityManagerInterface $em,
        private readonly ZoneRepository $zoneRepository,
        private readonly DataSourceRepository $dataSourceRepository,
        private readonly EnvironmentReadingRepository $readingRepository,
    ) {
    }

    public function ingest(string $sourceName, string $zoneCode, \DateTimeImmutable $since): int
    {
        $adapter = $this->findAdapter($sourceName);

        $zone = $this->zoneRepository->findOneBy(['code' => $zoneCode])
            ?? throw new \InvalidArgumentException(\sprintf('Zone inconnue : "%s".', $zoneCode));

        $dataSource = $this->dataSourceRepository->findOneBy(['name' => $sourceName])
            ?? throw new \InvalidArgumentException(\sprintf('Source de données non enregistrée en base : "%s".', $sourceName));

        $count = 0;
        foreach ($adapter->fetch($zone, $since) as $data) {
            $alreadyIngested = $this->readingRepository->findOneBy([
                'zone' => $zone,
                'dataSource' => $dataSource,
                'variable' => $data->variable,
                'measuredAt' => $data->measuredAt,
                'horizon' => $data->horizon,
            ]);

            if ($alreadyIngested !== null) {
                continue;
            }

            $this->em->persist(new EnvironmentReading(
                $zone,
                $dataSource,
                $data->variable,
                $data->value,
                $data->unit,
                $data->measuredAt,
                $data->horizon,
                $data->rawPayload,
            ));
            ++$count;
        }

        $dataSource->markIngestionSuccessful(new \DateTimeImmutable());
        $this->em->flush();

        return $count;
    }

    private function findAdapter(string $sourceName): EnvironmentDataSourceInterface
    {
        foreach ($this->dataSources as $dataSource) {
            if ($dataSource->getSourceName() === $sourceName) {
                return $dataSource;
            }
        }

        throw new \InvalidArgumentException(\sprintf('Source de données inconnue : "%s".', $sourceName));
    }
}
