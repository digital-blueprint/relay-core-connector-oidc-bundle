<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreConnectorOidcBundle\Authenticator;

use Dbp\Relay\CoreConnectorOidcBundle\UserSession\OIDCUserSessionProviderInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

/**
 * @internal
 */
class BearerAuthenticator extends AbstractAuthenticator implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly OIDCUserSessionProviderInterface $userSessionProvider,
        private readonly BearerUserProviderInterface $userProvider)
    {
    }

    public function supports(Request $request): ?bool
    {
        return $request->headers->has('Authorization');
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_UNAUTHORIZED);
    }

    public function authenticate(Request $request): Passport
    {
        $auth = $request->headers->get('Authorization', '');
        if ($auth === '') {
            throw new BadCredentialsException('Token is not present in the request headers');
        }

        $token = trim(preg_replace('/^(?:\s+)?Bearer\s/', '', $auth));

        $user = $this->userProvider->loadUserByToken($token);

        $passport = new SelfValidatingPassport(new UserBadge($user->getUserIdentifier(), function ($token) use ($user) {
            return $user;
        }));
        $passport->setAttribute('relay_user_session_provider', $this->userSessionProvider);

        return $passport;
    }
}
