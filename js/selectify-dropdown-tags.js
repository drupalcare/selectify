/**
 * @file
 * Contains utility functions for Selectify module multi-select searchable dropdowns.
 *
 * Filename: selectify-dropdown-tags.js
 * Website: https://www.flashwebcenter.com
 * Developer: Alaa Haddad https://www.alaahaddad.com.
 */
((Drupal, once, drupalSettings) => {
  'use strict';
  const type = 'tags';
  /**
   * Drupal behavior for custom multi-select tags dropdowns.
   */
  Drupal.behaviors.selectifySelectTags = {
    detach: (context, settings, trigger) => {
      if (trigger !== 'unload') {
        return;
      }
      (context.querySelectorAll ? context.querySelectorAll('.selectify-dropdown-tags-widget') : []).forEach((el) => {
        const display = el.querySelector('.dual-selected, .selectify-select-trigger');
        if (display && display._selectifyObserver) {
          display._selectifyObserver.disconnect();
          display._selectifyObserver = null;
        }
      });
    },
    attach: (context) => {
      once('selectifyMultiSelectTags', '.selectify-dropdown-tags-widget', context).forEach((selectifySelect) => {
        const constants = Drupal.selectify?.constants || {};
        const initDelay = constants.INIT_DELAY || 10;
        setTimeout(() => {
          Drupal.selectify.bootstrapWidget(selectifySelect, 'Tags', type, {
            initFn: (sel, native) => initializeSelectifyTags(sel, native),
            dropdownPanelSelector: '.selectify-available-display',
          });
        }, initDelay);
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

      return;
    }
    if (!selectedDisplay || !dropdownMenu || options.length === 0) {
      return;
    }
    // Toggle dropdown open/close (ignore clicks on remove-tag so only removal runs).
    selectedDisplay.addEventListener('click', (event) => {
      if (event.target.closest('.remove-tag')) {
        return;
      }
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
    if (clearAllButton) {
      once('clearAllTags', clearAllButton).forEach(() => {
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

    // Add event delegation for remove tag buttons
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
