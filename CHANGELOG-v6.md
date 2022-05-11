# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased] - undecided

### Added
- Logic to track tokens of not anonymous users in database
- Logic to invalidate tokens
- New table `oegraphqltoken`
- Module setting `sJsonWebTokenUserQuota` to limit the number of valid JWT for a single user.
- Classes
  - `OxidEsales\GraphQL\Base\Controller\Token`
  - `OxidEsales\GraphQL\Base\DataType\User`
  - `OxidEsales\GraphQL\Base\Infrastructure\Model\Token`
  - `OxidEsales\GraphQL\Base\Infrastructure\Token`
  - `OxidEsales\GraphQL\Base\Infrastructure\Repository`
  - `OxidEsales\GraphQL\Base\Exception\TokenQuota`
  - `OxidEsales\GraphQL\Base\Exception\UserNotFound`
  - `OxidEsales\GraphQL\Base\Service\TokenAdministration`
- Public Methods
  - `OxidEsales\GraphQL\Base\Exception\InvalidToken::unknownToken()`
  - `OxidEsales\GraphQL\Base\Service\ModuleConfiguration::getUserTokenQuota()`

### Fixed
- Fixed a link to documentation in troubleshooting section [PR-22](https://github.com/OXID-eSales/graphql-base-module/pull/22)
- Improved modules installation instructions [PR-23](https://github.com/OXID-eSales/graphql-base-module/pull/23)
- Missmatch in checkout documentation [PR-24](https://github.com/OXID-eSales/graphql-base-module/pull/24)

### Changed
- Update GraphQLite version to v5
- Code quality tools list simplified and reconfigured to fit our quality requirements

## [6.0.2] - 2022-03-31

### Changed
- Require version 4.2 of graphql-upload fork.

## [6.0.1] - 2021-12-08

### Fixed
- Coding style / docblock fixed:
  - [PR-21](https://github.com/OXID-eSales/graphql-base-module/pull/21)

### Changed
- Update documentation for Storefront 2.0
- Added translations for token lifetime setting.

## [6.0.0] - 2021-11-03

### Added
- PHP 8 support
- `OxidEsales\GraphQL\Base\DataType\ShopModelAwareInterface` intended to be implemented by DataTypes related to a `OxidEsales\Eshop\Core\Model\BaseModel`
- Token service `OxidEsales\GraphQL\Base\Service\Token` for accessing current token
- User DataType `OxidEsales\GraphQL\Base\DataType\User` in place of former `OxidEsales\GraphQL\Base\Framework\UserData` and `OxidEsales\GraphQL\Base\Framework\AnonymousUserData` classes
  - It holds an instance of `OxidEsales\Eshop\Application\Model\User` which it tries to actually load. For an anonymous user there will be no record in the database, so `OxidEsales\Eshop\Application\Model\User::isLoaded()` will be false in this case.
  - Implements the `OxidEsales\GraphQL\Base\DataType\ShopModelAwareInterface`
- File upload handling
  - Used the special fork of `Ecodev/graphql-upload`, which supports PHP8 and `webonyx/graphql-php ^0.13` to fit other components
- `OxidEsales\GraphQL\Base\Framework\Constraint\BelongsToShop` token validation constraint
- `OxidEsales\GraphQL\Base\Service\JwtConfigurationBuilder` to handle everything related to JWT token configuration.
- Token lifetime is configurable via module setting (default 8 hours) and checked during token validation.
- Added method `OxidEsales\GraphQL\Base\Service\ModuleConfiguration::getTokenLifeTime()`

### Changed
- Updated to `thecodingmachine/graphqlite ^4.0` and `lcobucci/jwt ^4.0`
- `Lcobucci\JWT\Token` cannot be used directly anymore, changed to UnencryptedToken interface everywhere
- DataType directory contents has been restructured to: Filter, Pagination, Sorting
- Improved `OxidEsales\GraphQL\Base\Service\Authentication`
  - `TheCodingMachine\GraphQLite\Security\AuthenticationServiceInterface` is now fully implemented
  - Extracted token creation to `OxidEsales\GraphQL\Base\Service\Token` service
  - Extracted token validation to `OxidEsales\GraphQL\Base\Service\TokenValidator` service
    - Token validation is done once during token reading procedure now
    - Checking if token user is in "blocked" group is done here as well
- Logic changes as `OxidEsales\GraphQL\Base\Framework\NullToken` has been removed. Token may be available or not, no stable default states anymore.
- Tests readability improved greatly
- `OxidEsales\GraphQL\Base\Framework\ModuleSetup` class moved to `OxidEsales\GraphQL\Base\Infrastructure\ModuleSetup`
- Renamed `OxidEsales\GraphQL\Base\Service\KeyRegistry` to `OxidEsales\GraphQL\Base\Service\ModuleConfiguration`

### Removed
- PHP 7.3 support
- Removed framework classes
  - `OxidEsales\GraphQL\Base\Framework\UserData`
  - `OxidEsales\GraphQL\Base\Framework\AnonymousUserData`
  - `OxidEsales\GraphQL\Base\Framework\NullToken`
- Removed deprecated `OxidEsales\GraphQL\Base\Service\Legacy`

[Undecided]: https://github.com/OXID-eSales/graphql-base-module/compare/v6.0.2...b-6.4.x
[6.0.2]: https://github.com/OXID-eSales/graphql-base-module/compare/v6.0.1...v6.0.2
[6.0.1]: https://github.com/OXID-eSales/graphql-base-module/compare/v6.0.0...v6.0.1
[6.0.0]: https://github.com/OXID-eSales/graphql-base-module/compare/v5.2.1...v6.0.0
