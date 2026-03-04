/**
 * @file
 * Contains utility functions for Selectify module multi-select.
 *
 * Filename: selectify-dropdown-checkbod.js
 * Website: https://www.flashwebcenter.com
 * Developer: Alaa Haddad https://www.alaahaddad.com.
 */
((Drupal, once, drupalSettings) => {
  'use strict';
  const type = 'checkbox';
  Drupal.behaviors.selectifySelectCheckbox = {
    attach: (context) => {
      once('selectifyMultiSelectCheckbox', '.selectify-dropdown-checkbox-widget', context).forEach((selectifySelect) => {
        const constants = Drupal.selectify?.constants || {};
        const initDelay = constants.INIT_DELAY || 10;
        setTimeout(() => {
          Drupal.selectify.bootstrapWidget(selectifySelect, 'Checkbox', type, {
            initFn: (sel, native) => initializeSelectifyCheckbox(sel, native),
            dropdownPanelSelector: '.selectify-available-display',
          });
        }, initDelay);
      });
    }
  };
  /**
   * Initializes the checkbox-based Selectify component.
   *
   * @param {HTMLElement} selectifySelect
   *   The Selectify wrapper element.
   * @param {HTMLElement} nativeSelect
   *   The hidden native <select> element.
   */
  function initializeSelectifyCheckbox(selectifySelect, nativeSelect) {
    const selectedDisplay = selectifySelect.querySelector('.selectify-selected-display');
    const dropdownMenu = selectifySelect.querySelector('.selectify-available-display');
    const options = selectifySelect.querySelectorAll('.selectify-available-one-option input[type="checkbox"]');
    const clearAllButton = selectifySelect.querySelector('.selectify-clear-all');
    if (!nativeSelect || nativeSelect.tagName !== 'SELECT') {

      return;
    }
    if (!selectedDisplay || !dropdownMenu || options.length === 0) {
      return;
    }
    // Handle dropdown toggle on click.
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
    // Handle checkbox selection within the dropdown.
    dropdownMenu.addEventListener('change', (event) => {
      if (event.target.matches('.selectify-available-one-option input[type="checkbox"]')) {
        const optionValue = event.target.value;
        const correspondingOption = nativeSelect.querySelector(`option[value="${optionValue}"]`);
        const parentOption = event.target.closest('.selectify-available-one-option');

        // Update aria-activedescendant
        if (parentOption && parentOption.id) {
          selectifySelect.setAttribute('aria-activedescendant', parentOption.id);
        }

        if (correspondingOption) {
          correspondingOption.selected = event.target.checked;
        }
        // **Add or remove .s-selected and .s-hidden on the parent div**
        if (parentOption) {
          if (event.target.checked) {
            parentOption.classList.add('s-selected');
          } else {
            parentOption.classList.remove('s-selected');
          }
        }
        Drupal.selectify.syncHiddenSelect(nativeSelect, Drupal.selectify.getSelectedValues(nativeSelect));
        Drupal.selectify.updateSelectedDisplay(type, nativeSelect, selectedDisplay, clearAllButton);
        nativeSelect.dispatchEvent(new Event('change'));
      }
    });
    // Handle "Clear All" functionality.
    if (clearAllButton) {
      once('clearAllCheckbox', clearAllButton).forEach(() => {
        clearAllButton.addEventListener('click', () => {
          Drupal.selectify.executeClear(type, selectifySelect, nativeSelect, selectedDisplay, clearAllButton, dropdownMenu, {
            onDeselectAll: (menu) => {
              options.forEach((opt) => {
                opt.checked = false;
                opt.disabled = false;
              });
              options.forEach((opt) => {
                const parentOption = opt.closest('.selectify-available-one-option');
                if (parentOption) parentOption.classList.remove('s-selected');
              });
            },
            focusTarget: () => selectedDisplay,
          });
        });
      });
    }
    // Enable keyboard navigation.
    Drupal.selectify.handleKeyboardNavigation(selectifySelect, dropdownMenu, selectedDisplay, type);
    Drupal.selectify.updateSelectedDisplay(type, nativeSelect, selectedDisplay, clearAllButton);
    const selectedValues = Drupal.selectify.getSelectedValues(nativeSelect);
    options.forEach(checkbox => {
      checkbox.checked = selectedValues.includes(checkbox.value);
    });
  }
})(Drupal, once, drupalSettings);
