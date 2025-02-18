/**
 * @file
 * Contains utility functions for Solo module multi-select.
 *
 * Filename: selectify-multi-select.js
 * Website: https://www.flashwebcenter.com
 * Developer: Alaa Haddad https://www.alaahaddad.com.
 */
((Drupal, once) => {
  'use strict';

  const type = 'checkbox';

  Drupal.behaviors.selectifySelectCheckbox = {
    attach: (context) => {
      once('selectifyMultiSelectCheckbox', '.selectify-select.checkbox', context).forEach((selectifySelect) => {
        setTimeout(() => {
        // Retrieve the target ID from data attribute.
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

        initializeSelectifyCheckbox(selectifySelect, nativeSelect);

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
      console.error('Selectify: Could not find the corresponding hidden <select> for', selectifySelect);
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

        if (correspondingOption) {
          correspondingOption.selected = event.target.checked;
        }

        Drupal.selectify.syncHiddenSelect(nativeSelect, Drupal.selectify.getSelectedValues(nativeSelect));
        Drupal.selectify.updateSelectedDisplay(type, nativeSelect, selectedDisplay, clearAllButton);
        nativeSelect.dispatchEvent(new Event('change'));
      }
    });

    // Handle "Clear All" functionality.
    if (clearAllButton) {
      clearAllButton.addEventListener('click', () => {
        nativeSelect.querySelectorAll('option').forEach(option => option.selected = false);
        options.forEach(opt => opt.checked = false);
        Drupal.selectify.syncHiddenSelect(nativeSelect, []);
        Drupal.selectify.updateSelectedDisplay(type, nativeSelect, selectedDisplay, clearAllButton);
        nativeSelect.dispatchEvent(new Event('change'));
      });
    }

    // Close dropdown when clicking outside.
    document.addEventListener('click', (event) => {
      if (!selectifySelect.contains(event.target)) {
        Drupal.selectify.closeDropdown(dropdownMenu, selectedDisplay);
      }
    });

    // Enable keyboard navigation.
    Drupal.selectify.handleKeyboardNavigation(selectifySelect, dropdownMenu, selectedDisplay);

    // Sync UI state with the hidden <select> element.
    syncUIWithHiddenSelect(nativeSelect, options);
    Drupal.selectify.updateSelectedDisplay(type, nativeSelect, selectedDisplay, clearAllButton);
  }

  /**
   * Syncs UI checkbox states with the hidden <select> element values.
   *
   * @param {HTMLElement} nativeSelect
   *   The hidden <select> element.
   * @param {NodeList} checkboxes
   *   The list of checkbox elements in the UI.
   */
  function syncUIWithHiddenSelect(nativeSelect, checkboxes) {
    const selectedValues = Drupal.selectify.getSelectedValues(nativeSelect);
    checkboxes.forEach(checkbox => {
      checkbox.checked = selectedValues.includes(checkbox.value);
    });
  }

})(Drupal, once);
