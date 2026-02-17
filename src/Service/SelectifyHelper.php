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

    if ($config->get('disable_on_admin_routes') && $this->adminContext->isAdminRoute()) {
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
    $form_id = $form['#id'] ?? ($form['#attributes']['id'] ?? '');

    if (is_array($form_id)) {
      $form_id = reset($form_id);
    }

    return str_replace('_', '-', (string) $form_id);
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
