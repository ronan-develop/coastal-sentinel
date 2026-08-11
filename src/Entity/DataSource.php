<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\DataSourceType;
use App\Repository\DataSourceRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: DataSourceRepository::class)]
#[ORM\Table(name: 'data_sources')]
class DataSource
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\Column(type: 'string', unique: true)]
    private string $name;

    #[ORM\Column(enumType: DataSourceType::class)]
    private DataSourceType $type;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastSuccessfulIngestionAt = null;

    public function __construct(string $name, DataSourceType $type)
    {
        $this->id = Uuid::v7();
        $this->name = $name;
        $this->type = $type;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): DataSourceType
    {
        return $this->type;
    }

    public function getLastSuccessfulIngestionAt(): ?\DateTimeImmutable
    {
        return $this->lastSuccessfulIngestionAt;
    }

    public function markIngestionSuccessful(\DateTimeImmutable $at): void
    {
        $this->lastSuccessfulIngestionAt = $at;
    }
}
