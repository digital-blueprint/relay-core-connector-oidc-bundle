# Overview

Source: https://github.com/digital-blueprint/relay-core-connector-oidc-bundle

The OIDC bundle connects the core bundle with an OIDC server. For each request
it validates the passed access token, creates a Symfony user and assigns Symfony
roles to that user.

```mermaid
graph LR
    style core_connector_oidc_bundle fill:#606096,color:#fff

    oidc_server("OIDC Server")

    subgraph API Gateway
        api(("API"))
        core_bundle("Core Bundle")
        core_connector_oidc_bundle("OIDC Bundle")
    end

    api --> core_bundle
    core_bundle --> core_connector_oidc_bundle
    core_connector_oidc_bundle --> core_bundle
    core_connector_oidc_bundle --> oidc_server
```

## Documentation

* [Configuration](./config.md)
* [Custom Roles Mapping](./roles.md)
