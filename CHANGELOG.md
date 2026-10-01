# Changelog

All notable changes to this project are documented here.

## [Unreleased] - 2026-10-01

### Security
- removed the legacy reliance on request Origin values for secret derivation
- hardened the authentication flow so a missing configured secret fails closed
- removed public client-specific references and example credentials from the public docs

### Tests
- added PHPUnit coverage for the encoder secret resolution logic
- added PHPUnit coverage for fail-closed authentication behavior
- kept the tests aligned with the real legacy OpenCart runtime behavior

### Compatibility
- removed PHP 8.2 deprecations in the legacy auth and TripleDES code paths
- kept the OpenCart extension structure and controller contract unchanged

## [4.0.2.1] - 2021-06-30

### Added
- version metadata now includes the runtime PHP version in exported XML output

### Changed
- kept legacy XML and job execution flows compatible with the installed OpenCart contract

## Historical notes

Older release notes in `version.txt` document previous milestones such as:

- support for custom product tabs
- support for extra tab product data
- CSV export support
- job table storage instead of file-based job storage

These files reflect the project’s actual evolution and should be read alongside the runtime code in `src/`.
