/**
 * @file
 * Contains utility functions for Solo module multi-select searchable dropdowns.
 *
 * Filename: selectify-multi-select.js
 * Website: https://www.flashwebcenter.com
 * Developer: Alaa Haddad https://www.alaahaddad.com.
 */
((Drupal, once) => {
  'use strict';

  const type = 'tags';

  /**
   * Drupal behavior for custom multi-select tags dropdowns.
   */
  Drupal.behaviors.selectifySelectTags = {
    attach: (context) => {
      once('selectifyMultiSelectTags', '.selectify-dropdown-tags-widget', context).forEach((selectifySelect) => {
        setTimeout(() => {
          // Retrieve the target ID from the data attribute.
          const targetId = selectifySelect.getAttribute('data-target-id');
          if (!targetId) {
            console.error('Selectify: Missing data-target-id on dropdown element', selectifySelect);
            return;
          }

          // Locate the corresponding hidden <select> element by its ID.
          const nativeSelect = document.getElementById(targetId);
          if (!nativeSelect || nativeSelect.tagName !== 'SELECT') {
            console.error(`Selectify: No matching <select> found for data-target-id="${targetId}"`, selectifySelect);
            return;
          }

          initializeSelectifyTags(selectifySelect, nativeSelect);

          let maxSelections = selectifySelect.getAttribute('data-max-selections');
          maxSelections = maxSelections === 'null' ? null : parseInt(maxSelections, 10);

          Drupal.selectify.handleSelectionLimit(
            type,
            selectifySelect,
            nativeSelect,
            maxSelections,
            '.selectify-available-one-option'
          );

          const dropdownMenu = selectifySelect.querySelector('.selectify-available-display');
          if (dropdownMenu) {
            // Generate a unique ID for the dropdown if not already set.
            const dropdownId = `${targetId}-dropdown`;
            dropdownMenu.setAttribute('id', dropdownId);

            // Set aria-controls on selectifySelect
            selectifySelect.setAttribute('aria-controls', dropdownId);
          }
        }, 10);

      });
    }
  };

  /**
   * Initializes the tags multi-select dropdown.
   *
   * @param {HTMLElement} selectifySelect
   *   The Selectify wrapper element.
   * @param {HTMLElement} nativeSelect
   *   The hidden native <select> element.
   */
  function initializeSelectifyTags(selectifySelect, nativeSelect) {
    const selectedDisplay = selectifySelect.querySelector('.selectify-selected-display');
    const dropdownMenu = selectifySelect.querySelector('.selectify-available-display');
    const options = selectifySelect.querySelectorAll('.selectify-available-one-option');
    const clearAllButton = selectifySelect.querySelector('.selectify-clear-all');

    if (!nativeSelect || nativeSelect.tagName !== 'SELECT') {
      console.error('Selectify: Could not find the corresponding hidden <select> for', selectifySelect);
      return;
    }

    if (!selectedDisplay || !dropdownMenu || options.length === 0) {
      return;
    }

    // Toggle dropdown open/close.
    selectedDisplay.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();
      if (dropdownMenu.classList.contains('toggled')) {
        Drupal.selectify.closeDropdown(dropdownMenu, selectedDisplay);
      } else {
        Drupal.selectify.adjustDropdownHeight(selectifySelect, dropdownMenu);
        Drupal.selectify.openDropdown(dropdownMenu, selectedDisplay);
      }
    });

    // Handle option selection.
    once('selectifyTagsEvents', dropdownMenu).forEach(() => {
      dropdownMenu.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();
        if (event.target.classList.contains('selectify-available-one-option')) {
          Drupal.selectify.toggleMultiSelectOption(type, nativeSelect, event.target, selectedDisplay, clearAllButton);
        }
      });
    });

    // Handle "Clear All" functionality.
    once('clearAllTags', clearAllButton).forEach(() => {
      clearAllButton.addEventListener('click', () => {
        // Unselect all options in the hidden <select> element.
        nativeSelect.querySelectorAll('option').forEach(option => (option.selected = false));

        // Remove all selected items from the display.
        const multiTagContainer = selectedDisplay.querySelector('.selectify-selected-options');
        if (multiTagContainer) {
          multiTagContainer.innerHTML = ''; // Clear selected items but keep the structure.
        }

        // Ensure dropdown items are fully reset
        dropdownMenu.querySelectorAll('.selectify-available-one-option').forEach(option => {
          option.classList.remove('selected', 'hidden'); // Unhide and unselect
        });

        // Sync the hidden select field.
        Drupal.selectify.syncHiddenSelect(nativeSelect, []);

        // Update the UI to reflect the cleared selections.
        Drupal.selectify.updateSelectedDisplay(type, nativeSelect, selectedDisplay, clearAllButton);

        // Ensure changes are registered.
        setTimeout(() => {
          nativeSelect.dispatchEvent(new Event('change', { bubbles: true }));
          nativeSelect.dispatchEvent(new Event('input', { bubbles: true }));
        }, 0);
      });
    });

    // Close dropdown when clicking outside.
    document.addEventListener('click', (event) => {
      if (!selectifySelect.contains(event.target)) {
        Drupal.selectify.closeDropdown(dropdownMenu, selectedDisplay);
      }
    });

    // Enable keyboard navigation.
    Drupal.selectify.handleKeyboardNavigation(selectifySelect, dropdownMenu, selectedDisplay);

    // Ensure UI syncs with the hidden select field on load.
    syncUIWithHiddenSelect(nativeSelect, options, selectedDisplay, clearAllButton);

    // Initialize UI with existing selections.
    Drupal.selectify.updateSelectedDisplay(type, nativeSelect, selectedDisplay, clearAllButton);
  }

  /**
   * Ensures UI reflects the hidden select field at page load.
   *
   * @param {HTMLElement} nativeSelect
   *   The hidden <select> element.
   * @param {NodeList} options
   *   The list of available options in the UI.
   * @param {HTMLElement} selectedDisplay
   *   The display area for selected options.
   * @param {HTMLElement} clearAllButton
   *   The button to clear all selected options.
   */
  function syncUIWithHiddenSelect(nativeSelect, options, selectedDisplay, clearAllButton) {
    const selectedValues = Array.from(nativeSelect.options)
      .filter(option => option.selected)
      .map(option => option.value);

    selectedValues.forEach(value => {
      const correspondingOption = Array.from(options).find(opt => opt.dataset.value === value);
      if (correspondingOption) {
        correspondingOption.classList.add('hidden');
      }
    });

    Drupal.selectify.updateSelectedDisplay(type, nativeSelect, selectedDisplay, clearAllButton);
  }

})(Drupal, once);
