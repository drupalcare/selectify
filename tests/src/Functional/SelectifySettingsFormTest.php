<?php

declare(strict_types=1);

namespace Drupal\Tests\selectify\Functional;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Selectify settings form, including Form API select configuration.
 *
 * @group selectify
 */
#[Group('selectify')]
#[RunTestsInSeparateProcesses]
class SelectifySettingsFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'selectify',
    'views',
    'field',
    'path_alias',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * An admin user with selectify permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser(['administer selectify settings']);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests that the settings form loads and contains the Form API section.
   */
  public function testSettingsFormLoads(): void {
    $this->drupalGet('admin/config/selectify/settings');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Form API Select Elements');
    $this->assertSession()->fieldExists('enable_form_api_selects');
  }

  /**
   * Tests saving Form API select settings.
   */
  public function testSaveFormApiSettings(): void {
    $this->drupalGet('admin/config/selectify/settings');

    $edit = [
      'enable_form_api_selects' => TRUE,
    ];
    $this->submitForm($edit, 'Save configuration');

    $this->assertSession()->pageTextContains('The configuration options have been saved.');

    // Verify config was persisted.
    $config = $this->config('selectify.settings');
    $this->assertTrue($config->get('enable_form_api_selects'));
  }

  /**
   * Tests that disabling Form API selects clears the widget setting behaviour.
   */
  public function testDisableFormApiSelects(): void {
    // First enable.
    $this->config('selectify.settings')
      ->set('enable_form_api_selects', TRUE)
      ->save();

    // Then disable via the form.
    $this->drupalGet('admin/config/selectify/settings');
    $edit = [
      'enable_form_api_selects' => FALSE,
    ];
    $this->submitForm($edit, 'Save configuration');

    $config = $this->config('selectify.settings');
    $this->assertFalse($config->get('enable_form_api_selects'));
  }

  /**
   * Tests default values on fresh install.
   */
  public function testDefaultValues(): void {
    $config = $this->config('selectify.settings');
    $this->assertFalse($config->get('enable_form_api_selects'));
  }

}
