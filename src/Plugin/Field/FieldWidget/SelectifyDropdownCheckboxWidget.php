<?php

declare(strict_types=1);

namespace Drupal\selectify\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsSelectWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Template\Attribute;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'selectify_dropdown_checkbox' widget.
 *
 * @FieldWidget(
 *   id = "selectify_dropdown_checkbox",
 *   module = "selectify",
 *   label = @Translation("Selectify Dropdown With Checkbox"),
 *   field_types = {
 *     "list_string",
 *     "list_integer",
 *     "list_float",
 *     "entity_reference"
 *   },
 *   multiple_values = TRUE
 * )
 */
class SelectifyDropdownCheckboxWidget extends OptionsSelectWidget {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Constructs a SelectifyDropdownWidget object.
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
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    array $third_party_settings,
    ConfigFactoryInterface $config_factory,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->configFactory = $config_factory;
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
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // Retrieve the default form element from the parent class.
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    // Get selected accent color from settings.
    $selected_accent = $this->configFactory->get('selectify.settings')->get('accent_color') ?: 'default';

    $is_multiple = $this->fieldDefinition->getFieldStorageDefinition()->isMultiple();
    // Generate a unique ID based on field name and delta.
    $unique_id = Html::getUniqueId('selectify-dropdown-checkbox-widget-' . $this->fieldDefinition->getName() . '-' . $delta);
    // Determine the maximum number of selections allowed.
    $max_selected = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();
    $max_selected = $max_selected === -1 ? 'null' : (string) $max_selected;

    // Ensure #attributes is initialized.
    $element['#attributes'] = $element['#attributes'] ?? [];

    // Set the field label explicitly to ensure it appears.
    $element['#title'] = $this->fieldDefinition->getLabel();
    $element['#title_display'] = 'before';

    // Ensure #title_attributes is always an Attribute object.
    if (!isset($element['#title_attributes']) || !($element['#title_attributes'] instanceof Attribute)) {
      $element['#title_attributes'] = new Attribute();
    }

    // Add label class.
    $element['#title_attributes']->addClass('form-item__label');

    // Add required class if the field is required.
    if (!empty($element['#required'])) {
      $element['#title_attributes']->addClass('form-required');
    }

    $element['#attributes'] += [
      'id' => $unique_id,
      'class' => ['selectify-apply-checkbox'],
      'data-max-selections' => $max_selected,
      'data-drupal-selector' => Html::getUniqueId('edit-' . str_replace('_', '-', $this->fieldDefinition->getName())),
      'data-field-name' => $this->fieldDefinition->getName(),
      'data-multiple' => $is_multiple ? 'true' : 'false',
      'data-placeholder' => $this->getSetting('placeholder') ?? '',
      'data-tracking' => 'selectify-widget',
      'aria-expanded' => 'false',
      'aria-haspopup' => 'listbox',
      'aria-describedby' => 'edit-' . $this->fieldDefinition->getName() . '--description',
      'aria-required' => $this->fieldDefinition->isRequired() ? 'true' : 'false',
      'role' => 'combobox',
    ];

    // Set #multiple correctly.
    $element['#multiple'] = $is_multiple;

    // Mark as required if necessary.
    if ($this->fieldDefinition->isRequired()) {
      $element['#attributes']['required'] = 'required';
    }

    // Attach JavaScript libraries required for the widget.
    $element['#attached']['library'] = [
      'selectify/selectify-base',
      'selectify/selectify-helper',
      'selectify/selectify-dropdowns',
      'selectify/selectify-dropdown-checkbox',
    ];

    // Conditionally add the color library with mode.
    if (!empty($selected_accent) && $selected_accent !== 'none') {
      $accent_color_mode = $this->configFactory->get('selectify.settings')->get('accent_color_mode') ?: 'light';
      $element['#attached']['library'][] = "selectify/selectify-color-{$selected_accent}-{$accent_color_mode}";
    }

    // Attach JavaScript settings specific to this widget.
    $element['#attached']['drupalSettings']['selectify']['maxSelections'][$this->fieldDefinition->getName()] = $max_selected;

    return $element;
  }

}
