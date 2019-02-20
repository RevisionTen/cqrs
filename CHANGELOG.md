# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
