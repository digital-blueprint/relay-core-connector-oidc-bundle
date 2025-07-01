<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreConnectorOidcBundle\Tests;

use Dbp\Relay\CoreConnectorOidcBundle\Service\UserAttributeProvider;
use Dbp\Relay\CoreConnectorOidcBundle\Tests\UserSession\DummyUserSessionProvider;
use PHPUnit\Framework\TestCase;

class UserAttributeProviderTest extends TestCase
{
    private UserAttributeProvider $authorizationDataProvider;

    public function testGetAvailableAttributes(): void
    {
        $this->setUpUserSession([]);

        $this->assertEquals(['ROLE_USER', 'ROLE_ADMIN', 'ROLE_WRITER'], $this->authorizationDataProvider->getAvailableAttributes());
    }

    public function testUserAttributes(): void
    {
        // NOTE: user identifier is not required
        $this->setUpUserSession(['foo', '__']);

        $this->assertEquals(true, $this->authorizationDataProvider->getUserAttribute('username', 'ROLE_USER'));
        $this->assertEquals(false, $this->authorizationDataProvider->getUserAttribute('username', 'ROLE_ADMIN'));
        $this->assertEquals(false, $this->authorizationDataProvider->getUserAttribute('username', 'ROLE_WRITER'));

        $this->setUpUserSession(['_', 'baz', '___']);

        $this->assertEquals(false, $this->authorizationDataProvider->getUserAttribute('username', 'ROLE_USER'));
        $this->assertEquals(true, $this->authorizationDataProvider->getUserAttribute('username', 'ROLE_ADMIN'));
        $this->assertEquals(true, $this->authorizationDataProvider->getUserAttribute('username', 'ROLE_WRITER'));

        $this->setUpUserSession(['bar']);

        $this->assertEquals(true, $this->authorizationDataProvider->getUserAttribute('username', 'ROLE_USER'));
        $this->assertEquals(false, $this->authorizationDataProvider->getUserAttribute('username', 'ROLE_ADMIN'));
        $this->assertEquals(true, $this->authorizationDataProvider->getUserAttribute('username', 'ROLE_WRITER'));

        $this->setUpUserSession(['baz', 'bar']);

        $this->assertEquals(true, $this->authorizationDataProvider->getUserAttribute('username', 'ROLE_USER'));
        $this->assertEquals(true, $this->authorizationDataProvider->getUserAttribute('username', 'ROLE_ADMIN'));
        $this->assertEquals(true, $this->authorizationDataProvider->getUserAttribute('username', 'ROLE_WRITER'));
    }

    private function setUpUserSession(array $scopes): void
    {
        $this->authorizationDataProvider = new UserAttributeProvider(new DummyUserSessionProvider('username', $scopes));
        $this->authorizationDataProvider->setConfig(self::createAuthorizationConfig());
    }

    private static function createAuthorizationConfig(): array
    {
        return [
            'authorization_attributes' => [
                [
                    'name' => 'ROLE_USER',
                    'scopes' => ['foo', 'bar'],
                ],
                [
                    'name' => 'ROLE_ADMIN',
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
