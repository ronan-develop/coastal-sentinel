<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\RiskAssessmentOutput;
use App\Repository\RiskAssessmentRepository;
use App\Repository\ZoneRepository;
use App\Service\Geometry\WktPolygonParser;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class RiskAssessmentProvider implements ProviderInterface
{
    public function __construct(
        private readonly ZoneRepository $zoneRepository,
        private readonly RiskAssessmentRepository $riskAssessmentRepository,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): RiskAssessmentOutput
    {
        $zone = $this->zoneRepository->findOneBy(['code' => $uriVariables['zone']]);

        if ($zone === null) {
            throw new NotFoundHttpException(\sprintf('Zone inconnue : "%s".', $uriVariables['zone']));
        }

        $assessment = $this->riskAssessmentRepository->findLatestForZone($zone);

        if ($assessment === null) {
            throw new NotFoundHttpException(\sprintf('Aucune évaluation de risque disponible pour la zone "%s".', $uriVariables['zone']));
        }

        return new RiskAssessmentOutput(
            zone: $zone->getCode(),
            riskType: $assessment->getRiskType()->value,
            score: $assessment->getScore(),
            windowStart: $assessment->getWindowStart()->format('Y-m-d'),
            windowEnd: $assessment->getWindowEnd()->format('Y-m-d'),
            recommendedAction: $assessment->getRecommendedAction(),
            computedAt: $assessment->getComputedAt()->format(\DateTimeInterface::ATOM),
            zonePolygons: WktPolygonParser::parse($zone->getGeometry()),
        );
    }
}
