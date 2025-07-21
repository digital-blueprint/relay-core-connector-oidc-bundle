<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreConnectorOidcBundle\UserSession;

use Dbp\Relay\CoreBundle\API\UserSessionProviderInterface;

/**
 * @internal
 */
interface OIDCUserSessionProviderInterface extends UserSessionProviderInterface
{
    public function setSessionToken(?array $jwt): void;

    public function getSessionToken(): ?array;

    public function getScopes(): array;
}
