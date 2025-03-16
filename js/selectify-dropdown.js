/**
 * @file
 * Contains utility functions for Selectify module multi-select dropdowns.
 *
 * Filename: selectify-dropdown.js
 * Website: https://www.flashwebcenter.com
 * Developer: Alaa Haddad https://www.alaahaddad.com.
 */
((Drupal, once) => {
  'use strict';
  const type = 'dropdown';
  /**
   * Drupal behavior for custom multi-select dropdowns.
   */
  Drupal.behaviors.selectifySelectDropdown = {
    attach: (context) => {
      once('selectifyMultiSelectDropdown', '.wrapper-selectify .selectify-dropdown-widget', context).forEach((selectifySelect) => {
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
            ;
            return;
          }
          initializeSelectifyDropdown(selectifySelect, nativeSelect);
          let maxSelections = selectifySelect.getAttribute('data-max-selections');
          maxSelections = maxSelections === 'null' ? null : parseInt(maxSelections, 10);
          Drupal.selectify.handleSelectionLimit(
            type
            , selectifySelect
            , nativeSelect
            , maxSelections
            , '.selectify-available-one-option'
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
   * Initializes the dropdown multi-select.
   *
   * @param {HTMLElement} selectifySelect
   *   The Selectify wrapper element.
   * @param {HTMLElement} nativeSelect
   *   The hidden native <select> element.
   */
  function initializeSelectifyDropdown(selectifySelect, nativeSelect) {
    const selectedDisplay = selectifySelect.querySelector('.selectify-selected-display');
    const dropdownMenu = selectifySelect.querySelector('.selectify-available-display');
    const options = selectifySelect.querySelectorAll('.selectify-available-one-option');
    const clearAllButton = selectifySelect.querySelector('.selectify-clear-all');
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
    once('selectifyDropdownEvents', dropdownMenu).forEach(() => {
      dropdownMenu.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();
        const optionElement = event.target.closest('.selectify-available-one-option');
        if (optionElement) {
          Drupal.selectify.toggleMultiSelectOption(type, nativeSelect, optionElement, selectedDisplay, clearAllButton);
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
          option.classList.remove('s-selected', 's-hidden'); // Unhide and unselect
        });
        // Sync the hidden select field.
        Drupal.selectify.syncHiddenSelect(nativeSelect, []);
        // Update the UI to reflect the cleared selections.
        Drupal.selectify.updateSelectedDisplay(type, nativeSelect, selectedDisplay, clearAllButton);
        // Ensure changes are registered.
        setTimeout(() => {
          nativeSelect.dispatchEvent(new Event('change', {
            bubbles: true
          }));
          nativeSelect.dispatchEvent(new Event('input', {
            bubbles: true
          }));
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
    Drupal.selectify.handleKeyboardNavigation(selectifySelect, dropdownMenu, selectedDisplay, type);
    // After basic setup, always sync visible state to hidden select.
    const selectedValues = Drupal.selectify.getSelectedValues(nativeSelect);
    Drupal.selectify.syncHiddenSelect(nativeSelect, selectedValues);
    Drupal.selectify.updateSelectedDisplay(type, nativeSelect, selectedDisplay, clearAllButton);
  }
})(Drupal, once);
