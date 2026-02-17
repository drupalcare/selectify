<?php

declare(strict_types=1);

namespace Drupal\selectify\Plugin\Field\FieldWidget;

/**
 * Plugin implementation of the 'selectify_dropdown_checkbox' widget.
 *
 * @FieldWidget(
 *   id = "selectify_dropdown_checkbox",
 *   module = "selectify",
 *   label = @Translation("Selectify Dropdown With Checkbox"),
 *   field_types = {
 *     "list_string",
 *     "list_integer",
 *     "list_float",
 *     "entity_reference"
 *   },
 *   multiple_values = TRUE
 * )
 */
class SelectifyDropdownCheckboxWidget extends SelectifyWidgetBase {

  /**
   * {@inheritdoc}
   */
  protected function getWidgetIdPrefix(): string {
    return 'selectify-dropdown-checkbox-widget';
  }

  /**
   * {@inheritdoc}
   */
  protected function getWidgetCssClass(): string {
    return 'selectify-apply-checkbox';
  }

  /**
   * {@inheritdoc}
   */
  protected function getWidgetLibraries(): array {
    return [
      'selectify/selectify-dropdowns',
      'selectify/selectify-dropdown-checkbox',
    ];
  }

}
