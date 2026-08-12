<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\EnvironmentVariable;
use App\Repository\EnvironmentReadingCellRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * Instantané brut de la grille du modèle (une ligne par maille) — donnée de
 * diagnostic pour visualiser la couverture réelle, distincte de la moyenne
 * journalière structurée (EnvironmentReading). Rétention courte prévue
 * (cf. ticket #33, même logique que le rawPayload du ticket #18).
 */
#[ORM\Entity(repositoryClass: EnvironmentReadingCellRepository::class)]
#[ORM\Table(name: 'environment_reading_cells')]
class EnvironmentReadingCell
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
    private float $lat;

    #[ORM\Column(type: 'float')]
    private float $lon;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $value;

    #[ORM\Column]
    private \DateTimeImmutable $measuredAt;

    #[ORM\Column]
    private \DateTimeImmutable $ingestedAt;

    public function __construct(
        Zone $zone,
        DataSource $dataSource,
        EnvironmentVariable $variable,
        float $lat,
        float $lon,
        ?float $value,
        \DateTimeImmutable $measuredAt,
    ) {
        $this->id = Uuid::v7();
        $this->zone = $zone;
        $this->dataSource = $dataSource;
        $this->variable = $variable;
        $this->lat = $lat;
        $this->lon = $lon;
        $this->value = $value;
        $this->measuredAt = $measuredAt;
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

    public function getLat(): float
    {
        return $this->lat;
    }

    public function getLon(): float
    {
        return $this->lon;
    }

    public function getValue(): ?float
    {
        return $this->value;
    }

    public function getMeasuredAt(): \DateTimeImmutable
    {
        return $this->measuredAt;
    }

    public function getIngestedAt(): \DateTimeImmutable
    {
        return $this->ingestedAt;
    }
}
