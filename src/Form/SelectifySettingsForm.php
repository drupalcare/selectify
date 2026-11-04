<?php

declare(strict_types=1);

namespace Drupal\selectify\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\webform\Entity\Webform;
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
   * Returns Selectify widget options (single source for widget labels).
   *
   * @return array
   *   Associative array of widget machine name => translated label.
   */
  protected function getSelectifyWidgetOptions(): array {
    return [
      'selectify_dropdown' => $this->t('Dropdown - Enhanced dropdown with better styling'),
      'selectify_tags' => $this->t('Tags - Enhanced dropdown displaying selections as removable tags'),
      'selectify_searchable' => $this->t('Searchable - Enhanced dropdown with search/filter capability'),
      'selectify_checkbox' => $this->t('Checkbox - Enhanced dropdown with checkbox-style selections'),
      'selectify_dual' => $this->t('Dual List - Side-by-side selection (available/selected lists)'),
    ];
  }

  /**
   * Returns accent color options (single source for color labels).
   *
   * @return array
   *   Associative array of color key => translated label.
   */
  protected function getAccentColorOptions(): array {
    return [
      'blue' => $this->t('Blue'),
      'coral' => $this->t('Coral'),
      'gold' => $this->t('Gold'),
      'indigo' => $this->t('Indigo'),
      'neutral' => $this->t('Neutral'),
      'slate' => $this->t('Slate'),
      'teal' => $this->t('Teal'),
    ];
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
        'checkbox' => $this->t('Checkbox with Checkmark - Traditional style with visual indicator'),
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
    // SECTION 4: Frontend Color Theme
    // =========================================================================
    $form['color_theme'] = [
      '#type' => 'details',
      '#title' => $this->t('Frontend Color Theme'),
      '#description' => $this->t('Choose accent colors for radio buttons, checkboxes, and select widgets on the frontend.'),
      '#open' => FALSE,
      '#weight' => 30,
    ];

    $form['color_theme']['accent_color_mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Color mode'),
      '#description' => $this->t('Choose between light and dark variants of the selected accent color.'),
      '#options' => [
        'none' => $this->t('None - Use default browser colors'),
        'light' => $this->t('Light'),
        'dark' => $this->t('Dark'),
      ],
      '#default_value' => $config->get('accent_color_mode') ?? 'light',
    ];

    $form['color_theme']['accent_color'] = [
      '#type' => 'radios',
      '#title' => $this->t('Accent color'),
      '#description' => $this->t('Choose the color that appears when a radio button or checkbox is selected.'),
      '#default_value' => $config->get('accent_color') ?? 'blue',
      '#options' => $this->getAccentColorOptions(),
      '#states' => [
        'disabled' => [
          ':input[name="accent_color_mode"]' => ['value' => 'none'],
        ],
      ],
      '#after_build' => [
        [get_called_class(), 'addDataAccent'],
      ],
    ];

    // =========================================================================
    // SECTION 4b: Admin Color Theme
    // =========================================================================
    $form['admin_color_theme'] = [
      '#type' => 'details',
      '#title' => $this->t('Admin Color Theme'),
      '#description' => $this->t('Choose accent colors for radio buttons, checkboxes, and select widgets on admin pages.'),
      '#open' => FALSE,
      '#weight' => 35,
    ];

    $form['admin_color_theme']['admin_accent_color_mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Color mode'),
      '#description' => $this->t('Choose between light and dark variants of the selected accent color for admin pages.'),
      '#options' => [
        'none' => $this->t('None - Use default browser colors'),
        'light' => $this->t('Light'),
        'dark' => $this->t('Dark'),
      ],
      '#default_value' => $config->get('admin_accent_color_mode') ?? 'light',
    ];

    $form['admin_color_theme']['admin_accent_color'] = [
      '#type' => 'radios',
      '#title' => $this->t('Accent color'),
      '#description' => $this->t('Choose the color that appears when a radio button or checkbox is selected on admin pages.'),
      '#default_value' => $config->get('admin_accent_color') ?? 'blue',
      '#options' => $this->getAccentColorOptions(),
      '#states' => [
        'disabled' => [
          ':input[name="admin_accent_color_mode"]' => ['value' => 'none'],
        ],
      ],
      '#after_build' => [
        [get_called_class(), 'addDataAccent'],
      ],
    ];

    // =========================================================================
    // SECTION 5: Form API Select Elements
    // =========================================================================
    $form['form_api_selects'] = [
      '#type' => 'details',
      '#title' => $this->t('Form API Select Elements'),
      '#description' => $this->t('Apply Selectify to plain &lt;select&gt; elements in regular Drupal forms (Form API). This covers any select that is not already handled by Views exposed filters, Webform submissions, or Field Widgets.<br><br><strong>Two ways to target selects:</strong><br>1. <strong>Per-form discovery</strong> &ndash; Enable the checkbox below, visit pages with forms, then return here to assign a widget to each discovered form.<br>2. <strong>CSS selector rules</strong> &ndash; Target specific selects by CSS selector (e.g. <code>select[id^="edit-parent"]</code>, <code>select.my-class</code>) and assign a widget per rule. CSS selector rules are evaluated first; any select already matched by a rule will not be overridden by the per-form widget.<br><br><strong>Safety:</strong> Selectify maintains a built-in blocklist of AJAX-dependent admin forms that are always excluded (Field UI, Views UI, Layout Builder, Editor config, Media Library, etc.). Individual selects with <code>#ajax</code> callbacks are also automatically skipped.'),
      '#open' => FALSE,
      '#weight' => 42,
    ];

    $form['form_api_selects']['enable_form_api_selects'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Selectify for Form API selects'),
      '#description' => $this->t('When enabled, Selectify can be applied to specific discovered forms below. <strong>Important:</strong> After enabling and saving, visit pages containing your forms, then return here and refresh to see discovered forms. AJAX-dependent selects and known admin forms are always excluded.'),
      '#default_value' => $config->get('enable_form_api_selects'),
    ];

    // CSS selector rules: target specific selects by CSS selector.
    $form['form_api_selects']['css_selector_configuration'] = [
      '#type' => 'details',
      '#title' => $this->t('CSS selector rules'),
      '#description' => $this->t('Target specific select elements using CSS selectors. These rules are evaluated first &mdash; any select matched here will not be overridden by the per-form widget below.<br><strong>Supported selectors:</strong> <code>select#edit-type</code>, <code>select[id^="edit-parent"]</code>, <code>select[id*="schema"]</code>, <code>select.my-class</code>, <code>select[name="field_color"]</code>, <code>select[name^="field_"]</code><br><strong>Note:</strong> Drupal appends <code>--N</code> suffixes (e.g. <code>edit-admin-theme--2</code>) to HTML IDs during rendering, but selectors are matched <em>before</em> rendering. Use the base ID without the suffix (e.g. <code>select#edit-admin-theme</code>) or a starts-with selector (e.g. <code>select[id^="edit-admin-theme"]</code>). The <code>name</code> attribute (e.g. <code>select[name="admin_theme"]</code>) is always reliable. If the ID contains <code>--N</code> suffixes and you want to style all selects in that form, use the <strong>Per-form configuration</strong> below instead &mdash; it applies the widget to every eligible select in the form without needing to match individual IDs.'),
      '#open' => FALSE,
      '#states' => [
        'visible' => [
          ':input[name="enable_form_api_selects"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $stored_css_rules = $config->get('css_selector_widget_map') ?? [];
    if (!is_array($stored_css_rules)) {
      $stored_css_rules = [];
    }

    $form['form_api_selects']['css_selector_configuration']['css_selector_rules'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('CSS Selector'),
        $this->t('Selectify widget'),
      ],
      '#tree' => TRUE,
    ];

    // Build rows from stored rules plus empty rows for adding new rules.
    $css_rows = [];
    foreach ($stored_css_rules as $rule) {
      if (!empty($rule['selector']) && !empty($rule['widget'])) {
        $css_rows[] = [
          'selector' => $rule['selector'],
          'widget' => $rule['widget'],
        ];
      }
    }
    // Add empty rows so the user can add new rules.
    for ($i = 0; $i < 5; $i++) {
      $css_rows[] = ['selector' => '', 'widget' => 'none'];
    }

    // Rotate placeholder examples to showcase supported selectors.
    $placeholders = [
      $this->t('e.g. select#edit-type'),
      $this->t('e.g. select[id^="edit-parent"]'),
      $this->t('e.g. select[name="field_color"]'),
      $this->t('e.g. select[name^="field_"]'),
      $this->t('e.g. select.my-class'),
    ];

    foreach ($css_rows as $delta => $row) {
      $placeholder = $placeholders[$delta % count($placeholders)];
      $form['form_api_selects']['css_selector_configuration']['css_selector_rules'][$delta] = [
        'selector' => [
          '#type' => 'textfield',
          '#title' => '',
          '#default_value' => $row['selector'],
          '#size' => 50,
          '#maxlength' => 255,
          '#placeholder' => $placeholder,
        ],
        'widget' => [
          '#type' => 'select',
          '#title' => '',
          '#options' => array_merge(['none' => $this->t('None')], $this->getSelectifyWidgetOptions()),
          '#default_value' => $row['widget'],
          '#wrapper_attributes' => ['class' => ['form-item--table-cell']],
        ],
      ];
    }

    // Per-form configuration (Views-like): list only discovered forms that
    // contain eligible select elements.
    $form['form_api_selects']['per_form_configuration'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          ':input[name="enable_form_api_selects"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['form_api_selects']['per_form_configuration']['description'] = [
      '#type' => 'markup',
      '#markup' => '<div class="messages messages--info">' . $this->t('<strong>Per-form configuration:</strong> Only forms that have been detected with eligible select elements are shown below.<br><br><strong>To discover forms:</strong><ol><li>Enable "@checkbox_label" above</li><li>Click "@save_button" at the bottom of this page</li><li>Visit the pages on your site that contain the forms you want to style</li><li>Return to this settings page and refresh/reload it</li></ol>The forms you visited will then appear in the table below.', [
        '@checkbox_label' => $this->t('Enable Selectify for Form API selects'),
        '@save_button' => $this->t('Save configuration'),
      ]) . '</div>',
    ];

    $stored_map = [];
    $stored_entries = $config->get('form_api_widget_map') ?? [];
    if (is_array($stored_entries)) {
      foreach ($stored_entries as $entry) {
        if (!empty($entry['form_id']) && !empty($entry['widget'])) {
          $stored_map[$entry['form_id']] = $entry['widget'];
        }
      }
    }

    // phpcs:ignore DrupalPractice.Objects.GlobalDrupal.GlobalDrupal
    $discovered = \Drupal::state()->get('selectify.form_api_discovered_forms', []);
    if (!is_array($discovered)) {
      $discovered = [];
    }
    $discovered_ids = array_keys($discovered);
    sort($discovered_ids, SORT_NATURAL | SORT_FLAG_CASE);

    /** @var \Drupal\selectify\Service\SelectifyHelper $selectify_helper */
    // phpcs:ignore DrupalPractice.Objects.GlobalDrupal.GlobalDrupal
    $selectify_helper = \Drupal::service('selectify.helper');
    // phpcs:ignore DrupalPractice.Objects.GlobalDrupal.GlobalDrupal
    $date_formatter = \Drupal::service('date.formatter');

    $form['form_api_selects']['per_form_configuration']['form_api_widgets'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Form ID'),
        $this->t('Last seen'),
        $this->t('Selectify widget'),
      ],
      '#tree' => TRUE,
    ];

    $row_count = 0;
    foreach ($discovered_ids as $discovered_form_id) {
      // Validate form_id to prevent XSS and ensure it's a valid string.
      if (!is_string($discovered_form_id) || empty($discovered_form_id) || strlen($discovered_form_id) > 255) {
        continue;
      }

      // Skip forms that are always excluded (built-in blocklist).
      if ($selectify_helper->isFormExcluded($discovered_form_id)) {
        continue;
      }

      $row_count++;
      $selected_widget = $stored_map[$discovered_form_id] ?? 'none';

      // Safely extract last_seen timestamp.
      $last_seen = NULL;
      if (isset($discovered[$discovered_form_id]) && is_array($discovered[$discovered_form_id])) {
        $last_seen = $discovered[$discovered_form_id]['last_seen'] ?? NULL;
      }
      // Ensure last_seen is a valid integer timestamp.
      $last_seen = (is_numeric($last_seen) && $last_seen > 0) ? (int) $last_seen : NULL;
      $last_seen_markup = $last_seen ? $date_formatter->format($last_seen, 'short') : '-';

      // Row structure matches Webform table: no #attributes on row, same cell keys pattern.
      $form['form_api_selects']['per_form_configuration']['form_api_widgets'][$discovered_form_id] = [
        'form_id' => [
          '#markup' => '<code>' . Html::escape($discovered_form_id) . '</code>',
        ],
        'last_seen' => [
          '#markup' => $last_seen_markup,
        ],
        'widget' => [
          '#type' => 'select',
          '#title' => '',
          '#options' => array_merge(['none' => $this->t('None')], $this->getSelectifyWidgetOptions()),
          '#default_value' => $selected_widget,
          '#wrapper_attributes' => ['class' => ['form-item--table-cell']],
        ],
      ];
    }

    // Show the built-in blocklist for transparency.
    $blocked = $selectify_helper->getInternalBlockedFormPatterns();
    $blocked_markup = '<code>' . implode('</code>, <code>', $blocked) . '</code>';

    $form['form_api_selects']['internal_blocklist'] = [
      '#type' => 'details',
      '#title' => $this->t('Built-in blocklist (always excluded)'),
      '#description' => $this->t('These form patterns are always excluded to prevent breaking AJAX-dependent admin forms. This list cannot be modified through the UI.'),
      '#open' => FALSE,
      '#states' => [
        'visible' => [
          ':input[name="enable_form_api_selects"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['form_api_selects']['internal_blocklist']['list'] = [
      '#markup' => '<div class="description">' . $blocked_markup . '</div>',
    ];

    // =========================================================================
    // SECTION 5b: Webform Integration (visible when submodule is installed)
    // =========================================================================
    // phpcs:ignore DrupalPractice.Objects.GlobalDrupal.GlobalDrupal
    if (\Drupal::moduleHandler()->moduleExists('selectify_webform')) {
      $webform_config = $this->configFactory->get('selectify_webform.settings');

      $form['webform_selects'] = [
        '#type' => 'details',
        '#title' => $this->t('Webform Select Elements'),
        '#description' => $this->t('Configure Selectify for Webform select elements, including composite elements like Select Other and Entity Select.'),
        '#open' => FALSE,
        '#weight' => 44,
      ];

      $form['webform_selects']['disable_webform_widgets'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Disable Selectify for all Webforms'),
        '#description' => $this->t('When enabled, all Webform submission forms will use their default widgets.'),
        '#default_value' => $webform_config->get('disable_webform_widgets'),
      ];

      $form['webform_selects']['apply_site_wide_webform'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Apply Selectify to all Webform submissions site-wide'),
        '#description' => $this->t('When enabled, all Webform submission forms with select elements will use the widget selected below.'),
        '#default_value' => $webform_config->get('apply_site_wide_webform'),
        '#states' => [
          'visible' => [
            ':input[name="disable_webform_widgets"]' => ['checked' => FALSE],
          ],
        ],
      ];

      $form['webform_selects']['global_webform_widget'] = [
        '#type' => 'select',
        '#title' => $this->t('Site-wide Webform widget'),
        '#description' => $this->t('This widget will be applied to all Webform submissions when site-wide mode is enabled.'),
        '#options' => array_merge(['none' => $this->t('None - Use default widgets')], $this->getSelectifyWidgetOptions()),
        '#default_value' => $webform_config->get('global_webform_widget') ?? 'none',
        '#states' => [
          'visible' => [
            ':input[name="disable_webform_widgets"]' => ['checked' => FALSE],
            ':input[name="apply_site_wide_webform"]' => ['checked' => TRUE],
          ],
        ],
      ];

      // Per-Webform configuration, mirroring Views behavior.
      $webforms_with_selects = $this->getWebformsWithSelects();
      if (!empty($webforms_with_selects)) {
        $stored_webform_widgets = $webform_config->get('widget_for_webforms') ?? [];

        $form['webform_selects']['per_webform_configuration'] = [
          '#type' => 'details',
          '#title' => $this->t('Per-Webform configuration'),
          '#open' => FALSE,
          '#states' => [
            'visible' => [
              ':input[name="disable_webform_widgets"]' => ['checked' => FALSE],
              ':input[name="apply_site_wide_webform"]' => ['checked' => FALSE],
            ],
          ],
        ];

        $form['webform_selects']['per_webform_configuration']['description'] = [
          '#type' => 'markup',
          '#markup' => '<div class="messages messages--info">' . $this->t('<strong>Per-Webform configuration:</strong> Only Webforms that contain select elements are listed below. Choose a widget for each Webform as needed.') . '</div>',
        ];

        $form['webform_selects']['per_webform_configuration']['webform_widgets'] = [
          '#type' => 'table',
          '#header' => [
            $this->t('Webform label'),
            $this->t('Machine name'),
            $this->t('Widget'),
          ],
          '#tree' => TRUE,
        ];

        foreach ($webforms_with_selects as $webform_id => $label) {
          $existing = NULL;
          if (is_array($stored_webform_widgets)) {
            foreach ($stored_webform_widgets as $entry) {
              if (!empty($entry['webform_id']) && $entry['webform_id'] === $webform_id) {
                $existing = $entry['widget'] ?? 'none';
                break;
              }
            }
          }

          $form['webform_selects']['per_webform_configuration']['webform_widgets'][$webform_id] = [
            'label' => [
              '#markup' => '<strong>' . Html::escape($label) . '</strong>',
            ],
            'id' => [
              '#markup' => '<code>' . Html::escape($webform_id) . '</code>',
            ],
            'widget' => [
              '#type' => 'select',
              '#title' => '',
              '#options' => array_merge(['none' => $this->t('None')], $this->getSelectifyWidgetOptions()),
              '#default_value' => $existing ?? 'none',
              '#wrapper_attributes' => ['class' => ['form-item--table-cell']],
            ],
          ];
        }
      }
    }

    // =========================================================================
    // SECTION 6: Views Select Filters
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
      '#options' => array_merge(['none' => $this->t('None - Use default widgets')], $this->getSelectifyWidgetOptions()),
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
      $stored_filters = $config->get('widget_for_filters') ?? [];
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
          // Validate required keys exist.
          if (empty($display_data['view_machine_name']) || empty($display_data['display_id'])) {
            continue;
          }
          $unique_key = $display_data['view_machine_name'] . ':' . $display_data['display_id'];

          // Check if this row has a selected widget (not 'none').
          $selected_widget = $stored_filters[$unique_key]['widget'] ?? 'none';
          $row_class = ($selected_widget !== 'none') ? 'selected-widget-row' : '';

          $display_title = $display_data['display_title'] ?? $this->t('Untitled');
          $display_type = $display_data['display_type'] ?? 'unknown';
          $display_id = $display_data['display_id'] ?? '';
          $exposed_form_id = $display_data['exposed_form_id'] ?? '';

          $form['views_filters']['per_view_configuration']['selectify_widgets'][$view_machine_name]['filters_table'][$unique_key] = [
          // Add class dynamically.
            '#attributes' => ['class' => [$row_class]],
            'display_title' => [
              '#markup' => '<strong>' . Html::escape($display_title) . '</strong>',
            ],
            'display_type' => [
              '#markup' => Html::escape(ucfirst(str_replace('_', ' ', $display_type))),
            ],
            'display_id' => [
              '#markup' => '<code>' . Html::escape($display_id) . '</code>',
            ],
            'exposed_form_id' => [
              '#markup' => '<code>' . Html::escape($exposed_form_id) . '</code>',
            ],
            'widget' => [
              '#type' => 'select',
              '#title' => '',
              '#options' => array_merge(['none' => $this->t('None')], $this->getSelectifyWidgetOptions()),
              '#default_value' => $selected_widget,
              '#parents' => ['selectify_widgets',
                $view_machine_name, 'filters_table',
                $unique_key, 'widget',
              ],
              '#wrapper_attributes' => ['class' => ['form-item--table-cell']],
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
   * Adds data-accent attributes to each radio button for visual styling.
   *
   * This callback adds data attributes to accent color radio buttons,
   * allowing CSS to display color circles instead of text labels.
   *
   * @param array $element
   *   The form element being processed.
   *
   * @return array
   *   The modified form element with data attributes added.
   */
  public static function addDataAccent(array $element): array {
    if (empty($element['#options']) || !is_array($element['#options'])) {
      return $element;
    }
    foreach ($element['#options'] as $key => $label) {
      if (!isset($element[$key])) {
        continue;
      }
      if (!isset($element[$key]['#attributes'])) {
        $element[$key]['#attributes'] = [];
      }
      $element[$key]['#attributes']['data-accent'] = $label;
    }
    return $element;
  }

  /**
   * Builds structured Form API widget map entries from submitted values.
   *
   * @param array $values
   *   The submitted 'form_api_widgets' values.
   *
   * @return array
   *   Structured configuration entries.
   */
  protected function buildFormApiWidgetMapConfig(array $values): array {
    $result = [];
    if (!is_array($values)) {
      return $result;
    }
    foreach ($values as $form_id => $row) {
      // Ensure form_id is a valid string and row is an array.
      if (!is_string($form_id) || !is_array($row)) {
        continue;
      }
      $widget = $row['widget'] ?? 'none';
      if (is_string($widget) && $widget !== 'none' && $widget !== '') {
        $result[] = [
          'form_id' => $form_id,
          'widget' => $widget,
        ];
      }
    }
    return $result;
  }

  /**
   * Builds structured CSS selector rules from submitted table values.
   *
   * @param array $values
   *   The submitted 'css_selector_rules' table values.
   *
   * @return array
   *   Structured configuration entries.
   */
  protected function buildCssSelectorRulesConfig(array $values): array {
    $result = [];
    if (!is_array($values)) {
      return $result;
    }
    foreach ($values as $row) {
      if (!is_array($row)) {
        continue;
      }
      $selector = trim($row['selector'] ?? '');
      $widget = $row['widget'] ?? 'none';
      if ($selector !== '' && is_string($widget) && $widget !== 'none' && $widget !== '') {
        // Defense-in-depth: strip control characters and angle brackets even
        // though validateForm() already rejects them. This guards against
        // programmatic saves that bypass form validation.
        $selector = preg_replace('/[\x00-\x1F\x7F<>]/', '', $selector);
        if ($selector === '' || strlen($selector) > 255) {
          continue;
        }
        $result[] = [
          'selector' => $selector,
          'widget' => $widget,
        ];
      }
    }
    return $result;
  }

  /**
   * Returns Webforms that contain select-like elements.
   *
   * Only Webforms with at least one element that will be styled by the
   * selectify_webform submodule are listed.
   *
   * @return array
   *   An array of webform_id => label.
   */
  protected function getWebformsWithSelects(): array {
    if (!class_exists(Webform::class)) {
      return [];
    }
    $storage = Webform::loadMultiple();
    if (empty($storage)) {
      return [];
    }
    $result = [];
    foreach ($storage as $webform) {
      if (!$webform || !method_exists($webform, 'getElementsDecoded')) {
        continue;
      }
      $webform_id = $webform->id();
      if (empty($webform_id)) {
        continue;
      }
      $elements = $webform->getElementsDecoded();
      if (!is_array($elements)) {
        continue;
      }
      if ($this->webformHasSelect($elements)) {
        $label = $webform->label() ?? $webform_id;
        $result[$webform_id] = $label;
      }
    }
    asort($result, SORT_NATURAL | SORT_FLAG_CASE);
    return $result;
  }

  /**
   * Recursively checks if a Webform element tree contains a select-like input.
   *
   * @param array $elements
   *   The Webform elements array.
   *
   * @return bool
   *   TRUE if any element is a select or a supported composite type.
   */
  protected function webformHasSelect(array $elements): bool {
    foreach ($elements as $element) {
      if (!is_array($element)) {
        continue;
      }
      $type = $element['#type'] ?? '';
      if ($type === 'select') {
        return TRUE;
      }
      // Composite element types known to contain selects.
      $composite_types = [
        'webform_select_other',
        'webform_entity_select',
        'webform_term_select',
        'webform_likert',
        'webform_mapping',
        'webform_tableselect',
        'webform_composite',
      ];
      if (in_array($type, $composite_types, TRUE)) {
        return TRUE;
      }
      if (!empty($element)) {
        if ($this->webformHasSelect($element)) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    // Validate CSS selector rules.
    $css_rules_raw = $form_state->getValue('css_selector_rules', []);
    if (!is_array($css_rules_raw)) {
      return;
    }

    // Characters that are never valid in CSS selectors and indicate
    // potential injection attempts or accidental input.
    $forbidden_pattern = '/[<>&;{}()\\\\\/\x00-\x1F\x7F]/';

    $rule_count = 0;
    foreach ($css_rules_raw as $delta => $row) {
      if (!is_array($row)) {
        continue;
      }
      $selector = trim($row['selector'] ?? '');
      $widget = $row['widget'] ?? 'none';

      // Skip empty rows.
      if ($selector === '' && $widget === 'none') {
        continue;
      }

      // Selector filled but widget not selected.
      if ($selector !== '' && ($widget === 'none' || $widget === '')) {
        $form_state->setErrorByName(
          "css_selector_rules][$delta][widget",
          $this->t('CSS selector rule @num: please select a widget for selector <code>@selector</code>.', [
            '@num' => $delta + 1,
            '@selector' => $selector,
          ])
        );
        continue;
      }

      // Reject forbidden characters.
      if (preg_match($forbidden_pattern, $selector)) {
        $form_state->setErrorByName(
          "css_selector_rules][$delta][selector",
          $this->t('CSS selector rule @num contains invalid characters. Only CSS selector syntax is allowed (e.g. <code>select[id^="edit-"]</code>).', [
            '@num' => $delta + 1,
          ])
        );
        continue;
      }

      $rule_count++;
    }

    // Limit total number of rules to prevent unbounded config growth.
    if ($rule_count > 50) {
      $form_state->setErrorByName(
        'css_selector_rules',
        $this->t('A maximum of 50 CSS selector rules is allowed. You have @count.', [
          '@count' => $rule_count,
        ])
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory()->getEditable('selectify.settings');

    // Preserve existing settings while updating necessary ones.
    // Drupal flattens form values when #tree is not set, so we can access
    // nested elements directly by their key names.
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
      ->set('accent_color', $form_state->getValue('accent_color', $config->get('accent_color') ?? 'neutral'))
      ->set('accent_color_mode', $form_state->getValue('accent_color_mode', $config->get('accent_color_mode') ?? 'light'))
      ->set('admin_accent_color', $form_state->getValue('admin_accent_color', $config->get('admin_accent_color') ?? 'neutral'))
      ->set('admin_accent_color_mode', $form_state->getValue('admin_accent_color_mode', $config->get('admin_accent_color_mode') ?? 'light'))
      ->set('enable_form_api_selects', (bool) $form_state->getValue('enable_form_api_selects', FALSE));

    // Build Form API per-form widget map — guard against non-array values
    // which occur when the per-form section is hidden via #states or when
    // no forms have been discovered yet.
    $form_api_widgets_raw = $form_state->getValue('form_api_widgets', []);
    // Ensure we always have an array, even if form state returns empty string
    // or other non-array value when the section is hidden.
    if (!is_array($form_api_widgets_raw) || empty($form_api_widgets_raw)) {
      $form_api_widgets_raw = [];
    }
    $config->set('form_api_widget_map', $this->buildFormApiWidgetMapConfig($form_api_widgets_raw));

    // Build CSS selector rules from submitted table values.
    $css_rules_raw = $form_state->getValue('css_selector_rules', []);
    if (!is_array($css_rules_raw)) {
      $css_rules_raw = [];
    }
    $config->set('css_selector_widget_map', $this->buildCssSelectorRulesConfig($css_rules_raw));

    // Retrieve values from form.
    $apply_site_wide = (bool) $form_state->getValue('apply_site_wide', FALSE);
    $disable_views_widgets = (bool) $form_state->getValue('disable_views_widgets', FALSE);
    $global_widget = $form_state->getValue('global_selectify_widget', $config->get('global_selectify_widget') ?? 'none');
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

    // Save Webform submodule settings if installed.
    // phpcs:ignore DrupalPractice.Objects.GlobalDrupal.GlobalDrupal
    if (\Drupal::moduleHandler()->moduleExists('selectify_webform')) {
      $webform_config = $this->configFactory()->getEditable('selectify_webform.settings');
      $disable_webform_widgets = (bool) $form_state->getValue('disable_webform_widgets', FALSE);
      $apply_site_wide_webform = (bool) $form_state->getValue('apply_site_wide_webform', FALSE);
      $global_webform_widget = $form_state->getValue('global_webform_widget', 'none');

      // If disabling Webform widgets, clear everything.
      if ($disable_webform_widgets) {
        $webform_config
          ->set('enable_webform_selects', TRUE)
          ->set('disable_webform_widgets', TRUE)
          ->set('apply_site_wide_webform', FALSE)
          ->set('global_webform_widget', 'none')
          ->set('widget_for_webforms', []);
      }
      // If applying site-wide, clear per-Webform config.
      elseif ($apply_site_wide_webform) {
        $webform_config
          ->set('enable_webform_selects', TRUE)
          ->set('disable_webform_widgets', FALSE)
          ->set('apply_site_wide_webform', TRUE)
          ->set('global_webform_widget', $global_webform_widget)
          ->set('widget_for_webforms', []);
      }
      // If using per-Webform configuration, save the widget map.
      else {
        // Build Webform per-form widget map.
        // With #tree => TRUE on the table, Drupal uses the table key
        // ('webform_widgets') as the form state key, matching Form API pattern.
        $webform_widgets_raw = $form_state->getValue('webform_widgets', []);
        if (!is_array($webform_widgets_raw)) {
          $webform_widgets_raw = [];
        }
        $webform_config
          ->set('enable_webform_selects', TRUE)
          ->set('disable_webform_widgets', FALSE)
          ->set('apply_site_wide_webform', FALSE)
          ->set('global_webform_widget', 'none')
          ->set('widget_for_webforms', $this->buildWebformWidgetConfig($webform_widgets_raw));
      }

      $webform_config->save();
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * Normalizes Webform widget configuration from form values.
   *
   * @param array $values
   *   The submitted 'webform_widgets' table values.
   *
   * @return array
   *   Structured configuration entries.
   */
  protected function buildWebformWidgetConfig(array $values): array {
    $result = [];
    if (!is_array($values)) {
      return $result;
    }
    foreach ($values as $webform_id => $row) {
      // Validate webform_id is a valid string and row is an array.
      if (!is_string($webform_id) || empty($webform_id) || !is_array($row)) {
        continue;
      }
      $widget = $row['widget'] ?? 'none';
      if (is_string($widget) && $widget !== 'none' && $widget !== '') {
        $result[] = [
          'webform_id' => $webform_id,
          'widget' => $widget,
        ];
      }
    }
    return $result;
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
  private function processViewsWidgetSettings(FormStateInterface $form_state, Config $config): array {

    $stored_widgets = $config->get('widget_for_filters') ?? [];
    $views_data = $form_state->getValue('selectify_widgets', []);

    if (!empty($views_data) && is_array($views_data)) {
      foreach ($views_data as $view_content) {
        if (!isset($view_content['filters_table']) || !is_array($view_content['filters_table'])) {
          continue;
        }

        foreach ($view_content['filters_table'] as $unique_key => $values) {
          if (!is_array($values) || empty($values['widget']) || $values['widget'] === 'none') {
            continue;
          }
          $exposed_form_id = $values['exposed_form_id'] ?? '';

          // Generate `exposed_form_id` if missing.
          if (empty($exposed_form_id) && is_string($unique_key)) {
            $parts = explode(':', $unique_key, 2);
            if (count($parts) === 2) {
              [$view_machine_name_part, $display_id] = $parts;
              $exposed_form_id = "views-exposed-form-{$view_machine_name_part}-{$display_id}";
            }
          }

          $stored_widgets[$unique_key] = [
            'widget' => $values['widget'],
            'exposed_form_id' => $exposed_form_id,
          ];
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
  protected function getExposedFilters(): array {
    $filters = [];
    $views = Views::getAllViews();

    if (empty($views)) {
      return $filters;
    }

    foreach ($views as $view) {
      if (!$view || !method_exists($view, 'id')) {
        continue;
      }
      $view_machine_name = $view->id();
      $view_human_name = $view->label() ?? $view_machine_name;

      if (empty($view_machine_name)) {
        continue;
      }

      $displays = $view->get('display');
      if (!is_array($displays) || empty($displays)) {
        continue;
      }

      // Get default display filters for inheritance checking.
      $default_filters = $displays['default']['display_options']['filters'] ?? [];

      foreach ($displays as $display_id => $display) {
        // Skip invalid display entries.
        if (!is_array($display) || empty($display_id)) {
          continue;
        }
        $display_type = $display['display_plugin'] ?? 'unknown';
        $exposed_form_id = "views-exposed-form-{$view_machine_name}-{$display_id}";
        $has_select_filter = FALSE;

        // Get display title.
        $display_title = $display['display_title'] ?? $this->t('Untitled Display');

        // Determine which filters to use for this display.
        $display_filters = $this->resolveDisplayFilters($display, $default_filters);

        // Check if this display has an exposed SELECT filter.
        if (!empty($display_filters)) {
          foreach ($display_filters as $filter) {
            // Skip if filter data is invalid.
            if (!is_array($filter)) {
              continue;
            }

            // Check if filter is exposed AND is a select/dropdown widget.
            if (!empty($filter['exposed']) &&
                !empty($filter['expose']['identifier'])) {

              // Determine the widget type.
              $widget = $this->getFilterWidget($filter);

              // Include filters that use select-based widgets.
              if (in_array($widget, ['select', 'radios'], TRUE)) {
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

  /**
   * Resolves the actual filters for a display, handling inheritance.
   *
   * @param array $display
   *   The display configuration array.
   * @param array $default_filters
   *   The filters from the default display.
   *
   * @return array
   *   The resolved filters for the display.
   */
  protected function resolveDisplayFilters(array $display, array $default_filters): array {
    // Get display options with safe fallback.
    $display_options = $display['display_options'] ?? [];

    // Check current display filters first.
    $display_filters = $display_options['filters'] ?? [];

    // Check if display has explicitly configured defaults.
    $defaults = $display_options['defaults'] ?? [];

    // Determine if this display uses default filters:
    // 1. If 'defaults' key doesn't exist, display inherits from default.
    // 2. If 'defaults.filters' is TRUE or not set, display inherits.
    // 3. If 'defaults.filters' is FALSE, display uses its own filters.
    if (!isset($defaults['filters'])) {
      // No defaults configuration means inherit everything.
      return empty($display_filters) ? $default_filters : $display_filters;
    }

    if ($defaults['filters'] === TRUE) {
      // Explicitly set to use defaults.
      return $default_filters;
    }

    if ($defaults['filters'] === FALSE) {
      // Display explicitly overrides filters, use only display filters.
      return $display_filters;
    }

    // Fallback: if display has filters, use them; otherwise inherit.
    return empty($display_filters) ? $default_filters : $display_filters;
  }

  /**
   * Determines the widget type for a filter.
   *
   * @param array $filter
   *   The filter configuration array.
   *
   * @return string
   *   The widget type (e.g., 'select', 'radios', 'textfield').
   */
  protected function getFilterWidget(array $filter): string {
    // Validate filter structure.
    if (!is_array($filter)) {
      return 'select';
    }

    // Check for explicitly set widget in expose configuration.
    if (isset($filter['expose']['widget']) && is_string($filter['expose']['widget'])) {
      return $filter['expose']['widget'];
    }

    // Check for widget in expose settings (alternative location).
    if (isset($filter['expose']['settings']['widget']) && is_string($filter['expose']['settings']['widget'])) {
      return $filter['expose']['settings']['widget'];
    }

    // Check the filter type - some filters default to select.
    $filter_type = $filter['type'] ?? '';

    // Common filter types that default to select widgets.
    $select_filter_types = [
      'select',
      'taxonomy_index_tid',
      'bundle',
      'boolean',
      'list_field',
      'entity_reference',
    ];

    if (in_array($filter_type, $select_filter_types, TRUE)) {
      return 'select';
    }

    // Default to 'select' if no widget is specified (most common case).
    return 'select';
  }

}
