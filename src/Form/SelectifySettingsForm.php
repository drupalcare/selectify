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

    // Section: Radio & Checkbox Styles.
    $form['enable_radio'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable custom styles for radio buttons'),
      '#default_value' => $config->get('enable_radio'),
    ];

    $form['radio_style'] = [
      '#type' => 'radios',
      '#title' => $this->t('Radio Button Style'),
      '#options' => [
        'toggle' => $this->t('Toggle Switch'),
        'checkbox' => $this->t('Checkbox with X/Checkmark'),
      ],
      '#default_value' => $config->get('radio_style') ?? 'toggle',
      '#states' => [
        'visible' => [
          ':input[name="enable_radio"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['radio_shape'] = [
      '#type' => 'radios',
      '#title' => $this->t('Radio Button Shape'),
      '#options' => [
        'circle' => $this->t('Circle'),
        'square' => $this->t('Square'),
      ],
      '#default_value' => $config->get('radio_shape') ?? 'circle',
      '#states' => [
        'visible' => [
          ':input[name="enable_radio"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['radio_size'] = [
      '#type' => 'radios',
      '#title' => $this->t('Radio Button Size'),
      '#options' => [
        'small' => $this->t('Small'),
        'medium' => $this->t('Medium'),
        'large' => $this->t('Large'),
      ],
      '#default_value' => $config->get('radio_size') ?? 'medium',
      '#states' => [
        'visible' => [
          ':input[name="enable_radio"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['enable_checkbox'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable custom styles for checkboxes'),
      '#default_value' => $config->get('enable_checkbox'),
    ];

    $form['checkbox_style'] = [
      '#type' => 'radios',
      '#title' => $this->t('Checkbox Style'),
      '#options' => [
        'toggle' => $this->t('Toggle Switch'),
        'checkbox' => $this->t('Checkbox with X/Checkmark'),
      ],
      '#default_value' => $config->get('checkbox_style') ?? 'toggle',
      '#states' => [
        'visible' => [
          ':input[name="enable_checkbox"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['checkbox_shape'] = [
      '#type' => 'radios',
      '#title' => $this->t('Checkbox Shape'),
      '#options' => [
        'circle' => $this->t('Circle'),
        'square' => $this->t('Square'),
      ],
      '#default_value' => $config->get('checkbox_shape') ?? 'circle',
      '#states' => [
        'visible' => [
          ':input[name="enable_checkbox"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['checkbox_size'] = [
      '#type' => 'radios',
      '#title' => $this->t('Checkbox Size'),
      '#options' => [
        'small' => $this->t('Small'),
        'medium' => $this->t('Medium'),
        'large' => $this->t('Large'),
      ],
      '#default_value' => $config->get('checkbox_size') ?? 'medium',
      '#states' => [
        'visible' => [
          ':input[name="enable_checkbox"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Section: Admin Route Control.
    $form['disable_on_admin_routes'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable Selectify on admin routes'),
      '#description' => $this->t('This action will disable the custom widget for radio buttons and checkboxes, as well as any Views exposed filters on administrative pages.'),
      '#default_value' => $config->get('disable_on_admin_routes'),
    ];

    // Section: Custom Disabled Pages.
    $form['disabled_pages'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Disable Selectify on specific pages'),
      '#description' => $this->t('Enter one URL path per line where Selectify should be completely disabled. You can use wildcards (*) for pattern matching. Examples:<br>- <code>admin/appearance/settings/gin</code> (exact match)<br>- <code>admin/appearance/settings/*</code> (all theme settings)<br>- <code>*/node/*/edit</code> (all node edit pages)<br>- <code>admin/config/*</code> (all config pages)'),
      '#default_value' => $config->get('disabled_pages') ?? "admin/appearance/settings/gin",
      '#rows' => 6,
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

    // Section: Accent Color Mode only visible when a color is selected.
    $form['accent_color_mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Accent Color Mode'),
      '#description' => $this->t('Choose whether to use light or dark mode for the selected accent color.'),
      '#default_value' => $config->get('accent_color_mode') ?? 'light',
      '#options' => [
        'light' => $this->t('Light'),
        'dark' => $this->t('Dark'),
      ],
      '#states' => [
        'visible' => [
          ':input[name="accent_color"]' => ['!value' => 'none'],
        ],
      ],
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
        'selectify_checkbox' => $this->t('Checkbox'),
        'selectify_dual' => $this->t('Dual List'),
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
            $this->t('Display Title'),
            $this->t('Display Type'),
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

      $this->messenger->addMessage($this->t('Selectify has been disabled. All related settings have been cleared.'), 'warning');
    }
    // If applying site-wide Views Select, store global settings/clear widgets.
    elseif ($apply_site_wide) {
      $config->set('apply_site_wide', TRUE)
        ->set('disable_views_widgets', FALSE)
        ->set('global_selectify_widget', $global_widget)
        ->clear('widget_for_filters');

      $this->messenger->addMessage($this->t('Global Selectify widget applied site-wide.'), 'status');
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
