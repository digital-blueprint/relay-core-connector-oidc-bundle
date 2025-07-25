<?php

declare(strict_types=1);

namespace Dbp\Relay\CoreConnectorOidcBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public const NAME_NODE = 'name';
    public const SCOPES_NODE = 'scopes';
    public const CLAIM_NODE = 'claim';
    public const ATTRIBUTES_NODE = 'authorization_attributes';

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('dbp_relay_core_connector_oidc');
        $treeBuilder->getRootNode()
            ->children()
                // Note: "<server_url>/.well-known/openid-configuration" has to exist
                ->scalarNode('server_url')
                    ->info('The base URL for the OIDC server (in case of Keycloak for the specific realm)')
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

                // Advanced settings
                ->arrayNode('user_identifier_claims')
                    ->info('The claims used for the user identifier. The first claim found in the token will be used.')
                    ->scalarPrototype()->end()
                    ->defaultValue([
                        'preferred_username',
                        'username',
                    ])
                ->end()
                ->booleanNode('set_symfony_roles_from_scopes')
                    ->info("Convert the token scopes to Symfony roles and set them on the user.\nBy default, scopes will be converted to upper-case and prefixed with 'ROLE_SCOPE_',\nso 'some-scope' will result in the 'ROLE_SCOPE_SOME_SCOPE' Symfony role being set.")
                    ->defaultTrue()
                ->end()

                // [DEPRECATED]
                ->scalarNode('frontend_keycloak_server')
                    ->setDeprecated('dbp/relay-core-connector-oidc-bundle', '0.1.12', 'No longer needed')
                    ->info('The Keycloak server base URL')
                    ->example('https://keycloak.example.com/auth')
                ->end()
                ->scalarNode('frontend_keycloak_realm')
                    ->setDeprecated('dbp/relay-core-connector-oidc-bundle', '0.1.12', 'No longer needed')
                    ->info('The keycloak realm')
                    ->example('client-docs')
                ->end()
                ->scalarNode('frontend_keycloak_client_id')
                    ->setDeprecated('dbp/relay-core-connector-oidc-bundle', '0.1.12', 'Use "frontend_client_id" instead')
                    ->info('The ID for the keycloak client (authorization code flow) used for API docs or similar')
                    ->example('client-docs')
                ->end()
                ->arrayNode(self::ATTRIBUTES_NODE)
                    ->info('The authorization attributes that are available for users and derived from OIDC token scopes')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode(self::NAME_NODE)->end()
                            ->arrayNode(self::SCOPES_NODE)
                               ->info('If the user\'s token contains any of the listed scopes, the user is granted the respective authorization attribute, i.e. its value evaluates to \'true\' if requested')
                               ->scalarPrototype()->end()
                            ->end()
                            ->scalarNode(self::CLAIM_NODE)
                            ->end()
                        ->end()
                        ->validate()
                            ->ifTrue(function ($node) {
                                return [] !== ($node[self::SCOPES_NODE] ?? []) && '' !== ($node[self::CLAIM_NODE] ?? '');
                            })
                            ->thenInvalid('Please only specify either the \''.self::SCOPES_NODE.'\' or the \''.self::CLAIM_NODE.'\' for user attributes')
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
