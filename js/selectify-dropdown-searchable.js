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
  const type = 'searchable';
  /**
   * Drupal behavior for custom multi-select searchable dropdowns.
   */
  Drupal.behaviors.selectifySelectSearchable = {
    attach: (context) => {
      once('selectifyMultiSelectSearchable', '.selectify-dropdown-searchable-widget', context).forEach((selectifySelect) => {
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
          initializeSelectifySearchable(selectifySelect, nativeSelect);
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
   * Initializes the searchable multi-select dropdown.
   */
  function initializeSelectifySearchable(selectifySelect, nativeSelect) {
    const selectedDisplay = selectifySelect.querySelector('.selectify-selected-display');
    const dropdownMenu = selectifySelect.querySelector('.selectify-available-display');
    const options = selectifySelect.querySelectorAll('.selectify-available-one-option');
    const clearAllButton = selectifySelect.querySelector('.selectify-clear-all');
    const dropdownArrow = selectifySelect.querySelector('.selectify-dorpdown-arrow');
    const searchInput = selectifySelect.querySelector('.multi-search-input');
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
        // Focus the search input when the dropdown opens.
        if (searchInput) {
          setTimeout(() => searchInput.focus(), 100);
        }
      }
    });
    // Handle search filtering.
    if (searchInput) {
      searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        let optionsArray = Array.from(selectifySelect.querySelectorAll('.selectify-available-one-option'));
        // Filter options based on search input.
        let filteredOptions = optionsArray.filter(option =>
          option.textContent.trim().toLowerCase().includes(searchTerm)
        );
        // Separate options based on type.
        let textOptions = [];
        let numberOptions = [];
        let emailOptions = [];
        let dateOptions = [];
        let entityOptions = [];
        let otherOptions = [];
        filteredOptions.forEach(option => {
          const optionText = option.textContent.trim();
          if (optionText.includes('@') && optionText.includes('.')) {
            emailOptions.push(option);
          } else if (!isNaN(parseFloat(optionText)) && isFinite(optionText)) {
            numberOptions.push(option);
          } else if (optionText.length >= 10 && optionText[4] === '-' && optionText[7] === '-') {
            dateOptions.push(option);
          } else if (optionText.startsWith("Node:") || optionText.startsWith("User:") || optionText.startsWith("Term:")) {
            entityOptions.push(option);
          } else {
            textOptions.push(option);
          }
        });
        // Sort only text alphabetically.
        textOptions.sort((a, b) => a.textContent.trim().localeCompare(b.textContent.trim()));
        // Hide all options before displaying the filtered ones.
        optionsArray.forEach(option => option.classList.add('hidden'));
        // Append sorted text, then original order for other types.
        [...textOptions, ...numberOptions, ...dateOptions, ...emailOptions, ...entityOptions, ...otherOptions].forEach(option => {
          option.classList.remove('hidden');
        });
      });
    }
    // Handle option selection.
    once('selectifySearchableEvents', dropdownMenu).forEach(() => {
      dropdownMenu.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();
        if (event.target.classList.contains('selectify-available-one-option')) {
          Drupal.selectify.toggleMultiSelectOption(type, nativeSelect, event.target, selectedDisplay, clearAllButton);
        }
      });
    });
    // Handle "Clear All" functionality.
    once('clearAllSearchable', clearAllButton).forEach(() => {
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
    const selectedValues = Drupal.selectify.getSelectedValues(nativeSelect);
    Drupal.selectify.syncHiddenSelect(nativeSelect, selectedValues);
    Drupal.selectify.updateSelectedDisplay(type, nativeSelect, selectedDisplay, clearAllButton);
  }
})(Drupal, once);
