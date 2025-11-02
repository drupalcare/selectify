<?php

namespace Drupal\selectify\TwigExtension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Provides Twig extensions for Selectify.
 */
class SelectifyTwigExtension extends AbstractExtension {

  /**
   * {@inheritdoc}
   */
  public function getFilters(): array {
    return [
      new TwigFilter('selectify_clean_id', [$this, 'cleanId']),
    ];
  }

  /**
   * Sanitizes a string to be used as a valid HTML ID.
   *
   * @param string $string
   *   The string to clean.
   *
   * @return string
   *   The cleaned ID.
   */
  public function cleanId(string $string): string {
    // Simply replace invalid characters, don't use Html::cleanCssIdentifier
    // because it adds underscores to numbers which we don't want.
    return preg_replace('/[^a-zA-Z0-9_-]/', '-', $string);
  }

}
