<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreConnectorOidcBundle\Authenticator;

use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @internal
 */
readonly class BearerUser implements UserInterface
{
    public function __construct(
        private ?string $identifier,
        private array $rolesDeprecated)
    {
    }

    public function getRoles(): array
    {
        return $this->rolesDeprecated;
    }

    public function getPassword(): ?string
    {
        return null;
    }

    public function getSalt(): ?string
    {
        return null;
    }

    public function getUsername(): string
    {
        return $this->getUserIdentifier();
    }

    public function getUserIdentifier(): string
    {
        return $this->identifier ?? '';
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
    }
}
