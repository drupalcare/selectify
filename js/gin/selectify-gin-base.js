/**
 * @file
 * Selectify integration with Gin admin theme.
 */

(function (Drupal, drupalSettings, once) {
  'use strict';

  /**
   * Detect Gin color scheme changes.
   */
  Drupal.behaviors.selectifyGinIntegration = {
    attach: function (context, settings) {
      if (!settings.selectify || !settings.selectify.gin) {
        return;
      }

      const ginColorScheme = settings.selectify.gin.colorScheme;
      const ginAccentColor = settings.selectify.gin.accentColor;

      // Watch for color scheme changes (Gin uses MutationObserver)
      const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
          if (mutation.attributeName === 'data-gin-color-scheme') {
            const newScheme = document.documentElement.getAttribute('data-gin-color-scheme');

            // Dispatch custom event for color scheme change
            document.dispatchEvent(new CustomEvent('selectifyGinColorSchemeChange', {
              detail: {
                colorScheme: newScheme,
                accentColor: ginAccentColor
              }
            }));
          }
        });
      });

      // Start observing the html element for attribute changes
      observer.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['data-gin-color-scheme']
      });

      // Add body class based on initial color scheme
      once('selectifyGinScheme', 'body', context).forEach(function(body) {
        body.classList.add('selectify-gin-' + ginColorScheme);
        if (ginAccentColor) {
          body.classList.add('selectify-gin-accent-' + ginAccentColor);
        }
      });
    }
  };

  /**
   * Listen for Gin color scheme changes.
   */
  document.addEventListener('selectifyGinColorSchemeChange', function(event) {
    const newScheme = event.detail.colorScheme;
    const accentColor = event.detail.accentColor;

    // Update body classes
    document.body.classList.remove('selectify-gin-dark', 'selectify-gin-light', 'selectify-gin-auto');
    document.body.classList.add('selectify-gin-' + newScheme);

    // Optional: Trigger any necessary re-renders or updates
    console.log('Selectify adjusted for Gin ' + newScheme + ' mode');
  });

})(Drupal, drupalSettings, once);
