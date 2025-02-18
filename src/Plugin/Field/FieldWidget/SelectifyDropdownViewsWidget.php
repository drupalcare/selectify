<?php

namespace Drupal\selectify\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;

/**
 * Defines the Selectify Dropdown Widget for Views Exposed Filters.
 *
 * @FieldWidget(
 *   id = "selectify_dropdown_views",
 *   label = @Translation("Selectify Dropdown (Views)"),
 *   field_types = {
 *     "list_string",
 *     "entity_reference"
 *   }
 * )
 */
class SelectifyDropdownViewsWidget extends FilterPluginBase {

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['selectify_widget'] = [
      '#type' => 'select',
      '#title' => $this->t('Selectify Widget'),
      '#options' => [
        'selectify_dropdown' => $this->t('Dropdown'),
        'selectify_tags' => $this->t('Tags'),
        'selectify_searchable' => $this->t('Searchable'),
        'selectify_dual' => $this->t('Dual List'),
        'selectify_checkbox' => $this->t('Checkbox'),
      ],
      '#default_value' => $this->options['selectify_widget'] ?? 'selectify_dropdown',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildExposedForm(&$form, FormStateInterface $form_state) {
    $field_name = $this->options['expose']['identifier'] ?? 'selectify_filter';

    // Map available Selectify widgets.
    $widget_map = [
      'selectify_dropdown' => ['template' => 'selectify--dropdown', 'libraries' => ['selectify/selectify-dropdown']],
      'selectify_tags' => ['template' => 'selectify--dropdown-tags', 'libraries' => ['selectify/selectify-tags']],
      'selectify_searchable' => ['template' => 'selectify--dropdown-searchable', 'libraries' => ['selectify/selectify-searchable']],
      'selectify_dual' => ['template' => 'selectify--dual', 'libraries' => ['selectify/selectify-dual-list']],
      'selectify_checkbox' => ['template' => 'selectify--dropdown-checkbox', 'libraries' => ['selectify/selectify-base']],
    ];

    $selected_widget = $this->options['selectify_widget'] ?? 'selectify_dropdown';
    $config = $widget_map[$selected_widget] ?? $widget_map['selectify_dropdown'];

    // Apply the selected Selectify widget.
    $form[$field_name]['#theme'] = $config['template'];
    $form[$field_name]['#attached']['library'] = $config['libraries'];
  }
}
