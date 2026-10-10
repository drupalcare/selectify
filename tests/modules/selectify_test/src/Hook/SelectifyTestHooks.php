<?php

namespace Drupal\selectify_test\Hook;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Hook implementations for selectify_test.
 */
class SelectifyTestHooks implements ContainerInjectionInterface {

  /**
   * Constructs the test hook implementations.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   Supplies the current request, which \Drupal::request() fetched before
   *   this code moved into a container-built class.
   */
  public function __construct(
    protected readonly RequestStack $requestStack,
  ) {}

  /**
   * {@inheritdoc}
   *
   * Used only when the container has no service for this class: on a site
   * updated from a release before the Hook services were declared (2.0.5),
   * until its next cache rebuild. \Drupal::classResolver() then calls this.
   * Dependencies are fetched by service ID, which the older containers hold.
   * The constructor stays the source of truth; HookClassResolutionTest pins
   * this method to it. Once the service exists, autowiring builds the class
   * through the constructor and this method is not called.
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('request_stack'),
    );
  }

  /**
   * @file
   * Test module implementing Selectify alter hooks for Kernel tests.
   */

  /**
   * Implements hook_selectify_widget_alter().
   */
  #[Hook('selectify_widget_alter')]
  public function selectifyWidgetAlter(string &$widget, array $context): void {
    if ($context['integration_type'] === 'form_api' && $context['form_id'] === 'selectify_test_form') {
      if ($widget === 'selectify_dropdown') {
        $widget = 'selectify_searchable';
      }
    }
  }

  /**
   * Implements hook_selectify_element_alter().
   */
  #[Hook('selectify_element_alter')]
  public function selectifyElementAlter(array &$element, array $context): void {
    $element['#attributes'] = $element['#attributes'] ?? [];
    $element['#attributes']['data-selectify-test'] = 'altered';
    if (isset($element['#type']) && $element['#type'] === 'select') {
      $options = $element['#options'] ?? [];
      if (count($options) <= 2) {
        $element['#selectify_skip'] = TRUE;
      }
    }
  }

  /**
   * Implements hook_selectify_is_applicable_alter().
   */
  #[Hook('selectify_is_applicable_alter')]
  public function selectifyIsApplicableAlter(bool &$applicable, array $context): void {
    $request = $this->requestStack->getCurrentRequest();
    if ($request && $request->query && $request->query->get('disable_selectify') === '1') {
      $applicable = FALSE;
    }
  }

}
