<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreConnectorOidcBundle\Service;

use Dbp\Relay\CoreBundle\User\UserAttributeProviderInterface;
use Dbp\Relay\CoreConnectorOidcBundle\DependencyInjection\Configuration;
use Dbp\Relay\CoreConnectorOidcBundle\UserSession\OIDCUserSessionProviderInterface;

/**
 * @internal
 */
class UserAttributeProvider implements UserAttributeProviderInterface
{
    private const SCOPE_ATTRIBUTE = 'scopes';
    private const CLAIM_ATTRIBUTE = 'claim';

    /** @var array[] */
    private array $userAttributesConfig = [];

    public function __construct(private readonly OIDCUserSessionProviderInterface $userSessionProvider)
    {
    }

    public function setConfig(array $config): void
    {
        $this->loadUserAttributesConfig($config[Configuration::ATTRIBUTES_NODE]);
    }

    public function hasUserAttribute(string $name): bool
    {
        return array_key_exists($name, $this->userAttributesConfig);
    }

    public function getUserAttribute(?string $userIdentifier, string $name): mixed
    {
        $userScopes = [];
        if ($this->userSessionProvider->getSessionToken() !== null
            && $this->userSessionProvider->getUserIdentifier() === $userIdentifier) {
            $userScopes = $this->userSessionProvider->getScopes();
        }

        $userAttributeConfig = $this->userAttributesConfig[$name] ?? [];
        if ([] !== $userAttributeConfig[self::SCOPE_ATTRIBUTE]) {
            return array_intersect($userScopes, $userAttributeConfig[self::SCOPE_ATTRIBUTE]) !== [];
        } elseif (($claim = $userAttributeConfig[self::CLAIM_ATTRIBUTE])
            && ($sessionToken = $this->userSessionProvider->getSessionToken())) {
            return $sessionToken[$claim] ?? null;
        }

        return null;
    }

    private function loadUserAttributesConfig(array $attributesConfig): void
    {
        foreach ($attributesConfig as $attributeConfig) {
            $scopes = $attributeConfig[Configuration::SCOPES_NODE] ?? [];
            $claim = $attributeConfig[Configuration::CLAIM_NODE] ?? null;
            $this->userAttributesConfig[$attributeConfig[Configuration::NAME_NODE]] = [
                self::SCOPE_ATTRIBUTE => $scopes,
                self::CLAIM_ATTRIBUTE => $claim,
            ];
        }
    }
}
