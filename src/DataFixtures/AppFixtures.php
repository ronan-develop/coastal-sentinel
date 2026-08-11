<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\DataSource;
use App\Entity\RiskThreshold;
use App\Entity\Zone;
use App\Enum\DataSourceType;
use App\Enum\EnvironmentVariable;
use App\Enum\RiskType;
use App\Enum\ThresholdOperator;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $geometry = file_get_contents(__DIR__ . '/data/rade-de-brest.wkt');

        $zone = new Zone('rade-de-brest', 'Rade de Brest', $geometry);
        $manager->persist($zone);

        $dataSource = new DataSource('copernicus', DataSourceType::Forecast);
        $manager->persist($dataSource);

        // Seuil provisoire, non validé scientifiquement — à confirmer via
        // Ifremer/CRC (cf. docs/questions.md et docs/architecture-ingestion.md).
        $threshold = new RiskThreshold(
            RiskType::Thermal,
            EnvironmentVariable::WaterTemperature,
            ThresholdOperator::GreaterThan,
            28.0,
            'Ifremer (seuil provisoire, non validé)',
            3,
        );
        $manager->persist($threshold);

        $manager->flush();
    }
}
