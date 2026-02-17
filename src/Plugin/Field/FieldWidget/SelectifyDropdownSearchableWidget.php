<?php

declare(strict_types=1);

namespace Drupal\selectify\Plugin\Field\FieldWidget;

/**
 * Plugin implementation of the 'selectify_dropdown_searchable' widget.
 *
 * @FieldWidget(
 *   id = "selectify_dropdown_searchable",
 *   module = "selectify",
 *   label = @Translation("Selectify Dropdown With Search"),
 *   field_types = {
 *     "list_string",
 *     "list_integer",
 *     "list_float",
 *     "entity_reference"
 *   },
 *   multiple_values = TRUE
 * )
 */
class SelectifyDropdownSearchableWidget extends SelectifyWidgetBase {

  /**
   * {@inheritdoc}
   */
  protected function getWidgetIdPrefix(): string {
    return 'selectify-dropdown-searchable-widget';
  }

  /**
   * {@inheritdoc}
   */
  protected function getWidgetCssClass(): string {
    return 'selectify-apply-searchable';
  }

  /**
   * {@inheritdoc}
   */
  protected function getWidgetLibraries(): array {
    return [
      'selectify/selectify-dropdowns',
      'selectify/selectify-dropdown-searchable',
    ];
  }

}
