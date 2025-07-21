<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreConnectorOidcBundle\Authenticator;

use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @internal
 */
interface BearerUserProviderInterface
{
    public function loadUserByToken(string $accessToken): UserInterface;

    public function loadUserByValidatedToken(array $jwt): UserInterface;
}
