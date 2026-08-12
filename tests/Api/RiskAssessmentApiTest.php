<?php

declare(strict_types=1);

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\RiskAssessment;
use App\Entity\Zone;
use App\Enum\RiskType;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\User\InMemoryUser;

final class RiskAssessmentApiTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = true;

    private function authHeader(): array
    {
        $token = static::getContainer()
            ->get(JWTTokenManagerInterface::class)
            ->create(new InMemoryUser('crc-pilot', null, ['ROLE_API_CLIENT']));

        return ['headers' => ['Authorization' => 'Bearer ' . $token]];
    }

    public function testReturns401WithoutToken(): void
    {
        static::createClient()->request('GET', '/api/risque/rade-de-brest');

        self::assertResponseStatusCodeSame(401);
    }

    public function testReturns200WithValidTokenAndKnownZone(): void
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        \assert($em instanceof EntityManagerInterface);

        $zone = new Zone('zone-api-ok', 'Zone API ok', 'MULTIPOLYGON(((-4.5 48.3, -4.3 48.3, -4.3 48.4, -4.5 48.4, -4.5 48.3)))');
        $em->persist($zone);
        $em->persist(new RiskAssessment(
            $zone,
            RiskType::Thermal,
            0.0,
            new \DateTimeImmutable('2026-08-14'),
            new \DateTimeImmutable('2026-08-18'),
            'Aucun risque thermique détecté sur la fenêtre J+3→J+7.',
        ));
        $em->flush();

        static::createClient()->request('GET', '/api/risque/zone-api-ok', $this->authHeader());

        self::assertResponseIsSuccessful();
        self::assertJsonContains([
            'zone' => 'zone-api-ok',
            'riskType' => 'thermal',
            // JSON n'encode pas 0.0 différemment de 0 (pas de distinction
            // int/float sur le fil) — le score reste bien un float côté PHP.
            'score' => 0,
            'windowStart' => '2026-08-14',
            'windowEnd' => '2026-08-18',
        ]);
    }

    public function testReturns404ForUnknownZoneWithValidToken(): void
    {
        static::createClient()->request('GET', '/api/risque/zone-inexistante', $this->authHeader());

        self::assertResponseStatusCodeSame(404);
    }
}
