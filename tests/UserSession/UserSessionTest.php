<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreConnectorOidcBundle\Tests\UserSession;

use Dbp\Relay\CoreConnectorOidcBundle\UserSession\OIDCUserSessionProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class UserSessionTest extends TestCase
{
    public function testIsServiceAccountToken()
    {
        $this->assertTrue(OIDCUserSessionProvider::isServiceAccountToken(['scope' => 'foo bar']));
        $this->assertFalse(OIDCUserSessionProvider::isServiceAccountToken(['scope' => 'openid foo bar']));
        $this->assertFalse(OIDCUserSessionProvider::isServiceAccountToken(['scope' => 'openid']));
        $this->assertFalse(OIDCUserSessionProvider::isServiceAccountToken(['scope' => 'foo openid bar']));
        $this->assertFalse(OIDCUserSessionProvider::isServiceAccountToken(['scope' => 'foo bar openid']));
    }

    public function testGetLoggingId()
    {
        $session = new OIDCUserSessionProvider(new ParameterBag());

        $session->setSessionToken([]);
        $this->assertSame('unknown-unknown', $session->getSessionLoggingId());
        $session->setSessionToken(['azp' => 'clientA', 'session_state' => 'state']);
        $this->assertSame('clientA-22957c', $session->getSessionLoggingId());
        $session->setSessionToken(['azp' => 'clientA', 'jti' => 'some-id']);
        $this->assertSame('clientA-6a96a9', $session->getSessionLoggingId());
        $session->setSessionToken(['azp' => 'clientA']);
        $this->assertSame('clientA-unknown', $session->getSessionLoggingId());
    }

    public function testGetSessionCacheKey()
    {
        $session = new OIDCUserSessionProvider(new ParameterBag());
        $session->setSessionToken(['scope' => 'foo']);
        $old = $session->getSessionCacheKey();
        $session->setSessionToken(['scope' => 'bar']);
        $new = $session->getSessionCacheKey();
        $this->assertNotSame($old, $new);
    }

    public function testGetSessionTTL()
    {
        $session = new OIDCUserSessionProvider(new ParameterBag());
        $session->setSessionToken([]);
        $this->assertSame(-1, $session->getSessionTTL());

        $session->setSessionToken(['exp' => 42, 'iat' => 24]);
        $this->assertSame(18, $session->getSessionTTL());
    }

    public function testUserIdClaims()
    {
        $session = new OIDCUserSessionProvider(new ParameterBag());
        $session->setSessionToken(['scope' => 'openid', 'something' => 'foo', 'username' => null]);
        $this->assertSame(null, $session->getUserIdentifier());
        $session->setConfig(['user_identifier_claims' => ['something']]);
        $this->assertSame('foo', $session->getUserIdentifier());
        $session->setConfig(['user_identifier_claims' => ['username', 'something']]);
        $this->assertSame('foo', $session->getUserIdentifier());
        $session->setConfig(['user_identifier_claims' => ['username']]);
        $this->assertSame(null, $session->getUserIdentifier());
        $session->setSessionToken(['azp' => 'client-1']);
        $session->setConfig(['user_identifier_claims' => ['azp']]);
        $this->assertSame('client-1', $session->getUserIdentifier());
    }

    public function testIsServiceAccount()
    {
        $session = new OIDCUserSessionProvider(new ParameterBag());
        $session->setSessionToken(['scope' => 'openid something']);
        $this->assertFalse($session->isServiceAccount());
        $session->setSessionToken(['scope' => 'something']);
        $this->assertTrue($session->isServiceAccount());
    }
}
