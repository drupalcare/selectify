/**
 * @file
 * Contains utility functions for Selectify module.
 *
 * Filename: selectify-radio-checkbox.js
 * Website: https://www.flashwebcenter.com
 * Developer: Alaa Haddad https://www.alaahaddad.com.
 */

((Drupal, once) => {
  'use strict';

  /**
   * Drupal behavior to enhance checkboxes and radio buttons.
   */
  Drupal.behaviors.selectifyRadioCheckbox = {
    attach: (context, settings) => {
      const checkboxes = once('selectifyCheckbox', 'input[type="checkbox"].selectify-checkbox', context);
      const radios = once('selectifyRadio', 'input[type="radio"].selectify-radio', context);

      // Initialize checkboxes
      checkboxes.forEach((checkbox) => {
        detectMultiSelect(checkbox);
        updateCheckedState(checkbox);
        updateDisabledState(checkbox);
        updateIndeterminateState(checkbox);

        checkbox.addEventListener('change', () => {
          updateCheckedState(checkbox);
          updateIndeterminateState(checkbox);
        });

        addFocusAndHoverListeners(checkbox);
      });

      // Initialize radio buttons
      radios.forEach((radio) => {
        detectRadioGroup(radio);
        updateCheckedState(radio);
        updateDisabledState(radio);

        radio.addEventListener('change', () => {
          updateRadioGroupState(radio);
        });

        addFocusAndHoverListeners(radio);
        enableToggleableRadio(radio); // Optional: Allow deselecting radios
      });

      // Inject ARIA labels if needed
      Drupal.selectify.injectAriaLabelledBy();
    }
  };

  /**
   * Detects whether a checkbox allows multiple selections.
   * Adds a class to indicate if it's part of a group.
   *
   * @param {HTMLInputElement} checkbox
   *   The checkbox element to analyze.
   */
  function detectMultiSelect(checkbox) {
    const name = checkbox.name;
    if (name) {
      const checkboxesInGroup = document.querySelectorAll(`input[type="checkbox"][name="${name}"]`);
      if (checkboxesInGroup.length > 1) {
        checkbox.classList.add('selectify-multi-checkbox');
      } else {
        checkbox.classList.add('selectify-single-checkbox');
      }
    }
  }

  /**
   * Detects and marks radio buttons in groups.
   *
   * @param {HTMLInputElement} radio
   *   The radio button element to analyze.
   */
  function detectRadioGroup(radio) {
    const name = radio.name;
    if (name) {
      const radiosInGroup = document.querySelectorAll(`input[type="radio"][name="${name}"]`);
      if (radiosInGroup.length > 1) {
        radio.classList.add('selectify-radio-group');
      }
    }
  }

  /**
   * Ensures only the selected radio in a group has the active class.
   *
   * @param {HTMLInputElement} selectedRadio
   *   The radio button that was selected.
   */
  function updateRadioGroupState(selectedRadio) {
    const name = selectedRadio.name;
    const radiosInGroup = document.querySelectorAll(`input[type="radio"][name="${name}"]`);
    radiosInGroup.forEach((radio) => {
      updateCheckedState(radio);
    });
  }

  /**
   * Updates the checked state class for checkboxes and radios.
   *
   * @param {HTMLInputElement} element
   *   The input element to update.
   */
  function updateCheckedState(element) {
    if (element.checked) {
      element.classList.add('selectify-checked');
    } else {
      element.classList.remove('selectify-checked');
    }
  }

  /**
   * Adds or removes disabled state class.
   *
   * @param {HTMLInputElement} element
   *   The input element to update.
   */
  function updateDisabledState(element) {
    if (element.disabled) {
      element.classList.add('selectify-disabled');
    } else {
      element.classList.remove('selectify-disabled');
    }
  }

  /**
   * Handles indeterminate checkboxes.
   *
   * @param {HTMLInputElement} checkbox
   *   The checkbox element to update.
   */
  function updateIndeterminateState(checkbox) {
    if (checkbox.indeterminate) {
      checkbox.classList.add('selectify-indeterminate');
    } else {
      checkbox.classList.remove('selectify-indeterminate');
    }
  }

  /**
   * Adds event listeners for focus and hover states.
   *
   * @param {HTMLInputElement} element
   *   The input element to enhance.
   */
  function addFocusAndHoverListeners(element) {
    element.addEventListener('focus', () => {
      element.classList.add('selectify-focused');
    });

    element.addEventListener('blur', () => {
      element.classList.remove('selectify-focused');
    });

    element.addEventListener('mouseover', () => {
      element.classList.add('selectify-hover');
    });

    element.addEventListener('mouseout', () => {
      element.classList.remove('selectify-hover');
    });
  }

  /**
   * Enables toggleable radio buttons (optional feature).
   * Allows users to deselect a radio button by clicking it again.
   *
   * @param {HTMLInputElement} radio
   *   The radio button to make toggleable.
   */
  function enableToggleableRadio(radio) {
    radio.addEventListener('click', function(event) {
      const name = this.name;
      const radiosInGroup = document.querySelectorAll(`input[type="radio"][name="${name}"]`);

      // If the clicked radio was already checked, allow deselection
      if (this.checked && this.dataset.wasChecked === "true") {
        this.checked = false;
        this.dataset.wasChecked = "false";

        // Ensure other radios in the group can now be selected properly
        radiosInGroup.forEach(radio => radio.dataset.wasChecked = "false");

        // Update visual state
        updateCheckedState(this);
      } else {
        // Reset all radios in the group before setting new selection
        radiosInGroup.forEach(radio => {
          radio.dataset.wasChecked = "false";
          updateCheckedState(radio);
        });

        // Mark the clicked radio as selected
        this.dataset.wasChecked = "true";
        updateCheckedState(this);
      }
    });
  }

})(Drupal, once);
