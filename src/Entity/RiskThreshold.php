<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\EnvironmentVariable;
use App\Enum\RiskType;
use App\Enum\ThresholdOperator;
use App\Repository\RiskThresholdRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: RiskThresholdRepository::class)]
#[ORM\Table(name: 'risk_thresholds')]
class RiskThreshold
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\Column(enumType: RiskType::class)]
    private RiskType $riskType;

    #[ORM\Column(enumType: EnvironmentVariable::class)]
    private EnvironmentVariable $variable;

    #[ORM\Column(enumType: ThresholdOperator::class)]
    private ThresholdOperator $operator;

    #[ORM\Column(type: 'float')]
    private float $value;

    #[ORM\Column(type: 'string')]
    private string $source;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $minExposureDays;

    public function __construct(
        RiskType $riskType,
        EnvironmentVariable $variable,
        ThresholdOperator $operator,
        float $value,
        string $source,
        ?int $minExposureDays = null,
    ) {
        $this->id = Uuid::v7();
        $this->riskType = $riskType;
        $this->variable = $variable;
        $this->operator = $operator;
        $this->value = $value;
        $this->source = $source;
        $this->minExposureDays = $minExposureDays;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getRiskType(): RiskType
    {
        return $this->riskType;
    }

    public function getVariable(): EnvironmentVariable
    {
        return $this->variable;
    }

    public function getOperator(): ThresholdOperator
    {
        return $this->operator;
    }

    public function getValue(): float
    {
        return $this->value;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getMinExposureDays(): ?int
    {
        return $this->minExposureDays;
    }
}
