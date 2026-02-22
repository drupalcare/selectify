<?php

declare(strict_types=1);

namespace Drupal\Tests\selectify\Kernel;

use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests hook_form_alter() for Form API select elements.
 *
 * @group selectify
 */
class SelectifyFormAlterTest extends KernelTestBase {

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
    // Ensure "Additional pages to disable" does not match in tests (clear path).
    $this->config('selectify.settings')->set('disabled_pages', '')->save();
  }

  /**
   * Tests that form_alter does nothing when Form API selects are disabled.
   */
  public function testFormAlterDisabled(): void {
    $form = $this->buildTestForm();
    $form_state = new FormState();

    selectify_form_alter($form, $form_state, 'test_form');

    // No selectify class should be present.
    $classes = $form['my_select']['#attributes']['class'] ?? [];
    $this->assertNotContains('selectify-apply-dropdown', $classes);
    $this->assertNotContains('selectify-form-api', $classes);
  }

  /**
   * Tests that form_alter applies widget when enabled.
   */
  public function testFormAlterEnabled(): void {
    $this->config('selectify.settings')
      ->set('enable_form_api_selects', TRUE)
      ->set('form_api_widget_map', [['form_id' => 'test_form', 'widget' => 'selectify_dropdown']])
      ->save();

    $form = $this->buildTestForm();
    $form_state = new FormState();

    selectify_form_alter($form, $form_state, 'test_form');

    $classes = $form['my_select']['#attributes']['class'] ?? [];
    $this->assertContains('selectify-apply-dropdown', $classes);
    $this->assertContains('selectify-form-api', $classes);
  }

  /**
   * Tests that already-styled selects are skipped.
   */
  public function testFormAlterSkipsStyledSelects(): void {
    $this->config('selectify.settings')
      ->set('enable_form_api_selects', TRUE)
      ->set('form_api_widget_map', [['form_id' => 'test_form', 'widget' => 'selectify_dropdown']])
      ->save();

    $form = $this->buildTestForm();
    // Pre-style the select (as if a field widget already did).
    $form['my_select']['#attributes']['class'][] = 'selectify-apply-tags';
    $form_state = new FormState();

    selectify_form_alter($form, $form_state, 'test_form');

    // Should still have tags, not dropdown.
    $classes = $form['my_select']['#attributes']['class'] ?? [];
    $this->assertContains('selectify-apply-tags', $classes);
    $this->assertNotContains('selectify-apply-dropdown', $classes);
  }

  /**
   * Tests that forms in the built-in blocklist are skipped.
   */
  public function testFormAlterBlocklistExcluded(): void {
    $this->config('selectify.settings')
      ->set('enable_form_api_selects', TRUE)
      ->save();

    $form = $this->buildTestForm();
    $form_state = new FormState();

    selectify_form_alter($form, $form_state, 'entity_form_display_edit_form');

    $classes = $form['my_select']['#attributes']['class'] ?? [];
    $this->assertNotContains('selectify-apply-dropdown', $classes);
  }

  /**
   * Tests that Views exposed forms are skipped.
   */
  public function testFormAlterSkipsViewsForms(): void {
    $this->config('selectify.settings')
      ->set('enable_form_api_selects', TRUE)
      ->save();

    $form = $this->buildTestForm();
    $form_state = new FormState();

    selectify_form_alter($form, $form_state, 'views_exposed_form');

    $classes = $form['my_select']['#attributes']['class'] ?? [];
    $this->assertNotContains('selectify-apply-dropdown', $classes);
  }

  /**
   * Tests that nested selects (inside containers) are found and styled.
   */
  public function testFormAlterNestedSelects(): void {
    $this->config('selectify.settings')
      ->set('enable_form_api_selects', TRUE)
      ->set('form_api_widget_map', [['form_id' => 'nested_test_form', 'widget' => 'selectify_searchable']])
      ->save();

    $form = [
      '#type' => 'form',
      '#attached' => ['library' => []],
      'wrapper' => [
        '#type' => 'container',
        'nested_select' => [
          '#type' => 'select',
          '#title' => 'Nested',
          '#options' => ['a' => 'A', 'b' => 'B'],
        ],
      ],
    ];
    $form_state = new FormState();

    selectify_form_alter($form, $form_state, 'nested_test_form');

    $classes = $form['wrapper']['nested_select']['#attributes']['class'] ?? [];
    $this->assertContains('selectify-apply-searchable', $classes);
    $this->assertContains('selectify-form-api', $classes);
  }

  /**
   * Tests that libraries are attached to styled selects.
   */
  public function testFormAlterAttachesLibraries(): void {
    $this->config('selectify.settings')
      ->set('enable_form_api_selects', TRUE)
      ->set('form_api_widget_map', [['form_id' => 'test_form', 'widget' => 'selectify_dropdown']])
      ->save();

    $form = $this->buildTestForm();
    $form_state = new FormState();

    selectify_form_alter($form, $form_state, 'test_form');

    $libraries = $form['my_select']['#attached']['library'] ?? [];
    $this->assertContains('selectify/selectify-base', $libraries);
    $this->assertContains('selectify/selectify-dropdown', $libraries);
  }

  /**
   * Tests single/multi wrapper classes.
   */
  public function testFormAlterSingleMultiClasses(): void {
    $this->config('selectify.settings')
      ->set('enable_form_api_selects', TRUE)
      ->set('form_api_widget_map', [
        ['form_id' => 'test_form_single', 'widget' => 'selectify_dropdown'],
        ['form_id' => 'test_form_multi', 'widget' => 'selectify_dropdown'],
      ])
      ->save();

    // Single select.
    $form_single = $this->buildTestForm();
    $form_state = new FormState();
    selectify_form_alter($form_single, $form_state, 'test_form_single');

    $wrapper = $form_single['my_select']['#wrapper_attributes']['class'] ?? [];
    $this->assertContains('selectify-single', $wrapper);

    // Multiple select.
    $form_multi = $this->buildTestForm();
    $form_multi['my_select']['#multiple'] = TRUE;
    $form_state2 = new FormState();
    selectify_form_alter($form_multi, $form_state2, 'test_form_multi');

    $wrapper = $form_multi['my_select']['#wrapper_attributes']['class'] ?? [];
    $this->assertContains('selectify-multi', $wrapper);
  }

  /**
   * Tests different widget types.
   */
  public function testFormAlterDifferentWidgets(): void {
    $widgets = [
      'selectify_dropdown' => 'selectify-apply-dropdown',
      'selectify_tags' => 'selectify-apply-tags',
      'selectify_searchable' => 'selectify-apply-searchable',
      'selectify_checkbox' => 'selectify-apply-checkbox',
      'selectify_dual' => 'selectify-apply-dual',
    ];

    foreach ($widgets as $widget => $expected_class) {
      $form_id = 'test_form_' . $widget;
      $this->config('selectify.settings')
        ->set('enable_form_api_selects', TRUE)
        ->set('form_api_widget_map', [['form_id' => $form_id, 'widget' => $widget]])
        ->save();

      // Clear the helper's cached config.
      $this->container->set('selectify.helper', NULL);

      $form = $this->buildTestForm();
      $form_state = new FormState();
      selectify_form_alter($form, $form_state, $form_id);

      $classes = $form['my_select']['#attributes']['class'] ?? [];
      $this->assertContains($expected_class, $classes, "Widget $widget should add class $expected_class");
    }
  }

  /**
   * Tests that internally blocklisted forms are always excluded.
   */
  public function testFormAlterInternalBlocklist(): void {
    $this->config('selectify.settings')
      ->set('enable_form_api_selects', TRUE)
      ->save();

    $blocked_forms = [
      'entity_form_display_edit_form',
      'entity_view_display_edit_form',
      'field_config_edit_form',
      'views_ui_edit_form',
      'layout_builder_add_block',
      'media_library_add_form',
      'editor_link_dialog',
      'filter_format_edit_form',
    ];

    foreach ($blocked_forms as $blocked_id) {
      $form = $this->buildTestForm();
      $form_state = new FormState();

      selectify_form_alter($form, $form_state, $blocked_id);

      $classes = $form['my_select']['#attributes']['class'] ?? [];
      $this->assertNotContains('selectify-apply-dropdown', $classes,
        "Blocked form '$blocked_id' should be excluded by internal blocklist.");
    }
  }

  /**
   * Tests that internal blocklist cannot be bypassed by user config.
   */
  public function testInternalBlocklistCannotBeOverridden(): void {
    $this->config('selectify.settings')
      ->set('enable_form_api_selects', TRUE)
      ->save();

    $form = $this->buildTestForm();
    $form_state = new FormState();

    // Even with empty user exclusions, internal blocklist applies.
    selectify_form_alter($form, $form_state, 'entity_form_display_edit_form');

    $classes = $form['my_select']['#attributes']['class'] ?? [];
    $this->assertNotContains('selectify-apply-dropdown', $classes,
      'Internal blocklist should protect forms even when user exclusion list is empty.');
  }

  /**
   * Tests hook_selectify_widget_alter().
   */
  public function testSelectifyWidgetAlterHook(): void {
    // Install a test module that implements the alter hook first.
    $this->enableModules(['selectify_test']);

    $this->config('selectify.settings')
      ->set('enable_form_api_selects', TRUE)
      ->set('form_api_widget_map', [
        ['form_id' => 'selectify_test_form', 'widget' => 'selectify_dropdown'],
      ])
      ->save();

    $form = $this->buildTestForm();
    $form_state = new FormState();

    selectify_form_alter($form, $form_state, 'selectify_test_form');

    // The test module should change dropdown to searchable for selectify_test_form.
    $classes = $form['my_select']['#attributes']['class'] ?? [];
    $this->assertContains('selectify-apply-searchable', $classes,
      'hook_selectify_widget_alter() should change widget from dropdown to searchable.');
    $this->assertNotContains('selectify-apply-dropdown', $classes,
      'Original dropdown widget should be replaced.');
  }

  /**
   * Tests hook_selectify_element_alter().
   */
  public function testSelectifyElementAlterHook(): void {
    // Install a test module that implements the alter hook first.
    $this->enableModules(['selectify_test']);

    $this->config('selectify.settings')
      ->set('enable_form_api_selects', TRUE)
      ->set('form_api_widget_map', [
        ['form_id' => 'test_form', 'widget' => 'selectify_dropdown'],
      ])
      ->save();

    $form = $this->buildTestForm();
    $form_state = new FormState();

    selectify_form_alter($form, $form_state, 'test_form');

    // The test module should add a data attribute.
    $attributes = $form['my_select']['#attributes'] ?? [];
    $this->assertArrayHasKey('data-selectify-test', $attributes,
      'hook_selectify_element_alter() should add custom data attribute.');
    $this->assertEquals('altered', $attributes['data-selectify-test'],
      'Custom data attribute should have correct value.');
  }

  /**
   * Tests hook_selectify_element_alter() skip functionality.
   */
  public function testSelectifyElementAlterSkip(): void {
    // Install a test module that implements the alter hook first.
    $this->enableModules(['selectify_test']);

    $this->config('selectify.settings')
      ->set('enable_form_api_selects', TRUE)
      ->set('form_api_widget_map', [
        ['form_id' => 'test_form', 'widget' => 'selectify_dropdown'],
      ])
      ->save();

    // Create a form with a select that has only 2 options (should be skipped).
    $form = [
      '#type' => 'form',
      '#attached' => ['library' => []],
      // Only 2 options — test module skips Selectify for simple selects.
      'simple_select' => [
        '#type' => 'select',
        '#title' => 'Simple Select',
        '#options' => ['a' => 'A', 'b' => 'B'],
      ],
    ];
    $form_state = new FormState();

    selectify_form_alter($form, $form_state, 'test_form');

    // The test module should skip Selectify for selects with <= 2 options.
    $classes = $form['simple_select']['#attributes']['class'] ?? [];
    $this->assertNotContains('selectify-apply-dropdown', $classes,
      'hook_selectify_element_alter() should skip Selectify for simple selects.');
  }

  /**
   * Tests hook_selectify_is_applicable_alter().
   */
  public function testSelectifyIsApplicableAlterHook(): void {
    // Install a test module that implements the alter hook first.
    $this->enableModules(['selectify_test']);

    $this->config('selectify.settings')
      ->set('enable_form_api_selects', TRUE)
      ->set('form_api_widget_map', [
        ['form_id' => 'test_form', 'widget' => 'selectify_dropdown'],
      ])
      ->save();

    // Mock the request with query parameter.
    $request = $this->container->get('request_stack')->getCurrentRequest();
    if ($request) {
      $request->query->set('disable_selectify', '1');
    }

    $form = $this->buildTestForm();
    $form_state = new FormState();

    selectify_form_alter($form, $form_state, 'test_form');

    // The test module should prevent Selectify from applying.
    $classes = $form['my_select']['#attributes']['class'] ?? [];
    $this->assertNotContains('selectify-apply-dropdown', $classes,
      'hook_selectify_is_applicable_alter() should prevent Selectify when query param is set.');
  }

  /**
   * Tests that selects with third-party libraries are skipped.
   */
  public function testFormAlterSkipsThirdPartyLibraries(): void {
    $this->config('selectify.settings')
      ->set('enable_form_api_selects', TRUE)
      ->set('form_api_widget_map', [['form_id' => 'test_third_party_form', 'widget' => 'selectify_dropdown']])
      ->save();

    $form = [
      '#type' => 'form',
      '#attached' => ['library' => []],
      'select2_select' => [
        '#type' => 'select',
        '#title' => 'Select2 Select',
        '#options' => ['a' => 'A', 'b' => 'B'],
        '#attributes' => ['class' => ['select2-hidden-accessible']],
      ],
      'chosen_select' => [
        '#type' => 'select',
        '#title' => 'Chosen Select',
        '#options' => ['x' => 'X', 'y' => 'Y'],
        '#attributes' => ['class' => ['chosen-select']],
      ],
      'normal_select' => [
        '#type' => 'select',
        '#title' => 'Normal Select',
        '#options' => ['1' => 'One', '2' => 'Two'],
      ],
    ];
    $form_state = new FormState();

    selectify_form_alter($form, $form_state, 'test_third_party_form');

    // Third-party selects should NOT be styled.
    $select2_classes = $form['select2_select']['#attributes']['class'] ?? [];
    $this->assertNotContains('selectify-apply-dropdown', $select2_classes,
      'Select2-enhanced select should be skipped.');

    $chosen_classes = $form['chosen_select']['#attributes']['class'] ?? [];
    $this->assertNotContains('selectify-apply-dropdown', $chosen_classes,
      'Chosen-enhanced select should be skipped.');

    // Normal select SHOULD be styled.
    $normal_classes = $form['normal_select']['#attributes']['class'] ?? [];
    $this->assertContains('selectify-apply-dropdown', $normal_classes,
      'Normal select without third-party library should be styled.');
  }

  /**
   * Tests that hidden/disabled selects are skipped.
   */
  public function testFormAlterSkipsHiddenDisabledSelects(): void {
    $this->config('selectify.settings')
      ->set('enable_form_api_selects', TRUE)
      ->set('form_api_widget_map', [['form_id' => 'test_hidden_form', 'widget' => 'selectify_dropdown']])
      ->save();

    $form = [
      '#type' => 'form',
      '#attached' => ['library' => []],
      'hidden_select' => [
        '#type' => 'select',
        '#title' => 'Hidden Select',
        '#options' => ['a' => 'A'],
        '#access' => FALSE,
      ],
      'disabled_select' => [
        '#type' => 'select',
        '#title' => 'Disabled Select',
        '#options' => ['b' => 'B'],
        '#disabled' => TRUE,
      ],
      'normal_select' => [
        '#type' => 'select',
        '#title' => 'Normal Select',
        '#options' => ['c' => 'C'],
      ],
    ];
    $form_state = new FormState();

    selectify_form_alter($form, $form_state, 'test_hidden_form');

    // Hidden/disabled selects should NOT be styled.
    $hidden_classes = $form['hidden_select']['#attributes']['class'] ?? [];
    $this->assertNotContains('selectify-apply-dropdown', $hidden_classes,
      'Hidden select (#access = FALSE) should be skipped.');

    $disabled_classes = $form['disabled_select']['#attributes']['class'] ?? [];
    $this->assertNotContains('selectify-apply-dropdown', $disabled_classes,
      'Disabled select should be skipped.');

    // Normal select SHOULD be styled.
    $normal_classes = $form['normal_select']['#attributes']['class'] ?? [];
    $this->assertContains('selectify-apply-dropdown', $normal_classes,
      'Normal select should be styled.');
  }

  /**
   * Tests that selects with #ajax callbacks are skipped.
   */
  public function testFormAlterSkipsAjaxSelects(): void {
    $this->config('selectify.settings')
      ->set('enable_form_api_selects', TRUE)
      ->set('form_api_widget_map', [['form_id' => 'test_ajax_form', 'widget' => 'selectify_dropdown']])
      ->save();

    $form = [
      '#type' => 'form',
      '#attached' => ['library' => []],
      'ajax_select' => [
        '#type' => 'select',
        '#title' => 'AJAX Select',
        '#options' => ['a' => 'A', 'b' => 'B'],
        '#ajax' => [
          'callback' => '::ajaxCallback',
          'wrapper' => 'dependent-wrapper',
        ],
      ],
      'normal_select' => [
        '#type' => 'select',
        '#title' => 'Normal Select',
        '#options' => ['x' => 'X', 'y' => 'Y'],
      ],
    ];
    $form_state = new FormState();

    selectify_form_alter($form, $form_state, 'test_ajax_form');

    // AJAX select should NOT be styled.
    $ajax_classes = $form['ajax_select']['#attributes']['class'] ?? [];
    $this->assertNotContains('selectify-apply-dropdown', $ajax_classes,
      'Select with #ajax callback should be skipped.');

    // Normal select SHOULD be styled.
    $normal_classes = $form['normal_select']['#attributes']['class'] ?? [];
    $this->assertContains('selectify-apply-dropdown', $normal_classes,
      'Normal select without #ajax should be styled.');
  }

  /**
   * Builds a minimal test form with a select element.
   *
   * @return array
   *   A form render array.
   */
  protected function buildTestForm(): array {
    return [
      '#type' => 'form',
      '#attached' => ['library' => []],
      'my_select' => [
        '#type' => 'select',
        '#title' => 'Test Select',
        '#options' => [
          '' => '- Select -',
          'option_1' => 'Option 1',
          'option_2' => 'Option 2',
        ],
      ],
      'my_textfield' => [
        '#type' => 'textfield',
        '#title' => 'Not a select',
      ],
    ];
  }

}
