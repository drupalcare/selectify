<?php

namespace Drupal\selectify_webform\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\selectify\Service\SelectifyHelperInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for selectify_webform.
 */
class SelectifyWebformHooks {

  /**
   * Constructs the hook implementations.
   *
   * Injected rather than fetched: the container builds this class
   * (autowire: true), so the static lookups the bot patch carried over from
   * the .module file are unnecessary here. See the sibling class in the parent
   * module for the full reasoning. Call sites are otherwise unchanged.
   *
   * @param \Drupal\selectify\Service\SelectifyHelperInterface $helper
   *   Answers whether Selectify applies to a given element.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   Invokes this module's own alter hooks.
   */
  public function __construct(
    protected readonly SelectifyHelperInterface $helper,
    protected readonly ModuleHandlerInterface $moduleHandler,
  ) {}

  /**
   * Implements hook_webform_element_alter().
   *
   * Intercepts each Webform element render array and applies Selectify styling
   * to select elements and composite elements containing selects.
   */
  #[Hook('webform_element_alter')]
  public function webformElementAlter(array &$element, FormStateInterface $form_state, array $context): void {
    $config = _selectify_webform_get_config();
    if ($config === FALSE) {
      return;
    }
    $webform = $context['webform'] ?? NULL;
    $element_type = $element['#type'] ?? '';
    // Determine the effective widget for this Webform:
    // - If apply_site_wide is enabled, always use the global widget.
    // - Otherwise, look for a specific widget for this Webform ID.
    //   If none is configured, do not style this element.
    $widget = NULL;
    $webform_id = $webform?->id() ?? '';
    if (!empty($config['apply_site_wide'])) {
      $widget = $config['global_widget'] ?? NULL;
    }
    elseif ($webform_id && !empty($config['widget_map'][$webform_id])) {
      $widget = $config['widget_map'][$webform_id];
    }
    // Allow other modules to alter the widget selection.
    if (!empty($widget) && $widget !== 'none') {
      $context = [
        'form_id' => $webform_id,
        'element' => &$element,
        'integration_type' => 'webform',
        'webform' => $webform,
      ];
      $this->moduleHandler->alter('selectify_widget', $widget, $context);
    }
    if (empty($widget) || $widget === 'none') {
      return;
    }
    /** @var \Drupal\selectify\Service\SelectifyHelper $helper */
    $helper = $this->helper;
    // Direct select element.
    if ($element_type === 'select') {
      // Skip selects with AJAX callbacks.
      if (!empty($element['#ajax'])) {
        return;
      }
      // Skip field widget selects.
      if (isset($element['#key_column'])) {
        return;
      }
      // Skip date/time picker selects (year, month, day, hour, minute).
      // These are part of a cohesive date picker UI and should remain grouped.
      if (_selectify_webform_is_date_select($element)) {
        return;
      }
      // Skip selects per shared rules (third-party libs, hidden/disabled,
      // context).
      if ($helper->shouldSkipSelectElement($element)) {
        return;
      }
      // Allow other modules to alter the element before Selectify processes it.
      $element_context = [
        'widget' => $widget,
        'webform_id' => $webform_id,
        'integration_type' => 'webform',
        'webform' => $webform,
      ];
      $this->moduleHandler->alter('selectify_element', $element, $element_context);
      // Check if the element was marked to skip Selectify processing.
      if (!empty($element['#selectify_skip'])) {
        return;
      }
      _selectify_webform_style_select($element, $helper, $widget);
      return;
    }
    // Composite element types — walk children to find nested selects.
    $composites = _selectify_webform_composite_types();
    if (in_array($element_type, $composites, TRUE)) {
      _selectify_webform_walk_composites($element, $helper, $widget);
    }
  }

  /**
   * Implements hook_form_alter().
   *
   * Safety net: catches any webform selects that hook_webform_element_alter
   * couldn't reach (e.g. dynamically added elements). Skips elements already
   * styled.
   */
  #[Hook('form_alter')]
  public function formAlter(array &$form, FormStateInterface $form_state, string $form_id): void {
    if (!str_starts_with($form_id, 'webform_submission_')) {
      return;
    }
    $config = _selectify_webform_get_config();
    if ($config === FALSE) {
      return;
    }
    $webform_id = '';
    if (isset($form['#webform']) && is_object($form['#webform']) && method_exists($form['#webform'], 'id')) {
      $webform_id = $form['#webform']->id() ?? '';
    }
    if ($webform_id === '' && preg_match('/^webform_submission_(.+)_(add|edit|api)_form$/', $form_id, $m)) {
      $webform_id = $m[1];
    }
    $widget = NULL;
    if (!empty($config['apply_site_wide'])) {
      $widget = $config['global_widget'] ?? NULL;
    }
    elseif ($webform_id && !empty($config['widget_map'][$webform_id])) {
      $widget = $config['widget_map'][$webform_id];
    }
    if (empty($widget) || $widget === 'none') {
      return;
    }
    /** @var \Drupal\selectify\Service\SelectifyHelper $helper */
    $helper = $this->helper;
    _selectify_webform_walk_composites($form, $helper, $widget);
  }

}
