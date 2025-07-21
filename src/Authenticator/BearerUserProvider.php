<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreConnectorOidcBundle\Authenticator;

use Dbp\Relay\CoreConnectorOidcBundle\API\UserRolesInterface;
use Dbp\Relay\CoreConnectorOidcBundle\Helpers\Tools;
use Dbp\Relay\CoreConnectorOidcBundle\OIDCProvider\OIDProvider;
use Dbp\Relay\CoreConnectorOidcBundle\TokenValidator\LocalTokenValidator;
use Dbp\Relay\CoreConnectorOidcBundle\TokenValidator\RemoteTokenValidator;
use Dbp\Relay\CoreConnectorOidcBundle\TokenValidator\TokenValidationException;
use Dbp\Relay\CoreConnectorOidcBundle\UserSession\OIDCUserSessionProviderInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * @internal
 */
class BearerUserProvider implements BearerUserProviderInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private array $config = [];
    private CacheInterface $cachePool;

    public function __construct(
        private readonly OIDCUserSessionProviderInterface $userSessionProvider,
        private readonly OIDProvider $oidProvider,
        private readonly UserRolesInterface $userRoles)
    {
        $this->cachePool = new ArrayAdapter();
    }

    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

    public function getValidationLeewaySeconds(): int
    {
        $config = $this->config;

        return $config['local_validation_leeway'];
    }

    public function usesRemoteValidation(): bool
    {
        return $this->config['remote_validation'];
    }

    public function setCache(CacheInterface $cachePool)
    {
        $this->cachePool = $cachePool;
    }

    public function loadUserByToken(string $accessToken): UserInterface
    {
        $config = $this->config;
        if (!$this->usesRemoteValidation()) {
            $leeway = $config['local_validation_leeway'];
            $validator = new LocalTokenValidator($this->oidProvider, $leeway);
        } else {
            $validator = new RemoteTokenValidator($this->oidProvider);
        }
        if ($this->logger !== null) {
            $validator->setLogger($this->logger);
        }

        try {
            $jwt = $validator->validate($accessToken);
        } catch (TokenValidationException $e) {
            $this->logger->info('Invalid token:', ['exception' => $e]);
            throw new AuthenticationException('Invalid token');
        }

        if (($config['required_audience'] ?? '') !== '') {
            try {
                $validator::checkAudience($jwt, $config['required_audience']);
            } catch (TokenValidationException $e) {
                $this->logger->info('Invalid audience:', ['exception' => $e]);
                throw new AuthenticationException('Invalid token audience');
            }
        }

        return $this->loadUserByValidatedToken($jwt);
    }

    /**
     * @return string[]
     */
    private function getSymfonyRolesFromScopes(?string $userIdentifier, array $scopes): array
    {
        $symfonyRoles = [];
        if ($this->config['set_symfony_roles_from_scopes']) {
            try {
                $cacheKey = Tools::escapeCacheKey(json_encode(
                    [$this->userSessionProvider->getSessionCacheKey(), $userIdentifier, $scopes],
                    JSON_THROW_ON_ERROR));

                $symfonyRoles = $this->cachePool->get($cacheKey,
                    function (ItemInterface $item) use ($scopes, $userIdentifier): array {
                        $item->expiresAfter($this->userSessionProvider->getSessionCacheTTL());

                        return $this->userRoles->getRoles($userIdentifier, $scopes);
                    });
            } catch (\Throwable) {
                throw new \RuntimeException('failed to set symfony roles from scopes');
            }
        }

        return $symfonyRoles;
    }

    public function loadUserByValidatedToken(array $jwt): UserInterface
    {
        $this->userSessionProvider->setSessionToken($jwt);
        $identifier = $this->userSessionProvider->getUserIdentifier();

        return new BearerUser(
            $identifier,
            $this->getSymfonyRolesFromScopes($identifier, Tools::extractScopes($jwt))
        );
    }
}
