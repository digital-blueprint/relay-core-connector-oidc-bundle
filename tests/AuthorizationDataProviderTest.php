<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreConnectorOidcBundle\Tests;

use Dbp\Relay\CoreConnectorOidcBundle\Service\AuthorizationDataProvider;
use Dbp\Relay\CoreConnectorOidcBundle\Tests\UserSession\DummyUserSessionProvider;
use PHPUnit\Framework\TestCase;

class AuthorizationDataProviderTest extends TestCase
{
    /**
     * @var AuthorizationDataProvider
     */
    private $authorizationDataProvider;

    public function testGetAvailableAttributes(): void
    {
        $this->setUpUserSession('username', []);

        $this->assertEquals(['ROLE_USER', 'ROLE_ADMIN', 'ROLE_WRITER'], $this->authorizationDataProvider->getAvailableAttributes());
    }

    public function testUserAttributesDeprecatedScopeAttribute(): void
    {
        // NOTE: user identifier is not required
        $this->setUpUserSession('username', ['__', 'user']);

        $this->assertEquals(true, $this->authorizationDataProvider->getUserAttributes('username')['ROLE_USER']);
        $this->assertEquals(false, $this->authorizationDataProvider->getUserAttributes('username')['ROLE_ADMIN']);

        $this->setUpUserSession('username', ['admin', '_', '__']);

        $this->assertEquals(false, $this->authorizationDataProvider->getUserAttributes('username')['ROLE_USER']);
        $this->assertEquals(true, $this->authorizationDataProvider->getUserAttributes('username')['ROLE_ADMIN']);
    }

    public function testUserAttributes(): void
    {
        // NOTE: user identifier is not required
        $this->setUpUserSession('username', ['foo', '__']);

        $this->assertEquals(true, $this->authorizationDataProvider->getUserAttributes('username')['ROLE_USER']);
        $this->assertEquals(false, $this->authorizationDataProvider->getUserAttributes('username')['ROLE_ADMIN']);
        $this->assertEquals(false, $this->authorizationDataProvider->getUserAttributes('username')['ROLE_WRITER']);

        $this->setUpUserSession('username', ['_', 'baz', '___']);

        $this->assertEquals(false, $this->authorizationDataProvider->getUserAttributes('username')['ROLE_USER']);
        $this->assertEquals(true, $this->authorizationDataProvider->getUserAttributes('username')['ROLE_ADMIN']);
        $this->assertEquals(true, $this->authorizationDataProvider->getUserAttributes('username')['ROLE_WRITER']);

        $this->setUpUserSession('username', ['bar']);

        $this->assertEquals(true, $this->authorizationDataProvider->getUserAttributes('username')['ROLE_USER']);
        $this->assertEquals(false, $this->authorizationDataProvider->getUserAttributes('username')['ROLE_ADMIN']);
        $this->assertEquals(true, $this->authorizationDataProvider->getUserAttributes('username')['ROLE_WRITER']);

        $this->setUpUserSession('username', ['baz', 'bar']);

        $this->assertEquals(true, $this->authorizationDataProvider->getUserAttributes('username')['ROLE_USER']);
        $this->assertEquals(true, $this->authorizationDataProvider->getUserAttributes('username')['ROLE_ADMIN']);
        $this->assertEquals(true, $this->authorizationDataProvider->getUserAttributes('username')['ROLE_WRITER']);
    }

    private function setUpUserSession(string $userId, array $scopes): void
    {
        $this->authorizationDataProvider = new AuthorizationDataProvider(new DummyUserSessionProvider($userId, $scopes));
        $this->authorizationDataProvider->setConfig(self::createAuthorizationConfig());
    }

    private static function createAuthorizationConfig(): array
    {
        return [
            'authorization_attributes' => [
                [
                    'name' => 'ROLE_USER',
                    'scope' => 'user',
                    'scopes' => ['foo', 'bar'],
                ],
                [
                    'name' => 'ROLE_ADMIN',
                    'scope' => 'admin',
                    'scopes' => ['baz'],
                ],
                [
                    'name' => 'ROLE_WRITER',
                    'scopes' => ['bar', 'baz'],
                ],
            ],
        ];
    }
}
