<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\CoverageOutput;
use App\Enum\EnvironmentVariable;
use App\Repository\EnvironmentReadingCellRepository;
use App\Repository\RiskThresholdRepository;
use App\Repository\ZoneRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CoverageProvider implements ProviderInterface
{
    public function __construct(
        private readonly ZoneRepository $zoneRepository,
        private readonly EnvironmentReadingCellRepository $cellRepository,
        private readonly RiskThresholdRepository $thresholdRepository,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): CoverageOutput
    {
        $zone = $this->zoneRepository->findOneBy(['code' => $uriVariables['zone']]);

        if ($zone === null) {
            throw new NotFoundHttpException(\sprintf('Zone inconnue : "%s".', $uriVariables['zone']));
        }

        // Un seul risque/variable en MVP (cf. .claude/architecture.md, phase 0).
        $threshold = $this->thresholdRepository->findOneBy(['variable' => EnvironmentVariable::WaterTemperature]);

        $cells = array_map(
            static function ($cell) use ($threshold) {
                $value = $cell->getValue();
                $status = match (true) {
                    $value === null => 'absent',
                    $threshold !== null && $threshold->getOperator()->compare($value, $threshold->getValue()) => 'risque',
                    default => 'ok',
                };

                return [
                    'lat' => $cell->getLat(),
                    'lon' => $cell->getLon(),
                    'value' => $value,
                    'status' => $status,
                ];
            },
            $this->cellRepository->findLatestForZone($zone),
        );

        return new CoverageOutput(
            zone: $zone->getCode(),
            variable: EnvironmentVariable::WaterTemperature->value,
            threshold: $threshold?->getValue() ?? 0.0,
            cells: $cells,
        );
    }
}
