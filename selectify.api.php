<?php

/**
 * @file
 * Hooks provided by the Selectify module.
 *
 * These hooks allow other modules to extend and customize Selectify's behavior.
 */

/**
 * Alter the widget that Selectify will apply to a form or element.
 *
 * This hook is invoked before Selectify applies a widget, allowing modules to
 * change which widget is used based on context.
 *
 * @param string &$widget
 *   The widget machine name (e.g., 'selectify_dropdown', 'selectify_tags').
 *   Set to 'none' to prevent Selectify from applying any widget.
 * @param array $context
 *   An associative array containing:
 *   - form_id: (string) The form ID or normalized form ID.
 *   - form: (array, optional) Reference to the form array (for Form API).
 *   - element: (array, optional) Reference to the element array (for Webform).
 *   - integration_type: (string) One of 'form_api', 'views', 'webform', or
 *     'field_widget'.
 *   - view_name: (string|null, Views only) The View machine name.
 *   - display_id: (string|null, Views only) The display ID.
 *   - webform: (\Drupal\webform\Entity\Webform|null, Webform only) The
 *     Webform entity.
 *
 * @ingroup selectify
 */
function hook_selectify_widget_alter(string &$widget, array $context): void {
  // Example: Use searchable dropdown for forms with many options.
  if ($context['integration_type'] === 'form_api' && $context['form_id'] === 'my_custom_form') {
    // Check if the form has a select with many options.
    $form = &$context['form'];
    foreach ($form as $key => $element) {
      if (isset($element['#type']) && $element['#type'] === 'select') {
        $options = $element['#options'] ?? [];
        if (count($options) > 50) {
          $widget = 'selectify_searchable';
          break;
        }
      }
    }
  }

  // Example: Disable Selectify for specific Views.
  if ($context['integration_type'] === 'views' && $context['view_name'] === 'my_view') {
    $widget = 'none';
  }
}

/**
 * Alter a form element before Selectify processes it.
 *
 * This hook allows modules to modify form elements (add attributes, change
 * options, etc.) before Selectify applies its widget styling. Modules can also
 * set #selectify_skip to TRUE to prevent Selectify from processing the element.
 *
 * @param array &$element
 *   The form element array. This is passed by reference and can be modified.
 *   Set $element['#selectify_skip'] = TRUE to skip Selectify processing.
 * @param array $context
 *   An associative array containing:
 *   - widget: (string) The widget machine name that will be applied.
 *   - form_id: (string|null) The form ID or normalized form ID.
 *   - element_key: (string|null) The element key within the form (Form API).
 *   - webform_id: (string|null) The Webform ID (Webform integration).
 *   - integration_type: (string) One of 'form_api', 'views', 'webform', or
 *     'field_widget'.
 *   - webform: (\Drupal\webform\Entity\Webform|null, Webform only) The
 *     Webform entity.
 *
 * @ingroup selectify
 */
function hook_selectify_element_alter(array &$element, array $context): void {
  // Example: Add custom data attributes for JavaScript.
  if ($context['integration_type'] === 'form_api') {
    $element['#attributes']['data-custom-tracking'] = 'enabled';
  }

  // Example: Skip Selectify for specific element types.
  if (isset($element['#type']) && $element['#type'] === 'select') {
    $options = $element['#options'] ?? [];
    // Skip if element has fewer than 3 options (too simple to enhance).
    if (count($options) < 3) {
      $element['#selectify_skip'] = TRUE;
    }
  }

  // Example: Modify options before rendering.
  if (isset($element['#options']) && is_array($element['#options'])) {
    // Add a prefix to all option labels.
    foreach ($element['#options'] as $key => $label) {
      if (is_string($label)) {
        $element['#options'][$key] = 'Option: ' . $label;
      }
    }
  }
}

/**
 * Alter whether Selectify should be applicable to a form or element.
 *
 * This hook is invoked early in the process to determine if Selectify should
 * process a form at all. Returning FALSE prevents Selectify from applying any
 * widgets.
 *
 * @param bool &$applicable
 *   Whether Selectify should apply. Set to FALSE to skip Selectify entirely.
 * @param array $context
 *   An associative array containing:
 *   - form_id: (string) The form ID or normalized form ID.
 *   - form: (array, optional) Reference to the form array (for Form API).
 *   - element: (array, optional) Reference to the element array (for Webform).
 *   - integration_type: (string) One of 'form_api', 'views', 'webform', or
 *     'field_widget'.
 *   - view_name: (string|null, Views only) The View machine name.
 *   - display_id: (string|null, Views only) The display ID.
 *   - webform: (\Drupal\webform\Entity\Webform|null, Webform only) The
 *     Webform entity.
 *
 * @ingroup selectify
 */
function hook_selectify_is_applicable_alter(bool &$applicable, array $context): void {
  // Example: Disable Selectify for specific user roles.
  $current_user = \Drupal::currentUser();
  if ($current_user->hasPermission('skip selectify')) {
    $applicable = FALSE;
  }

  // Example: Disable Selectify for specific routes.
  $route_name = \Drupal::routeMatch()->getRouteName();
  if ($route_name === 'my_module.special_form') {
    $applicable = FALSE;
  }
}
