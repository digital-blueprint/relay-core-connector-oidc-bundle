# Configuration

## Recipe

The default [Symfony recipe](https://github.com/digital-blueprint/symfony-recipes/tree/main/dbp/relay-core-connector-oidc-bundle)
creates a minimal configuration using two environment variables, which you have to fill out:

* `AUTH_SERVER_URL`: The URL to the OIDC server (or in case of Keycloak to the realm on the server)
* `AUTH_FRONTEND_CLIENT_ID`: The client ID for the API documentation page

## Bundle Configuration

created via `./bin/console config:dump-reference DbpRelayCoreConnectorOidcBundle | sed '/^$/d'`

```yaml
# Default configuration for "DbpRelayCoreConnectorOidcBundle"
dbp_relay_core_connector_oidc:
  # The base URL for the OIDC server (in case of Keycloak for the specific realm)
  server_url:           ~ # Example: 'https://keycloak.example.com/auth/realms/my-realm'
  # If set only tokens which contain this audience are accepted (optional)
  required_audience:    ~ # Example: my-api
  # How much the system time of the API server and the Keycloak server
  # can be out of sync (in seconds). Used for local token validation.
  local_validation_leeway: 120
  # If remote validation should be used. If set to false the token signature will
  # be only checked locally and not send to the keycloak server
  remote_validation:    false
  # The ID of the client (client credentials flow) used for remote token validation
  # (optional)
  remote_validation_id: ~ # Example: client-token-check
  # The client secret for the client referenced by client_id (optional)
  remote_validation_secret: ~ # Example: mysecret
  # The client ID for the OIDC client (authorization code flow) used for API docs and other frontends provided by the API itself
  frontend_client_id:   ~ # Example: client-docs
  # The claims used for the user identifier. The first claim found in the token will be used.
  user_identifier_claims:
    # Defaults:
    - preferred_username
    - username
  # Convert the token scopes to Symfony roles and set them on the user.
  # By default, scopes will be converted to upper-case and prefixed with 'ROLE_SCOPE_',
  # so 'some-scope' will result in the 'ROLE_SCOPE_SOME_SCOPE' Symfony role being set.
  set_symfony_roles_from_scopes: true
  # The authorization attributes that are available for users and derived from OIDC token scopes
  authorization_attributes:
    # Prototype
    -
      name:                 ~
      # If the user's token contains any of the listed scopes, the user is granted the respective authorization attribute, i.e. its value evaluates to 'true' if requested
      scopes:               []

```

## Configuration Discovery

The OIDC bundle requires for the OIDC server to implement [OpenID Connect
Discovery](https://openid.net/specs/openid-connect-discovery-1_0.html) as well
as the metadata defined in the [OAuth 2.0 Authorization Server
Metadata](https://datatracker.ietf.org/doc/html/rfc8414).

Example: https://auth-demo.tugraz.at/auth/realms/tugraz-vpu/.well-known/openid-configuration


## Token Validation Modes

There are two modes of operation:

* **Local validation** (default): The bundle fetches (and caches) the public
  singing key from the OIDC server and verifies the access token signature (and
  all other properties like expiration dates) in process. The has the upside of
  being a fast, but has the downside of not taking token revocation into
  account, so should only be used if the access tokens lifetime isn't too long.
  Another extra requirement is that the system clock of the gateway and the OIDC
  server shouldn't deviate too much to avoid valid tokens being marked as
  expired or not valid yet.

* **Remote validation**: The bundle passes the access token to the OIDC server
  introspection endpoint for each request. This adds overhead to each request but
  everything is handled by the OIDC server.


## Remote Validation Client with Keycloak

To create a client which can validate/introspect tokens in Keycloak create a
new client with an ID of your choosing:

* Switch the "Access Type" to confidential
* Enable "Service Accounts Enabled"

* `remote_validation_id` is the "Client ID" of the client visible on the "Settings" page
* `remote_validation_secret` is the "Secret" of the client visible on the "Credentials" page

## User Identifier Selection

The list of claims specified in `user_identifier_claims` will be tried in the
given order. The first one that exists in the OIDC token will be used as the
user identifier. If none of the claims can be found then the user will be
authenticated but without a user ID.

You need to make sure that the used claim is unique across all possible users
and clients and that it is not possible to change it by the user. Otherwise
users could impersonate each other.

Possible usage scenarios:

* The defaults `['preferred_username', 'username']` assumes that the OIDC
  provider is used with user federation, for example when the users are managed
  provided by an LDAP server and the LDAP username is mapped into the token to
  `preferred_username` or `username`.

* In case the OIDC provider is the source of truth for the users then you could
  set it to `['sub']` to use the OIDC user ID directly.

* In case you have an OIDC system with service accounts that don't have a user
  ID you could set it to `['azp']` or append it as a fallback, so that service
  accounts get a user ID based on the client ID.

## Authorization Attributes

`authorization_attributes` allows you to set authorization attributes based on
the scopes present in the OIDC token. If the user's token contains any of the
listed scopes, the user is granted the respective authorization attribute, i.e.
its value evaluates to 'true' if requested.

For example, if the user's token contains the scope `admin`, the user is granted
the authorization attribute `admin`.

```yaml
authorization_attributes:
  - name: 'admin'
    scopes: ['admin']
```