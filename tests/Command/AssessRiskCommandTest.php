<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Entity\RiskAssessment;
use App\Entity\RiskThreshold;
use App\Entity\Zone;
use App\Enum\EnvironmentVariable;
use App\Enum\RiskType;
use App\Enum\ThresholdOperator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class AssessRiskCommandTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get('doctrine')->getManager();
    }

    public function testAssessesRiskForKnownZone(): void
    {
        $zone = new Zone('rade-de-brest-assess', 'Rade de Brest (assess)', 'MULTIPOLYGON(((-4.5 48.3, -4.3 48.3, -4.3 48.4, -4.5 48.4, -4.5 48.3)))');
        $this->em->persist($zone);
        $this->em->persist(new RiskThreshold(RiskType::Thermal, EnvironmentVariable::WaterTemperature, ThresholdOperator::GreaterThan, 28.0, 'test', 3));
        $this->em->flush();

        $application = new Application(self::$kernel);
        $tester = new CommandTester($application->find('app:assess-risk'));

        $exitCode = $tester->execute(['--zone' => 'rade-de-brest-assess']);

        self::assertSame(0, $exitCode);
        self::assertCount(1, $this->em->getRepository(RiskAssessment::class)->findBy(['zone' => $zone]));
    }

    public function testReturnsFailureForUnknownZone(): void
    {
        $application = new Application(self::$kernel);
        $tester = new CommandTester($application->find('app:assess-risk'));

        $exitCode = $tester->execute(['--zone' => 'zone-inexistante']);

        self::assertNotSame(0, $exitCode);
    }
}
