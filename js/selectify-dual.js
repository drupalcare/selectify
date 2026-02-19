/**
 * @file
 * Contains utility functions for Selectify module multi-select dual list.
 *
 * Filename: selectify-dual.js
 * Website: https://www.flashwebcenter.com
 * Developer: Alaa Haddad https://www.alaahaddad.com.
 */
((Drupal, once, drupalSettings) => {
  'use strict';
  const type = 'dual';
  /**
   * Drupal behavior for custom multi-select dual lists.
   */
  Drupal.behaviors.selectifySelectDual = {
    attach: (context) => {
      once('selectifyMultiSelectDual', '.selectify-select-dual-list', context).forEach((selectifySelect) => {
        const constants = Drupal.selectify?.constants || {};
        const initDelay = constants.INIT_DELAY || 10;
        setTimeout(() => {
          // Retrieve the target ID from the data attribute.
          const targetId = selectifySelect.getAttribute('data-target-id');
          if (!targetId) {
            if (drupalSettings?.selectify?.debug) {
              console.warn('[Selectify] Dual widget missing data-target-id attribute');
            }
            return;
          }
          // Locate the corresponding hidden <select> element by its ID.
          const nativeSelect = document.getElementById(targetId);
          if (!nativeSelect || nativeSelect.tagName !== 'SELECT') {
            if (drupalSettings?.selectify?.debug) {
              console.warn('[Selectify] Native select element not found:', targetId);
            }
            return;
          }
          initializeSelectifyDual(selectifySelect, nativeSelect);
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
          Drupal.selectify.injectAriaLabelledBy();
        }, initDelay);
      });
    }
  };
  /**
   * Initializes the dual list multi-select.
   *
   * @param {HTMLElement} selectifySelect - The Selectify wrapper element.
   * @param {HTMLElement} nativeSelect - The hidden native <select> element.
   */
  function initializeSelectifyDual(selectifySelect, nativeSelect) {
    const selectedDisplay = selectifySelect.querySelector('.dual-selected');
    const dropdownMenu = selectifySelect.querySelector('.dual-available');
    const options = selectifySelect.querySelectorAll('.dual-available-option');
    const clearAllButton = selectifySelect.querySelector('.dual-clear-all');
    if (!nativeSelect || nativeSelect.tagName !== 'SELECT') {

      return;
    }
    if (!selectedDisplay || !dropdownMenu || options.length === 0) {
      return;
    }
    // Click event to select an item.
    once('selectifyDualEvents', dropdownMenu).forEach(() => {
      dropdownMenu.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();
        if (event.target.classList.contains('selectify-available-one-option')) {
          Drupal.selectify.toggleMultiSelectOption(type, nativeSelect, event.target, selectedDisplay, clearAllButton);
        }
      });
    });
    // Click event to remove selected items.
    selectedDisplay.addEventListener('click', (event) => {
      if (event.target.classList.contains('selectify-selected-one-option')) {
        event.preventDefault();
        event.stopPropagation();

        // Check if field is required and single-value
        const isRequired = nativeSelect.hasAttribute('required');
        const isMultiple = nativeSelect.getAttribute('data-multiple') === 'true';
        const selectedCount = selectedDisplay.querySelectorAll('.selectify-selected-one-option').length;

        // For required single-value fields, prevent removing the only selected item
        if (isRequired && !isMultiple && selectedCount === 1) {
          return; // Don't allow removal
        }

        const selectedItem = event.target;
        const valueToRemove = selectedItem.getAttribute('data-value');
        selectedItem.remove();
        const correspondingOption = dropdownMenu.querySelector(`.selectify-available-one-option[data-value="${valueToRemove}"]`);
        if (correspondingOption) {
          correspondingOption.classList.remove('s-hidden', 's-selected');
        }
        const selectOption = nativeSelect.querySelector(`option[value="${valueToRemove}"]`);
        if (selectOption) {
          selectOption.selected = false;
        }
        // Collect all current selected values after the removal.
        const selectedValues = [...selectedDisplay.querySelectorAll('.selectify-selected-one-option')].map(item => item.getAttribute('data-value'));
        Drupal.selectify.syncHiddenSelect(nativeSelect, selectedValues);
        Drupal.selectify.updateSelectedDisplay(type, nativeSelect, selectedDisplay, clearAllButton);
        nativeSelect.dispatchEvent(new Event('change'));
      }
    });
    // "Clear All" Button Logic.
    if (clearAllButton) {
      once('clearAllDual', clearAllButton).forEach(() => {
        clearAllButton.addEventListener('click', () => {
          nativeSelect.querySelectorAll('option').forEach(option => {
            option.selected = false;
          });
          selectedDisplay.querySelectorAll('.selectify-selected-one-option').forEach(item => item.remove());
          dropdownMenu.querySelectorAll('.selectify-available-one-option.s-selected').forEach(option => {
            option.classList.remove('s-selected');
          });
          dropdownMenu.querySelectorAll('.selectify-available-one-option.s-hidden').forEach(option => {
            option.classList.remove('s-hidden');
          });
          Drupal.selectify.syncHiddenSelect(nativeSelect, []);
          Drupal.selectify.updateSelectedDisplay(type, nativeSelect, selectedDisplay, clearAllButton);
          setTimeout(() => {
            nativeSelect.dispatchEvent(new Event('change', { bubbles: true }));
            nativeSelect.dispatchEvent(new Event('input', { bubbles: true }));
          }, 0);

          // Return focus to the dual available list
          const availableList = selectifySelect.querySelector('.dual-available');
          if (availableList && typeof availableList.focus === 'function') {
            const constants = Drupal.selectify?.constants || {};
            const focusDelay = constants.FOCUS_DELAY || 100;
            setTimeout(() => availableList.focus(), focusDelay);
          }
        });
      });
    }
    // Enable keyboard navigation.
    Drupal.selectify.handleKeyboardNavigation(selectifySelect, dropdownMenu, selectedDisplay, type);
    const selectedValues = Drupal.selectify.getSelectedValues(nativeSelect);
    Drupal.selectify.syncHiddenSelect(nativeSelect, selectedValues);
    Drupal.selectify.updateSelectedDisplay(type, nativeSelect, selectedDisplay, clearAllButton);
  }
})(Drupal, once, drupalSettings);
