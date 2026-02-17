# Changelog

All notable changes to the Selectify module will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

### Added
- PHPCS configuration (phpcs.xml.dist) for Drupal coding standards.
- PHPStan configuration (phpstan.neon.dist) at level 6.
- ESLint and Stylelint enabled in CI/CD pipeline.
- SelectifyHelper service to consolidate module helper functions.
- Install/uninstall hooks in selectify.install.
- CHANGELOG.md for tracking version history.
- Missing config schema entries for `disabled_pages` and `disable_views_widgets`.

### Changed
- Added `declare(strict_types=1)` to all PHP class files.
- Refactored `.module` file to delegate helper logic to SelectifyHelper service.
- Reduced static `\Drupal::` calls by centralizing config access in service.
- Cleaned up duplicate PHPDoc block in SelectifyStyleService.

## [1.0.0] - 2025-01-01

### Added
- Initial release of the Selectify module.
- Five field widgets: Dropdown, Tags, Searchable, Checkbox, and Dual List.
- Custom radio button and checkbox styling with toggle/checkbox, circle/square, and small/medium/large options.
- Seven accent color themes with light and dark modes.
- Views exposed filter integration with site-wide and per-view configuration.
- Theme integration for Claro, Gin, and Olivero.
- Page control settings with wildcard pattern matching.
- Comprehensive WCAG 2.1 Level AA accessibility support.
- Vanilla JavaScript implementation with zero jQuery dependency.
