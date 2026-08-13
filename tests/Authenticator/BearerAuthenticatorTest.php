<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreConnectorOidcBundle\Tests\Authenticator;

use Dbp\Relay\CoreConnectorOidcBundle\Authenticator\BearerAuthenticator;
use Dbp\Relay\CoreConnectorOidcBundle\Authenticator\BearerUser;
use Dbp\Relay\CoreConnectorOidcBundle\Tests\DummyUserProvider;
use Dbp\Relay\CoreConnectorOidcBundle\Tests\UserSession\DummyUserSessionProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

class BearerAuthenticatorTest extends TestCase
{
    public function testAuthenticateNoHeader()
    {
        $user = new BearerUser('foo', ['role']);
        $provider = new DummyUserProvider($user, 'nope');
        $auth = new BearerAuthenticator(new DummyUserSessionProvider(), $provider);

        $req = new Request();
        $this->expectException(BadCredentialsException::class);
        $auth->authenticate($req);
    }

    public function testAuthenticate()
    {
        $user = new BearerUser('foo', ['role']);
        $provider = new DummyUserProvider($user, 'nope');
        $auth = new BearerAuthenticator(new DummyUserSessionProvider(), $provider);

        // The "Bearer" scheme is case-insensitive and the separator is 1*SP
        // per RFC 6750, the token 'nope' must be extracted in every case.
        foreach (['Bearer nope', 'bearer nope', 'BEARER nope', 'Bearer   nope'] as $header) {
            $req = new Request();
            $req->headers->set('Authorization', $header);
            $passport = $auth->authenticate($req);
            $badge = $passport->getBadge(UserBadge::class);
            assert($badge instanceof UserBadge);
            $this->assertSame('foo', $badge->getUser()->getUserIdentifier());
        }
    }

    public function testSupports()
    {
        $user = new BearerUser('foo', ['role']);
        $provider = new DummyUserProvider($user, 'bar');
        $auth = new BearerAuthenticator(new DummyUserSessionProvider(), $provider);

        $this->assertFalse($auth->supports(new Request()));

        $r = new Request();
        $r->headers->set('Authorization', 'Bearer nope');
        $this->assertTrue($auth->supports($r));

        // Non-Bearer schemes (e.g. Basic auth) must not be handled by this connector
        $r = new Request();
        $r->headers->set('Authorization', 'Basic dXNlcjpwYXNz');
        $this->assertFalse($auth->supports($r));

        $r = new Request();
        $r->headers->set('Authorization', 'foobar');
        $this->assertFalse($auth->supports($r));

        // The scheme is matched case-insensitively (RFC 6749 §5.1 / RFC 5234)
        foreach (['bearer nope', 'BEARER nope', 'BeArEr nope'] as $header) {
            $r = new Request();
            $r->headers->set('Authorization', $header);
            $this->assertTrue($auth->supports($r));
        }
    }

    public function testOnAuthenticationSuccess()
    {
        $user = new BearerUser('foo', ['role']);
        $provider = new DummyUserProvider($user, 'bar');
        $auth = new BearerAuthenticator(new DummyUserSessionProvider(), $provider);
        $response = $auth->onAuthenticationSuccess(new Request(), new NullToken(), 'firewall');
        $this->assertNull($response);
    }

    public function testOnAuthenticationFailure()
    {
        $user = new BearerUser('foo', ['role']);
        $provider = new DummyUserProvider($user, 'bar');
        $auth = new BearerAuthenticator(new DummyUserSessionProvider(), $provider);
        $response = $auth->onAuthenticationFailure(new Request(), new AuthenticationException());
        $this->assertSame(401, $response->getStatusCode());
        $this->assertNotNull(json_decode($response->getContent()));
    }
}
