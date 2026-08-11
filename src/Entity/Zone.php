<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ZoneRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ZoneRepository::class)]
#[ORM\Table(name: 'zones')]
class Zone
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\Column(type: 'string', unique: true)]
    private string $code;

    #[ORM\Column(type: 'string')]
    private string $name;

    /**
     * Géométrie au format WKT (ex. "MULTIPOLYGON(((...)))").
     * Pas de type Doctrine spatial : aucune requête SQL spatiale nécessaire
     * au MVP, l'usage réel se fait côté PHP dans l'adaptateur d'ingestion.
     */
    #[ORM\Column(type: 'text')]
    private string $geometry;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct(string $code, string $name, string $geometry)
    {
        $this->id = Uuid::v7();
        $this->code = $code;
        $this->name = $name;
        $this->geometry = $geometry;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getGeometry(): string
    {
        return $this->geometry;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
