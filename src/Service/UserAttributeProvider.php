<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreConnectorOidcBundle\Service;

use Dbp\Relay\CoreBundle\User\UserAttributeProviderInterface;
use Dbp\Relay\CoreConnectorOidcBundle\DependencyInjection\Configuration;
use Dbp\Relay\CoreConnectorOidcBundle\UserSession\OIDCUserSessionProviderInterface;

class UserAttributeProvider implements UserAttributeProviderInterface
{
    /** @var string[][] */
    private array $attributeToScopeMap = [];

    public function __construct(private readonly OIDCUserSessionProviderInterface $userSessionProvider)
    {
    }

    public function setConfig(array $config): void
    {
        $this->loadAttributeToScopeMapFromConfig($config[Configuration::ATTRIBUTES_ATTRIBUTE]);
    }

    public function hasUserAttribute(string $name): bool
    {
        return array_key_exists($name, $this->attributeToScopeMap);
    }

    public function getUserAttribute(?string $userIdentifier, string $name): mixed
    {
        $userScopes = [];
        if ($this->userSessionProvider->getSessionToken() !== null
            && $this->userSessionProvider->getUserIdentifier() === $userIdentifier) {
            $userScopes = $this->userSessionProvider->getScopes();
        }

        foreach ($this->attributeToScopeMap[$name] ?? [] as $scope) {
            if (in_array($scope, $userScopes, true)) {
                return true;
            }
        }

        return false;
    }

    public function getAvailableAttributes(): array
    {
        return array_keys($this->attributeToScopeMap);
    }

    private function loadAttributeToScopeMapFromConfig(array $attributes): void
    {
        foreach ($attributes as $attribute) {
            $scopes = $attribute[Configuration::SCOPES_ATTRIBUTE] ?? [];
            $this->attributeToScopeMap[$attribute[Configuration::NAME_ATTRIBUTE]] = $scopes;
        }
    }
}
