/**
 * @file
 * Contains utility functions for Selectify module multi-select searchable dropdowns.
 *
 * Filename: selectify-dropdown-searchable.js
 * Website: https://www.flashwebcenter.com
 * Developer: Alaa Haddad https://www.alaahaddad.com.
 */
((Drupal, once, drupalSettings) => {
  'use strict';
  const type = 'searchable';
  /**
   * Drupal behavior for custom multi-select searchable dropdowns.
   */
  Drupal.behaviors.selectifySelectSearchable = {
    detach: (context, settings, trigger) => {
      if (trigger !== 'unload') {
        return;
      }
      (context.querySelectorAll ? context.querySelectorAll('.selectify-dropdown-searchable-widget') : []).forEach((el) => {
        const display = el.querySelector('.dual-selected, .selectify-select-trigger');
        if (display && display._selectifyObserver) {
          display._selectifyObserver.disconnect();
          display._selectifyObserver = null;
        }
      });
    },
    attach: (context) => {
      once('selectifyMultiSelectSearchable', '.selectify-dropdown-searchable-widget', context).forEach((selectifySelect) => {
        const constants = Drupal.selectify?.constants || {};
        const initDelay = constants.INIT_DELAY || 10;
        setTimeout(() => {
          // Retrieve the target ID from the data attribute.
          const targetId = selectifySelect.getAttribute('data-target-id');
          if (!targetId) {
            if (drupalSettings?.selectify?.debug) {
              console.warn('[Selectify] Searchable widget missing data-target-id attribute');
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
          Drupal.selectify.injectAriaLabelledBy();
        }, initDelay);
      });
    }
  };
  /**
   * Initializes the searchable multi-select dropdown.
   *
   * @param {HTMLElement} selectifySelect - The Selectify wrapper element.
   * @param {HTMLElement} nativeSelect - The hidden native <select> element.
   */
  function initializeSelectifySearchable(selectifySelect, nativeSelect) {
    const selectedDisplay = selectifySelect.querySelector('.selectify-selected-display');
    const dropdownMenu = selectifySelect.querySelector('.selectify-available-display');
    const options = selectifySelect.querySelectorAll('.selectify-available-one-option');
    const clearAllButton = selectifySelect.querySelector('.selectify-clear-all');
    const dropdownArrow = selectifySelect.querySelector('.selectify-dorpdown-arrow');
    const searchInput = selectifySelect.querySelector('.multi-search-input');
    if (!nativeSelect || nativeSelect.tagName !== 'SELECT') {

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
          const constants = Drupal.selectify?.constants || {};
          const focusDelay = constants.FOCUS_DELAY || 100;
          setTimeout(() => searchInput.focus(), focusDelay);
        }
      }
    });
    // Handle search filtering with debounce for performance.
    if (searchInput) {
      const constants = Drupal.selectify?.constants || {};
      const debounceDelay = constants.SEARCH_DEBOUNCE || 150;
      let searchTimer = null;
      searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        if (searchTimer) {
          clearTimeout(searchTimer);
        }
        searchTimer = setTimeout(() => {
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
        optionsArray.forEach(option => option.classList.add('s-hidden'));
        // Append sorted text, then original order for other types.
        [...textOptions, ...numberOptions, ...dateOptions, ...emailOptions, ...entityOptions, ...otherOptions].forEach(option => {
          option.classList.remove('s-hidden');
        });
        }, debounceDelay);
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
    if (clearAllButton) {
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
          option.classList.remove('s-selected', 's-hidden'); // Unhide and unselect
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

        // Return focus to the main trigger
        if (selectedDisplay && typeof selectedDisplay.focus === 'function') {
          const constants = Drupal.selectify?.constants || {};
          const focusDelay = constants.FOCUS_DELAY || 100;
          setTimeout(() => selectedDisplay.focus(), focusDelay);
        }
      });
    });
    }
    // Add keyboard support for remove tag buttons
    selectedDisplay.querySelectorAll('.remove-tag').forEach(btn => {
      btn.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ') {
          event.preventDefault();
          btn.click();
        }
      });
    });

    // Re-add keyboard support when tags are dynamically added.
    // Store observer reference on element for cleanup.
    if (selectedDisplay._selectifyObserver) {
      selectedDisplay._selectifyObserver.disconnect();
    }
    const observer = new MutationObserver(() => {
      selectedDisplay.querySelectorAll('.remove-tag').forEach(btn => {
        if (!btn.hasAttribute('data-keyboard-enabled')) {
          btn.setAttribute('data-keyboard-enabled', 'true');
          btn.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
              event.preventDefault();
              btn.click();
            }
          });
        }
      });
    });
    observer.observe(selectedDisplay, { childList: true, subtree: true });
    selectedDisplay._selectifyObserver = observer;

    // Add event delegation for remove tag buttons (ONE listener for all)
    once('selectifyRemoveTagDelegation', selectedDisplay).forEach(() => {
      selectedDisplay.addEventListener('click', function(event) {
        const removeButton = event.target.closest('.remove-tag');
        if (removeButton) {
          event.preventDefault();
          event.stopPropagation();
          Drupal.selectify.removeTag(type, nativeSelect, removeButton.parentElement, selectedDisplay, clearAllButton);
        }
      });
    });
    // Enable keyboard navigation.
    Drupal.selectify.handleKeyboardNavigation(selectifySelect, dropdownMenu, selectedDisplay, type);
    const selectedValues = Drupal.selectify.getSelectedValues(nativeSelect);
    Drupal.selectify.syncHiddenSelect(nativeSelect, selectedValues);
    Drupal.selectify.updateSelectedDisplay(type, nativeSelect, selectedDisplay, clearAllButton);
  }
})(Drupal, once, drupalSettings);
