<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreConnectorOidcBundle\UserSession;

use Dbp\Relay\CoreConnectorOidcBundle\Helpers\Tools;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * @internal
 */
class OIDCUserSessionProvider implements OIDCUserSessionProviderInterface
{
    private ?array $jwt = null;
    private array $userIdentifierClaims = [];

    public function __construct(private readonly ParameterBagInterface $parameters)
    {
    }

    public function setConfig(array $config): void
    {
        $this->userIdentifierClaims = $config['user_identifier_claims'] ?? [];
    }

    private function ensureJwt(): array
    {
        if ($this->jwt === null) {
            throw new \RuntimeException('JWT not set');
        }

        return $this->jwt;
    }

    public function getUserIdentifier(): ?string
    {
        $jwt = $this->ensureJwt();

        foreach ($this->userIdentifierClaims as $claim) {
            $value = $jwt[$claim] ?? null;
            if (is_string($value)) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Given a token returns if the token was generated through a client credential flow.
     */
    public static function isServiceAccountToken(array $jwt): bool
    {
        $scopes = Tools::extractScopes($jwt);

        // XXX: This is the main difference I found compared to other flows,
        // but that's a Keycloak implementation detail, I guess.
        return false === in_array('openid', $scopes, true);
    }

    public function setSessionToken(?array $jwt): void
    {
        $this->jwt = $jwt;
    }

    public function getScopes(): array
    {
        $jwt = $this->ensureJwt();

        return Tools::extractScopes($jwt);
    }

    public function getSessionLoggingId(): string
    {
        $unknown = 'unknown';
        if ($this->jwt === null) {
            return $unknown;
        }
        $jwt = $this->ensureJwt();

        // We want to know where the request is coming from and which requests likely belong together for debugging
        // purposes while still preserving the privacy of the user.
        // The session ID gets logged in the Keycloak event log under 'code_id' and stays the same during a login
        // session. When the event in keycloak expires it's no longer possible to map the ID to a user.
        // The keycloak client ID is in azp, so add that too, and hash it with the user ID so we get different
        // user ids for different clients for the same session.

        $client = $jwt['azp'] ?? $unknown;

        // For service accounts we don't get a session_state, so fall back to the jwt ID, so requests with the
        // same token are at least connected
        $sessionId = $jwt['session_state'] ?? $jwt['jti'] ?? null;
        if ($sessionId === null) {
            $user = $unknown;
        } else {
            $appSecret = $this->parameters->has('kernel.secret') ? $this->parameters->get('kernel.secret') : '';
            $user = substr(hash('sha256', $client.'.'.$sessionId.'.'.$appSecret), 0, 6);
        }

        return $client.'-'.$user;
    }

    public function getSessionCacheKey(): string
    {
        $jwt = $this->ensureJwt();

        return hash('sha256', $this->getUserIdentifier().'.'.json_encode($jwt));
    }

    public function getSessionTTL(): int
    {
        $jwt = $this->ensureJwt();
        if (!isset($jwt['exp']) || !isset($jwt['iat'])) {
            return -1;
        }

        return max($jwt['exp'] - $jwt['iat'], 0);
    }

    public function isServiceAccount(): bool
    {
        $jwt = $this->ensureJwt();

        return self::isServiceAccountToken($jwt);
    }

    public function getSessionCacheTTL(): int
    {
        return $this->getSessionTTL();
    }

    public function getSessionToken(): ?array
    {
        return $this->jwt;
    }
}
