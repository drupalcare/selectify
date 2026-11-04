# Changelog

All notable changes to the Selectify module will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

### Added
- "Additional pages to disable Selectify" now disables all Selectify features
  on matching paths (selects, radios, checkboxes). Field widgets (Manage Form
  Display) respect disabled pages and render plain selects when the current
  page matches.
- **CSS selector rules** for Form API selects: target specific select elements
  by CSS selector (e.g. `select#edit-type`, `select[name^="field_"]`) and
  assign a widget per rule. Rules are evaluated before per-form configuration;
  first matching rule wins. Up to 50 rules; validated and sanitized for safety.

### Fixed
- Radio/checkbox styling and body classes no longer applied on pages listed in
  "Additional pages to disable Selectify" (previously only select widgets were
  disabled).
- Menu link title capitalization: "selectify Settings" → "Selectify Settings".
- Menu link description updated for clarity.

## [2.0.3] - 2026-02-22

### Added
- **Documentation (README and project page):**
  - "Who is this for?" note for site builders, themers, and developers.
  - Clear All button behavior: when shown (typically 4+ options), hidden for
    small lists (e.g. Sort by, Order, Items per page), reset-to-"All" or
    deselect-all, and hide-after-clear.
  - RTL and LTR support: CSS logical properties for correct layout in both
    directions.
  - Form API per-element targeting: optional CSS selector rules documented.
  - Modular JavaScript: helper, accessibility, render, and search scripts
    described under Developer Features.
- **Module description** (`selectify.info.yml`): expanded to mention five
  dropdown widgets, RTL/LTR support, and WCAG 2.1 AA.
- **Drupal project page** (`homepage-drupal.html`): in line with README (who
  it’s for, Key Features including RTL/LTR, Clear All, modular JS; Form API
  per-element targeting; Theme Integration RTL/LTR).

### Changed
- README.md: removed duplicate/junk content from the top so the file starts
  with the main project heading.

## [2.0.2] - 2026-02-17

### Added
- `SelectifyWidgetBase` abstract class: single source of truth for all widget
  constructor, dependency injection, and `formElement()` logic (~553 lines
  removed across the five widget classes).
- `SelectifyHelper` service with per-request config caching, replacing ~15
  redundant `\Drupal::` static calls per request.
- `SelectifyHelperInterface` and `SelectifyStyleServiceInterface` for
  testability and service decoration support.
- Shared Twig partials (`_selectify-base.html.twig`,
  `_selectify-widget-footer.html.twig`) via `{% embed %}` / `{% include %}`,
  eliminating duplicated wrapper markup across all five templates.
- `ENTITY_REFERENCE_TYPES` class constant for DRY entity-reference field type
  checks.
- `isApplicable()` guard on `SelectifyWidgetBase` to hide Selectify widgets for
  multi-bundle and all-bundle entity reference fields (including
  `entity_reference_revisions` / Paragraphs) that produce unsupported optgroups.
- PHPCS configuration (`phpcs.xml.dist`) for Drupal/DrupalPractice standards.
- PHPStan configuration (`phpstan.neon.dist`) at level 6.
- ESLint and Stylelint enabled in GitLab CI/CD pipeline.
- Choice constraints in config schema for `radio_style`, `radio_shape`,
  `radio_size`, `checkbox_style`, `checkbox_shape`, `checkbox_size`,
  `accent_color`, `accent_color_mode`, and `global_selectify_widget`.
- Missing config schema entries for `disabled_pages` and
  `disable_views_widgets`.
- Install/uninstall hooks in `selectify.install` for clean config removal.
- 150ms debounce on searchable dropdown filter input for large option lists.
- Independent admin and front-end color theme controls, separate
  `admin_accent_color` / `admin_accent_color_mode` config fields so admin pages
  and public pages can each use a different palette and mode.
- "None" color mode option per scope that strips all Selectify colors, letting
  form elements inherit the active theme's native styling.

### Changed
- All five widget classes now extend `SelectifyWidgetBase` and only declare
  their annotation plus three small methods (`getWidgetIdPrefix`,
  `getWidgetCssClass`, `getWidgetLibraries`).
- `.module` file reduced from ~948 to ~523 lines by delegating to
  `SelectifyHelper` service.
- `declare(strict_types=1)` added to all PHP class files.
- Inline CSS variables output minified to a single compact line in `<head>`.
- Color theme settings split into two independent scopes (admin / front-end),
  each offering light, dark, and none modes.
- Replaced hardcoded CSS error colors with custom properties
  (`--selectify-error-bg`, `--selectify-error-color`).
- `use` statements alphabetized per Drupal coding standards.

### Fixed
- Entity reference fields with multiple bundles showing empty values: options
  sharing `value=""` caused duplicate selections.
- Click-outside-to-close not working: `once()` on `document` (a Document node,
  not an Element) returned an empty array, so the listener was never bound.
- Dropdown open/close animation broken: `removeProperty()` requires kebab-case
  but was called with camelCase, making cleanup a no-op for multi-word
  properties. Also added missing `transitionTimingFunction` and `boxSizing` to
  cleanup lists.
- Toggle circle displaced in Claro/Gin admin themes. Admin themes set
  `top: 50%; left: 50%` on `.form-boolean::before`, leaking into Selectify
  toggle positioning. Added explicit `inset-block-start: 0` /
  `inset-inline-start: 0` resets with logical properties for RTL support.
- Checkbox rendering broken in admin themes: Claro/Gin `mask-image` on
  `.form-boolean::before` distorted toggle circles. Added `mask-image: none`,
  `-webkit-mask-image: none`, and `clip-path: none` resets.
- Duplicate `document.addEventListener('click')` in four JS files (already
  handled globally in `selectify-helper.js`).
- `MutationObserver` leak: previous observers not disconnected before creating
  new ones. Now stores and disconnects references.
- `console.error` calls removed from production JS.
- Type coercion bug in dual widget where `$max_selected === 1` (int vs string)
  caused `selectify-single` class to never be applied.
- GitLab CI invalid Docker image reference from `_TARGET_PHP` /_TARGET_CORE`
  comma-separated values interpolated literally into image name.
- Missing `ArrayAccess` fallback in `selectify_preprocess_container()`.
- Duplicate PHPDoc block in `SelectifyStyleService`.

### Removed
- Redundant optgroup flattening in preprocess: replaced by `isApplicable()`
  guard that prevents the unsupported configuration entirely.

## [1.0.0] - 2025-01-01

### Added
- Initial release of the Selectify module.
- Five field widgets: Dropdown, Tags, Searchable, Checkbox, and Dual List.
- Custom radio button and checkbox styling with toggle/checkbox, circle/square,
  and small/medium/large options.
- Seven accent color themes with light and dark modes.
- Views exposed filter integration with site-wide and per-view configuration.
- Theme integration for Claro, Gin, and Olivero.
- Page control settings with wildcard pattern matching.
- Comprehensive WCAG 2.1 Level AA accessibility support.
- Vanilla JavaScript implementation with zero jQuery dependency.
