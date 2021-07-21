<?php

declare(strict_types=1);

namespace DBP\API\KeycloakBundle\Tests\Keycloak;

use DBP\API\KeycloakBundle\Keycloak\KeycloakBearerUser;
use PHPUnit\Framework\TestCase;

class KeycloakBearerUserTest extends TestCase
{
    public function testRolesWithNoRealUser()
    {
        $user = new KeycloakBearerUser(null, ['foobar']);
        $this->assertSame(['foobar'], $user->getRoles());
    }

    public function testGetUserIdentifier()
    {
        $user = new KeycloakBearerUser(null, ['foobar']);
        $this->assertSame('', $user->getUserIdentifier());
        $this->assertSame('', $user->getUsername());
        $user = new KeycloakBearerUser('quux', ['foobar']);
        $this->assertSame('quux', $user->getUserIdentifier());
        $this->assertSame('quux', $user->getUsername());
    }
}
