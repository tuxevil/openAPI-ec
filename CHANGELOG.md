# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.1] - 2026-07-21

### Fixed
- **Security:** Updated `guzzlehttp/guzzle` and `guzzlehttp/psr7` to address multiple security vulnerabilities (CVE fixes).
- **CI/CD:** Added explicit permissions block in the GitHub Actions `ci.yml` workflow to restrict `GITHUB_TOKEN` scope for better security.

## [1.0.0] - 2026-07-21

### Added
- Initial release of openAPI-ec.
- Normalized API for Contifico and Payphone.
- Rate limiting and standard error mapping.
