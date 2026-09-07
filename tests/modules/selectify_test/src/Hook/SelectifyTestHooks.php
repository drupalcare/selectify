<?php

namespace Drupal\selectify_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for selectify_test.
 */
class SelectifyTestHooks {
  /**
   * @file
   * Test module implementing Selectify alter hooks for Kernel tests.
   */

  /**
   * Implements hook_selectify_widget_alter().
   */
  #[Hook('selectify_widget_alter')]
  public function selectifyWidgetAlter(string &$widget, array $context): void {
    if ($context['integration_type'] === 'form_api' && $context['form_id'] === 'selectify_test_form') {
      if ($widget === 'selectify_dropdown') {
        $widget = 'selectify_searchable';
      }
    }
  }

  /**
   * Implements hook_selectify_element_alter().
   */
  #[Hook('selectify_element_alter')]
  public function selectifyElementAlter(array &$element, array $context): void {
    $element['#attributes'] = $element['#attributes'] ?? [];
    $element['#attributes']['data-selectify-test'] = 'altered';
    if (isset($element['#type']) && $element['#type'] === 'select') {
      $options = $element['#options'] ?? [];
      if (count($options) <= 2) {
        $element['#selectify_skip'] = TRUE;
      }
    }
  }

  /**
   * Implements hook_selectify_is_applicable_alter().
   */
  #[Hook('selectify_is_applicable_alter')]
  public function selectifyIsApplicableAlter(bool &$applicable, array $context): void {
    $request = \Drupal::request();
    if ($request && $request->query && $request->query->get('disable_selectify') === '1') {
      $applicable = FALSE;
    }
  }

}
