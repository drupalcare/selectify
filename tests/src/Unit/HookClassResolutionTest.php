<?php

declare(strict_types=1);

namespace Drupal\Tests\selectify\Unit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\selectify\Service\SelectifyHelperInterface;
use Drupal\selectify\Service\SelectifyStyleServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Procedural wrappers must build the Hook classes without needing them as services.
 *
 * WHY THIS EXISTS. Issue #3628961: "You have requested a non-existent
 * service ...Hooks". The SelectifyHooks, SelectifyWebformHooks and
 * SelectifyTestHooks services were first declared in 2.0.5. A site updated
 * from 2.0.2-2.0.4 runs the new .module files against its old compiled
 * container until a cache rebuild, and every #[LegacyHook] wrapper called
 * \Drupal::service(<X>Hooks::class), so every page answered HTTP 500.
 *
 * The wrappers now use \Drupal::classResolver(). When the container lacks
 * the service, the resolver calls <X>Hooks::create() with the live container.
 * create() can only fetch services that the OLD container holds.
 *
 * WHAT THIS ENFORCES:
 * - No \Drupal::service() call in a *.module, *.inc or *.install file names
 *   a class in a Drupal\selectify or Drupal\selectify_* namespace, and
 *   exactly EXPECTED_WRAPPERS classResolver() wrappers exist.
 * - Every *Hooks class with a required constructor parameter implements
 *   ContainerInjectionInterface. Without it the resolver falls back to
 *   `new X()` and throws ArgumentCountError.
 * - create() succeeds against a container holding ONLY the service ids that
 *   a 2.0.2-2.0.4 container holds. If create() asks for a newer service, or
 *   the constructor gains a parameter that create() does not pass, this
 *   test fails. The constructor is the source of truth; create() is the
 *   copy that this pins.
 *
 * Red-proofed against the 2.0.6 code: 16 calls flagged, 3 classes without
 * ContainerInjectionInterface, and create() missing on all 3 classes.
 *
 * @group selectify
 *
 * @license SPDX-License-Identifier: GPL-2.0-or-later
 */
#[Group('selectify')]
class HookClassResolutionTest extends TestCase {

  /**
   * The number of #[LegacyHook] wrappers that resolve a Hook class.
   *
   * The count is selectify.module 11, selectify_webform.module 2 and
   * selectify_test.module 3.
   */
  private const EXPECTED_WRAPPERS = 16;

  /**
   * The Hook classes, which the scan below must find exactly.
   */
  private const HOOK_CLASSES = [
    'Drupal\selectify\Hook\SelectifyHooks',
    'Drupal\selectify_test\Hook\SelectifyTestHooks',
    'Drupal\selectify_webform\Hook\SelectifyWebformHooks',
  ];

  /**
   * The module root.
   */
  private function root(): string {
    return dirname(__DIR__, 3);
  }

  /**
   * Procedural files in the module, its sub-modules and its test modules.
   *
   * @return list<string>
   *   Absolute paths.
   */
  private function proceduralFiles(): array {
    $files = [];
    foreach (['', '/modules/*', '/tests/modules/*'] as $dir) {
      foreach (['module', 'inc', 'install'] as $extension) {
        $files = array_merge($files, glob($this->root() . $dir . '/*.' . $extension) ?: []);
      }
    }
    return $files;
  }

  /**
   * Maps each imported short class name to its fully qualified name.
   *
   * @return array<string, string>
   *   FQCN keyed by the short name (or alias) used in the file.
   */
  private function imports(string $code): array {
    $map = [];
    preg_match_all('/^use\s+([\w\\\\]+)(?:\s+as\s+(\w+))?\s*;/m', $code, $matches, PREG_SET_ORDER);
    foreach ($matches as $match) {
      $fqcn = ltrim($match[1], '\\');
      $short = $match[2] ?? '';
      if ($short === '') {
        $parts = explode('\\', $fqcn);
        $short = end($parts);
      }
      $map[$short] = $fqcn;
    }
    return $map;
  }

  /**
   * Whether a class name belongs to this project.
   */
  private function isOwnClass(string $fqcn): bool {
    return (bool) preg_match('/^Drupal\\\\selectify(_\w+)?\\\\/', $fqcn);
  }

  /**
   * Finds every *Hooks class in the project by its file path.
   *
   * @return list<string>
   *   Fully qualified class names, sorted.
   */
  private function hookClasses(): array {
    $classes = [];
    $patterns = [
      $this->root() . '/src/Hook/*Hooks.php',
      $this->root() . '/modules/*/src/Hook/*Hooks.php',
      $this->root() . '/tests/modules/*/src/Hook/*Hooks.php',
    ];
    foreach ($patterns as $pattern) {
      foreach (glob($pattern) ?: [] as $file) {
        $code = (string) file_get_contents($file);
        preg_match('/^namespace\s+([\w\\\\]+)\s*;/m', $code, $namespace);
        $classes[] = ($namespace[1] ?? '') . '\\' . basename($file, '.php');
      }
    }
    sort($classes);
    return $classes;
  }

  /**
   * Builds a container with only the ids a 2.0.2-2.0.4 site holds.
   *
   * Service ids, not interface aliases: the ids are what those containers
   * are guaranteed to hold.
   */
  private function preUpdateContainer(): Container {
    $container = new Container();
    $container->set('selectify.helper', $this->createStub(SelectifyHelperInterface::class));
    $container->set('selectify.style_service', $this->createStub(SelectifyStyleServiceInterface::class));
    $container->set('config.factory', $this->createStub(ConfigFactoryInterface::class));
    $container->set('module_handler', $this->createStub(ModuleHandlerInterface::class));
    $container->set('request_stack', new RequestStack());
    return $container;
  }

  /**
   * No wrapper asks the container for a class the project itself defines.
   */
  public function testNoContainerLookupOfOwnClasses(): void {
    $offenders = [];
    $resolved = 0;
    foreach ($this->proceduralFiles() as $file) {
      $code = (string) file_get_contents($file);
      $imports = $this->imports($code);
      preg_match_all('/\\\\Drupal::(service|classResolver)\(\s*\\\\?([\w\\\\]+)::class\s*\)/', $code, $calls, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
      foreach ($calls as $call) {
        $name = $call[2][0];
        $fqcn = str_contains($name, '\\') ? ltrim($name, '\\') : ($imports[$name] ?? $name);
        if (!$this->isOwnClass($fqcn)) {
          continue;
        }
        if ($call[1][0] === 'classResolver') {
          $resolved++;
          continue;
        }
        $line = substr_count(substr($code, 0, $call[0][1]), "\n") + 1;
        $offenders[] = sprintf('%s:%d \\Drupal::service(%s::class)', basename($file), $line, $name);
      }
    }

    $this->assertSame([], $offenders, sprintf(
      "A service declared in a new release does not exist in the container of a site updated from an older one until a cache rebuild; use \\Drupal::classResolver() instead. %d offending call(s):\n%s",
      count($offenders),
      implode("\n", $offenders)
    ));
    // A scan that matches nothing proves nothing: a moved file or a broken
    // pattern must fail rather than pass on an empty result.
    $this->assertSame(self::EXPECTED_WRAPPERS, $resolved, sprintf('Found %d classResolver() wrappers; expected %d.', $resolved, self::EXPECTED_WRAPPERS));
  }

  /**
   * Hook classes the resolver cannot build with `new` must provide create().
   */
  public function testHookClassesWithArgumentsAreContainerInjectable(): void {
    $classes = $this->hookClasses();
    $this->assertSame(self::HOOK_CLASSES, $classes, 'The *Hooks classes on disk changed; update HOOK_CLASSES and preUpdateContainer().');

    $offenders = [];
    foreach ($classes as $class) {
      $constructor = (new \ReflectionClass($class))->getConstructor();
      if ($constructor === NULL || $constructor->getNumberOfRequiredParameters() === 0) {
        continue;
      }
      if (!is_subclass_of($class, ContainerInjectionInterface::class)) {
        $offenders[] = $class;
      }
    }
    $this->assertSame([], $offenders, 'These Hook classes have constructor parameters but do not implement ContainerInjectionInterface, so \Drupal::classResolver() would call `new` without arguments and throw ArgumentCountError.');
  }

  /**
   * Create() works against the container of a site not yet rebuilt.
   */
  public function testCreateUsesOnlyPreUpdateServices(): void {
    $container = $this->preUpdateContainer();
    foreach (self::HOOK_CLASSES as $class) {
      $this->assertTrue(method_exists($class, 'create'), $class . ' has no create() method.');
      $this->assertInstanceOf($class, $class::create($container), $class . '::create() did not return an instance.');
    }
  }

}
