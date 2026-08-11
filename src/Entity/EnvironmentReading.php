<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\EnvironmentVariable;
use App\Repository\EnvironmentReadingRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: EnvironmentReadingRepository::class)]
#[ORM\Table(name: 'environment_readings')]
#[ORM\UniqueConstraint(
    name: 'uniq_reading',
    columns: ['zone_id', 'data_source_id', 'variable', 'measured_at', 'horizon'],
)]
class EnvironmentReading
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Zone::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Zone $zone;

    #[ORM\ManyToOne(targetEntity: DataSource::class)]
    #[ORM\JoinColumn(nullable: false)]
    private DataSource $dataSource;

    #[ORM\Column(enumType: EnvironmentVariable::class)]
    private EnvironmentVariable $variable;

    #[ORM\Column(type: 'float')]
    private float $value;

    #[ORM\Column(type: 'string')]
    private string $unit;

    #[ORM\Column]
    private \DateTimeImmutable $measuredAt;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $horizon;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $rawPayload;

    #[ORM\Column]
    private \DateTimeImmutable $ingestedAt;

    /**
     * @param array<string, mixed>|null $rawPayload
     */
    public function __construct(
        Zone $zone,
        DataSource $dataSource,
        EnvironmentVariable $variable,
        float $value,
        string $unit,
        \DateTimeImmutable $measuredAt,
        ?int $horizon = null,
        ?array $rawPayload = null,
    ) {
        $this->id = Uuid::v7();
        $this->zone = $zone;
        $this->dataSource = $dataSource;
        $this->variable = $variable;
        $this->value = $value;
        $this->unit = $unit;
        $this->measuredAt = $measuredAt;
        $this->horizon = $horizon;
        $this->rawPayload = $rawPayload;
        $this->ingestedAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getZone(): Zone
    {
        return $this->zone;
    }

    public function getDataSource(): DataSource
    {
        return $this->dataSource;
    }

    public function getVariable(): EnvironmentVariable
    {
        return $this->variable;
    }

    public function getValue(): float
    {
        return $this->value;
    }

    public function getUnit(): string
    {
        return $this->unit;
    }

    public function getMeasuredAt(): \DateTimeImmutable
    {
        return $this->measuredAt;
    }

    public function getHorizon(): ?int
    {
        return $this->horizon;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getRawPayload(): ?array
    {
        return $this->rawPayload;
    }

    public function getIngestedAt(): \DateTimeImmutable
    {
        return $this->ingestedAt;
    }
}
