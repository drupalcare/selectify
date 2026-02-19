/**
 * @file
 * Constants used across Selectify JavaScript files.
 *
 * Centralizing timing delays and other magic numbers makes the code more
 * maintainable and allows easy adjustment without searching through multiple
 * files.
 *
 * Filename: selectify-constants.js
 * Website: https://www.flashwebcenter.com
 * Developer: Alaa Haddad https://www.alaahaddad.com.
 */
((Drupal) => {
  'use strict';

  /**
   * Selectify timing constants.
   *
   * @namespace
   */
  Drupal.selectify = Drupal.selectify || {};
  Drupal.selectify.constants = {
    // Initialization delay to ensure DOM is ready after Drupal.behaviors.attach
    INIT_DELAY: 10,

    // Focus delay after dropdown operations (allows animation to complete)
    FOCUS_DELAY: 100,

    // Search input debounce delay (prevents excessive filtering)
    SEARCH_DEBOUNCE: 150,

    // Animation duration for dropdown open/close
    ANIMATION_DURATION: 300,

    // Screen reader announcement debounce delay
    ANNOUNCEMENT_DEBOUNCE: 500,

    // Auto-dismiss delay for selection limit messages
    MESSAGE_AUTO_DISMISS: 3000,
  };
})(Drupal);
