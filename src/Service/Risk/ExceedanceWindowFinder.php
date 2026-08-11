<?php

declare(strict_types=1);

namespace App\Service\Risk;

final class ExceedanceWindowFinder
{
    /**
     * Cherche la plus longue série de jours consécutifs (sans trou) dont
     * `exceeds` est vrai, et atteignant au moins $minConsecutiveDays.
     *
     * Un jour manquant dans la série (trou de données) casse la série en
     * cours, même si les jours de part et d'autre du trou dépassent le
     * seuil — on ne suppose jamais une exposition non confirmée.
     *
     * @param list<array{date: \DateTimeImmutable, exceeds: bool}> $series Trié par date croissante.
     */
    public function findLongestQualifyingWindow(array $series, int $minConsecutiveDays): ?ExceedanceWindow
    {
        $best = null;
        $runStart = null;
        $runEnd = null;
        $previousDate = null;

        foreach ($series as $point) {
            $date = $point['date'];
            $isContiguous = $previousDate !== null && $previousDate->diff($date)->days === 1;

            if ($point['exceeds'] && $isContiguous && $runEnd !== null) {
                $runEnd = $date;
            } elseif ($point['exceeds']) {
                $runStart = $date;
                $runEnd = $date;
            } else {
                $runStart = null;
                $runEnd = null;
            }

            if ($runStart !== null && $runEnd !== null) {
                $candidate = new ExceedanceWindow($runStart, $runEnd);
                if ($candidate->days() >= $minConsecutiveDays && ($best === null || $candidate->days() > $best->days())) {
                    $best = $candidate;
                }
            }

            $previousDate = $date;
        }

        return $best;
    }
}
