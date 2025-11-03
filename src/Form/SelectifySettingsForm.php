<?php

namespace Drupal\selectify\Form;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\views\Views;

/**
 * Configure settings for Selectify Radio/Checkbox & Views Filters.
 */
class SelectifySettingsForm extends ConfigFormBase {

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new SelectifySettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config_manager
   *   The typed config manager service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    TypedConfigManagerInterface $typed_config_manager,
    MessengerInterface $messenger,
  ) {
    parent::__construct($config_factory, $typed_config_manager);
    $this->messenger = $messenger;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('messenger')
    );
  }

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

    // =========================================================================
    // SECTION 1: Page Control Settings
    // =========================================================================
    $form['page_control'] = [
      '#type' => 'details',
      '#title' => $this->t('Page Control Settings'),
      '#description' => $this->t('Control where Selectify is enabled or disabled on your site.'),
      '#open' => TRUE,
      '#weight' => 0,
    ];

    $form['page_control']['disable_on_admin_routes'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable Selectify on admin routes'),
      '#description' => $this->t('When enabled, Selectify will be disabled on all administrative pages (paths starting with <code>/admin</code>). This prevents conflicts with admin interfaces.'),
      '#default_value' => $config->get('disable_on_admin_routes'),
    ];

    $form['page_control']['disabled_pages'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Additional pages to disable Selectify'),
      '#description' => $this->t('Enter one URL path per line where Selectify should be completely disabled. You can use wildcards (*) for pattern matching.<br><strong>Examples:</strong><ul><li><code>admin/appearance/settings/gin</code> - exact match</li><li><code>admin/appearance/settings/*</code> - all theme settings</li><li><code>*/node/*/edit</code> - all node edit pages</li><li><code>admin/config/*</code> - all config pages</li></ul>'),
      '#default_value' => $config->get('disabled_pages') ?? "admin/appearance/settings/gin",
      '#rows' => 6,
    ];

    // =========================================================================
    // SECTION 2: Radio Button Styling
    // =========================================================================
    $form['radio_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Radio Button Styling'),
      '#description' => $this->t('Customize the appearance of radio buttons throughout your site.'),
      '#open' => TRUE,
      '#weight' => 10,
    ];

    $form['radio_settings']['enable_radio'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable custom styles for radio buttons'),
      '#description' => $this->t('Apply Selectify styling to all radio buttons. Uncheck to use default browser styles.'),
      '#default_value' => $config->get('enable_radio'),
    ];

    $form['radio_settings']['radio_style'] = [
      '#type' => 'radios',
      '#title' => $this->t('Radio button style'),
      '#options' => [
        'toggle' => $this->t('Toggle Switch - Modern sliding switch design'),
        'checkbox' => $this->t('Checkbox with X/Checkmark - Traditional style with visual indicator'),
      ],
      '#default_value' => $config->get('radio_style') ?? 'toggle',
      '#states' => [
        'visible' => [
          ':input[name="enable_radio"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['radio_settings']['radio_shape'] = [
      '#type' => 'radios',
      '#title' => $this->t('Radio button shape'),
      '#options' => [
        'circle' => $this->t('Circle - Traditional round shape'),
        'square' => $this->t('Square - Modern rectangular shape'),
      ],
      '#default_value' => $config->get('radio_shape') ?? 'circle',
      '#states' => [
        'visible' => [
          ':input[name="enable_radio"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['radio_settings']['radio_size'] = [
      '#type' => 'radios',
      '#title' => $this->t('Radio button size'),
      '#options' => [
        'small' => $this->t('Small - Compact for dense forms'),
        'medium' => $this->t('Medium - Recommended for most use cases'),
        'large' => $this->t('Large - Better for accessibility and touch interfaces'),
      ],
      '#default_value' => $config->get('radio_size') ?? 'medium',
      '#states' => [
        'visible' => [
          ':input[name="enable_radio"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // =========================================================================
    // SECTION 3: Checkbox Styling
    // =========================================================================
    $form['checkbox_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Checkbox Styling'),
      '#description' => $this->t('Customize the appearance of checkboxes throughout your site.'),
      '#open' => TRUE,
      '#weight' => 20,
    ];

    $form['checkbox_settings']['enable_checkbox'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable custom styles for checkboxes'),
      '#description' => $this->t('Apply Selectify styling to all checkboxes. Uncheck to use default browser styles.'),
      '#default_value' => $config->get('enable_checkbox'),
    ];

    $form['checkbox_settings']['checkbox_style'] = [
      '#type' => 'radios',
      '#title' => $this->t('Checkbox style'),
      '#options' => [
        'toggle' => $this->t('Toggle Switch - Modern sliding switch design'),
        'checkbox' => $this->t('Checkbox with X/Checkmark - Traditional style with visual indicator'),
      ],
      '#default_value' => $config->get('checkbox_style') ?? 'toggle',
      '#states' => [
        'visible' => [
          ':input[name="enable_checkbox"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['checkbox_settings']['checkbox_shape'] = [
      '#type' => 'radios',
      '#title' => $this->t('Checkbox shape'),
      '#options' => [
        'circle' => $this->t('Circle - Modern rounded style'),
        'square' => $this->t('Square - Traditional checkbox shape'),
      ],
      '#default_value' => $config->get('checkbox_shape') ?? 'circle',
      '#states' => [
        'visible' => [
          ':input[name="enable_checkbox"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['checkbox_settings']['checkbox_size'] = [
      '#type' => 'radios',
      '#title' => $this->t('Checkbox size'),
      '#options' => [
        'small' => $this->t('Small - Compact for dense forms'),
        'medium' => $this->t('Medium - Recommended for most use cases'),
        'large' => $this->t('Large - Better for accessibility and touch interfaces'),
      ],
      '#default_value' => $config->get('checkbox_size') ?? 'medium',
      '#states' => [
        'visible' => [
          ':input[name="enable_checkbox"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // =========================================================================
    // SECTION 4: Color Theme
    // =========================================================================
    $form['color_theme'] = [
      '#type' => 'details',
      '#title' => $this->t('Color Theme'),
      '#description' => $this->t('Choose accent colors for radio buttons and checkboxes when selected.'),
      '#open' => FALSE,
      '#weight' => 30,
    ];

    $form['color_theme']['accent_color'] = [
      '#type' => 'radios',
      '#title' => $this->t('Accent color'),
      '#description' => $this->t('Choose the color that appears when a radio button or checkbox is selected.'),
      '#default_value' => $config->get('accent_color') ?? 'none',
      '#options' => [
        'none' => $this->t('None - Use default browser colors'),
        'blue' => $this->t('Blue'),
        'coral' => $this->t('Coral'),
        'gold' => $this->t('Gold'),
        'indigo' => $this->t('Indigo'),
        'neutral' => $this->t('Neutral'),
        'slate' => $this->t('Slate'),
        'teal' => $this->t('Teal'),
      ],
    ];

    $form['color_theme']['accent_color_mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Color mode'),
      '#description' => $this->t('Choose between light and dark variants of the selected accent color.'),
      '#options' => [
        'light' => $this->t('Light'),
        'dark' => $this->t('Dark'),
      ],
      '#default_value' => $config->get('accent_color_mode') ?? 'light',
      '#states' => [
        'invisible' => [
          ':input[name="accent_color"]' => ['value' => 'none'],
        ],
      ],
    ];

    // =========================================================================
    // SECTION 5: Views Select Filters
    // =========================================================================
    $form['views_filters'] = [
      '#type' => 'details',
      '#title' => $this->t('Views Select Filters'),
      '#description' => $this->t('Configure custom widgets for Views exposed filters. Choose between site-wide or per-view configuration.'),
      '#open' => FALSE,
      '#weight' => 40,
    ];

    $form['views_filters']['disable_views_widgets'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable Selectify for all Views filters'),
      '#description' => $this->t('When enabled, all Views exposed select filters will use their default widgets.'),
      '#default_value' => $config->get('disable_views_widgets'),
    ];

    $form['views_filters']['apply_site_wide'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Apply Selectify to all Views exposed filters site-wide'),
      '#description' => $this->t('When enabled, all Views exposed select filters will use the widget selected below. This overrides individual view configurations.'),
      '#default_value' => $config->get('apply_site_wide'),
      '#states' => [
        'visible' => [
          ':input[name="disable_views_widgets"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['views_filters']['global_selectify_widget'] = [
      '#type' => 'select',
      '#title' => $this->t('Site-wide widget style'),
      '#description' => $this->t('This widget will be applied to all Views exposed select filters across your site.'),
      '#options' => [
        'none' => $this->t('None - Use default widgets'),
        'selectify_dropdown' => $this->t('Dropdown - Enhanced dropdown with better styling'),
        'selectify_tags' => $this->t('Tags - Enhanced dropdown displaying selections as removable tags'),
        'selectify_searchable' => $this->t('Searchable - Enhanced dropdown with search/filter capability'),
        'selectify_checkbox' => $this->t('Checkbox - Enhanced dropdown with checkbox-style selections'),
        'selectify_dual' => $this->t('Dual List - Side-by-side selection (available/selected lists)'),
      ],
      '#default_value' => $config->get('global_selectify_widget') ?? 'none',
      '#states' => [
        'visible' => [
          ':input[name="disable_views_widgets"]' => ['checked' => FALSE],
          ':input[name="apply_site_wide"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Per-view widget configuration.
    $form['views_filters']['per_view_configuration'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          ':input[name="disable_views_widgets"]' => ['checked' => FALSE],
          ':input[name="apply_site_wide"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['views_filters']['per_view_configuration']['description'] = [
      '#type' => 'markup',
      '#markup' => '<div class="messages messages--info">' . $this->t('<strong>Per-View Configuration:</strong> Select individual widgets for each View display below. Only Views with exposed select filters are shown.') . '</div>',
    ];

    $all_filters = $this->getExposedFilters();
    if (!empty($all_filters)) {
      $stored_filters = $config->get('widget_for_filters', []);
      $grouped_displays = [];

      // Group displays by view.
      foreach ($all_filters as $display_data) {
        $view_machine_name = $display_data['view_machine_name'];
        $grouped_displays[$view_machine_name][] = $display_data;
      }

      $form['views_filters']['per_view_configuration']['selectify_widgets'] = [
        '#tree' => TRUE,
      ];

      foreach ($grouped_displays as $view_machine_name => $displays) {
        $view_human_name = $displays[0]['view_human_name'];

        $form['views_filters']['per_view_configuration']['selectify_widgets'][$view_machine_name] = [
          '#type' => 'details',
          '#title' => $view_human_name,
          '#open' => FALSE,
        ];

        $form['views_filters']['per_view_configuration']['selectify_widgets'][$view_machine_name]['filters_table'] = [
          '#type' => 'table',
          '#header' => [
            $this->t('Display Name'),
            $this->t('Display Type'),
            $this->t('Display ID'),
            $this->t('Exposed Form ID'),
            $this->t('Selectify Widget'),
          ],
          '#tree' => TRUE,
        ];

        foreach ($displays as $display_data) {
          $unique_key = $display_data['view_machine_name'] . ':' . $display_data['display_id'];

          // Check if this row has a selected widget (not 'none').
          $selected_widget = $stored_filters[$unique_key]['widget'] ?? 'none';
          $row_class = ($selected_widget !== 'none') ? 'selected-widget-row' : '';

          $form['views_filters']['per_view_configuration']['selectify_widgets'][$view_machine_name]['filters_table'][$unique_key] = [
          // Add class dynamically.
            '#attributes' => ['class' => [$row_class]],
            'display_title' => [
              '#markup' => '<strong>' . $display_data['display_title'] . '</strong>',
            ],
            'display_type' => [
              '#markup' => ucfirst(str_replace('_', ' ', $display_data['display_type'])),
            ],
            'display_id' => [
              '#markup' => '<code>' . $display_data['display_id'] . '</code>',
            ],
            'exposed_form_id' => [
              '#markup' => "<code>{$display_data['exposed_form_id']}</code>",
            ],
            'widget' => [
              '#type' => 'select',
              '#title' => $this->t('Widget for %label', ['%label' => $display_data['display_title']]),
              '#title_display' => 'invisible',
              '#options' => [
                'none' => $this->t('None'),
                'selectify_dropdown' => $this->t('Dropdown - Enhanced dropdown with better styling'),
                'selectify_tags' => $this->t('Tags - Enhanced dropdown displaying selections as removable tags'),
                'selectify_searchable' => $this->t('Searchable - Enhanced dropdown with search/filter capability'),
                'selectify_checkbox' => $this->t('Checkbox - Enhanced dropdown with checkbox-style selections'),
                'selectify_dual' => $this->t('Dual List - Side-by-side selection (available/selected lists)'),
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
    else {
      $form['views_filters']['per_view_configuration']['no_views'] = [
        '#type' => 'markup',
        '#markup' => '<div class="messages messages--warning">' . $this->t('No Views with exposed select filters were found. Create a View with exposed filters to configure Selectify widgets.') . '</div>',
      ];
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
      ->set('radio_style', $form_state->getValue('radio_style', 'toggle'))
      ->set('radio_shape', $form_state->getValue('radio_shape', 'circle'))
      ->set('radio_size', $form_state->getValue('radio_size', 'medium'))
      ->set('enable_checkbox', (bool) $form_state->getValue('enable_checkbox', FALSE))
      ->set('checkbox_style', $form_state->getValue('checkbox_style', 'toggle'))
      ->set('checkbox_shape', $form_state->getValue('checkbox_shape', 'circle'))
      ->set('checkbox_size', $form_state->getValue('checkbox_size', 'medium'))
      ->set('disable_on_admin_routes', (bool) $form_state->getValue('disable_on_admin_routes', FALSE))
      ->set('disabled_pages', $form_state->getValue('disabled_pages', ''))
      ->set('accent_color', $form_state->getValue('accent_color', $config->get('accent_color', 'neutral')))
      ->set('accent_color_mode', $form_state->getValue('accent_color_mode', $config->get('accent_color_mode', 'light')));

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

      $this->messenger->addMessage($this->t('Selectify has been disabled for all Views filters. All related settings have been cleared.'), 'warning');
    }
    // If applying site-wide Views Select, store global settings/clear widgets.
    elseif ($apply_site_wide) {
      $config->set('apply_site_wide', TRUE)
        ->set('disable_views_widgets', FALSE)
        ->set('global_selectify_widget', $global_widget)
        ->clear('widget_for_filters');

      $this->messenger->addMessage($this->t('Global Selectify widget applied site-wide to all Views exposed filters.'), 'status');
    }
    // If specific widgets are selected, store them & clear global settings.
    elseif (!empty($views_widgets)) {
      $stored_widgets = $this->processViewsWidgetSettings($form_state, $config);

      $config->set('apply_site_wide', FALSE)
        ->set('disable_views_widgets', FALSE)
        ->set('widget_for_filters', $stored_widgets)
        ->clear('global_selectify_widget');

      $this->messenger->addMessage($this->t('Custom Selectify widgets applied to specific Views filters.'), 'status');
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
   * Retrieves all Views displays with exposed select filters.
   *
   * @return array
   *   An array of exposed forms grouped by View & Display, only including
   *   displays that have at least one exposed select/dropdown filter.
   */
  protected function getExposedFilters() {
    $filters = [];
    $views = Views::getAllViews();

    foreach ($views as $view) {
      $view_machine_name = $view->id();
      $view_human_name = $view->label();

      foreach ($view->get('display') as $display_id => $display) {
        $display_type = $display['display_plugin'];
        $exposed_form_id = "views-exposed-form-{$view_machine_name}-{$display_id}";
        $has_select_filter = FALSE;

        // Get display title.
        $display_title = $display['display_title'] ?? $this->t('Untitled Display');

        // Check if this display has an exposed SELECT filter.
        if (!empty($display['display_options']['filters'])) {
          foreach ($display['display_options']['filters'] as $filter) {
            // Check if filter is exposed AND is a select/dropdown widget.
            if (!empty($filter['exposed']) &&
                !empty($filter['expose']['identifier'])) {

              // Check if the widget is a select-based type.
              $widget = $filter['expose']['widget'] ?? 'select';

              // Include filters that use select, dropdown, or haven't specified
              // a widget. (default widget for most filters is select).
              if (in_array($widget, ['select', 'radios'], TRUE) || empty($filter['expose']['widget'])) {
                $has_select_filter = TRUE;
                break;
              }
            }
          }
        }

        // Only add this display if it has an exposed SELECT filter.
        if ($has_select_filter) {
          $unique_key = "{$view_machine_name}:{$display_id}";

          // Store display information.
          $filters[$unique_key] = [
            'view_machine_name' => $view_machine_name,
            'view_human_name' => $view_human_name,
            'display_id' => $display_id,
            'display_title' => $display_title,
            'display_type' => $display_type,
            'exposed_form_id' => $exposed_form_id,
          ];
        }
      }
    }

    return $filters;
  }

}
