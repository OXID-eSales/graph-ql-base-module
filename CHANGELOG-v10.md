# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [10.0.0] - Unreleased

### Added
- `DataType\Filter\StringFilter::matches()` to check if string match the filter conditions
- Refresh token functionality
  - New queries:
    - `OxidEsales\GraphQL\Base\Controller\Login::login`
    - `OxidEsales\GraphQL\Base\Controller\Token::refresh`
  - New datatypes:
    - `OxidEsales\GraphQL\Base\DataType\Login` as a return type for `login` query, containing refresh and access token
    - `OxidEsales\GraphQL\Base\DataType\RefreshToken`
  - New event:
    - `OxidEsales\GraphQL\Base\Event\BeforeTokenCreation`
  - New services:
    - `OxidEsales\GraphQL\Base\Service\CookieService`
    - `OxidEsales\GraphQL\Base\Service\FingerprintService`
    - `OxidEsales\GraphQL\Base\Service\HeaderService`
    - `OxidEsales\GraphQL\Base\Service\LoginService`
    - `OxidEsales\GraphQL\Base\Service\RefreshTokenService`
  - New configuration options:
    - `sRefreshTokenLifetime` - options for refresh token lifetime, from 24 hours to 90 days
    - `sFingerprintCookieMode` - option for the authentication fingerprint cookie mode, same or cross origin
- Access and refresh tokens are now invalidated when the user's password is changed
  - New methods:
    - `OxidEsales\GraphQL\Base\Infrastructure\RefreshTokenRepositoryInterface::invalidateUserTokens`
    - `OxidEsales\GraphQL\Base\Infrastructure\Token::invalidateUserTokens`
    - `OxidEsales\GraphQL\Base\Infrastructure\Token::isTokenExpired`
  - New event subscriber:
    - `OxidEsales\GraphQL\Base\Event\Subscriber\PasswordChangeSubscriber`

## Changed
- Renamed OxidEsales\GraphQL\Base\Infrastructure\Token::cleanUpTokens() to deleteOrphanedTokens()

[10.0.0]: https://github.com/OXID-eSales/graphql-base-module/compare/v9.0.0...b-7.2.x
