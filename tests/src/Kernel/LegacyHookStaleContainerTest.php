<?php

declare(strict_types=1);

namespace Drupal\Tests\selectify\Kernel;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\KernelTests\KernelTestBase;
use Drupal\selectify\Hook\SelectifyHooks;
use Drupal\selectify_test\Hook\SelectifyTestHooks;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder as SymfonyContainerBuilder;

/**
 * The #[LegacyHook] wrappers work when the Hook classes are not services.
 *
 * WHY THIS EXISTS. Issue #3628961: "You have requested a non-existent
 * service ...Hooks". The Hook class services were first declared in 2.0.5.
 * A site updated from 2.0.2-2.0.4 runs the new .module files against the
 * container compiled for the old release until a cache rebuild. Every
 * wrapper called \Drupal::service(<X>Hooks::class), and because
 * hook_preprocess_html() fires on every page, every page answered HTTP 500.
 *
 * The Hook classes have constructor arguments, so the classResolver() swap
 * alone is not enough: without create() the resolver calls `new X()` and
 * throws ArgumentCountError. This test therefore fails both on the 2.0.6
 * code and on a classResolver()-only edit.
 *
 * This test builds that container: the Hook class definitions are removed
 * after every other compiler pass, including core's hook collector, which
 * registers Hook classes itself on Drupal 11.1+.
 *
 * Updating from 2.0.0 or 2.0.1 is not covered: those containers lack
 * selectify.helper as well, which create() needs. That window predates the
 * Hook services and is documented in the release notes.
 *
 * @group selectify
 *
 * @license SPDX-License-Identifier: GPL-2.0-or-later
 */
#[Group('selectify')]
#[RunTestsInSeparateProcesses]
class LegacyHookStaleContainerTest extends KernelTestBase implements CompilerPassInterface {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'field',
    'views',
    'path_alias',
    'selectify',
    'selectify_test',
  ];

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    parent::register($container);
    $container->addCompilerPass($this, PassConfig::TYPE_AFTER_REMOVING, -1000);
  }

  /**
   * Removes the Hook class services, as a pre-2.0.5 container lacks them.
   */
  public function process(SymfonyContainerBuilder $container): void {
    $container->removeDefinition(SelectifyHooks::class);
    $container->removeDefinition(SelectifyTestHooks::class);
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['system', 'selectify']);
  }

  /**
   * The wrappers resolve the Hook classes without the container services.
   */
  public function testWrappersRunWithoutTheHookServices(): void {
    // Control: without this, a green result would prove nothing.
    $this->assertFalse(
      $this->container->has(SelectifyHooks::class),
      'SelectifyHooks must be absent from the container, as on a site updated from 2.0.4 before a cache rebuild.'
    );
    $this->assertFalse(
      $this->container->has(SelectifyTestHooks::class),
      'SelectifyTestHooks must be absent from the container.'
    );

    $route_match = $this->container->get('current_route_match');

    // The help page answer proves the Hook class really was built and its
    // method ran, not merely that nothing threw.
    $help = selectify_help('help.page.selectify', $route_match);
    $this->assertIsString($help);
    $this->assertNotSame('', $help);
    $this->assertNull(selectify_help('some.other.route', $route_match));

    // The wrapper that fires on every page in the reported failure.
    $variables = ['attributes' => ['class' => []]];
    selectify_preprocess_html($variables);
    $this->assertIsArray($variables['attributes']['class']);

    // A test-module wrapper whose Hook class takes a different dependency.
    $widget = 'selectify_dropdown';
    selectify_test_selectify_widget_alter($widget, [
      'integration_type' => 'form_api',
      'form_id' => 'selectify_test_form',
    ]);
    $this->assertSame('selectify_searchable', $widget);
  }

}
