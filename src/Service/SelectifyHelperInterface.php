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
   * Detects if Gin theme is active and returns its settings.
   *
   * @return array|false
   *   Array with 'is_gin', 'color_scheme', and 'accent_color' keys,
   *   or FALSE if Gin is not the active theme.
   */
  public function detectGinTheme(): array|false;

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

}
