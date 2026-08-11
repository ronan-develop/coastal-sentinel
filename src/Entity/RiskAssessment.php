<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\RiskType;
use App\Repository\RiskAssessmentRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: RiskAssessmentRepository::class)]
#[ORM\Table(name: 'risk_assessments')]
class RiskAssessment
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Zone::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Zone $zone;

    #[ORM\Column(enumType: RiskType::class)]
    private RiskType $riskType;

    #[ORM\Column(type: 'float')]
    private float $score;

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $windowStart;

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $windowEnd;

    #[ORM\Column(type: 'string')]
    private string $recommendedAction;

    #[ORM\Column]
    private \DateTimeImmutable $computedAt;

    public function __construct(
        Zone $zone,
        RiskType $riskType,
        float $score,
        \DateTimeImmutable $windowStart,
        \DateTimeImmutable $windowEnd,
        string $recommendedAction,
    ) {
        $this->id = Uuid::v7();
        $this->zone = $zone;
        $this->riskType = $riskType;
        $this->score = $score;
        $this->windowStart = $windowStart;
        $this->windowEnd = $windowEnd;
        $this->recommendedAction = $recommendedAction;
        $this->computedAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getZone(): Zone
    {
        return $this->zone;
    }

    public function getRiskType(): RiskType
    {
        return $this->riskType;
    }

    public function getScore(): float
    {
        return $this->score;
    }

    public function getWindowStart(): \DateTimeImmutable
    {
        return $this->windowStart;
    }

    public function getWindowEnd(): \DateTimeImmutable
    {
        return $this->windowEnd;
    }

    public function getRecommendedAction(): string
    {
        return $this->recommendedAction;
    }

    public function getComputedAt(): \DateTimeImmutable
    {
        return $this->computedAt;
    }
}
