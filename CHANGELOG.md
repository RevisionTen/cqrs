# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [3.1.3] - 2023-01-31
### Changed
- Performance improvements (add indexes to entities for faster order-by queries)
- **Update your database schema**

## [3.1.2] - 2022-11-11
### Changed
- Fix deprecations

## [3.1.1] - 2022-11-11
### Changed
- Update ramsey/uuid

## [3.1.0] - 2022-11-11
### Changed
- Allow PHP 8.1

## [3.0.0] - 2021-06-07
### Changed
- Upgrade to PHP 7.4+
- Snapshots use serialized PHP object instead of json arrays. **Delete Snapshots** before upgrading.

## [2.0.8] - 2021-05-27
### Changed
- Save snapshots as serialized PHP objects instead of json arrays

## [2.0.7] - 2020-05-14
### Changed
- Fixed doctrine mapping

## [2.0.6] - 2020-05-14
### Changed
- **Changed extension class case** from `CqrsExtension` to `CQRSExtension`

## [2.0.5] - 2020-05-14
### Changed
- **Changed bundle class case** from `CqrsBundle` to `CQRSBundle`

## [2.0.4] - 2020-05-14
### Changed
- Added class alias

## [2.0.3] - 2019-11-15
### Changed
- Bugfix

## [2.0.2] - 2019-11-13
### Changed
- Updated required `symfony/framework-bundle` versions

## [2.0.1] - 2019-10-17
### Changed
- Bugfix for `$commandBus->execute()` qeued use

## [2.0.0] - 2019-08-22
### Changed
- Use symfony events
- Changed command, event and handler classes and interfaces
- Removed MessageBus from Handlers, throw CommandValidationException instead
- Handlers can now be services (they must be public)
### Removed
- Removed abstract Handler class

## [1.1.3] - 2019-08-06
### Changed
- Improved `$commandBus->execute()` convenience method

## [1.1.2] - 2019-08-05
### Added
- Added `$commandBus->execute()` convenience method for dispatching commands

## [1.1.1] - 2019-08-02
### Added
- Added user id to aggregate history entries

## [1.1.0] - 2019-06-03
### Added
- Added dependency to symfony/event-dispatcher ^4.3

## [1.0.6] - 2019-03-28
### Changed
- Set utf8_unicode_ci collaction on uuid fields to make it MySQL 5.5. compatible
- **Update your database schema**

## [1.0.5] - 2019-03-04
### Changed
- Fixed logger bug

## [1.0.4] - 2019-02-28
### Changed
- Aggregate subscribers are now only notified via the AggregateUpdatedEvent when not-qeued events are persisted or previously qeued events are persisted to the event stream

## [1.0.3] - 2019-02-20
### Added
- Added optional context array property to Message
- Added monolog dependency
### Changed
- Add all messages to debug log

## [1.0.2] - 2018-11-14
### Added
- Added `AggregateUpdatedEvent` symfony event (occurs after an aggregate has changed and all listeners were called)
### Changed
- Code cleanup

## [1.0.1] - 2018-11-08
### Added
- Added version constant to bundle
### Changed
- Fixed return type for nullable listener

## [1.0.0] - 2018-11-01
### Added
- Changelog
### Removed
- Bundle configuration
