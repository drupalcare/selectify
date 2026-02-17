<?php

declare(strict_types=1);

namespace Drupal\selectify\Plugin\Field\FieldWidget;

/**
 * Plugin implementation of the 'selectify_dropdown' widget.
 *
 * @FieldWidget(
 *   id = "selectify_dropdown",
 *   module = "selectify",
 *   label = @Translation("Selectify Dropdown"),
 *   field_types = {
 *     "list_string",
 *     "list_integer",
 *     "list_float",
 *     "entity_reference"
 *   },
 *   multiple_values = TRUE
 * )
 */
class SelectifyDropdownWidget extends SelectifyWidgetBase {

  /**
   * {@inheritdoc}
   */
  protected function getWidgetIdPrefix(): string {
    return 'selectify-dropdown-widget';
  }

  /**
   * {@inheritdoc}
   */
  protected function getWidgetCssClass(): string {
    return 'selectify-apply-dropdown';
  }

  /**
   * {@inheritdoc}
   */
  protected function getWidgetLibraries(): array {
    return [
      'selectify/selectify-dropdowns',
      'selectify/selectify-dropdown',
    ];
  }

}
