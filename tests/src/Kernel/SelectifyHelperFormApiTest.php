<?php

declare(strict_types=1);

namespace Drupal\Tests\selectify\Kernel;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests SelectifyHelper::getFormApiConfig().
 *
 * @group selectify
 */
#[Group('selectify')]
#[RunTestsInSeparateProcesses]
class SelectifyHelperFormApiTest extends KernelTestBase {

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
  }

  /**
   * Tests that getFormApiConfig returns FALSE when disabled.
   */
  public function testFormApiConfigDisabled(): void {
    /** @var \Drupal\selectify\Service\SelectifyHelper $helper */
    $helper = $this->container->get('selectify.helper');

    // Default config has enable_form_api_selects = FALSE.
    $result = $helper->getFormApiConfig();
    $this->assertFalse($result, 'Form API config should be FALSE when disabled.');
  }

  /**
   * Tests that getFormApiConfig returns config array when enabled.
   */
  public function testFormApiConfigEnabled(): void {
    $config = $this->config('selectify.settings');
    $config->set('enable_form_api_selects', TRUE)->save();

    // New service instance to clear cache.
    $helper = $this->container->get('selectify.helper');

    $result = $helper->getFormApiConfig();
    $this->assertIsArray($result);
    $this->assertArrayHasKey('widget_map', $result);
    $this->assertArrayHasKey('css_selector_rules', $result);
    // Built-in blocklist still excludes settings form.
    $this->assertTrue($helper->isFormExcluded('selectify_settings_form'));
  }

  /**
   * Tests the internal blocklist returns expected patterns.
   */
  public function testInternalBlockedFormPatterns(): void {
    $helper = $this->container->get('selectify.helper');
    $patterns = $helper->getInternalBlockedFormPatterns();

    $this->assertIsArray($patterns);
    $this->assertNotEmpty($patterns);

    // Verify critical forms are in the blocklist.
    $this->assertContains('entity_form_display_edit_form', $patterns);
    $this->assertContains('entity_view_display_edit_form', $patterns);
    $this->assertContains('field_config_edit_form', $patterns);
    $this->assertContains('views_ui_*', $patterns);
    $this->assertContains('layout_builder_*', $patterns);
    $this->assertContains('media_library_*', $patterns);
    $this->assertContains('editor_*', $patterns);
    $this->assertContains('selectify_settings_form', $patterns);
  }

  /**
   * Tests isFormExcluded with internal blocklist.
   */
  public function testIsFormExcludedInternal(): void {
    $helper = $this->container->get('selectify.helper');

    // Direct match.
    $this->assertTrue($helper->isFormExcluded('entity_form_display_edit_form'));
    // Wildcard match.
    $this->assertTrue($helper->isFormExcluded('views_ui_edit_form'));
    $this->assertTrue($helper->isFormExcluded('layout_builder_add_block'));
    $this->assertTrue($helper->isFormExcluded('media_library_add_form'));
    // Not blocked.
    $this->assertFalse($helper->isFormExcluded('my_custom_form'));
    $this->assertFalse($helper->isFormExcluded('contact_message_feedback_form'));
  }

  /**
   * Tests isFormExcluded with user-defined patterns.
   */
  public function testIsFormExcludedUserPatterns(): void {
    $helper = $this->container->get('selectify.helper');

    // User adds a custom exclusion.
    $this->assertTrue($helper->isFormExcluded('my_custom_form', ['my_custom_*']));
    $this->assertFalse($helper->isFormExcluded('other_form', ['my_custom_*']));
  }

  /**
   * Tests that internal blocklist works even with empty user patterns.
   */
  public function testIsFormExcludedInternalTakesPriority(): void {
    $helper = $this->container->get('selectify.helper');

    // Internal blocklist still applies with empty user list.
    $this->assertTrue($helper->isFormExcluded('entity_form_display_edit_form', []));
    $this->assertTrue($helper->isFormExcluded('views_ui_add_form', []));
  }

  /**
   * Tests third-party select library detection (Select2, Chosen, etc.).
   */
  public function testHasThirdPartySelectLibrary(): void {
    $helper = $this->container->get('selectify.helper');

    // Select2 detection.
    $element = [
      '#type' => 'select',
      '#attributes' => ['class' => ['select2-hidden-accessible']],
    ];
    $this->assertTrue($helper->hasThirdPartySelectLibrary($element));

    $element = [
      '#type' => 'select',
      '#attributes' => ['class' => ['form-select', 'js-select2']],
    ];
    $this->assertTrue($helper->hasThirdPartySelectLibrary($element));

    $element = [
      '#type' => 'select',
      '#attributes' => ['data-select2-id' => '123'],
    ];
    $this->assertTrue($helper->hasThirdPartySelectLibrary($element));

    // Chosen detection.
    $element = [
      '#type' => 'select',
      '#attributes' => ['class' => ['chosen-select']],
    ];
    $this->assertTrue($helper->hasThirdPartySelectLibrary($element));

    // Selectize detection.
    $element = [
      '#type' => 'select',
      '#attributes' => ['class' => ['selectize-control']],
    ];
    $this->assertTrue($helper->hasThirdPartySelectLibrary($element));

    // Choices.js detection.
    $element = [
      '#type' => 'select',
      '#attributes' => ['class' => ['choices']],
    ];
    $this->assertTrue($helper->hasThirdPartySelectLibrary($element));

    // Normal select without third-party library.
    $element = [
      '#type' => 'select',
      '#attributes' => ['class' => ['form-select']],
    ];
    $this->assertFalse($helper->hasThirdPartySelectLibrary($element));
  }

  /**
   * Tests hidden/disabled select detection.
   */
  public function testIsSelectHiddenOrDisabled(): void {
    $helper = $this->container->get('selectify.helper');

    // Hidden via #access.
    $element = [
      '#type' => 'select',
      '#access' => FALSE,
    ];
    $this->assertTrue($helper->isSelectHiddenOrDisabled($element));

    // Disabled.
    $element = [
      '#type' => 'select',
      '#disabled' => TRUE,
    ];
    $this->assertTrue($helper->isSelectHiddenOrDisabled($element));

    // Readonly.
    $element = [
      '#type' => 'select',
      '#readonly' => TRUE,
    ];
    $this->assertTrue($helper->isSelectHiddenOrDisabled($element));

    // Normal select.
    $element = [
      '#type' => 'select',
    ];
    $this->assertFalse($helper->isSelectHiddenOrDisabled($element));
  }

  /**
   * Tests excluded context detection (toolbar, modal, off-canvas).
   */
  public function testIsSelectInExcludedContext(): void {
    $helper = $this->container->get('selectify.helper');

    // Toolbar context.
    $element = [
      '#type' => 'select',
      '#attributes' => ['class' => ['toolbar-item']],
    ];
    $this->assertTrue($helper->isSelectInExcludedContext($element));

    // Off-canvas context.
    $element = [
      '#type' => 'select',
      '#wrapper_attributes' => ['class' => ['off-canvas']],
    ];
    $this->assertTrue($helper->isSelectInExcludedContext($element));

    // Modal/UI dialog context.
    $element = [
      '#type' => 'select',
      '#attributes' => ['class' => ['ui-dialog-content']],
    ];
    $this->assertTrue($helper->isSelectInExcludedContext($element));

    // Normal context.
    $element = [
      '#type' => 'select',
      '#attributes' => ['class' => ['form-select']],
    ];
    $this->assertFalse($helper->isSelectInExcludedContext($element));
  }

  /**
   * Tests combined shouldSkipSelectElement method.
   */
  public function testShouldSkipSelectElement(): void {
    $helper = $this->container->get('selectify.helper');

    // Should skip: third-party library.
    $element = [
      '#type' => 'select',
      '#attributes' => ['class' => ['select2']],
    ];
    $this->assertTrue($helper->shouldSkipSelectElement($element));

    // Should skip: disabled.
    $element = [
      '#type' => 'select',
      '#disabled' => TRUE,
    ];
    $this->assertTrue($helper->shouldSkipSelectElement($element));

    // Should skip: excluded context.
    $element = [
      '#type' => 'select',
      '#attributes' => ['class' => ['modal']],
    ];
    $this->assertTrue($helper->shouldSkipSelectElement($element));

    // Should NOT skip: normal select.
    $element = [
      '#type' => 'select',
      '#attributes' => ['class' => ['form-select']],
    ];
    $this->assertFalse($helper->shouldSkipSelectElement($element));
  }

}
