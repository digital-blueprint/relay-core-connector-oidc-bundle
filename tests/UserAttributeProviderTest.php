<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreConnectorOidcBundle\Tests;

use Dbp\Relay\CoreBundle\User\UserAttributeException;
use Dbp\Relay\CoreConnectorOidcBundle\Service\UserAttributeProvider;
use Dbp\Relay\CoreConnectorOidcBundle\Tests\UserSession\DummyUserSessionProvider;
use PHPUnit\Framework\TestCase;

class UserAttributeProviderTest extends TestCase
{
    private ?UserAttributeProvider $authorizationDataProvider = null;

    public function testHasAttribute(): void
    {
        $this->setUpUserSession();
        $this->assertTrue($this->authorizationDataProvider->hasUserAttribute('ROLE_USER'));
        $this->assertTrue($this->authorizationDataProvider->hasUserAttribute('ROLE_ADMIN'));
        $this->assertTrue($this->authorizationDataProvider->hasUserAttribute('ROLE_WRITER'));
        $this->assertTrue($this->authorizationDataProvider->hasUserAttribute('USERNAME'));
        $this->assertTrue($this->authorizationDataProvider->hasUserAttribute('EMAIL'));
        $this->assertFalse($this->authorizationDataProvider->hasUserAttribute('ROLE_FOO'));
    }

    /**
     * @throws UserAttributeException
     */
    public function testScopeUserAttributes(): void
    {
        // NOTE: user identifier is not required (ignored)
        $this->setUpUserSession(['foo', '__']);

        $this->assertTrue($this->authorizationDataProvider->getUserAttribute('testuser', 'ROLE_USER'));
        $this->assertFalse($this->authorizationDataProvider->getUserAttribute('testuser', 'ROLE_ADMIN'));
        $this->assertFalse($this->authorizationDataProvider->getUserAttribute('testuser', 'ROLE_WRITER'));

        $this->setUpUserSession(['_', 'baz', '___']);

        $this->assertFalse($this->authorizationDataProvider->getUserAttribute('testuser', 'ROLE_USER'));
        $this->assertTrue($this->authorizationDataProvider->getUserAttribute('testuser', 'ROLE_ADMIN'));
        $this->assertTrue($this->authorizationDataProvider->getUserAttribute('testuser', 'ROLE_WRITER'));

        $this->setUpUserSession(['bar']);

        $this->assertTrue($this->authorizationDataProvider->getUserAttribute('testuser', 'ROLE_USER'));
        $this->assertFalse($this->authorizationDataProvider->getUserAttribute('testuser', 'ROLE_ADMIN'));
        $this->assertTrue($this->authorizationDataProvider->getUserAttribute('testuser', 'ROLE_WRITER'));

        $this->setUpUserSession(['baz', 'bar']);

        $this->assertTrue($this->authorizationDataProvider->getUserAttribute('testuser', 'ROLE_USER'));
        $this->assertTrue($this->authorizationDataProvider->getUserAttribute('testuser', 'ROLE_ADMIN'));
        $this->assertTrue($this->authorizationDataProvider->getUserAttribute('testuser', 'ROLE_WRITER'));
    }

    /**
     * @throws UserAttributeException
     */
    public function testClaimUserAttributes(): void
    {
        $this->setUpUserSession();
        $this->assertEquals('testuser', $this->authorizationDataProvider->getUserAttribute('testuser', 'USERNAME'));
        $this->assertEquals('test@test.com', $this->authorizationDataProvider->getUserAttribute('testuser', 'EMAIL'));
    }

    private function setUpUserSession(array $scopes = ['foo']): void
    {
        $claims = ['username' => 'testuser', 'email' => 'test@test.com'];
        $claims['scope'] = implode(' ', $scopes);

        $this->authorizationDataProvider = new UserAttributeProvider(new DummyUserSessionProvider('testuser', $claims));
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
                [
                    'name' => 'USERNAME',
                    'claim' => 'username',
                ],
                [
                    'name' => 'EMAIL',
                    'claim' => 'email',
                ],
            ],
        ];
    }
}
