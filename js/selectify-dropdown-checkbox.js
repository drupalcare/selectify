/**
 * @file
 * Contains utility functions for Selectify module multi-select.
 *
 * Filename: selectify-dropdown-checkbox.js
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
        // The row's look and its announced state follow the checkbox.
        setRowState(parentOption, event.target.checked);
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
                setRowState(opt.closest('.selectify-available-one-option'), false);
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
      setRowState(checkbox.closest('.selectify-available-one-option'), checkbox.checked);
    });
  }

  /**
   * Keeps an option row's class and aria-selected in step with its checkbox.
   *
   * The server renders aria-selected="true" on saved values and the row
   * color keys on it, so clearing only the class left the row colored.
   *
   * @param {HTMLElement|null} row
   *   The .selectify-available-one-option element.
   * @param {boolean} selected
   *   Whether the option is selected.
   */
  function setRowState(row, selected) {
    // The "_none" placeholder row is role="presentation"; it is not an
    // option, so it must never carry aria-selected.
    if (!row || row.getAttribute('role') === 'presentation') {
      return;
    }
    row.classList.toggle('s-selected', selected);
    row.setAttribute('aria-selected', selected ? 'true' : 'false');
  }
})(Drupal, once, drupalSettings);
