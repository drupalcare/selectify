<?php

namespace Drupal\selectify\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Views;

/**
 * Configure settings for Selectify Radio/Checkbox & Views Filters.
 */
class SelectifySettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['selectify.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'selectify_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('selectify.settings');

    // Attach a library for styling the radio buttons.
    $form['#attached']['library'][] = 'selectify/selectify-settings-style';

    // Section: Radio & Checkbox Styles
    $form['enable_radio'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable custom styles for radio buttons'),
      '#default_value' => $config->get('enable_radio'),
      '#attributes' => ['class' => ['selectify-radio']],
    ];

    $form['enable_checkbox'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable custom styles for checkboxes'),
      '#default_value' => $config->get('enable_checkbox'),
      '#attributes' => ['class' => ['selectify-checkbox']],
    ];

    // Section: Admin Route Control
    $form['disable_on_admin_routes'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable Selectify on admin routes'),
      '#description' => $this->t('Prevent Selectify from applying styles on administrative pages.'),
      '#default_value' => $config->get('disable_on_admin_routes'),
    ];

    // Section: Accent Color Selection
    $form['accent_color'] = [
      '#type' => 'radios',
      '#title' => $this->t('Choose your favorite color'),
      '#default_value' => $config->get('accent_color'),
      '#options' => [
        'blue' => $this->t('Blue'),
        'coral' => $this->t('Coral'),
        'gold' => $this->t('Gold'),
        'indigo' => $this->t('Indigo'),
        'neutral' => $this->t('Neutral'),
        'slate' => $this->t('Slate'),
        'teal' => $this->t('Teal'),
      ],
      '#attributes' => ['class' => ['selectify-color-options']],
    ];

    // Apply Selectify to all exposed filters toggle.
    $form['apply_site_wide'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Apply Selectify to all exposed filters'),
      '#description' => $this->t('If checked, all exposed filters will use the same Selectify widget.'),
      '#default_value' => $config->get('apply_site_wide'),
    ];

    // Global widget selection when site-wide is enabled.
    $form['global_selectify_widget'] = [
      '#type' => 'select',
      '#title' => $this->t('Global Selectify Widget'),
      '#options' => [
        'selectify_dropdown' => $this->t('Dropdown'),
        'selectify_tags' => $this->t('Tags'),
        'selectify_searchable' => $this->t('Searchable'),
        'selectify_dual' => $this->t('Dual List'),
        'selectify_checkbox' => $this->t('Checkbox'),
      ],
      '#default_value' => $config->get('global_selectify_widget') ?? 'selectify_dropdown',
      '#states' => [
        'visible' => [
          ':input[name="apply_site_wide"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Section: Individual filter selection
    $views_filters = $this->getExposedFilters();
    if (!empty($views_filters)) {
      $form['selectify_widgets'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Selectify Widgets for Exposed Filters'),
        '#description' => $this->t('Choose which Selectify widget should be used for each Views exposed filter.'),
        '#states' => [
          'visible' => [
            ':input[name="apply_site_wide"]' => ['checked' => FALSE],
          ],
        ],
      ];

      foreach ($views_filters as $filter_id => $filter_label) {
        $form['selectify_widgets'][$filter_id] = [
          '#type' => 'select',
          '#title' => $this->t('Widget for %label', ['%label' => $filter_label]),
          '#options' => [
            'none' => $this->t('None'),
            'selectify_dropdown' => $this->t('Dropdown'),
            'selectify_tags' => $this->t('Tags'),
            'selectify_searchable' => $this->t('Searchable'),
            'selectify_dual' => $this->t('Dual List'),
            'selectify_checkbox' => $this->t('Checkbox'),
          ],
          '#default_value' => $config->get("widget_for_filter.$filter_id") ?? 'none',
        ];
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
      $config = $this->config('selectify.settings');

      // Save general settings.
      $config->set('enable_radio', $form_state->getValue('enable_radio'))
        ->set('enable_checkbox', $form_state->getValue('enable_checkbox'))
        ->set('disable_on_admin_routes', $form_state->getValue('disable_on_admin_routes'))
        ->set('accent_color', $form_state->getValue('accent_color'))
        ->set('apply_site_wide', $form_state->getValue('apply_site_wide'))
        ->set('global_selectify_widget', $form_state->getValue('global_selectify_widget'));

      // Save Selectify widget choices for each exposed filter (only if site-wide is disabled).
      if (!$form_state->getValue('apply_site_wide')) {
        $views_filters = $this->getExposedFilters();
        foreach ($views_filters as $filter_id => $filter_label) {
          $config->set("widget_for_filter.$filter_id", $form_state->getValue($filter_id));
        }
      }

      $config->save();
      parent::submitForm($form, $form_state);
  }

  /**
   * Get all Views exposed filters.
   *
   * @return array
   *   An array of filter machine names and their labels.
   */
  protected function getExposedFilters() {
    $filters = [];
    $views = Views::getAllViews();
    foreach ($views as $view) {
      foreach ($view->get('display') as $display_id => $display) {
        if (!empty($display['display_options']['filters'])) {
          foreach ($display['display_options']['filters'] as $filter_id => $filter) {
            if (!empty($filter['expose']['identifier'])) {
              $filters[$filter_id] = $filter['expose']['label'] ?? $filter_id;
            }
          }
        }
      }
    }
    return $filters;
  }

}
