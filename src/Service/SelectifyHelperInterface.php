<?php

declare(strict_types=1);

namespace Drupal\selectify\Service;

use Drupal\Core\Config\ImmutableConfig;

/**
 * Interface for the Selectify helper service.
 */
interface SelectifyHelperInterface {

  /**
   * Gets the Selectify configuration object (cached per request).
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   The selectify.settings configuration.
   */
  public function getConfig(): ImmutableConfig;

  /**
   * Retrieves Selectify radio/checkbox settings with admin route check.
   *
   * @return array|false
   *   Array of settings if Selectify should be applied, FALSE otherwise.
   */
  public function getRadioCheckboxConfig(): array|false;

  /**
   * Loads Selectify Views configuration settings.
   *
   * @return array
   *   An associative array containing Views widget settings.
   */
  public function getViewsConfig(): array;

  /**
   * Checks if Selectify is disabled on the current admin route.
   *
   * @return bool
   *   TRUE if Selectify is disabled on an admin route, FALSE otherwise.
   */
  public function isDisabledOnAdminRoute(): bool;

  /**
   * Checks if the current page matches any disabled page pattern.
   *
   * @return bool
   *   TRUE if the current page should have Selectify disabled.
   */
  public function isPageDisabled(): bool;

  /**
   * Checks if the current route is an admin route.
   *
   * @return bool
   *   TRUE if the current route is an admin route.
   */
  public function isAdminRoute(): bool;

  /**
   * Checks if the active theme is or extends the given base theme.
   *
   * @param string $theme_name
   *   The theme name to check for (e.g. 'gin', 'claro', 'olivero').
   *
   * @return bool
   *   TRUE if the active theme is or extends the given theme.
   */
  public function isBaseTheme(string $theme_name): bool;

  /**
   * Recursively finds the topmost base theme (the original parent).
   *
   * @param string $theme_name
   *   The theme name to find the root parent for.
   *
   * @return string
   *   The root parent theme name.
   */
  public function getRootParentTheme(string $theme_name): string;

  /**
   * Cleans a theme name to allow only letters, numbers, and dashes.
   *
   * @param string $name
   *   The theme name to clean.
   *
   * @return string
   *   The cleaned theme name.
   */
  public function cleanThemeName(string $name): string;

  /**
   * Gets the active theme name.
   *
   * @return string
   *   The active theme name.
   */
  public function getActiveThemeName(): string;

  /**
   * Retrieves the library dependencies for each Selectify widget type.
   *
   * @return array
   *   An associative array mapping widget names to their library arrays.
   */
  public function getWidgetLibraries(): array;

  /**
   * Retrieves the CSS class mapping for each Selectify widget type.
   *
   * @return array
   *   An associative array mapping widget names to their CSS classes.
   */
  public function getWidgetClasses(): array;

  /**
   * Extracts and normalizes a form ID by replacing underscores with hyphens.
   *
   * @param array $form
   *   The Views exposed form array.
   *
   * @return string
   *   The normalized form ID.
   */
  public function normalizeFormId(array $form): string;

  /**
   * Applies a Selectify widget to all select elements in a form.
   *
   * @param array &$form
   *   The Views exposed form array.
   * @param string $widget_name
   *   The widget type name (e.g. 'selectify_dropdown').
   */
  public function applyWidgetToForm(array &$form, string $widget_name): void;

  /**
   * Retrieves Selectify Form API select configuration.
   *
   * @return array|false
   *   Array of settings if Form API selects are enabled, FALSE otherwise.
   */
  public function getFormApiConfig(): array|false;

  /**
   * Returns hardcoded form ID patterns that must always be excluded.
   *
   * These are admin forms with AJAX-dependent selects or other sensitivity
   * that prevents safe replacement. This list cannot be overridden by users.
   *
   * @return string[]
   *   Array of form ID patterns (supports fnmatch wildcards).
   */
  public function getInternalBlockedFormPatterns(): array;

  /**
   * Checks whether a form ID matches any internal or user-defined exclusion.
   *
   * @param string $form_id
   *   The form ID to check.
   * @param string[] $user_excluded
   *   Additional user-defined exclusion patterns.
   *
   * @return bool
   *   TRUE if the form should be excluded.
   */
  public function isFormExcluded(string $form_id, array $user_excluded = []): bool;

  /**
   * Checks if a select element is enhanced by a third-party library.
   *
   * When Select2, Chosen, Selectize, or Choices.js is applied, we skip
   * Selectify to avoid conflicts.
   *
   * @param array $element
   *   The form element array.
   *
   * @return bool
   *   TRUE if a third-party select library is detected.
   */
  public function hasThirdPartySelectLibrary(array $element): bool;

  /**
   * Checks if a select element is hidden or disabled.
   *
   * @param array $element
   *   The form element array.
   *
   * @return bool
   *   TRUE if the element should not receive Selectify (hidden/disabled).
   */
  public function isSelectHiddenOrDisabled(array $element): bool;

  /**
   * Checks if a select is in an excluded context (toolbar, modal, off-canvas).
   *
   * @param array $element
   *   The form element array.
   *
   * @return bool
   *   TRUE if the element is in a context where Selectify should be skipped.
   */
  public function isSelectInExcludedContext(array $element): bool;

  /**
   * Checks if a select element should be skipped (single source for skip rules).
   *
   * Combines third-party library, hidden/disabled, and context checks.
   * Call after integration-specific checks (#ajax, #key_column, etc.).
   *
   * @param array $element
   *   The form element array.
   *
   * @return bool
   *   TRUE if Selectify should skip this select.
   */
  public function shouldSkipSelectElement(array $element): bool;

  /**
   * Checks if a select element is eligible for Selectify styling.
   *
   * Single source of truth for all eligibility checks: #ajax, #key_column,
   * third-party libraries, hidden/disabled, excluded contexts, and
   * already-styled guard. Used by both the per-form walker and the CSS
   * selector walker.
   *
   * @param array $element
   *   The form element render array.
   *
   * @return bool
   *   TRUE if the select can be styled, FALSE if it should be skipped.
   */
  public function isSelectEligibleForStyling(array $element): bool;

  /**
   * Applies Selectify widget styling to a single select element.
   *
   * Single source of truth for adding classes, wrapper attributes, and
   * attaching libraries to a select element. Used by both the per-form
   * walker and the CSS selector walker.
   *
   * @param array &$element
   *   The form element render array (modified in place).
   * @param string $widget
   *   The widget machine name (e.g. 'selectify_dropdown').
   * @param string|null $accent_library
   *   Optional accent colour library to attach.
   *
   * @return bool
   *   TRUE if styling was applied, FALSE if the widget is invalid.
   */
  public function applyWidgetStyling(array &$element, string $widget, ?string $accent_library): bool;

  /**
   * Returns CSS selector rules from configuration.
   *
   * Each rule maps a CSS selector string to a widget machine name.
   *
   * @return array
   *   Array of rules, each with 'selector' and 'widget' keys.
   */
  public function getCssSelectorRules(): array;

  /**
   * Checks whether a form select element matches a CSS selector string.
   *
   * Supports simple CSS selectors targeting select element attributes:
   * - ID: select#edit-type, select[id^="edit-parent"], select[id*="foo"]
   * - Class: select.my-class
   * - Attribute: select[name="field_color"], select[name^="field_"]
   *
   * @param array $element
   *   The form element render array.
   * @param string $key
   *   The element key in the parent form array.
   * @param string $selector
   *   The CSS selector string.
   *
   * @return bool
   *   TRUE if the element matches the selector.
   */
  public function elementMatchesCssSelector(array $element, string $key, string $selector): bool;

}
