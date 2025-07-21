<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreConnectorOidcBundle\Tests\UserSession;

use Dbp\Relay\CoreConnectorOidcBundle\Helpers\Tools;
use Dbp\Relay\CoreConnectorOidcBundle\UserSession\OIDCUserSessionProviderInterface;

class DummyUserSessionProvider implements OIDCUserSessionProviderInterface
{
    public function __construct(
        private readonly ?string $userIdentifier = 'id',
        private ?array $jwt = [])
    {
    }

    public function setSessionToken(?array $jwt): void
    {
        $this->jwt = $jwt;
    }

    public function getSessionToken(): ?array
    {
        return $this->jwt;
    }

    public function getScopes(): array
    {
        return Tools::extractScopes($this->jwt);
    }

    public function getUserIdentifier(): ?string
    {
        return $this->userIdentifier;
    }

    public function getSessionLoggingId(): ?string
    {
        return 'logging-id';
    }

    public function getSessionCacheKey(): ?string
    {
        return 'cache';
    }

    public function getSessionCacheTTL(): int
    {
        return 42;
    }

    public function getSessionTTL(): int
    {
        return 42;
    }

    public function isServiceAccount(): bool
    {
        return false;
    }
}
