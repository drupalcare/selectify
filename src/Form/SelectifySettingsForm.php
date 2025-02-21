<?php

namespace Drupal\selectify\Form;

use Drupal\Core\Config\Config;
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

    // Section: Radio & Checkbox Styles.
    $form['enable_radio'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable custom styles for radio buttons'),
      '#default_value' => $config->get('enable_radio'),
    ];

    $form['enable_checkbox'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable custom styles for checkboxes'),
      '#default_value' => $config->get('enable_checkbox'),
    ];

    // Section: Admin Route Control.
    $form['disable_on_admin_routes'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable Selectify on admin routes'),
      '#description' => $this->t('This action will disable the custom widget for radio buttons and checkboxes, as well as any Views exposed filters on administrative pages.'),
      '#default_value' => $config->get('disable_on_admin_routes'),
    ];

    // Section: Accent Color Selection.
    $form['accent_color'] = [
      '#type' => 'radios',
      '#title' => $this->t('Choose your favorite color'),
      '#default_value' => $config->get('accent_color') ?? 'none',
      '#options' => [
        'none' => $this->t('None'),
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

    $form['disable_views_widgets'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable Select Widgets'),
      '#description' => $this->t('Checking this will remove all the custom select widgets and settings.'),
      '#default_value' => $config->get('disable_views_widgets', FALSE),
    ];

    // Apply Selectify to all exposed filters toggle.
    $form['apply_site_wide'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Apply Selectify to all exposed filters'),
      '#description' => $this->t('If checked, all exposed filters will use the same Selectify widget.'),
      '#default_value' => $config->get('apply_site_wide') ?? TRUE,
      '#states' => [
        'disabled' => [
          ':input[name="disable_views_widgets"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['global_selectify_widget'] = [
      '#type' => 'select',
      '#title' => $this->t('Global Selectify Widget'),
      '#options' => [
        'none' => $this->t('None'),
        'selectify_dropdown' => $this->t('Dropdown'),
        'selectify_tags' => $this->t('Tags'),
        'selectify_searchable' => $this->t('Searchable'),
        'selectify_dual' => $this->t('Dual List'),
        'selectify_checkbox' => $this->t('Checkbox'),
      ],
      '#default_value' => $config->get('global_selectify_widget') ?? 'none',
      '#states' => [
        'disabled' => [
          ':input[name="disable_views_widgets"]' => ['checked' => TRUE],
        ],
        'visible' => [
        // Only show when `apply_site_wide` is checked.
          ':input[name="apply_site_wide"]' => ['checked' => TRUE],
        ],

      ],
    ];

    $views_displays = $this->getExposedFilters();
    $stored_filters = $config->get('widget_for_filters') ?? [];

    // Step 1: Group by View Machine Name.
    $grouped_views = [];
    foreach ($views_displays as $display_data) {
      $view_name = $display_data['view_machine_name'];
      $grouped_views[$view_name][] = $display_data;
    }

    // Step 2: Build the accordion structure.
    if (!empty($grouped_views)) {
      $form['selectify_widgets'] = [
        '#type' => 'details',
        '#title' => $this->t('Selectify Select Widgets for Views Exposed Filters'),
        '#description' => $this->t('Choose the Selectify Select widget for each Views Display with an Exposed Filter.'),
        '#open' => FALSE,
        '#states' => [
          'disabled' => [
            ':input[name="disable_views_widgets"]' => ['checked' => TRUE],
          ],
          'invisible' => [
            [':input[name="apply_site_wide"]' => ['checked' => TRUE]],
          ],
        ],
      ];

      foreach ($grouped_views as $view_machine_name => $displays) {
        // Sort displays inside each View (Move selected widgets to the top)
        usort($displays, function ($a, $b) use ($stored_filters) {
          $widgetA = $stored_filters[$a['view_machine_name'] . ':' . $a['display_id']]['widget'] ?? 'none';
          $widgetB = $stored_filters[$b['view_machine_name'] . ':' . $b['display_id']]['widget'] ?? 'none';
          return ($widgetA === 'none') - ($widgetB === 'none');
        });

        // Step 3: Create an accordion section per View.
        $form['selectify_widgets'][$view_machine_name] = [
          '#type' => 'details',
          '#title' => $this->t('@view_name', ['@view_name' => $view_machine_name]),
          '#open' => FALSE,
        ];

        // Step 4: Create the table inside each accordion.
        $form['selectify_widgets'][$view_machine_name]['filters_table'] = [
          '#type' => 'table',
          '#header' => [
            $this->t('Display ID'),
            $this->t('Exposed Form ID'),
            $this->t('Selectify Widget'),
          ],
          '#tree' => TRUE,
        ];

        foreach ($displays as $display_data) {
          $unique_key = $display_data['view_machine_name'] . ':' . $display_data['display_id'];

          // Check if this row has a selected widget (not 'none')
          $selected_widget = $stored_filters[$unique_key]['widget'] ?? 'none';
          $row_class = ($selected_widget !== 'none') ? 'selected-widget-row' : '';

          $form['selectify_widgets'][$view_machine_name]['filters_table'][$unique_key] = [
          // Add class dynamically.
            '#attributes' => ['class' => [$row_class]],
            'display_id' => [
              '#markup' => $display_data['display_id'],
            ],
            'exposed_form_id' => [
              '#markup' => "<code>{$display_data['exposed_form_id']}</code>",
            ],
            'widget' => [
              '#type' => 'select',
              '#title' => $this->t('Widget for %label', ['%label' => $display_data['display_id']]),
              '#options' => [
                'none' => $this->t('None'),
                'selectify_dropdown' => $this->t('Dropdown'),
                'selectify_tags' => $this->t('Tags'),
                'selectify_searchable' => $this->t('Searchable'),
                'selectify_dual' => $this->t('Dual List'),
                'selectify_checkbox' => $this->t('Checkbox'),
              ],
              '#default_value' => $selected_widget,
              '#parents' => ['selectify_widgets',
                $view_machine_name, 'filters_table',
                $unique_key, 'widget',
              ],
            ],
          ];
        }
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory()->getEditable('selectify.settings');

    // Preserve existing settings while updating necessary ones.
    $config->set('enable_radio', (bool) $form_state->getValue('enable_radio', FALSE))
      ->set('enable_checkbox', (bool) $form_state->getValue('enable_checkbox', FALSE))
      ->set('disable_on_admin_routes', (bool) $form_state->getValue('disable_on_admin_routes', FALSE))
      ->set('accent_color', $form_state->getValue('accent_color', $config->get('accent_color', 'neutral')));

    // Retrieve values from form.
    $apply_site_wide = (bool) $form_state->getValue('apply_site_wide', FALSE);
    $disable_views_widgets = (bool) $form_state->getValue('disable_views_widgets', FALSE);
    $global_widget = $form_state->getValue('global_selectify_widget', $config->get('global_selectify_widget', 'none'));
    $views_widgets = $form_state->getValue('selectify_widgets', []);

    // If disabling Views Select, clear everything.
    if ($disable_views_widgets) {
      $config->set('disable_views_widgets', TRUE)
        ->clear('apply_site_wide')
        ->clear('global_selectify_widget')
        ->clear('widget_for_filters');

      \Drupal::messenger()->addMessage($this->t('Selectify has been disabled. All related settings have been cleared.'), 'warning');
    }
    // If applying site-wide Views Select, store global settings/clear widgets.
    elseif ($apply_site_wide) {
      $config->set('apply_site_wide', TRUE)
        ->set('disable_views_widgets', FALSE)
        ->set('global_selectify_widget', $global_widget)
        ->clear('widget_for_filters');

      \Drupal::messenger()->addMessage($this->t('Global Selectify widget applied site-wide.'), 'status');
    }
    // If specific widgets are selected, store them & clear global settings.
    elseif (!empty($views_widgets)) {
      $stored_widgets = $this->processViewsWidgetSettings($form_state, $config);

      $config->set('apply_site_wide', FALSE)
        ->set('disable_views_widgets', FALSE)
        ->set('widget_for_filters', $stored_widgets)
        ->clear('global_selectify_widget');

      \Drupal::messenger()->addMessage($this->t('Custom Selectify widgets applied to specific Views filters.'), 'status');
    }

    $config->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * Processes Views widget settings and returns updated widget configuration.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   * @param \Drupal\Core\Config\Config $config
   *   The Selectify configuration object.
   *
   * @return array
   *   An associative array of Views widget settings.
   */
  private function processViewsWidgetSettings(FormStateInterface $form_state, Config $config) {

    $stored_widgets = $config->get('widget_for_filters', []);
    $views_data = $form_state->getValue('selectify_widgets', []);

    if (!empty($views_data) && is_array($views_data)) {
      foreach ($views_data as $view_machine_name => $view_content) {
        if (!isset($view_content['filters_table']) || !is_array($view_content['filters_table'])) {
          continue;
        }

        foreach ($view_content['filters_table'] as $unique_key => $values) {
          if (!empty($values['widget']) && $values['widget'] !== 'none') {
            $exposed_form_id = $values['exposed_form_id'] ?? '';

            // Generate `exposed_form_id` if missing.
            if (empty($exposed_form_id)) {
              [$view_machine_name, $display_id] = explode(':', $unique_key, 2);
              $exposed_form_id = "views-exposed-form-{$view_machine_name}-{$display_id}";
            }

            $stored_widgets[$unique_key] = [
              'widget' => $values['widget'],
              'exposed_form_id' => $exposed_form_id,
            ];
          }
        }
      }
    }

    return $stored_widgets;
  }

  /**
   * Retrieves all Views displays that have an exposed form with at least one.
   *
   * @return array
   *   An array of exposed forms grouped by View & Display.
   */
  protected function getExposedFilters() {
    $filters = [];
    $views = Views::getAllViews();

    foreach ($views as $view) {
      $view_machine_name = $view->id();
      $view_human_name = $view->label();
      $has_exposed_filter = FALSE;

      foreach ($view->get('display') as $display_id => $display) {
        $display_type = $display['display_plugin'];
        $exposed_form_id = "views-exposed-form-{$view_machine_name}-{$display_id}";

        // Check if this display has an exposed filter.
        if (!empty($display['display_options']['filters'])) {
          foreach ($display['display_options']['filters'] as $filter_id => $filter) {
            if (!empty($filter['expose']['identifier'])) {
              $has_exposed_filter = TRUE;
              // No need to check further once we find an exposed filter.
              break;
            }
          }
        }

        // Only add this display if it has an exposed filter.
        if ($has_exposed_filter) {
          $unique_key = "{$view_machine_name}:{$display_id}";

          // Store display information.
          $filters[$unique_key] = [
            'view_machine_name' => $view_machine_name,
            'view_human_name' => $view_human_name,
            'display_id' => $display_id,
            'display_type' => $display_type,
            'exposed_form_id' => $exposed_form_id,
          ];
        }
      }
    }

    return $filters;
  }

}
