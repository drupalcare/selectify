<?php

declare(strict_types=1);

namespace Drupal\selectify\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\path_alias\AliasManagerInterface;

/**
 * Provides helper methods for the Selectify module.
 *
 * Consolidates configuration access, theme detection, and page/route
 * checks that were previously scattered across the .module file.
 */
class SelectifyHelper implements SelectifyHelperInterface {

  /**
   * Cached Selectify configuration object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig|null
   */
  protected ?ImmutableConfig $selectifyConfig = NULL;

  /**
   * Cached result for page disabled check.
   *
   * @var bool|null
   */
  protected ?bool $isPageDisabled = NULL;

  /**
   * Cached result for radio/checkbox config.
   *
   * @var array|false|null
   */
  protected $radioCheckboxConfig = NULL;

  /**
   * Constructs a SelectifyHelper object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory service.
   * @param \Drupal\Core\Routing\AdminContext $adminContext
   *   The admin context service.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $themeManager
   *   The theme manager service.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $themeHandler
   *   The theme handler service.
   * @param \Drupal\Core\Path\PathMatcherInterface $pathMatcher
   *   The path matcher service.
   * @param \Drupal\Core\Path\CurrentPathStack $currentPath
   *   The current path service.
   * @param \Drupal\path_alias\AliasManagerInterface $aliasManager
   *   The alias manager service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The current route match service.
   */
  public function __construct(
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly AdminContext $adminContext,
    protected readonly ThemeManagerInterface $themeManager,
    protected readonly ThemeHandlerInterface $themeHandler,
    protected readonly PathMatcherInterface $pathMatcher,
    protected readonly CurrentPathStack $currentPath,
    protected readonly AliasManagerInterface $aliasManager,
    protected readonly RouteMatchInterface $routeMatch,
  ) {}

  /**
   * Gets the Selectify configuration object (cached per request).
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   The selectify.settings configuration.
   */
  public function getConfig(): ImmutableConfig {
    if ($this->selectifyConfig === NULL) {
      $this->selectifyConfig = $this->configFactory->get('selectify.settings');
    }
    return $this->selectifyConfig;
  }

  /**
   * Retrieves Selectify radio/checkbox settings with admin route check.
   *
   * Results are cached per request.
   *
   * @return array|false
   *   Array of settings if Selectify should be applied, FALSE otherwise.
   */
  public function getRadioCheckboxConfig(): array|false {
    if ($this->radioCheckboxConfig !== NULL) {
      return $this->radioCheckboxConfig;
    }

    $config = $this->getConfig();

    // Check if disabled on admin routes.
    if ($config->get('disable_on_admin_routes') && $this->adminContext->isAdminRoute()) {
      $this->radioCheckboxConfig = FALSE;
      return FALSE;
    }

    // Check if current page is in the disabled pages list.
    if ($this->isPageDisabled()) {
      $this->radioCheckboxConfig = FALSE;
      return FALSE;
    }

    $this->radioCheckboxConfig = [
      'enable_radio' => $config->get('enable_radio'),
      'enable_checkbox' => $config->get('enable_checkbox'),
      'disable_on_admin_routes' => $config->get('disable_on_admin_routes'),
      'accent_color' => $config->get('accent_color'),
    ];
    return $this->radioCheckboxConfig;
  }

  /**
   * Loads Selectify Views configuration settings.
   *
   * @return array
   *   An associative array containing Views widget settings.
   */
  public function getViewsConfig(): array {
    $config = $this->getConfig();

    return [
      'disable_on_admin_routes' => $config->get('disable_on_admin_routes'),
      'widget_map' => $config->get('widget_for_filters') ?? [],
      'apply_site_wide' => $config->get('apply_site_wide'),
      'global_widget' => $config->get('global_selectify_widget'),
    ];
  }

  /**
   * Checks if Selectify is disabled on the current admin route.
   *
   * @return bool
   *   TRUE if Selectify is disabled on an admin route, FALSE otherwise.
   */
  public function isDisabledOnAdminRoute(): bool {
    $config = $this->getViewsConfig();

    if ($config['disable_on_admin_routes'] && $this->routeMatch->getRouteName() !== NULL) {
      $current_path = $this->currentPath->getPath();
      if (str_starts_with($current_path, '/admin')) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Checks if the current page matches any disabled page pattern.
   *
   * Results are cached per request.
   *
   * @return bool
   *   TRUE if the current page should have Selectify disabled.
   */
  public function isPageDisabled(): bool {
    if ($this->isPageDisabled !== NULL) {
      return $this->isPageDisabled;
    }

    $disabled_pages = $this->getConfig()->get('disabled_pages') ?? '';

    if (empty(trim($disabled_pages))) {
      $this->isPageDisabled = FALSE;
      return FALSE;
    }

    $current_path = ltrim($this->currentPath->getPath(), '/');
    $path_alias = ltrim($this->aliasManager->getAliasByPath('/' . $current_path), '/');

    $this->isPageDisabled = $this->pathMatcher->matchPath($current_path, $disabled_pages)
      || ($path_alias !== $current_path && $this->pathMatcher->matchPath($path_alias, $disabled_pages));

    return $this->isPageDisabled;
  }

  /**
   * Checks if the current route is an admin route.
   *
   * @return bool
   *   TRUE if the current route is an admin route.
   */
  public function isAdminRoute(): bool {
    return $this->adminContext->isAdminRoute();
  }

  /**
   * Checks if the active theme is or extends the given base theme.
   *
   * @param string $theme_name
   *   The theme name to check for (e.g. 'gin', 'claro', 'olivero').
   *
   * @return bool
   *   TRUE if the active theme is or extends the given theme.
   */
  public function isBaseTheme(string $theme_name): bool {
    $active_theme = $this->themeManager->getActiveTheme();

    if ($active_theme->getName() === $theme_name) {
      return TRUE;
    }

    foreach ($active_theme->getBaseThemeExtensions() as $base_theme) {
      if ($base_theme->getName() === $theme_name) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Recursively finds the topmost base theme (the original parent).
   *
   * @param string $theme_name
   *   The theme name to find the root parent for.
   *
   * @return string
   *   The root parent theme name.
   */
  public function getRootParentTheme(string $theme_name): string {
    $theme_info = $this->themeHandler->getTheme($theme_name);

    if (!empty($theme_info->info['base theme'])) {
      $base_themes = (array) $theme_info->info['base theme'];
      return $this->getRootParentTheme(reset($base_themes));
    }

    return $theme_name;
  }

  /**
   * Cleans a theme name to allow only letters, numbers, and dashes.
   *
   * @param string $name
   *   The theme name to clean.
   *
   * @return string
   *   The cleaned theme name.
   */
  public function cleanThemeName(string $name): string {
    $name = str_replace([' ', '_'], '-', $name);
    return preg_replace('/[^a-zA-Z0-9-]/', '', $name) ?? '';
  }

  /**
   * Gets the active theme name.
   *
   * @return string
   *   The active theme name.
   */
  public function getActiveThemeName(): string {
    return $this->themeManager->getActiveTheme()->getName();
  }

  /**
   * Retrieves the library dependencies for each Selectify widget type.
   *
   * @return array
   *   An associative array mapping widget names to their library arrays.
   */
  public function getWidgetLibraries(): array {
    return [
      'selectify_dropdown' => [
        'selectify/selectify-base',
        'selectify/selectify-helper',
        'selectify/selectify-dropdowns',
        'selectify/selectify-dropdown',
      ],
      'selectify_tags' => [
        'selectify/selectify-base',
        'selectify/selectify-helper',
        'selectify/selectify-dropdowns',
        'selectify/selectify-dropdown-tags',
      ],
      'selectify_searchable' => [
        'selectify/selectify-base',
        'selectify/selectify-helper',
        'selectify/selectify-dropdowns',
        'selectify/selectify-dropdown-searchable',
      ],
      'selectify_dual' => [
        'selectify/selectify-base',
        'selectify/selectify-helper',
        'selectify/selectify-dual',
      ],
      'selectify_checkbox' => [
        'selectify/selectify-base',
        'selectify/selectify-helper',
        'selectify/selectify-dropdowns',
        'selectify/selectify-dropdown-checkbox',
      ],
    ];
  }

  /**
   * Retrieves the CSS class mapping for each Selectify widget type.
   *
   * @return array
   *   An associative array mapping widget names to their CSS classes.
   */
  public function getWidgetClasses(): array {
    return [
      'selectify_dropdown' => 'selectify-apply-dropdown',
      'selectify_tags' => 'selectify-apply-tags',
      'selectify_searchable' => 'selectify-apply-searchable',
      'selectify_checkbox' => 'selectify-apply-checkbox',
      'selectify_dual' => 'selectify-apply-dual',
    ];
  }

  /**
   * Extracts and normalizes a form ID by replacing underscores with hyphens.
   *
   * @param array $form
   *   The Views exposed form array.
   *
   * @return string
   *   The normalized form ID.
   */
  public function normalizeFormId(array $form): string {
    $form_id = $form['#id'] ?? '';

    // Fallback to #attributes['id'] if #id is not set.
    if (empty($form_id) && isset($form['#attributes']['id'])) {
      $form_id = $form['#attributes']['id'];
    }

    // Handle array form IDs (shouldn't happen, but be defensive).
    if (is_array($form_id)) {
      $form_id = reset($form_id);
      // reset() returns FALSE if array is empty.
      if ($form_id === FALSE) {
        $form_id = '';
      }
    }

    return str_replace('_', '-', (string) $form_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormApiConfig(): array|false {
    $config = $this->getConfig();

    if (!$config->get('enable_form_api_selects')) {
      return FALSE;
    }

    if ($config->get('disable_on_admin_routes') && $this->adminContext->isAdminRoute()) {
      return FALSE;
    }

    // Per-form widget map: each entry contains a form_id and a widget. This
    // allows specific forms to use different widgets than the global default.
    $map_raw = $config->get('form_api_widget_map') ?? [];
    $widget_map = [];
    if (is_array($map_raw)) {
      foreach ($map_raw as $entry) {
        if (empty($entry['form_id']) || empty($entry['widget'])) {
          continue;
        }
        $widget_map[$entry['form_id']] = $entry['widget'];
      }
    }

    return [
      'widget' => $config->get('form_api_widget') ?? 'selectify_dropdown',
      'widget_map' => $widget_map,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getInternalBlockedFormPatterns(): array {
    return [
      // -----------------------------------------------------------------
      // Field UI — Manage Form Display & Manage Display use AJAX selects
      // to swap widget/formatter types and load their settings dynamically.
      // -----------------------------------------------------------------
      'entity_form_display_edit_form',
      'entity_view_display_edit_form',
      'field_config_edit_form',
      'field_storage_config_edit_form',
      'field_ui_field_storage_add_form',

      // -----------------------------------------------------------------
      // Views UI — All admin forms are deeply AJAX-dependent.
      // -----------------------------------------------------------------
      'views_ui_*',

      // -----------------------------------------------------------------
      // Layout Builder — AJAX modal forms for adding/configuring blocks.
      // -----------------------------------------------------------------
      'layout_builder_*',

      // -----------------------------------------------------------------
      // Configuration synchronisation — critical admin operations.
      // -----------------------------------------------------------------
      'config_single_import_form',
      'config_single_export_form',
      'config_import_form',

      // -----------------------------------------------------------------
      // Block layout — AJAX region assignment selects.
      // -----------------------------------------------------------------
      'block_admin_display_form',

      // -----------------------------------------------------------------
      // System modules page — complex admin tables.
      // -----------------------------------------------------------------
      'system_modules',
      'system_modules_uninstall',
      'system_modules_confirm_form',

      // -----------------------------------------------------------------
      // User permissions — large table form, not a normal select context.
      // -----------------------------------------------------------------
      'user_admin_permissions',

      // -----------------------------------------------------------------
      // Taxonomy overview — drag-and-drop weight interface.
      // -----------------------------------------------------------------
      'taxonomy_overview_terms',

      // -----------------------------------------------------------------
      // Text editor / filter configuration — AJAX plugin settings.
      // -----------------------------------------------------------------
      'editor_*',
      'filter_format_*',

      // -----------------------------------------------------------------
      // Media library — AJAX-heavy modal/selection UI.
      // -----------------------------------------------------------------
      'media_library_*',

      // -----------------------------------------------------------------
      // Update manager — critical update operations.
      // -----------------------------------------------------------------
      'update_manager_*',

      // -----------------------------------------------------------------
      // Webform admin UI (NOT submission forms — those are handled by
      // the selectify_webform submodule).
      // -----------------------------------------------------------------
      'webform_ui_*',
      'webform_settings_*',
      'webform_handler_*',
      'webform_element_*',

      // -----------------------------------------------------------------
      // Content moderation / workflows — configuration sensitivity.
      // -----------------------------------------------------------------
      'content_moderation_*',
      'workflow_*',

      // -----------------------------------------------------------------
      // Selectify's own settings form.
      // -----------------------------------------------------------------
      'selectify_settings_form',

      // -----------------------------------------------------------------
      // Common contrib with AJAX-heavy admin forms.
      // -----------------------------------------------------------------
      'paragraphs_*',
      'search_api_*',
      'feeds_*',
      'migrate_*',
      'devel_*',

      // -----------------------------------------------------------------
      // AJAX-dependent element forms that rebuild dynamically.
      // -----------------------------------------------------------------
      'inline_entity_form_*',
      'entity_browser_*',
      'commerce_*_settings_form',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isFormExcluded(string $form_id, array $user_excluded = []): bool {
    if (empty($form_id) || !is_string($form_id)) {
      return FALSE;
    }

    // Check hardcoded internal blocklist first.
    foreach ($this->getInternalBlockedFormPatterns() as $pattern) {
      // phpcs:ignore Drupal.Functions.DiscouragedFunctions.Discouraged
      if (is_string($pattern) && fnmatch($pattern, $form_id)) {
        return TRUE;
      }
    }

    // Check user-defined exclusions.
    foreach ($user_excluded as $pattern) {
      // phpcs:ignore Drupal.Functions.DiscouragedFunctions.Discouraged
      if (is_string($pattern) && fnmatch($pattern, $form_id)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function hasThirdPartySelectLibrary(array $element): bool {
    $attributes = $element['#attributes'] ?? [];
    $classes = $attributes['class'] ?? [];
    $class_string = is_array($classes) ? implode(' ', $classes) : (string) $classes;

    // Select2, Chosen, Selectize, Choices.js — common class patterns.
    $patterns = [
      'select2-hidden-accessible',
      'select2',
      'js-select2',
      'chosen-select',
      'chosen-container',
      'selectize',
      'selectize-control',
      'choices',
      'choices__inner',
    ];
    foreach ($patterns as $pattern) {
      if (preg_match('/\b' . preg_quote($pattern, '/') . '\b/i', $class_string)) {
        return TRUE;
      }
    }

    if (isset($attributes['data-select2-id']) || isset($attributes['data-chosen']) ||
        isset($attributes['data-selectize-id']) || isset($attributes['data-choices'])) {
      return TRUE;
    }

    $element_id = $element['#id'] ?? '';
    $third_party_ids = ['select2', 'chosen', 'selectize', 'choices'];
    foreach ($third_party_ids as $id) {
      if (stripos($element_id, $id) !== FALSE) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isSelectHiddenOrDisabled(array $element): bool {
    if (isset($element['#access']) && $element['#access'] === FALSE) {
      return TRUE;
    }
    if (isset($element['#disabled']) && $element['#disabled'] === TRUE) {
      return TRUE;
    }
    if (isset($element['#readonly']) && $element['#readonly'] === TRUE) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isSelectInExcludedContext(array $element): bool {
    $context_classes = [
      'toolbar',
      'toolbar-item',
      'off-canvas',
      'ui-dialog',
      'modal',
      'ui-dialog-content',
    ];

    $wrapper = $element['#wrapper_attributes']['class'] ?? [];
    $wrapper_string = is_array($wrapper) ? implode(' ', $wrapper) : (string) $wrapper;
    $attributes = $element['#attributes']['class'] ?? [];
    $attr_string = is_array($attributes) ? implode(' ', $attributes) : (string) $attributes;
    $combined = $wrapper_string . ' ' . $attr_string;

    foreach ($context_classes as $cls) {
      if (preg_match('/\b' . preg_quote($cls, '/') . '\b/i', $combined)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function shouldSkipSelectElement(array $element): bool {
    return $this->hasThirdPartySelectLibrary($element)
      || $this->isSelectHiddenOrDisabled($element)
      || $this->isSelectInExcludedContext($element);
  }

  /**
   * Applies a Selectify widget to all select elements in a form.
   *
   * @param array &$form
   *   The Views exposed form array.
   * @param string $widget_name
   *   The widget type name (e.g. 'selectify_dropdown').
   */
  public function applyWidgetToForm(array &$form, string $widget_name): void {
    $widget_libraries = $this->getWidgetLibraries();
    $widget_classes = $this->getWidgetClasses();

    if (!isset($widget_libraries[$widget_name])) {
      return;
    }

    foreach ($widget_libraries[$widget_name] as $library) {
      $form['#attached']['library'][] = $library;
    }

    if (isset($widget_classes[$widget_name])) {
      $form['#attributes']['class'][] = 'selectify-views-form';

      foreach ($form as &$element) {
        if (is_array($element) && isset($element['#type']) && $element['#type'] === 'select') {
          $element['#attributes']['class'][] = $widget_classes[$widget_name];
        }
      }
    }
  }

}
