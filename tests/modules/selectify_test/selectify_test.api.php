<?php

/**
 * @file
 * Example implementations of Selectify alter hooks for testing.
 *
 * This file demonstrates how to use Selectify's alter hooks to customize
 * widget selection and element processing.
 */

/**
 * Implements hook_selectify_widget_alter().
 *
 * Example: Change widget based on form context.
 */
function selectify_test_selectify_widget_alter(string &$widget, array $context): void {
  // Example: Use searchable dropdown for test form.
  if ($context['integration_type'] === 'form_api' && $context['form_id'] === 'selectify_test_form') {
    // Change to searchable widget for demonstration.
    if ($widget === 'selectify_dropdown') {
      $widget = 'selectify_searchable';
    }
  }
}

/**
 * Implements hook_selectify_element_alter().
 *
 * Example: Add custom attributes and skip simple selects.
 */
function selectify_test_selectify_element_alter(array &$element, array $context): void {
  // Add a test data attribute.
  $element['#attributes']['data-selectify-test'] = 'altered';

  // Skip Selectify for selects with very few options (too simple).
  if (isset($element['#type']) && $element['#type'] === 'select') {
    $options = $element['#options'] ?? [];
    if (count($options) <= 2) {
      $element['#selectify_skip'] = TRUE;
    }
  }
}

/**
 * Implements hook_selectify_is_applicable_alter().
 *
 * Example: Skip Selectify for specific test scenarios.
 */
function selectify_test_selectify_is_applicable_alter(bool &$applicable, array $context): void {
  // Example: Disable Selectify when a specific query parameter is present.
  $request = \Drupal::request();
  if ($request && $request->query && $request->query->get('disable_selectify') === '1') {
    $applicable = FALSE;
  }
}
