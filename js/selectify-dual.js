/**
 * @file
 * Contains utility functions for Solo module multi-select dual list.
 *
 * Filename: selectify-multi-select.js
 * Website: https://www.flashwebcenter.com
 * Developer: Alaa Haddad https://www.alaahaddad.com.
 */

((Drupal, once) => {
  'use strict';

  const type = 'dual';

  /**
   * Drupal behavior for custom multi-select dual lists.
   */
  Drupal.behaviors.selectifySelectDual = {
    attach: (context) => {
      once('selectifyMultiSelectDual', '.selectify-select-dual-list', context).forEach((selectifySelect) => {
        setTimeout(() => {
          // Retrieve the target ID from the data attribute.
          const targetId = selectifySelect.getAttribute('data-target-id');
          if (!targetId) {
            console.error('Selectify: Missing data-target-id on dual list element', selectifySelect);
            return;
          }

          // Locate the corresponding hidden <select> element by its ID.
          const nativeSelect = document.getElementById(targetId);
          if (!nativeSelect || nativeSelect.tagName !== 'SELECT') {
            console.error(`Selectify: No matching <select> found for data-target-id="${targetId}"`, selectifySelect);
            return;
          }

          initializeSelectifyDual(selectifySelect, nativeSelect);

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
   * Initializes the dual list multi-select.
   */
  function initializeSelectifyDual(selectifySelect, nativeSelect) {
    const selectedDisplay = selectifySelect.querySelector('.dual-selected');
    const dropdownMenu = selectifySelect.querySelector('.dual-available');
    const options = selectifySelect.querySelectorAll('.dual-available-option');
    const clearAllButton = selectifySelect.querySelector('.dual-clear-all');

    if (!nativeSelect || nativeSelect.tagName !== 'SELECT') {
      console.error('Selectify: Could not find the corresponding hidden <select> for', selectifySelect);
      return;
    }

    if (!selectedDisplay || options.length === 0) {
      return;
    }

    // Click event to select an item.
    once('selectifyDualEvents', dropdownMenu).forEach(() => {
      dropdownMenu.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();
        if (event.target.classList.contains('selectify-available-one-option')) {
          Drupal.selectify.toggleMultiSelectOption(type, nativeSelect, event.target, selectedDisplay, clearAllButton);
          event.target.classList.add('hidden'); // Hide selected item in dropdown.
        }
      });
    });

    // Click event to remove selected items.
    selectedDisplay.addEventListener('click', (event) => {
      if (event.target.classList.contains('selectify-selected-one-option')) {
        event.preventDefault();
        event.stopPropagation();
        const selectedItem = event.target;
        const valueToRemove = selectedItem.getAttribute('data-value');

        // Remove the selected tag from display.
        selectedItem.remove();

        // Unhide the corresponding option in the dropdown.
        const correspondingOption = dropdownMenu.querySelector(`.selectify-available-one-option[data-value="${valueToRemove}"]`);
        if (correspondingOption) {
          correspondingOption.classList.remove('hidden');
        }

        // Update hidden <select> field.
        const selectOption = nativeSelect.querySelector(`option[value="${valueToRemove}"]`);
        if (selectOption) {
          selectOption.selected = false;
        }

        // Sync display & state.
        Drupal.selectify.syncHiddenSelect(nativeSelect, selectedDisplay);
        Drupal.selectify.updateSelectedDisplay(type, nativeSelect, selectedDisplay, clearAllButton);
        nativeSelect.dispatchEvent(new Event('change'));
      }
    });

    // "Clear All" Button Logic.
    once('clearAllDual', clearAllButton).forEach(() => {
      clearAllButton.addEventListener('click', () => {
        nativeSelect.querySelectorAll('option').forEach(option => {
          option.selected = false;
        });

        selectedDisplay.querySelectorAll('.selectify-selected-one-option').forEach(item => item.remove());

        dropdownMenu.querySelectorAll('.selectify-available-one-option.selected').forEach(option => {
          option.classList.remove('selected');
        });

        dropdownMenu.querySelectorAll('.selectify-available-one-option.hidden').forEach(option => {
          option.classList.remove('hidden');
        });

        Drupal.selectify.syncHiddenSelect(nativeSelect, []);
        Drupal.selectify.updateSelectedDisplay(type, nativeSelect, selectedDisplay, clearAllButton);

        setTimeout(() => {
          nativeSelect.dispatchEvent(new Event('change', { bubbles: true }));
          nativeSelect.dispatchEvent(new Event('input', { bubbles: true }));
        }, 0);
      });
    });

    // Enable keyboard navigation.
    Drupal.selectify.handleKeyboardNavigation(selectifySelect, dropdownMenu, selectedDisplay);

    // Ensure UI syncs with hidden select field on load.
    syncUIWithHiddenSelect(nativeSelect, options, selectedDisplay, clearAllButton);

    // Initialize UI with existing selections.
    Drupal.selectify.updateSelectedDisplay(type, nativeSelect, selectedDisplay, clearAllButton);
  }

  /**
   * Ensures UI reflects the hidden select field at page load.
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

