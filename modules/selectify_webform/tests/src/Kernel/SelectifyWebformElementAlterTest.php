<?php

declare(strict_types=1);

namespace Drupal\Tests\selectify_webform\Kernel;

use Drupal\Core\Form\FormState;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\KernelTests\KernelTestBase;
use Drupal\webform\WebformInterface;
use PHPUnit\Framework\Exception as PHPUnitException;

/**
 * Tests Selectify Webform element alter integration.
 *
 * These tests require the Webform module to be installed. They verify that
 * the selectify_webform submodule correctly styles Webform select elements
 * and composite elements.
 *
 * @group selectify_webform
 * @requires module webform
 */
class SelectifyWebformElementAlterTest extends KernelTestBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'field',
    'views',
    'path_alias',
    'selectify',
    'webform',
    'selectify_webform',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    try {
      parent::setUp();
    }
    catch (PHPUnitException $e) {
      if (str_contains($e->getMessage(), "Unavailable module") && str_contains($e->getMessage(), 'webform')) {
        $this->markTestSkipped('Webform module is not available.');
      }
      throw $e;
    }
    $this->installConfig(['selectify', 'selectify_webform']);
    // Apply site-wide dropdown so tests that expect styling receive it.
    $this->config('selectify_webform.settings')
      ->set('apply_site_wide_webform', TRUE)
      ->set('global_webform_widget', 'selectify_dropdown')
      ->save();
  }

  /**
   * Tests that webform select elements receive Selectify styling.
   */
  public function testPlainSelectStyled(): void {
    $element = [
      '#type' => 'select',
      '#title' => 'Country',
      '#options' => [
        'us' => $this->t('United States'),
        'uk' => $this->t('United Kingdom'),
      ],
    ];
    $form_state = new FormState();
    $context = $this->createMockContext('test_webform');

    selectify_webform_webform_element_alter($element, $form_state, $context);

    $classes = $element['#attributes']['class'] ?? [];
    $this->assertContains('selectify-apply-dropdown', $classes);
    $this->assertContains('selectify-webform', $classes);
  }

  /**
   * Tests that composite elements have their nested selects styled.
   */
  public function testCompositeSelectStyled(): void {
    $element = [
      '#type' => 'webform_select_other',
      'select' => [
        '#type' => 'select',
        '#options' => ['a' => 'A', 'b' => 'B'],
      ],
      'other' => [
        '#type' => 'textfield',
      ],
    ];
    $form_state = new FormState();
    $context = $this->createMockContext('test_webform');

    selectify_webform_webform_element_alter($element, $form_state, $context);

    $classes = $element['select']['#attributes']['class'] ?? [];
    $this->assertContains('selectify-apply-dropdown', $classes);
    $this->assertContains('selectify-webform', $classes);

    // The textfield should not be styled.
    $other_classes = $element['other']['#attributes']['class'] ?? [];
    $this->assertNotContains('selectify-apply-dropdown', $other_classes);
  }

  /**
   * Tests that disabled config prevents styling.
   */
  public function testDisabledConfig(): void {
    $this->config('selectify_webform.settings')
      ->set('enable_webform_selects', FALSE)
      ->save();

    $element = [
      '#type' => 'select',
      '#options' => ['a' => 'A'],
    ];
    $form_state = new FormState();
    $context = $this->createMockContext('test_webform');

    // Clear static cache.
    drupal_static_reset('_selectify_webform_get_config');

    selectify_webform_webform_element_alter($element, $form_state, $context);

    $classes = $element['#attributes']['class'] ?? [];
    $this->assertNotContains('selectify-apply-dropdown', $classes);
  }

  /**
   * Tests that disable_webform_widgets prevents styling.
   */
  public function testDisableWebformWidgets(): void {
    $this->config('selectify_webform.settings')
      ->set('disable_webform_widgets', TRUE)
      ->save();

    $element = [
      '#type' => 'select',
      '#options' => ['a' => 'A'],
    ];
    $form_state = new FormState();
    $context = $this->createMockContext('test_webform');

    drupal_static_reset('_selectify_webform_get_config');

    selectify_webform_webform_element_alter($element, $form_state, $context);

    $classes = $element['#attributes']['class'] ?? [];
    $this->assertNotContains('selectify-apply-dropdown', $classes);
  }

  /**
   * Tests that form_alter safety net catches remaining selects.
   */
  public function testFormAlterSafetyNet(): void {
    $form = [
      '#type' => 'form',
      '#attached' => ['library' => []],
      'elements' => [
        'late_select' => [
          '#type' => 'select',
          '#options' => ['a' => 'A'],
        ],
      ],
    ];
    $form_state = new FormState();

    selectify_webform_form_alter($form, $form_state, 'webform_submission_test_add_form');

    $classes = $form['elements']['late_select']['#attributes']['class'] ?? [];
    $this->assertContains('selectify-apply-dropdown', $classes);
    $this->assertContains('selectify-webform', $classes);
  }

  /**
   * Tests that form_alter only targets webform submission forms.
   */
  public function testFormAlterSkipsNonWebformForms(): void {
    $form = [
      '#type' => 'form',
      '#attached' => ['library' => []],
      'some_select' => [
        '#type' => 'select',
        '#options' => ['a' => 'A'],
      ],
    ];
    $form_state = new FormState();

    selectify_webform_form_alter($form, $form_state, 'node_article_edit_form');

    $classes = $form['some_select']['#attributes']['class'] ?? [];
    $this->assertNotContains('selectify-webform', $classes);
  }

  /**
   * Tests that date/time picker selects are skipped.
   */
  public function testSkipsDateSelects(): void {
    $element = [
      '#type' => 'select',
      '#title' => 'Year',
      '#options' => ['2020' => '2020', '2021' => '2021'],
      '#attributes' => ['class' => ['solo-date-related']],
      '#id' => 'edit-datelist-year',
      '#name' => 'datelist[year]',
    ];
    $form_state = new FormState();
    $context = $this->createMockContext('test_webform');

    selectify_webform_webform_element_alter($element, $form_state, $context);

    $classes = $element['#attributes']['class'] ?? [];
    $this->assertNotContains('selectify-apply-dropdown', $classes,
      'Date select (year) should be skipped.');

    // Test month select.
    $element = [
      '#type' => 'select',
      '#id' => 'edit-datelist-month',
      '#name' => 'datelist[month]',
      '#options' => ['1' => 'Jan', '2' => 'Feb'],
    ];
    selectify_webform_webform_element_alter($element, $form_state, $context);
    $classes = $element['#attributes']['class'] ?? [];
    $this->assertNotContains('selectify-apply-dropdown', $classes,
      'Date select (month) should be skipped.');
  }

  /**
   * Tests that selects with third-party libraries are skipped.
   */
  public function testSkipsThirdPartyLibraries(): void {
    $element = [
      '#type' => 'select',
      '#title' => 'Select2 Select',
      '#options' => ['a' => 'A', 'b' => 'B'],
      '#attributes' => ['class' => ['select2-hidden-accessible']],
    ];
    $form_state = new FormState();
    $context = $this->createMockContext('test_webform');

    selectify_webform_webform_element_alter($element, $form_state, $context);

    $classes = $element['#attributes']['class'] ?? [];
    $this->assertNotContains('selectify-apply-dropdown', $classes,
      'Select2-enhanced select should be skipped.');
  }

  /**
   * Tests that hidden/disabled selects are skipped.
   */
  public function testSkipsHiddenDisabledSelects(): void {
    $element = [
      '#type' => 'select',
      '#title' => 'Hidden Select',
      '#options' => ['a' => 'A'],
      '#access' => FALSE,
    ];
    $form_state = new FormState();
    $context = $this->createMockContext('test_webform');

    selectify_webform_webform_element_alter($element, $form_state, $context);

    $classes = $element['#attributes']['class'] ?? [];
    $this->assertNotContains('selectify-apply-dropdown', $classes,
      'Hidden select (#access = FALSE) should be skipped.');

    // Test disabled select.
    $element = [
      '#type' => 'select',
      '#disabled' => TRUE,
      '#options' => ['b' => 'B'],
    ];
    selectify_webform_webform_element_alter($element, $form_state, $context);
    $classes = $element['#attributes']['class'] ?? [];
    $this->assertNotContains('selectify-apply-dropdown', $classes,
      'Disabled select should be skipped.');
  }

  /**
   * Creates a mock webform context array.
   *
   * @param string $webform_id
   *   The webform ID.
   *
   * @return array
   *   A context array with a mock webform object.
   */
  protected function createMockContext(string $webform_id): array {
    $webform = $this->createMock(WebformInterface::class);
    $webform->method('id')->willReturn($webform_id);

    return [
      'webform' => $webform,
    ];
  }

}
