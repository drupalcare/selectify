<?php

declare(strict_types=1);

namespace Drupal\selectify\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Plugin implementation of the 'selectify_dropdown_tags' widget.
 *
 * @FieldWidget(
 *   id = "selectify_dropdown_tags",
 *   module = "selectify",
 *   label = @Translation("Selectify Dropdown Taggable"),
 *   field_types = {
 *     "list_string",
 *     "list_integer",
 *     "list_float",
 *     "entity_reference"
 *   },
 *   multiple_values = TRUE
 * )
 */
#[FieldWidget(
  id: 'selectify_dropdown_tags',
  label: new TranslatableMarkup('Selectify Dropdown Taggable'),
  field_types: ['list_string', 'list_integer', 'list_float', 'entity_reference'],
  multiple_values: TRUE,
)]
class SelectifyDropdownTagsWidget extends SelectifyWidgetBase {

  /**
   * {@inheritdoc}
   */
  protected function getWidgetIdPrefix(): string {
    return 'selectify-dropdown-tags-widget';
  }

  /**
   * {@inheritdoc}
   */
  protected function getWidgetCssClass(): string {
    return 'selectify-apply-tags';
  }

  /**
   * {@inheritdoc}
   */
  protected function getWidgetLibraries(): array {
    return [
      'selectify/selectify-dropdowns',
      'selectify/selectify-dropdown-tags',
    ];
  }

}
