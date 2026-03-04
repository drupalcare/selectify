/**
 * @file
 * Contains utility functions for Selectify module multi-select dropdowns.
 *
 * Filename: selectify-dropdown.js
 * Website: https://www.flashwebcenter.com
 * Developer: Alaa Haddad https://www.alaahaddad.com.
 */
((Drupal, once, drupalSettings) => {
  'use strict';
  const type = 'dropdown';
  /**
   * Drupal behavior for custom multi-select dropdowns.
   */
  Drupal.behaviors.selectifySelectDropdown = {
    attach: (context) => {
      once('selectifyMultiSelectDropdown', '.wrapper-selectify .selectify-dropdown-widget', context).forEach((selectifySelect) => {
        const constants = Drupal.selectify?.constants || {};
        const initDelay = constants.INIT_DELAY || 10;
        setTimeout(() => {
          Drupal.selectify.bootstrapWidget(selectifySelect, 'Dropdown', type, {
            initFn: (sel, native) => initializeSelectifyDropdown(sel, native),
            dropdownPanelSelector: '.selectify-available-display',
          });
        }, initDelay);
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
    if (clearAllButton) {
      once('clearAllDropdown', clearAllButton).forEach(() => {
        clearAllButton.addEventListener('click', () => {
          Drupal.selectify.executeClear(type, selectifySelect, nativeSelect, selectedDisplay, clearAllButton, dropdownMenu, {
            onDeselectAll: (menu, selDisplay) => {
              const container = selDisplay.querySelector('.selectify-selected-options');
              if (container) container.innerHTML = '';
              menu.querySelectorAll('.selectify-available-one-option').forEach((opt) => opt.classList.remove('s-selected', 's-hidden'));
            },
            focusTarget: () => selectedDisplay,
          });
        });
      });
    }
    // Enable keyboard navigation.
    Drupal.selectify.handleKeyboardNavigation(selectifySelect, dropdownMenu, selectedDisplay, type);
    // After basic setup, always sync visible state to hidden select.
    const selectedValues = Drupal.selectify.getSelectedValues(nativeSelect);
    Drupal.selectify.syncHiddenSelect(nativeSelect, selectedValues);
    Drupal.selectify.updateSelectedDisplay(type, nativeSelect, selectedDisplay, clearAllButton);
  }
})(Drupal, once, drupalSettings);
