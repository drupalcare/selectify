<?php

declare(strict_types=1);

namespace Drupal\selectify\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsSelectWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Template\Attribute;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for all Selectify field widgets.
 *
 * Provides the shared constructor, dependency injection, and formElement()
 * logic. Subclasses only need to provide their annotation and override
 * the three abstract methods that define per-widget differences.
 */
abstract class SelectifyWidgetBase extends OptionsSelectWidget {

  /**
   * Entity reference field types that may produce optgroups.
   *
   * Selectify does not support optgroup-based options. Fields of these types
   * are excluded via isApplicable() when they target multiple bundles.
   *
   * @var string[]
   */
  protected const ENTITY_REFERENCE_TYPES = [
    'entity_reference',
    'entity_reference_revisions',
  ];

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * The admin context service.
   *
   * @var \Drupal\Core\Routing\AdminContext
   */
  protected AdminContext $adminContext;

  /**
   * Constructs a SelectifyWidgetBase object.
   *
   * @param string $plugin_id
   *   The plugin ID for the field widget.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third-party settings.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Routing\AdminContext $admin_context
   *   The admin context service.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    array $third_party_settings,
    ConfigFactoryInterface $config_factory,
    AdminContext $admin_context,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->configFactory = $config_factory;
    $this->adminContext = $admin_context;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('config.factory'),
      $container->get('router.admin_context')
    );
  }

  /**
   * Returns the HTML ID prefix for this widget type.
   *
   * @return string
   *   A prefix like 'selectify-dropdown-widget'.
   */
  abstract protected function getWidgetIdPrefix(): string;

  /**
   * Returns the CSS class applied to the select element.
   *
   * @return string
   *   A class like 'selectify-apply-dropdown'.
   */
  abstract protected function getWidgetCssClass(): string;

  /**
   * Returns the JS libraries required by this widget.
   *
   * @return string[]
   *   Library machine names, e.g. ['selectify/selectify-dropdown'].
   */
  abstract protected function getWidgetLibraries(): array;

  /**
   * Returns the ARIA role for this widget.
   *
   * @return string
   *   Defaults to 'combobox'. Override for alternative roles.
   */
  protected function getAriaRole(): string {
    return 'combobox';
  }

  /**
   * Returns the aria-haspopup value for this widget.
   *
   * @return string
   *   Defaults to 'listbox'. Override for alternative values.
   */
  protected function getAriaHaspopup(): string {
    return 'listbox';
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition): bool {
    // Entity reference fields that target multiple bundle types produce
    // optgroups which Selectify does not support. Hide the widget for those.
    if (in_array($field_definition->getType(), static::ENTITY_REFERENCE_TYPES, TRUE)) {
      $handler_settings = $field_definition->getSetting('handler_settings');
      $target_bundles = $handler_settings['target_bundles'] ?? NULL;
      // NULL means all bundles; more than one explicit bundle also produces
      // optgroups. Only allow when exactly one bundle is targeted.
      if ($target_bundles === NULL || count($target_bundles) !== 1) {
        return FALSE;
      }
    }

    return parent::isApplicable($field_definition);
  }

  /**
   * Allows subclasses to alter the element before it is returned.
   *
   * @param array &$element
   *   The form element.
   * @param string $max_selected
   *   The max selections value.
   */
  protected function alterElement(array &$element, string $max_selected): void {
    // No-op by default. Subclasses can override.
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $selected_accent = $this->configFactory->get('selectify.settings')->get('accent_color') ?: 'default';
    $is_multiple = $this->fieldDefinition->getFieldStorageDefinition()->isMultiple();

    $unique_id = Html::getUniqueId($this->getWidgetIdPrefix() . '-' . $this->fieldDefinition->getName() . '-' . $delta);

    $max_selected = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();
    $max_selected = $max_selected === -1 ? 'null' : (string) $max_selected;

    $element['#attributes'] = $element['#attributes'] ?? [];

    $element['#title'] = $this->fieldDefinition->getLabel();
    $element['#title_display'] = 'before';

    if (!isset($element['#title_attributes']) || !($element['#title_attributes'] instanceof Attribute)) {
      $element['#title_attributes'] = new Attribute();
    }

    $element['#title_attributes']->addClass('form-item__label');

    if (!empty($element['#required'])) {
      $element['#title_attributes']->addClass('form-required');
    }

    $element['#attributes'] += [
      'id' => $unique_id,
      'class' => [$this->getWidgetCssClass()],
      'data-max-selections' => $max_selected,
      'data-drupal-selector' => Html::getUniqueId('edit-' . str_replace('_', '-', $this->fieldDefinition->getName())),
      'data-field-name' => $this->fieldDefinition->getName(),
      'data-multiple' => $is_multiple ? 'true' : 'false',
      'data-placeholder' => $this->getSetting('placeholder') ?? '',
      'data-tracking' => 'selectify-widget',
      'aria-expanded' => 'false',
      'aria-haspopup' => $this->getAriaHaspopup(),
      'aria-describedby' => 'edit-' . $this->fieldDefinition->getName() . '--description',
      'aria-required' => $this->fieldDefinition->isRequired() ? 'true' : 'false',
      'role' => $this->getAriaRole(),
    ];

    $element['#multiple'] = $is_multiple;

    if ($this->fieldDefinition->isRequired()) {
      $element['#attributes']['required'] = 'required';
    }

    // Allow subclasses to modify the element.
    $this->alterElement($element, $max_selected);

    $element['#attached']['library'] = array_merge(
      ['selectify/selectify-base', 'selectify/selectify-helper'],
      $this->getWidgetLibraries()
    );

    $isAdmin = $this->adminContext->isAdminRoute();
    $selectifySettings = $this->configFactory->get('selectify.settings');
    $accent = $isAdmin
      ? ($selectifySettings->get('admin_accent_color') ?: 'default')
      : $selected_accent;
    $accent_color_mode = $isAdmin
      ? ($selectifySettings->get('admin_accent_color_mode') ?: 'light')
      : ($selectifySettings->get('accent_color_mode') ?: 'light');

    if (!empty($accent) && $accent !== 'none' && $accent_color_mode !== 'none') {
      $element['#attached']['library'][] = "selectify/selectify-color-{$accent}-{$accent_color_mode}";
    }

    $element['#attached']['drupalSettings']['selectify']['maxSelections'][$this->fieldDefinition->getName()] = $max_selected;

    return $element;
  }

}
