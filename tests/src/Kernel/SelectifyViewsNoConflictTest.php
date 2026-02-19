<?php

declare(strict_types=1);

namespace Drupal\Tests\selectify\Kernel;

use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests that the four Selectify paths do not conflict with each other.
 *
 * Path 1: Views exposed filters (hook_preprocess_views_exposed_form)
 * Path 2: Field widgets (SelectifyWidgetBase plugins)
 * Path 3: Form API selects (hook_form_alter)
 * Path 4: Radio/Checkbox styling (hook_preprocess_form_element / input)
 *
 * @group selectify
 */
class SelectifyViewsNoConflictTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'field',
    'views',
    'path_alias',
    'selectify',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['selectify']);
    $this->config('selectify.settings')->set('disabled_pages', '')->save();
  }

  /**
   * Tests that form_alter skips Views exposed forms.
   *
   * Ensures Path 1 and Path 3 don't overlap.
   */
  public function testFormAlterSkipsViewsExposedForms(): void {
    $this->config('selectify.settings')
      ->set('enable_form_api_selects', TRUE)
      ->save();

    $form = [
      '#type' => 'form',
      '#attached' => ['library' => []],
      'filter_select' => [
        '#type' => 'select',
        '#options' => ['a' => 'A'],
      ],
    ];
    $form_state = new FormState();

    selectify_form_alter($form, $form_state, 'views_exposed_form');

    $classes = $form['filter_select']['#attributes']['class'] ?? [];
    $this->assertNotContains('selectify-form-api', $classes,
      'Form API alter should not touch Views exposed forms.');
  }

  /**
   * Tests that form_alter skips elements already styled by field widgets.
   *
   * Ensures Path 2 and Path 3 don't overlap.
   */
  public function testFormAlterSkipsFieldWidgetSelectsWithClass(): void {
    $this->config('selectify.settings')
      ->set('enable_form_api_selects', TRUE)
      ->set('form_api_widget', 'selectify_dropdown')
      ->save();

    $form = [
      '#type' => 'form',
      '#attached' => ['library' => []],
      'field_select' => [
        '#type' => 'select',
        '#options' => ['a' => 'A'],
        '#attributes' => [
          'class' => ['selectify-apply-tags'],
        ],
      ],
    ];
    $form_state = new FormState();

    selectify_form_alter($form, $form_state, 'node_article_edit_form');

    $classes = $form['field_select']['#attributes']['class'] ?? [];
    $this->assertContains('selectify-apply-tags', $classes);
    $this->assertNotContains('selectify-apply-dropdown', $classes,
      'Form API alter should not override Selectify field widget styling.');
    $this->assertNotContains('selectify-form-api', $classes);
  }

  /**
   * Tests that form_alter skips core field widget selects (OptionsSelectWidget).
   *
   * Core's OptionsWidgetBase sets #key_column on every field widget select.
   * Category 3 must detect this and skip the element, even when the field
   * uses the default core widget (no selectify-apply-* class).
   */
  public function testFormAlterSkipsCoreFieldWidgetSelects(): void {
    $this->config('selectify.settings')
      ->set('enable_form_api_selects', TRUE)
      ->set('form_api_widget', 'selectify_dropdown')
      ->save();

    $form = [
      '#type' => 'form',
      '#attached' => ['library' => []],
      // Simulates a core OptionsSelectWidget element — has #key_column.
      'field_category' => [
        '#type' => 'select',
        '#title' => 'Category',
        '#options' => ['news' => 'News', 'blog' => 'Blog'],
        '#key_column' => 'value',
      ],
      // A plain form API select — should be styled.
      'language' => [
        '#type' => 'select',
        '#title' => 'Language',
        '#options' => ['en' => 'English', 'fr' => 'French'],
      ],
    ];
    $form_state = new FormState();

    selectify_form_alter($form, $form_state, 'node_article_edit_form');

    // The field widget select must NOT be styled.
    $field_classes = $form['field_category']['#attributes']['class'] ?? [];
    $this->assertNotContains('selectify-apply-dropdown', $field_classes,
      'Core field widget select (#key_column) must not be styled by Category 3.');
    $this->assertNotContains('selectify-form-api', $field_classes);

    // The plain select SHOULD be styled.
    $lang_classes = $form['language']['#attributes']['class'] ?? [];
    $this->assertContains('selectify-apply-dropdown', $lang_classes,
      'Plain form API select without #key_column should be styled.');
    $this->assertContains('selectify-form-api', $lang_classes);
  }

  /**
   * Tests that form_alter styles plain selects alongside radio/checkbox hooks.
   *
   * Ensures Path 3 and Path 4 coexist without conflicts.
   */
  public function testFormAlterAndRadioCheckboxCoexist(): void {
    $this->config('selectify.settings')
      ->set('enable_form_api_selects', TRUE)
      ->set('enable_radio', TRUE)
      ->set('enable_checkbox', TRUE)
      ->save();

    $form = [
      '#type' => 'form',
      '#attached' => ['library' => []],
      'my_select' => [
        '#type' => 'select',
        '#options' => ['a' => 'A'],
      ],
      'my_radios' => [
        '#type' => 'radios',
        '#options' => ['x' => 'X'],
      ],
    ];
    $form_state = new FormState();

    selectify_form_alter($form, $form_state, 'mixed_form');

    // Select should be styled.
    $select_classes = $form['my_select']['#attributes']['class'] ?? [];
    $this->assertContains('selectify-apply-dropdown', $select_classes);

    // Radios should NOT be touched by form_alter (they use preprocess hooks).
    $radio_classes = $form['my_radios']['#attributes']['class'] ?? [];
    $this->assertNotContains('selectify-apply-dropdown', $radio_classes,
      'Form API alter should not touch radio elements.');
  }

  /**
   * Tests that only select elements are styled, not other element types.
   */
  public function testFormAlterOnlyStylesSelects(): void {
    $this->config('selectify.settings')
      ->set('enable_form_api_selects', TRUE)
      ->save();

    $form = [
      '#type' => 'form',
      '#attached' => ['library' => []],
      'my_select' => [
        '#type' => 'select',
        '#options' => ['a' => 'A'],
      ],
      'my_textfield' => [
        '#type' => 'textfield',
      ],
      'my_checkbox' => [
        '#type' => 'checkbox',
      ],
      'my_radios' => [
        '#type' => 'radios',
        '#options' => ['a' => 'A'],
      ],
      'my_textarea' => [
        '#type' => 'textarea',
      ],
    ];
    $form_state = new FormState();

    selectify_form_alter($form, $form_state, 'type_test_form');

    // Only the select should have selectify classes.
    $select_classes = $form['my_select']['#attributes']['class'] ?? [];
    $this->assertContains('selectify-form-api', $select_classes);

    foreach (['my_textfield', 'my_checkbox', 'my_radios', 'my_textarea'] as $key) {
      $classes = $form[$key]['#attributes']['class'] ?? [];
      $this->assertNotContains('selectify-form-api', $classes,
        "Element '$key' should not be styled by form_alter.");
    }
  }

}
