# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [7.0.1] - 2022-06-28

### Fixed
- Documentation improvements

## [7.0.0] - 2022-06-17

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

[7.0.1]: https://github.com/OXID-eSales/graphql-base-module/compare/v7.0.0...v7.0.1
[7.0.0]: https://github.com/OXID-eSales/graphql-base-module/compare/v6.0.2...v7.0.0
