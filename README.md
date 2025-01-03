# oxid-esales/graphql-base

[![Build Status](https://img.shields.io/github/workflow/status/OXID-eSales/graphql-base-module/CI?logo=github-actions&style=for-the-badge)](https://github.com/OXID-eSales/graphql-base-module/actions)
[![Build
Status](https://img.shields.io/sonar/quality_gate/OXID-eSales_graphql-base-module?server=https%3A%2F%2Fsonarcloud.io&style=for-the-badge&logo=sonarcloud)](https://sonarcloud.io/dashboard?id=OXID-eSales_graphql-base-module)

[![Stable Version](https://img.shields.io/packagist/v/OXID-eSales/graphql-base?style=for-the-badge&logo=composer&label=stable)](https://packagist.org/packages/oxid-esales/graphql-base)
[![Latest Version](https://img.shields.io/packagist/v/OXID-eSales/graphql-base?style=for-the-badge&logo=composer&label=latest&include_prereleases&color=orange)](https://packagist.org/packages/oxid-esales/graphql-base)
[![PHP Version](https://img.shields.io/packagist/php-v/oxid-esales/graphql-base?style=for-the-badge)](https://github.com/oxid-esales/graphql-base-module)

This module provides:
- a basic [GraphQL](https://www.graphql.org) implementation for the [OXID eShop](https://www.oxid-esales.com/)
- authorization and authentication using [JWT](https://jwt.io)
- a query to log you in and get a JWT for further authentication


## Documentation

* Full documentation, including GraphQL schema, can be found [here](https://docs.oxid-esales.com/interfaces/graphql/en/latest/).

## Usage

This assumes you have OXID eShop (at least `OXID-eSales/oxideshop_ce: v7.3.0` component, which is part of the `7.3.0` compilation) up and running.

## Branch Compatibility

* 10.1.x versions (or b-7.3.x branch) are compatible with latest shop compilation 7.3.x resp. b-7.3.x shop compilation branches
* 10.0.x versions (or b-7.2.x branch) are compatible with latest shop compilation 7.2.x resp. b-7.2.x shop compilation branches
* 9.x versions (or b-7.1.x branch) are compatible with latest shop compilation 7.1.x resp. b-7.1.x shop compilation branches
* 8.x versions (or b-7.0.x branch) are compatible with latest shop compilation: 7.0.x resp. b-7.0.x shop compilation branches
* 7.x versions (or b-6.5.x branch) are compatible with latest shop compilations: 6.5.x resp. b-6.5.x shop compilation branches
* 6.x versions (or b-6.4.x branch) are compatible with latest shop compilations: 6.4.x resp. b-6.4.x shop compilation branches
* 5.x versions (or b-6.3.x branch) are compatible with latest shop compilations: 6.3.x resp. b-6.3.x shop compilation branches (NOTE: no support for PHP 8 yet)

### Install

```bash
# Install desired version of oxid-esales/graphql-base module, in this case - latest released 9.x version, While updating the version you should add additional flag --with-all-dependencies with below command.
$ composer require oxid-esales/graphql-base ^9.0.0 --with-all-dependencies
```

You should run migrations both after installing the module and after each module update:

```bash
$ vendor/bin/oe-eshop-doctrine_migration migrations:migrate oe_graphql_base
```

After requiring the module, you need to activate it, either via OXID eShop admin or CLI.

```bash
$ bin/oe-console oe:module:activate oe_graphql_base
```

### Update

If you when to update this module from older version to new version. Then run below command to ensure that all dependencies including in the composer.lock are updated that are compatible with each other.

```bash
$ composer update --with-all-dependencies
```

### How to use

You can use your favourite GraphQL client to explore the API, if you do not
already have one installed, you may use [Altair GraphQL Client](https://altair.sirmuel.design/).

To login and retrieve a token send the following GraphQL query to the server

```graphql
query {
    token (
        username: "noreply@oxid-esales.com",
        password: "admin"
    )
}
```

You could simply fire up your terminal and use `curl` to do a basic check
if the GraphQL base module is up and running as expected. To retrieve a valid
token you need to replace the username and password below with valid login
credentials.

```bash
$ curl http://oxideshop.local/graphql/ \
  -H 'Content-Type: application/json' \
  --data-binary '{"query":"query {token(username: \"noreply@oxid-esales.com\", password: \"admin\")}"}'
```

You should see a response similar to this:

```json
{
    "data": {
        "token": "a-very-long-jwt"
    }
}
```

This `token` is then to be send as your authorization with every request in the
HTTP `Authorization` header like this:

```
Authorization: Bearer a-very-long-jwt
```

### How to use refresh tokens

To login and retrieve a refresh and access token send the following GraphQL query to the server:

```graphql
query {
    login (
        username: "noreply@oxid-esales.com",
        password: "admin"
    ) {
        refreshToken
        accessToken
    }
}
```

The response should contain both requested tokens:

```json
{
    "data": {
        "login": {
            "accessToken": "the-same-long-jwt-token",
            "refreshToken": "a-255-character-long-string"
        }
    }
}
```

The request will set an `HttpOnly` cookie with unique fingerprint.
The `accessToken` claims contain a hashed version of this fingerprint.
The access token should be sent as Bearer type authorization as described above.
After the access token's lifetime has elapsed, you will need to refresh it.
To do this you will need to send the following query:

```graphql
query {
    refresh (
        refreshToken: "your-refresh-token",
        fingerprintHash: "from-access-token-claims"
    )
}
```

If the token is valid and the hash matches the fingerprint sent as cookie, you will receive a fresh token as a response:

```json
{
    "data": {
        "refresh": "a-new-long-jwt"
    }
}
```
And along with it, a new fingerprint cookie and `fingerprintHash` claim in the jwt token.

### How to extend

The information on extending any module can be found in the [OXID eSales documentation](https://docs.oxid-esales.com).

How to extend GraphQL module types and implement your new mutations and queries is shown in [OXID GraphQL API documentation](https://docs.oxid-esales.com/interfaces/graphql/en/7.0/tutorials/index.html).

## Testing

### Syntax check and static analysis

```bash
$ composer static
```

### Unit/Integration/Acceptance tests

- install this module into a running OXID eShop
- reset shop's database
```bash
$ bin/oe-console oe:database:reset --db-host=db-host --db-port=db-port --db-name=db-name --db-user=db-user --db-password=db-password --force
```
- run Unit/Integration tests
```bash
$ ./vendor/bin/phpunit -c vendor/oxid-esales/graphql-base/tests/phpunit.xml
```
- run Acceptance tests
```bash
$ SELENIUM_SERVER_HOST=selenium MODULE_IDS=oe_graphql_base vendor/bin/codecept run acceptance -c vendor/oxid-esales/graphql-base/tests/codeception.yml
```

## Issues

To report issues with GraphQL module please use the [OXID eShop bugtracking system](https://bugs.oxid-esales.com/).

## Contributing

You like to contribute? 🙌 AWESOME 🙌\
Go and check the [contribution guidelines](CONTRIBUTING.md)

## Build with

- [GraphQLite](https://graphqlite.thecodingmachine.io/)
- [lcobucci/jwt](https://github.com/lcobucci/jwt)

## License

OXID Module and Component License, see [LICENSE file](LICENSE).
