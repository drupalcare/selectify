<?php

declare(strict_types=1);

namespace Drupal\selectify\Plugin\Field\FieldWidget;

/**
 * Plugin implementation of the 'selectify_dual' widget.
 *
 * @FieldWidget(
 *   id = "selectify_dual",
 *   module = "selectify",
 *   label = @Translation("Selectify Dual List"),
 *   field_types = {
 *     "list_string",
 *     "list_integer",
 *     "list_float",
 *     "entity_reference"
 *   },
 *   multiple_values = TRUE
 * )
 */
class SelectifyDualWidget extends SelectifyWidgetBase {

  /**
   * {@inheritdoc}
   */
  protected function getWidgetIdPrefix(): string {
    return 'selectify-dual-widget';
  }

  /**
   * {@inheritdoc}
   */
  protected function getWidgetCssClass(): string {
    return 'selectify-apply-dual';
  }

  /**
   * {@inheritdoc}
   */
  protected function getWidgetLibraries(): array {
    return [
      'selectify/selectify-dual',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getAriaRole(): string {
    return 'listbox';
  }

  /**
   * {@inheritdoc}
   */
  protected function getAriaHaspopup(): string {
    return 'grid';
  }

  /**
   * {@inheritdoc}
   */
  protected function alterElement(array &$element, string $max_selected): void {
    $element['#wrapper_attributes']['class'][] = $max_selected === '1' ? 'selectify-single' : 'selectify-multi';
  }

}
