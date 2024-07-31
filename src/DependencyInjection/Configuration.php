<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreConnectorOidcBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public const NAME_ATTRIBUTE = 'name';
    public const SCOPE_ATTRIBUTE = 'scope';
    public const SCOPES_ATTRIBUTE = 'scopes';
    public const ATTRIBUTES_ATTRIBUTE = 'authorization_attributes';

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('dbp_core_connector_oidc');
        $treeBuilder->getRootNode()
            ->children()
                // Note: "<server_url>/.well-known/openid-configuration" has to exist
                ->scalarNode('server_url')
                    ->info('The base URL for the OIDC server (in case of Keycloak fort the specific realm)')
                    ->example('https://keycloak.example.com/auth/realms/my-realm')
                ->end()

                // Settings for token validation
                ->scalarNode('required_audience')
                    ->info('If set only tokens which contain this audience are accepted (optional)')
                    ->example('my-api')
                ->end()
                ->integerNode('local_validation_leeway')
                    ->defaultValue(120)
                    ->min(0)
                    ->info("How much the system time of the API server and the Keycloak server\ncan be out of sync (in seconds). Used for local token validation.")
                ->end()

                // Remote validation
                ->booleanNode('remote_validation')
                    ->info("If remote validation should be used. If set to false the token signature will\nbe only checked locally and not send to the keycloak server")
                    ->defaultFalse()
                ->end()
                ->scalarNode('remote_validation_id')
                    ->info("The ID of the client (client credentials flow) used for remote token validation\n(optional)")
                    ->example('client-token-check')
                ->end()
                ->scalarNode('remote_validation_secret')
                    ->info('The client secret for the client referenced by client_id (optional)')
                    ->example('mysecret')
                ->end()

                // API Frontend (API docs etc)
                ->scalarNode('frontend_client_id')
                    ->info('The client ID for the OIDC client (authorization code flow) used for API docs and other frontends provided by the API itself')
                    ->example('client-docs')
                ->end()

                // [DEPRECATED]
                ->scalarNode('frontend_keycloak_server')
                    ->setDeprecated('dbp/core-connector-oidc-bundle', '0.1.12', 'No longer needed')
                    ->info('The Keycloak server base URL')
                    ->example('https://keycloak.example.com/auth')
                ->end()
                ->scalarNode('frontend_keycloak_realm')
                    ->setDeprecated('dbp/core-connector-oidc-bundle', '0.1.12', 'No longer needed')
                    ->info('The keycloak realm')
                    ->example('client-docs')
                ->end()
                ->scalarNode('frontend_keycloak_client_id')
                    ->setDeprecated('dbp/core-connector-oidc-bundle', '0.1.12', 'Use "frontend_client_id" instead')
                    ->info('The ID for the keycloak client (authorization code flow) used for API docs or similar')
                    ->example('client-docs')
                ->end()
                ->arrayNode(self::ATTRIBUTES_ATTRIBUTE)
                    ->info('The authorization attributes that are available for users and derived from OIDC token scopes')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode(self::NAME_ATTRIBUTE)->end()
                            ->scalarNode(self::SCOPE_ATTRIBUTE)
                               ->setDeprecated('dbp/core-connector-oidc-bundle', '0.1.21', 'Use \'scopes\' instead')
                            ->end()
                            ->arrayNode(self::SCOPES_ATTRIBUTE)
                               ->info('If the user\'s token contains any of the listed scopes, the user is granted the respective authorization attribute, i.e. its value evaluates to \'true\' if requested')
                               ->scalarPrototype()->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
