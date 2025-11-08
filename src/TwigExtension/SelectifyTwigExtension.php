<?php

declare(strict_types=1);

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
   * @param string|null $string
   *   The string to clean. Can be null for optional form values.
   *
   * @return string
   *   The cleaned ID, or empty string if null.
   */
  public function cleanId(?string $string): string {
    if ($string === NULL || $string === '') {
      return '';
    }

    $cleaned = preg_replace('/[^a-zA-Z0-9_-]/', '-', $string);

    return $cleaned ?? '';
  }

}
