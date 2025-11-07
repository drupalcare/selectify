/**
 * @file
 * Selectify settings.
 *
 * Filename:     selectify-settings.js
 * Website:      https://www.flashwebcenter.com
 * Developer:    Alaa Haddad https://www.alaahaddad.com.
 */

(function (Drupal) {
  Drupal.behaviors.selectifyAccentTooltip = {
    attach: function (context) {
      document.querySelectorAll('form.selectify-settings-form #edit-accent-color--wrapper input.form-radio').forEach((input) => {
        if (!input.dataset.processed) {
          input.dataset.processed = true; // Prevent multiple executions

          const accent = input.getAttribute("data-accent"); // Get accent color name
          const label = document.querySelector(`label[for="${input.id}"]`);

          if (accent && label) {
            // Create tooltip element
            const tooltip = document.createElement("span");
            tooltip.className = "accent-tooltip";
            tooltip.textContent = accent;

            // Insert tooltip **after** the label
            label.parentNode.insertBefore(tooltip, label.nextSibling);

            // Show tooltip on hover
            label.addEventListener("mouseenter", () => {
              tooltip.style.opacity = "1";
              tooltip.style.visibility = "visible";
            });

            label.addEventListener("mouseleave", () => {
              tooltip.style.opacity = "0";
              tooltip.style.visibility = "hidden";
            });
          }
        }
      });
    },
  };
})(Drupal);
