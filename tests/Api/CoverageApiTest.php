<?php

declare(strict_types=1);

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\DataSource;
use App\Entity\EnvironmentReadingCell;
use App\Entity\Zone;
use App\Enum\DataSourceType;
use App\Enum\EnvironmentVariable;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\User\InMemoryUser;

final class CoverageApiTest extends ApiTestCase
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
        static::createClient()->request('GET', '/api/risque/rade-de-brest/couverture');

        self::assertResponseStatusCodeSame(401);
    }

    public function testReturns200WithValidTokenAndKnownZone(): void
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        \assert($em instanceof EntityManagerInterface);

        $zone = new Zone('zone-coverage-api-ok', 'Zone coverage API ok', 'MULTIPOLYGON(((-4.5 48.3, -4.3 48.3, -4.3 48.4, -4.5 48.4, -4.5 48.3)))');
        $dataSource = new DataSource('source-coverage-api-ok', DataSourceType::Forecast);
        $em->persist($zone);
        $em->persist($dataSource);
        $em->persist(new EnvironmentReadingCell($zone, $dataSource, EnvironmentVariable::WaterTemperature, 48.3, -4.4, 21.5, new \DateTimeImmutable()));
        $em->flush();

        static::createClient()->request('GET', '/api/risque/zone-coverage-api-ok/couverture', $this->authHeader());

        self::assertResponseIsSuccessful();
        self::assertJsonContains([
            'zone' => 'zone-coverage-api-ok',
            'variable' => 'water_temperature',
        ]);
    }

    public function testReturns404ForUnknownZoneWithValidToken(): void
    {
        static::createClient()->request('GET', '/api/risque/zone-inexistante/couverture', $this->authHeader());

        self::assertResponseStatusCodeSame(404);
    }
}
