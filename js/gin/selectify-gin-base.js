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

      // Watch for color scheme changes — only create one observer.
      once('selectifyGinObserver', 'html', context).forEach(function(html) {
        const observer = new MutationObserver(function(mutations) {
          mutations.forEach(function(mutation) {
            if (mutation.attributeName === 'data-gin-color-scheme') {
              const newScheme = document.documentElement.getAttribute('data-gin-color-scheme');

              document.dispatchEvent(new CustomEvent('selectifyGinColorSchemeChange', {
                detail: {
                  colorScheme: newScheme,
                  accentColor: ginAccentColor
                }
              }));
            }
          });
        });

        observer.observe(document.documentElement, {
          attributes: true,
          attributeFilter: ['data-gin-color-scheme']
        });

        // Store reference for cleanup on detach.
        html._selectifyGinObserver = observer;
      });

      // Add body class based on initial color scheme
      once('selectifyGinScheme', 'body', context).forEach(function(body) {
        body.classList.add('selectify-gin-' + ginColorScheme);
        if (ginAccentColor) {
          body.classList.add('selectify-gin-accent-' + ginAccentColor);
        }
      });
    },

    detach: function (context, settings, trigger) {
      if (trigger !== 'unload') {
        return;
      }
      const html = document.documentElement;
      if (html._selectifyGinObserver) {
        html._selectifyGinObserver.disconnect();
        html._selectifyGinObserver = null;
      }
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
  });

})(Drupal, drupalSettings, once);
