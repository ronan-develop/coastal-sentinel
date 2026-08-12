<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Zone;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Deuxième zone de démo (ticket #30) — chargée en complément d'AppFixtures
 * via `--append --group=arcachon`, sans dépendre d'elle ni recréer ses
 * données (DataSource et RiskThreshold sont globaux, pas propres à une
 * zone) — évite le doublon sur AppFixtures lors du chargement en append.
 */
class ArcachonFixtures extends Fixture implements FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['arcachon'];
    }

    public function load(ObjectManager $manager): void
    {
        // Sous-zone officielle Sandre "Arguin" (code 33.01), à l'embouchure
        // du bassin. La sous-zone "Intra Bassin" (33.10), plus intuitive,
        // s'est révélée non résolue par le modèle océanique Copernicus IBI
        // (résolution ~3km, trop grossière pour ce lagon peu profond et
        // découpé — toutes les mailles y ressortaient en NaN, testé et
        // confirmé). Arguin, à l'interface avec l'océan ouvert, est bien
        // couverte et reste une vraie zone de production classée (groupe 3,
        // classe A).
        $geometry = file_get_contents(__DIR__ . '/data/bassin-arcachon.wkt');

        $zone = new Zone('bassin-arcachon', 'Bassin d\'Arcachon', $geometry);
        $manager->persist($zone);
        $manager->flush();
    }
}
