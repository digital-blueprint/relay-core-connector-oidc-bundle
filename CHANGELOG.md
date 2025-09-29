# Changelog

## Unreleased

## v0.1.46

- Add support for kevinrob/guzzle-cache-middleware v7

## v0.1.45

- Modernize PHP code
- Allow for the mapping of token claims to authz user attributes

## v0.1.44

- Import `services_test.yaml` from the core bundle for testing

## v0.1.43

- Update core and adapt

## v0.1.42

- Add support for web-token/jwt-library v4

## v0.1.41

- Drop support for PHP 8.1
- Drop support for Symfony 5
- Drop support for Psalm

## v0.1.40

- config: add new `set_symfony_roles_from_scopes` option, to allow disabling the default scope to role mapping.

## v0.1.39

- config: removed support for the deprecated authorization_attributes.scope key, use authorization_attributes.scopes instead.
- Add support for kevinrob/guzzle-cache-middleware v6
- Various docs updates
- Test with PHP 8.4
- Port to phpstan v2

## v0.1.38

- Add support for the following JWT signing algorithms: ES256, ES384, ES512, HS256, HS384, HS512, EdDSA
  (previously only RS256, RS384, RS512, PS256, PS384, PS512 were supported)

## v0.1.37

- Fix attribute access in case the user has no user ID.

## v0.1.35

- Fix user attribute provider in case it is called outside of a request context,
  for example in health checks.

## v0.1.34

- Add new optional `user_identifier_claims` bundle config which allows users to
  choose which claims to use as the user identifier. The default is the same as
  before.
- Provide a session ID spanning the lifetime of the token for service accounts
  instead of a random one for each request.
- Adjust for core bundle API breakage in v0.1.180

## v0.1.33

- Minor cleanups
- Add a conflict with dbp/relay-auth-bundle

## v0.1.32

- Renamed the bundle from "auth-bundle" to "core-connector-oidc-bundle"

Migration guide:

- Replace the bundle in your Symfony app:
   - `mv config/packages/dbp_relay_auth.yaml temp.yaml`
   - `composer remove dbp/relay-auth-bundle`
   - `mv temp.yaml config/packages/dbp_relay_core_connector_oidc.yaml`
   - `sed -i 's/dbp_relay_auth/dbp_relay_core_connector_oidc/g' config/packages/dbp_relay_core_connector_oidc.yaml`
   - `composer require dbp/relay-core-connector-oidc-bundle`
- Replace usage of `Dbp\Relay\AuthBundle\API\UserRolesInterface` in your code or services config with `Dbp\Relay\CoreConnectorOidcBundle\API\UserRolesInterface` (only if you used that interface)

## v0.1.31

- Minor cleanup of the codebase

## v0.1.30

- Return 401 instead of 403 on authentication failure

## v0.1.29

- Port to PHPUnit 10

## v0.1.28

- user-roles-cache: properly escape the cache key to avoid Symfony erroring out on special keys

## v0.1.27

- Support symfony/cache-contracts v3

## v0.1.26

- Add support for Symfony 6

## v0.1.25

- dev: replace abandoned composer-git-hooks with captainhook.
  Run `vendor/bin/captainhook install -f` to replace the old hooks with the new ones
  on an existing checkout.

## v0.1.24

- Port from web-token/jwt-core 2.0 to web-token/jwt-library 3.3

## v0.1.23

- Drop support for PHP 7.4/8.0

## v0.1.22

- Drop support for PHP 7.3

## v0.1.20

- Add some more unit tests
- Removal of some deprecated API usages

## v0.1.19

- Add support for kevinrob/guzzle-cache-middleware v5

## v0.1.18

- Add caching for roles fetched via UserRolesInterface

## v0.1.17

- Use the global "cache.app" adapter for caching instead of always using the filesystem adapter

## v0.1.16

- Move to GitHub

## v0.1.15

- Fix tests with newer core bundle versions

## v0.1.12 - 2022-11-15

- Added new `frontend_client_id` config entry as a replacement for `frontend_keycloak_client_id`
- Deprecated config entries: `frontend_keycloak_server`, `frontend_keycloak_realm`, `frontend_keycloak_client_id`

## v0.1.9 - 2022-05-11

- Add a health check for remote token validation via the introspection endpoint

## v0.1.8 - 2022-05-09

- Add a health check for fetching the OIDC config provided by the OIDC server
  (Keycloak for example)
- Add a health check which checks if the server time is in sync with the OIDC
  server time
- Stop using the abandoned web-token/jwt-easy and use to the underlying
  libraries directly instead, as recommended
