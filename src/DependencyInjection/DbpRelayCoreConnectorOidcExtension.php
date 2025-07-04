<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreConnectorOidcBundle\DependencyInjection;

use Dbp\Relay\CoreConnectorOidcBundle\Authenticator\BearerUserProvider;
use Dbp\Relay\CoreConnectorOidcBundle\OIDCProvider\OIDProvider;
use Dbp\Relay\CoreConnectorOidcBundle\Service\UserAttributeProvider;
use Dbp\Relay\CoreConnectorOidcBundle\UserSession\OIDCUserSessionProvider;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

class DbpRelayCoreConnectorOidcExtension extends ConfigurableExtension implements PrependExtensionInterface
{
    public function loadInternal(array $mergedConfig, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );
        $loader->load('services.yaml');

        $definition = $container->getDefinition(BearerUserProvider::class);
        $definition->addMethodCall('setConfig', [$mergedConfig]);

        $definition = $container->getDefinition(OIDProvider::class);
        $definition->addMethodCall('setConfig', [$mergedConfig]);

        $definition = $container->getDefinition(UserAttributeProvider::class);
        $definition->addMethodCall('setConfig', [$mergedConfig]);

        $definition = $container->getDefinition(OIDCUserSessionProvider::class);
        $definition->addMethodCall('setConfig', [$mergedConfig]);
    }

    public function prepend(ContainerBuilder $container): void
    {
        $config = $container->getExtensionConfig($this->getAlias())[0];
        $this->extendArrayParameter($container, 'dbp_api.twig_globals', [
            'oidc_server_url' => $config['server_url'] ?? '',
            'oidc_frontend_client_id' => $config['frontend_client_id'] ?? '',
            'keycloak_server_url' => $config['frontend_keycloak_server'] ?? '',
            'keycloak_realm' => $config['frontend_keycloak_realm'] ?? '',
            'keycloak_frontend_client_id' => $config['frontend_keycloak_client_id'] ?? '',
        ]);
    }

    private function extendArrayParameter(ContainerBuilder $container, string $parameter, array $values)
    {
        if (!$container->hasParameter($parameter)) {
            $container->setParameter($parameter, []);
        }
        $oldValues = $container->getParameter($parameter);
        $container->setParameter($parameter, array_merge($oldValues, $values));
    }
}
