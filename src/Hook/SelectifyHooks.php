<?php

namespace Drupal\selectify\Hook;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\selectify\Service\SelectifyHelperInterface;
use Drupal\selectify\Service\SelectifyStyleServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Hook implementations for selectify.
 */
class SelectifyHooks implements ContainerInjectionInterface {

  /**
   * Constructs the hook implementations.
   *
   * INJECTED RATHER THAN FETCHED, and the reason is not style. The Project
   * Update Bot's patch moved these hook bodies verbatim out of the .module
   * file, where `\Drupal::service()` was the only option, into a class where
   * it is not: the service container builds this class (autowire: true in
   * selectify.services.yml), so every dependency can be handed to it.
   * DrupalPractice flags each remaining static call, and it was right to —
   * moving procedural code into a class without giving it constructor
   * injection keeps the drawback of both shapes.
   *
   * The call sites below are otherwise untouched: each `\Drupal::service(...)`
   * became `$this->...` and the local variable kept its name, so no logic in
   * these hooks changed with this edit.
   *
   * @param \Drupal\selectify\Service\SelectifyHelperInterface $helper
   *   Answers whether Selectify applies to the current page and element.
   * @param \Drupal\selectify\Service\SelectifyStyleServiceInterface $styleService
   *   Builds the body classes and inline style payload.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Reads selectify.settings.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   Invokes this module's own alter hooks, and checks for selectify_webform.
   */
  public function __construct(
    protected readonly SelectifyHelperInterface $helper,
    protected readonly SelectifyStyleServiceInterface $styleService,
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly ModuleHandlerInterface $moduleHandler,
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
      $container->get('selectify.helper'),
      $container->get('selectify.style_service'),
      $container->get('config.factory'),
      $container->get('module_handler'),
    );
  }

  /**
   * Implements hook_preprocess_html().
   */
  #[Hook('preprocess_html')]
  public function preprocessHtml(&$variables) {
    /** @var \Drupal\selectify\Service\SelectifyHelper $helper */
    $helper = $this->helper;
    // Check if current page is in the disabled pages list.
    if ($helper->isPageDisabled()) {
      return;
    }
    $config = $this->configFactory->get('selectify.settings');
    $radioEnabled = $config->get('enable_radio');
    $checkboxEnabled = $config->get('enable_checkbox');
    if (!$radioEnabled && !$checkboxEnabled) {
      return;
    }
    /** @var \Drupal\selectify\Service\SelectifyStyleService $styleService */
    $styleService = $this->styleService;
    $bodyClasses = $styleService->getBodyClasses();
    if (!empty($bodyClasses)) {
      $classes = explode(' ', $bodyClasses);
      foreach ($classes as $class) {
        $variables['attributes']['class'][] = $class;
      }
    }
    // Add selectify-color-disabled class when the active color mode is none.
    $colorMode = $helper->isAdminRoute() ? $config->get('admin_accent_color_mode') ?? 'light' : $config->get('accent_color_mode') ?? 'light';
    if ($colorMode === 'none') {
      $variables['attributes']['class'][] = 'selectify-color-disabled';
    }
  }

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match): ?string {
    if ($route_name === 'help.page.selectify') {
      return _selectify_render_readme();
    }
    return NULL;
  }

  /**
   * Implements hook_theme_suggestions_select_alter().
   */
  #[Hook('theme_suggestions_select_alter')]
  public function themeSuggestionsSelectAlter(array &$suggestions, array $variables) {
    if (empty($variables['element']['#attributes']['class'])) {
      return;
    }
    $classes = (array) $variables['element']['#attributes']['class'];
    foreach ($suggestions as $key => $suggestion) {
      if (strpos($suggestion, 'form_element') !== FALSE) {
        unset($suggestions[$key]);
      }
    }
    $class_to_suggestion = [
      'selectify-apply-dropdown' => 'select__selectify_dropdown',
      'selectify-apply-checkbox' => 'select__selectify_dropdown_checkbox',
      'selectify-apply-searchable' => 'select__selectify_dropdown_searchable',
      'selectify-apply-tags' => 'select__selectify_dropdown_tags',
      'selectify-apply-dual' => 'select__selectify_dual',
    ];
    foreach ($class_to_suggestion as $class => $suggestion) {
      if (in_array($class, $classes)) {
        $suggestions[] = $suggestion;
      }
    }
  }

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme($existing, $type, $theme, $path) {
    return [
      'select__selectify_dropdown' => [
        'base hook' => 'select',
        'template' => 'select--selectify-dropdown',
        'path' => $path . '/templates',
      ],
      'select__selectify_dropdown_checkbox' => [
        'base hook' => 'select',
        'template' => 'select--selectify-dropdown-checkbox',
        'path' => $path . '/templates',
      ],
      'select__selectify_dropdown_searchable' => [
        'base hook' => 'select',
        'template' => 'select--selectify-dropdown-searchable',
        'path' => $path . '/templates',
      ],
      'select__selectify_dropdown_tags' => [
        'base hook' => 'select',
        'template' => 'select--selectify-dropdown-tags',
        'path' => $path . '/templates',
      ],
      'select__selectify_dual' => [
        'base hook' => 'select',
        'template' => 'select--selectify-dual',
        'path' => $path . '/templates',
      ],
    ];
  }

  /**
   * Implements hook_preprocess_form_element() for Selectify.
   */
  #[Hook('preprocess_form_element')]
  public function preprocessFormElement(array &$variables): void {
    /** @var \Drupal\selectify\Service\SelectifyHelper $helper */
    $helper = $this->helper;
    $config = $helper->getRadioCheckboxConfig();
    if ($config === FALSE) {
      return;
    }
    if (!isset($variables['element']['#type'])) {
      return;
    }
    $type = $variables['element']['#type'];
    if ($type === 'radio' && $config['enable_radio'] === TRUE) {
      $variables['attributes']['class'][] = 'selectify-form-item selectify-radio-wrapper';
    }
    elseif ($type === 'checkbox' && $config['enable_checkbox'] === TRUE) {
      $variables['attributes']['class'][] = 'selectify-form-item selectify-checkbox-wrapper';
    }
  }

  /**
   * Implements hook_preprocess_input() for Selectify.
   */
  #[Hook('preprocess_input')]
  public function preprocessInput(array &$variables): void {
    /** @var \Drupal\selectify\Service\SelectifyHelper $helper */
    $helper = $this->helper;
    $config = $helper->getRadioCheckboxConfig();
    if ($config === FALSE) {
      return;
    }
    if (!isset($variables['attributes']['type'])) {
      return;
    }
    $type = $variables['attributes']['type'];
    if ($type === 'radio' && $config['enable_radio'] === TRUE) {
      $variables['attributes']['class'][] = 'selectify-radio';
    }
    elseif ($type === 'checkbox' && $config['enable_checkbox'] === TRUE) {
      $variables['attributes']['class'][] = 'selectify-checkbox';
    }
  }

  /**
   * Implements hook_page_attachments() for Selectify.
   */
  #[Hook('page_attachments')]
  public function pageAttachments(array &$attachments) {
    /** @var \Drupal\selectify\Service\SelectifyHelper $helper */
    $helper = $this->helper;
    $config = $helper->getRadioCheckboxConfig();
    if ($config === FALSE) {
      return;
    }
    if ($config['enable_checkbox'] === TRUE || $config['enable_radio'] === TRUE) {
      $attachments['#attached']['library'][] = 'selectify/selectify-base';
      $attachments['#attached']['library'][] = 'selectify/selectify-radio-checkbox';
      $selectifyConfig = $helper->getConfig();
      $isAdmin = $helper->isAdminRoute();
      $selected_accent = $isAdmin ? $selectifyConfig->get('admin_accent_color') : $config['accent_color'];
      $accent_color_mode = $isAdmin ? $selectifyConfig->get('admin_accent_color_mode') ?: 'light' : ($selectifyConfig->get('accent_color_mode') ?: 'light');
      if (!empty($selected_accent) && $selected_accent !== 'none' && $accent_color_mode !== 'none') {
        $attachments['#attached']['library'][] = "selectify/selectify-color-{$selected_accent}-{$accent_color_mode}";
      }
    }
  }

  /**
   * Implements hook_page_attachments_alter().
   */
  #[Hook('page_attachments_alter')]
  public function pageAttachmentsAlter(array &$attachments): void {
    /** @var \Drupal\selectify\Service\SelectifyHelper $helper */
    $helper = $this->helper;
    // Check if current page is in the disabled pages list.
    if ($helper->isPageDisabled()) {
      if (isset($attachments['#attached']['library'])) {
        foreach ($attachments['#attached']['library'] as $key => $library) {
          if (is_string($library) && strpos($library, 'selectify/') === 0) {
            unset($attachments['#attached']['library'][$key]);
          }
        }
      }
      if (isset($attachments['#attached']['drupalSettings']['selectify'])) {
        unset($attachments['#attached']['drupalSettings']['selectify']);
      }
      return;
    }
    // Load configuration.
    $selectifyConfig = $helper->getConfig();
    // Attach radio-checkbox library and inject CSS variables if enabled.
    $radioEnabled = (bool) $selectifyConfig->get('enable_radio');
    $checkboxEnabled = (bool) $selectifyConfig->get('enable_checkbox');
    if ($radioEnabled || $checkboxEnabled) {
      $attachments['#attached']['library'][] = 'selectify/selectify-radio-checkbox';
      /** @var \Drupal\selectify\Service\SelectifyStyleService $styleService */
      $styleService = $this->styleService;
      $inlineCSS = $styleService->getConfiguredInlineCss();
      if (!empty($inlineCSS)) {
        $attachments['#attached']['html_head'][] = [
          [
            '#tag' => 'style',
            '#value' => $inlineCSS,
            '#attributes' => [
              'id' => 'selectify-inline-variables',
            ],
          ],
          'selectify_inline_variables',
        ];
      }
      $isAdmin = $helper->isAdminRoute();
      $accentColor = $isAdmin ? $selectifyConfig->get('admin_accent_color') : $selectifyConfig->get('accent_color');
      $accentColorMode = $isAdmin ? $selectifyConfig->get('admin_accent_color_mode') ?? 'light' : $selectifyConfig->get('accent_color_mode') ?? 'light';
      if (!empty($accentColor) && $accentColor !== 'none' && $accentColorMode !== 'none') {
        $attachments['#attached']['library'][] = "selectify/selectify-color-{$accentColor}-{$accentColorMode}";
      }
    }
    // Gin theme integration.
    if ($helper->isBaseTheme('gin')) {
      $attachments['#attached']['library'][] = 'selectify/selectify-gin-base';
      $adminColorMode = $selectifyConfig->get('admin_accent_color_mode') ?? 'light';
      if ($adminColorMode === 'dark') {
        $attachments['#attached']['library'][] = 'selectify/selectify-gin-dark';
      }
      elseif ($adminColorMode === 'light') {
        $attachments['#attached']['library'][] = 'selectify/selectify-gin-light';
      }
    }
    // Claro theme integration.
    if ($helper->isBaseTheme('claro')) {
      $attachments['#attached']['library'][] = 'selectify/selectify-claro';
    }
    // Olivero theme integration.
    if ($helper->isBaseTheme('olivero')) {
      $attachments['#attached']['library'][] = 'selectify/selectify-olivero';
    }
  }

  /**
   * Implements hook_preprocess_container().
   */
  #[Hook('preprocess_container')]
  public function preprocessContainer(array &$variables): void {
    if (empty($variables['attributes'])) {
      return;
    }
    $attr = $variables['attributes'];
    $classes = [];
    // Normalize classes to a clean string[] in all cases.
    if ($attr instanceof Attribute) {
      $classes = $attr->offsetGet('class') ?? [];
    }
    elseif (is_array($attr) && array_key_exists('class', $attr)) {
      $classes = $attr['class'];
    }
    elseif (isset($attr['class'])) {
      $classes = $attr['class'];
    }
    if (is_string($classes)) {
      $classes = preg_split('/\s+/', trim($classes), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }
    elseif (!is_array($classes)) {
      $classes = [];
    }
    if (!$classes) {
      return;
    }
    foreach ($classes as $class) {
      if (is_string($class) && str_starts_with($class, 'field--widget-selectify-')) {
        $max = $variables['element']['widget'][0]['#attributes']['data-max-selections'] ?? NULL;
        $new_class = $max === '1' ? 'selectify-single' : 'selectify-multi';
        if ($attr instanceof Attribute) {
          $variables['attributes']->addClass($new_class);
        }
        else {
          if (!isset($variables['attributes']['class']) || !is_array($variables['attributes']['class'])) {
            $variables['attributes']['class'] = (array) ($variables['attributes']['class'] ?? []);
          }
          $variables['attributes']['class'][] = $new_class;
        }
        break;
      }
    }
  }

  /**
   * Implements hook_preprocess_views_exposed_form() for Selectify.
   */
  #[Hook('preprocess_views_exposed_form')]
  public function preprocessViewsExposedForm(&$variables) {
    $form =& $variables['form'];
    /** @var \Drupal\selectify\Service\SelectifyHelper $helper */
    $helper = $this->helper;
    if ($helper->isPageDisabled()) {
      return;
    }
    if ($helper->isDisabledOnAdminRoute()) {
      return;
    }
    // Add the 'selectify-views' class to all select elements.
    foreach ($form as &$element) {
      if (is_array($element) && isset($element['#type']) && $element['#type'] === 'select') {
        $element['#attributes'] = $element['#attributes'] ?? [];
        $element['#attributes']['class'] = $element['#attributes']['class'] ?? [];
        $element['#attributes']['class'][] = 'selectify-views';
      }
    }
    unset($element);
    // Retrieve the Selectify configuration.
    $config = $helper->getViewsConfig();
    $apply_site_wide = $config['apply_site_wide'];
    $global_widget = $config['global_widget'];
    $widget_map = $config['widget_map'];
    $clean_form_id = $helper->normalizeFormId($form);
    // Determine widget to apply.
    $widget_to_apply = NULL;
    if ($apply_site_wide) {
      $widget_to_apply = $global_widget;
    }
    else {
      foreach ($widget_map as $widget_data) {
        $stored_id = $widget_data['exposed_form_id'] ?? '';
        $normalized_id = str_replace('_', '-', $stored_id);
        if (!empty($normalized_id) && $normalized_id === $clean_form_id) {
          $widget_to_apply = $widget_data['widget'];
          break;
        }
      }
    }
    // Allow other modules to alter the widget selection.
    if (!empty($widget_to_apply)) {
      $view = $variables['view'] ?? NULL;
      $context = [
        'form_id' => $clean_form_id,
        'form' => &$form,
        'integration_type' => 'views',
        'view_name' => $view && is_object($view) && method_exists($view, 'id') ? $view->id() : NULL,
        'display_id' => $view && is_object($view) && isset($view->current_display) ? $view->current_display : NULL,
      ];
      $this->moduleHandler->alter('selectify_widget', $widget_to_apply, $context);
    }
    // Apply global or per-view widget.
    if (!empty($widget_to_apply)) {
      $helper->applyWidgetToForm($form, $widget_to_apply);
    }
    // Add single/multi class to each select wrapper.
    foreach ($form as &$element) {
      if (is_array($element) && isset($element['#type']) && $element['#type'] === 'select') {
        $is_multiple = isset($element['#multiple']) && $element['#multiple'] === TRUE;
        $element['#wrapper_attributes']['class'][] = $is_multiple ? 'selectify-multi' : 'selectify-single';
      }
    }
  }

  /**
   * Implements hook_form_alter().
   *
   * Applies Selectify widgets to plain Form API <select> elements.
   * Skips Views exposed forms (handled by hook_preprocess_views_exposed_form)
   * and elements already styled by field widgets.
   */
  #[Hook('form_alter')]
  public function formAlter(array &$form, FormStateInterface $form_state, string $form_id): void {
    /** @var \Drupal\selectify\Service\SelectifyHelper $helper */
    $helper = $this->helper;
    // Bail early if the feature is disabled or on a disabled admin route.
    $config = $helper->getFormApiConfig();
    if ($config === FALSE) {
      return;
    }
    // Bail if page is in the disabled pages list.
    if ($helper->isPageDisabled()) {
      return;
    }
    // Skip Views exposed forms — handled by
    // hook_preprocess_views_exposed_form().
    if (str_starts_with($form_id, 'views_exposed_form')) {
      return;
    }
    // Skip Webform submission forms if the selectify_webform submodule handles
    // them.
    if (str_starts_with($form_id, 'webform_submission_') && $this->moduleHandler->moduleExists('selectify_webform')) {
      return;
    }
    // Check built-in blocklist (always-excluded admin forms: Field UI,
    // Views UI, Layout Builder, etc.) that break with custom selects.
    if ($helper->isFormExcluded($form_id)) {
      return;
    }
    // Allow other modules to prevent Selectify from applying to this form.
    $applicable = TRUE;
    $context = [
      'form_id' => $form_id,
      'form' => &$form,
      'integration_type' => 'form_api',
    ];
    $this->moduleHandler->alter('selectify_is_applicable', $applicable, $context);
    if (!$applicable) {
      return;
    }
    // Also skip any form element that has an #ajax callback on a select,
    // since custom select replacement can break AJAX triggers.
    // (Individual selects with #ajax are skipped in the recursive walker.)
    // Record this form in the discovered list if it contains any eligible
    // (non-AJAX, non-field-widget) selects. This powers the Views-like
    // configuration UI for Form API.
    if (_selectify_form_api_has_eligible_select($form)) {
      _selectify_form_api_record_discovered_form($form_id);
    }
    // Compute accent color library once for this request.
    $selectifyConfig = $helper->getConfig();
    $isAdmin = $helper->isAdminRoute();
    $accent = $isAdmin ? $selectifyConfig->get('admin_accent_color') ?: 'neutral' : ($selectifyConfig->get('accent_color') ?: 'neutral');
    $accent_mode = $isAdmin ? $selectifyConfig->get('admin_accent_color_mode') ?: 'light' : ($selectifyConfig->get('accent_color_mode') ?: 'light');
    $accent_library = NULL;
    if (!empty($accent) && $accent !== 'none' && $accent_mode !== 'none') {
      $accent_library = "selectify/selectify-color-{$accent}-{$accent_mode}";
    }
    // CSS selector rules: apply per-element targeting first. Matched selects
    // receive their rule's widget and are marked with selectify-apply-* so
    // the per-form widget step (below) will not override them.
    $css_rules = $config['css_selector_rules'] ?? [];
    if (!empty($css_rules)) {
      _selectify_apply_css_selector_rules($form, $helper, $css_rules, $accent_library);
    }
    // Form API does not have a global "apply to all forms" mode. Only forms
    // explicitly configured in the per-form widget map will be styled.
    $widget = $config['widget_map'][$form_id] ?? 'none';
    // Allow other modules to alter the widget selection.
    $context = [
      'form_id' => $form_id,
      'form' => &$form,
      'integration_type' => 'form_api',
    ];
    $this->moduleHandler->alter('selectify_widget', $widget, $context);
    if (empty($widget) || $widget === 'none') {
      return;
    }
    _selectify_apply_to_form_selects($form, $helper, $widget, $accent_library);
  }

}
