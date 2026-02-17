<?php

declare(strict_types=1);

namespace Drupal\selectify\Service;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Service for generating Selectify CSS variable overrides.
 *
 * This service generates inline CSS variables that override the base
 * selectify-radio-checkbox.css defaults based on user configuration.
 */
class SelectifyStyleService {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Constructs a SelectifyStyleService object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

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
  public function getRadioVariables(string $style, string $shape, string $size): array {
    $variables = [];

    // Get dimension values based on style and size.
    $dimensions = $this->getDimensions('radio', $style, $size);
    $variables = array_merge($variables, $dimensions);

    // Get shape-specific values.
    $shapeVars = $this->getShapeVariables('radio', $shape, $style, $size);
    $variables = array_merge($variables, $shapeVars);

    // Get animation values based on style and size.
    $animationVars = $this->getAnimationVariables('radio', $style, $size);
    $variables = array_merge($variables, $animationVars);

    return $variables;
  }

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
  public function getCheckboxVariables(string $style, string $shape, string $size): array {
    $variables = [];

    // Get dimension values based on style and size.
    $dimensions = $this->getDimensions('checkbox', $style, $size);
    $variables = array_merge($variables, $dimensions);

    // Get shape-specific values.
    $shapeVars = $this->getShapeVariables('checkbox', $shape, $style, $size);
    $variables = array_merge($variables, $shapeVars);

    // Get animation values based on style and size.
    $animationVars = $this->getAnimationVariables('checkbox', $style, $size);
    $variables = array_merge($variables, $animationVars);

    return $variables;
  }

  /**
   * Gets dimension variables based on element type, style, and size.
   *
   * @param string $elementType
   *   Element type: 'radio' or 'checkbox'.
   * @param string $style
   *   Style type: 'toggle' or 'checkbox'.
   * @param string $size
   *   Size type: 'small', 'medium', or 'large'.
   *
   * @return array
   *   Associative array of dimension variables.
   */
  protected function getDimensions(string $elementType, string $style, string $size): array {
    $prefix = "--selectify-{$elementType}";

    // Define dimensions for toggle style.
    if ($style === 'toggle') {
      $dimensions = [
        'small' => [
          "{$prefix}-width" => '2rem',
          "{$prefix}-height" => '1.25rem',
          "{$prefix}-circle-size" => '0.9rem',
          "{$prefix}-circle-margin" => '1px',
          "{$prefix}-icon-size" => '0.7rem',
          "{$prefix}-border-width" => '2px',
        ],
        'medium' => [
          "{$prefix}-width" => '2.5rem',
          "{$prefix}-height" => '1.5rem',
          "{$prefix}-circle-size" => '1.125rem',
          "{$prefix}-circle-margin" => '1px',
          "{$prefix}-icon-size" => '0.875rem',
          "{$prefix}-border-width" => '2px',
        ],
        'large' => [
          "{$prefix}-width" => '3rem',
          "{$prefix}-height" => '1.75rem',
          "{$prefix}-circle-size" => '1.4rem',
          "{$prefix}-circle-margin" => '1px',
          "{$prefix}-icon-size" => '1rem',
          "{$prefix}-border-width" => '2px',
        ],
      ];
    }
    // Define dimensions for checkbox style.
    else {
      $dimensions = [
        'small' => [
          "{$prefix}-width" => '1.5rem',
          "{$prefix}-height" => '1.5rem',
          "{$prefix}-circle-size" => '0',
          "{$prefix}-circle-margin" => '0',
          "{$prefix}-icon-size" => '1.125rem',
          "{$prefix}-border-width" => '2px',
        ],
        'medium' => [
          "{$prefix}-width" => '1.75rem',
          "{$prefix}-height" => '1.75rem',
          "{$prefix}-circle-size" => '0',
          "{$prefix}-circle-margin" => '0',
          "{$prefix}-icon-size" => '1.375rem',
          "{$prefix}-border-width" => '2px',
        ],
        'large' => [
          "{$prefix}-width" => '2rem',
          "{$prefix}-height" => '2rem',
          "{$prefix}-circle-size" => '0',
          "{$prefix}-circle-margin" => '0',
          "{$prefix}-icon-size" => '1.5rem',
          "{$prefix}-border-width" => '2px',
        ],
      ];
    }

    return $dimensions[$size] ?? $dimensions['medium'];
  }

  /**
   * Gets shape-related variables.
   *
   * @param string $elementType
   *   Element type: 'radio' or 'checkbox'.
   * @param string $shape
   *   Shape type: 'circle' or 'square'.
   * @param string $style
   *   Style type: 'toggle' or 'checkbox'.
   * @param string $size
   *   Size type: 'small', 'medium', or 'large'.
   *
   * @return array
   *   Associative array of shape variables.
   */
  protected function getShapeVariables(string $elementType, string $shape, string $style, string $size): array {
    $prefix = "--selectify-{$elementType}";

    if ($shape === 'square') {
      if ($style === 'toggle') {
        // Toggle square - size-dependent border-radius.
        $squareRadii = [
          'small' => [
            "{$prefix}-border-radius" => '0.3rem',
            "{$prefix}-circle-radius" => '0.2rem',
          ],
          'medium' => [
            "{$prefix}-border-radius" => '0.375rem',
            "{$prefix}-circle-radius" => '0.25rem',
          ],
          'large' => [
            "{$prefix}-border-radius" => '0.45rem',
            "{$prefix}-circle-radius" => '0.3rem',
          ],
        ];
        return $squareRadii[$size] ?? $squareRadii['medium'];
      }
      else {
        // Checkbox square - size-dependent border-radius, no circle.
        $squareRadii = [
          'small' => [
            "{$prefix}-border-radius" => '0.25rem',
            "{$prefix}-circle-radius" => '0',
          ],
          'medium' => [
            "{$prefix}-border-radius" => '0.375rem',
            "{$prefix}-circle-radius" => '0',
          ],
          'large' => [
            "{$prefix}-border-radius" => '0.5rem',
            "{$prefix}-circle-radius" => '0',
          ],
        ];
        return $squareRadii[$size] ?? $squareRadii['medium'];
      }
    }

    // Circle (default) - use pill shape for toggle, full circle for checkbox.
    return [
      "{$prefix}-border-radius" => ($style === 'toggle') ? '999px' : '50%',
      "{$prefix}-circle-radius" => ($style === 'toggle') ? '50%' : '0',
    ];
  }

  /**
   * Gets animation-related variables.
   *
   * @param string $elementType
   *   Element type: 'radio' or 'checkbox'.
   * @param string $style
   *   Style type: 'toggle' or 'checkbox'.
   * @param string $size
   *   Size type: 'small', 'medium', or 'large'.
   *
   * @return array
   *   Associative array of animation variables.
   */
  protected function getAnimationVariables(string $elementType, string $style, string $size): array {
    $prefix = "--selectify-{$elementType}";

    if ($style === 'toggle') {
      // Different animation timings per size.
      $animations = [
        'small' => [
          "{$prefix}-transition-speed" => '0.25s',
          "{$prefix}-transition-easing" => 'cubic-bezier(0.34, 1.56, 0.64, 1)',
          "{$prefix}-icon-delay" => '0.15s',
          "{$prefix}-circle-translate-unchecked" => '2px',
          "{$prefix}-circle-translate-checked" => '11px',
          "{$prefix}-icon-left-unchecked" => '0.25rem',
          "{$prefix}-icon-right-checked" => '0.25rem',
        ],
        'medium' => [
          "{$prefix}-transition-speed" => '0.3s',
          "{$prefix}-transition-easing" => 'cubic-bezier(0.34, 1.56, 0.64, 1)',
          "{$prefix}-icon-delay" => '0.20s',
          "{$prefix}-circle-translate-unchecked" => '2px',
          "{$prefix}-circle-translate-checked" => '14px',
          "{$prefix}-icon-left-unchecked" => '0.3rem',
          "{$prefix}-icon-right-checked" => '0.3rem',
        ],
        'large' => [
          "{$prefix}-transition-speed" => '0.35s',
          "{$prefix}-transition-easing" => 'cubic-bezier(0.34, 1.56, 0.64, 1)',
          "{$prefix}-icon-delay" => '0.22s',
          "{$prefix}-circle-translate-unchecked" => '2px',
          "{$prefix}-circle-translate-checked" => '17px',
          "{$prefix}-icon-left-unchecked" => '0.35rem',
          "{$prefix}-icon-right-checked" => '0.35rem',
        ],
      ];
      return $animations[$size] ?? $animations['medium'];
    }

    // Checkbox style - no circle movement, icons controlled by CSS class.
    return [
      "{$prefix}-transition-speed" => '0.2s',
      "{$prefix}-transition-easing" => 'ease-in-out',
      "{$prefix}-icon-delay" => '0s',
      "{$prefix}-circle-translate-unchecked" => '0',
      "{$prefix}-circle-translate-checked" => '0',
      "{$prefix}-icon-left-unchecked" => '50%',
      "{$prefix}-icon-right-checked" => '50%',
    ];
  }

  /**
   * Generates complete inline CSS from variable arrays.
   *
   * @param array $radioVars
   *   Radio button CSS variables.
   * @param array $checkboxVars
   *   Checkbox CSS variables.
   *
   * @return string
   *   CSS string with :root selector and variables.
   */
  public function generateInlineCss(array $radioVars, array $checkboxVars): string {
    $parts = [];

    // Generate minified scoped radio variables.
    if (!empty($radioVars)) {
      $declarations = [];
      foreach ($radioVars as $varName => $varValue) {
        $declarations[] = "{$varName}:{$varValue}";
      }
      $parts[] = 'input.selectify-radio[type="radio"]{' . implode(';', $declarations) . '}';
    }

    // Generate minified scoped checkbox variables.
    if (!empty($checkboxVars)) {
      $declarations = [];
      foreach ($checkboxVars as $varName => $varValue) {
        $declarations[] = "{$varName}:{$varValue}";
      }
      $parts[] = 'input.selectify-checkbox[type="checkbox"]{' . implode(';', $declarations) . '}';
    }

    return implode('', $parts);
  }

  /**
   * Gets the current configuration and generates inline CSS.
   *
   * @return string
   *   Complete inline CSS string based on current configuration.
   */
  public function getConfiguredInlineCss(): string {
    $config = $this->configFactory->get('selectify.settings');

    $radioEnabled = (bool) $config->get('enable_radio');
    $checkboxEnabled = (bool) $config->get('enable_checkbox');

    $radioVars = [];
    $checkboxVars = [];

    if ($radioEnabled) {
      $radioStyle = $config->get('radio_style') ?? 'toggle';
      $radioShape = $config->get('radio_shape') ?? 'circle';
      $radioSize = $config->get('radio_size') ?? 'medium';
      $radioVars = $this->getRadioVariables($radioStyle, $radioShape, $radioSize);
    }

    if ($checkboxEnabled) {
      $checkboxStyle = $config->get('checkbox_style') ?? 'toggle';
      $checkboxShape = $config->get('checkbox_shape') ?? 'circle';
      $checkboxSize = $config->get('checkbox_size') ?? 'medium';
      $checkboxVars = $this->getCheckboxVariables($checkboxStyle, $checkboxShape, $checkboxSize);
    }

    return $this->generateInlineCss($radioVars, $checkboxVars);
  }

  /**
   * Gets body data attributes for current configuration.
   *
   * @return array
   *   Associative array of data attribute names and values.
   */
  public function getBodyDataAttributes(): array {
    $config = $this->configFactory->get('selectify.settings');
    $attributes = [];

    $radioEnabled = (bool) $config->get('enable_radio');
    $checkboxEnabled = (bool) $config->get('enable_checkbox');

    if ($radioEnabled) {
      $attributes['data-selectify-radio-style'] = $config->get('radio_style') ?? 'toggle';
      $attributes['data-selectify-radio-shape'] = $config->get('radio_shape') ?? 'circle';
      $attributes['data-selectify-radio-size'] = $config->get('radio_size') ?? 'medium';
    }

    if ($checkboxEnabled) {
      $attributes['data-selectify-checkbox-style'] = $config->get('checkbox_style') ?? 'toggle';
      $attributes['data-selectify-checkbox-shape'] = $config->get('checkbox_shape') ?? 'circle';
      $attributes['data-selectify-checkbox-size'] = $config->get('checkbox_size') ?? 'medium';
    }

    return $attributes;
  }

  /**
   * Gets body CSS classes for current configuration.
   *
   * This is the preferred method for styling as CSS classes are much faster
   * than data attribute selectors.
   *
   * @return string
   *   Space-separated CSS class names.
   */
  public function getBodyClasses(): string {
    $config = $this->configFactory->get('selectify.settings');
    $classes = [];

    $radioEnabled = (bool) $config->get('enable_radio');
    $checkboxEnabled = (bool) $config->get('enable_checkbox');

    if ($radioEnabled) {
      $style = $config->get('radio_style') ?? 'toggle';
      $shape = $config->get('radio_shape') ?? 'circle';
      $size = $config->get('radio_size') ?? 'medium';
      $classes[] = "selectify-radio-{$style}-{$shape}-{$size}";
    }

    if ($checkboxEnabled) {
      $style = $config->get('checkbox_style') ?? 'toggle';
      $shape = $config->get('checkbox_shape') ?? 'circle';
      $size = $config->get('checkbox_size') ?? 'medium';
      $classes[] = "selectify-checkbox-{$style}-{$shape}-{$size}";
    }

    return implode(' ', $classes);
  }

}
