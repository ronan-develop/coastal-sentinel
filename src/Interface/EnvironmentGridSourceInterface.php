<?php

declare(strict_types=1);

namespace App\Interface;

use App\DTO\EnvironmentGridCellData;
use App\Entity\Zone;

/**
 * Distinct de EnvironmentDataSourceInterface : fournit un instantané brut de
 * la grille du modèle (une maille = un point), pas une moyenne journalière
 * structurée. Sert au diagnostic de couverture (cf. ticket #33).
 */
interface EnvironmentGridSourceInterface
{
    /**
     * @return iterable<EnvironmentGridCellData>
     */
    public function fetch(Zone $zone): iterable;

    public function getSourceName(): string;
}
