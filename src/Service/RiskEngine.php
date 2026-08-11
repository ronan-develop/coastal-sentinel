<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\RiskAssessment;
use App\Entity\Zone;
use App\Enum\RiskType;
use App\Repository\EnvironmentReadingRepository;
use App\Repository\RiskThresholdRepository;
use App\Service\Risk\ExceedanceWindowFinder;
use Doctrine\ORM\EntityManagerInterface;

final class RiskEngine
{
    private const HORIZON_START_DAYS = 3;
    private const HORIZON_END_DAYS = 7;

    public function __construct(
        private readonly EnvironmentReadingRepository $readingRepository,
        private readonly RiskThresholdRepository $thresholdRepository,
        private readonly EntityManagerInterface $em,
        private readonly ExceedanceWindowFinder $finder,
    ) {
    }

    public function assess(Zone $zone, RiskType $riskType, \DateTimeImmutable $today): RiskAssessment
    {
        $windowStart = $today->modify(\sprintf('+%d days', self::HORIZON_START_DAYS));
        $windowEnd = $today->modify(\sprintf('+%d days', self::HORIZON_END_DAYS));

        $qualifying = null;
        foreach ($this->thresholdRepository->findBy(['riskType' => $riskType]) as $threshold) {
            $readings = $this->readingRepository->findByZoneAndVariableWithinRange(
                $zone,
                $threshold->getVariable(),
                $windowStart,
                $windowEnd,
            );

            $series = array_map(
                static fn ($reading) => [
                    'date' => $reading->getMeasuredAt(),
                    'exceeds' => $threshold->getOperator()->compare($reading->getValue(), $threshold->getValue()),
                ],
                $readings,
            );

            $qualifying = $this->finder->findLongestQualifyingWindow($series, $threshold->getMinExposureDays() ?? 1);

            if ($qualifying !== null) {
                break;
            }
        }

        $triggered = $qualifying !== null;

        $assessment = new RiskAssessment(
            $zone,
            $riskType,
            $triggered ? 1.0 : 0.0,
            $triggered ? $qualifying->start : $windowStart,
            $triggered ? $qualifying->end : $windowEnd,
            $this->recommendedAction($riskType, $triggered),
        );

        $this->em->persist($assessment);
        $this->em->flush();

        return $assessment;
    }

    private function recommendedAction(RiskType $riskType, bool $triggered): string
    {
        return match ($riskType) {
            RiskType::Thermal => $triggered
                ? 'Risque thermique détecté (J+3→J+7) : surveiller les mortalités, limiter les manipulations aux heures les plus fraîches.'
                : 'Aucun risque thermique détecté sur la fenêtre J+3→J+7.',
            default => throw new \LogicException(\sprintf(
                'Aucun message de recommandation défini pour le risque "%s".',
                $riskType->value,
            )),
        };
    }
}
