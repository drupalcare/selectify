<?php

declare(strict_types=1);

namespace Drupal\selectify\Service;

/**
 * Interface for the Selectify style service.
 */
interface SelectifyStyleServiceInterface {

  /**
   * Generates inline CSS variable overrides for radio buttons.
   *
   * @param string $style
   *   Style type: 'toggle' or 'checkbox'.
   * @param string $shape
   *   Shape type: 'circle' or 'square'.
   * @param string $size
   *   Size type: 'small', 'medium', or 'large'.
   *
   * @return array
   *   Associative array of CSS variable names and values.
   */
  public function getRadioVariables(string $style, string $shape, string $size): array;

  /**
   * Generates inline CSS variable overrides for checkboxes.
   *
   * @param string $style
   *   Style type: 'toggle' or 'checkbox'.
   * @param string $shape
   *   Shape type: 'circle' or 'square'.
   * @param string $size
   *   Size type: 'small', 'medium', or 'large'.
   *
   * @return array
   *   Associative array of CSS variable names and values.
   */
  public function getCheckboxVariables(string $style, string $shape, string $size): array;

  /**
   * Generates complete inline CSS from variable arrays.
   *
   * @param array $radioVars
   *   Radio button CSS variables.
   * @param array $checkboxVars
   *   Checkbox CSS variables.
   *
   * @return string
   *   CSS string with scoped selectors and variables.
   */
  public function generateInlineCss(array $radioVars, array $checkboxVars): string;

  /**
   * Gets the current configuration and generates inline CSS.
   *
   * @return string
   *   Complete inline CSS string based on current configuration.
   */
  public function getConfiguredInlineCss(): string;

  /**
   * Gets body data attributes for current configuration.
   *
   * @return array
   *   Associative array of data attribute names and values.
   */
  public function getBodyDataAttributes(): array;

  /**
   * Gets body CSS classes for current configuration.
   *
   * @return string
   *   Space-separated CSS class names.
   */
  public function getBodyClasses(): string;

}
