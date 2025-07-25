<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreConnectorOidcBundle\TokenValidator;

use Dbp\Relay\CoreConnectorOidcBundle\OIDCProvider\OIDError;
use Dbp\Relay\CoreConnectorOidcBundle\OIDCProvider\OIDProvider;

/**
 * @internal
 */
class RemoteTokenValidator extends TokenValidatorBase
{
    public function __construct(
        private readonly OIDProvider $oidProvider)
    {
    }

    /**
     * Validates the token with the Keycloak introspection endpoint.
     *
     * @return array the token
     *
     * @throws TokenValidationException
     */
    public function validate(string $accessToken): array
    {
        try {
            $jwt = $this->oidProvider->introspectToken($accessToken);
        } catch (OIDError $e) {
            throw new TokenValidationException('Introspection failed: '.$e->getMessage());
        }

        if (!$jwt['active']) {
            throw new TokenValidationException('The token does not exist or is not valid anymore');
        }

        return $jwt;
    }
}
