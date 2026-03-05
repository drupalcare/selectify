/**
 * @file
 * Contains utility functions for Selectify module multi-select.
 *
 * Filename: selectify-helper.js
 * Website: https://www.flashwebcenter.com
 * Developer: Alaa Haddad https://www.alaahaddad.com.
 */
((Drupal, drupalSettings) => {
  'use strict';
  // Ensure Drupal.selectify namespace exists
  Drupal.selectify = Drupal.selectify || {};
  
  /**
   * Debug logging helper (only logs if debug mode is enabled).
   *
   * @param {string} message - The message to log.
   * @param {...*} args - Additional arguments to log.
   */
  const debugLog = (message, ...args) => {
    if (drupalSettings?.selectify?.debug) {
      console.warn('[Selectify]', message, ...args);
    }
  };
  /**
   * Reports init failure (only when debug is on). Use in widget bootstrap.
   *
   * @param {string} widgetName - Widget name for the log (e.g. "Dropdown", "Dual").
   * @param {string} message - Failure message.
   */
  Drupal.selectify.reportInitFailure = (widgetName, message) => {
    debugLog(`${widgetName}: ${message}`);
  };
  /**
   * Shared widget bootstrap: resolve native select, run init, selection limit, dropdown id/aria.
   * Call from each behavior after once(selector, context). Keeps dual and dropdown widgets
   * consistent; dual passes dropdownPanelSelector '.dual-available', others '.selectify-available-display'.
   *
   * @param {HTMLElement} selectifySelect - The widget wrapper (already matched by once).
   * @param {string} widgetName - Name for debug messages (e.g. "Dropdown", "Dual", "Tags").
   * @param {string} type - Widget type: 'dropdown'|'tags'|'searchable'|'checkbox'|'dual'.
   * @param {Object} options - Bootstrap options.
   * @param {function(HTMLElement, HTMLSelectElement)} options.initFn - (selectifySelect, nativeSelect) runs widget-specific init.
   * @param {string} [options.dropdownPanelSelector] - Selector for the dropdown/available panel to set id and aria-controls (e.g. '.selectify-available-display' or '.dual-available').
   * @param {string} [options.itemSelector='.selectify-available-one-option'] - Selector for selectable options (handleSelectionLimit).
   */
  Drupal.selectify.bootstrapWidget = (selectifySelect, widgetName, type, options) => {
    const targetId = selectifySelect.getAttribute('data-target-id');
    if (!targetId) {
      Drupal.selectify.reportInitFailure(widgetName, 'missing data-target-id attribute');
      return;
    }
    const nativeSelect = document.getElementById(targetId);
    if (!nativeSelect || nativeSelect.tagName !== 'SELECT') {
      Drupal.selectify.reportInitFailure(widgetName, `native select element not found: ${targetId}`);
      return;
    }
    if (options.initFn) {
      options.initFn(selectifySelect, nativeSelect);
    }
    let maxSelections = selectifySelect.getAttribute('data-max-selections');
    maxSelections = maxSelections === 'null' ? null : parseInt(maxSelections, 10);
    const itemSelector = options.itemSelector || '.selectify-available-one-option';
    Drupal.selectify.handleSelectionLimit(type, selectifySelect, nativeSelect, maxSelections, itemSelector);
    if (options.dropdownPanelSelector) {
      const panel = selectifySelect.querySelector(options.dropdownPanelSelector);
      if (panel) {
        panel.setAttribute('id', `${targetId}-dropdown`);
        selectifySelect.setAttribute('aria-controls', `${targetId}-dropdown`);
      }
    }
    Drupal.selectify.injectAriaLabelledBy();
  };
  /**
   * Shared "Clear All" handler: clear to "All" or deselect all, update UI, dispatch events, focus.
   * Dual and dropdown widgets pass their own onDeselectAll and focusTarget so behavior stays correct.
   *
   * @param {string} type - Widget type: 'dropdown'|'tags'|'searchable'|'checkbox'|'dual'.
   * @param {HTMLElement} selectifySelect - The widget wrapper.
   * @param {HTMLSelectElement} nativeSelect - The hidden <select>.
   * @param {HTMLElement} selectedDisplay - The display area for selected options.
   * @param {HTMLElement|null} clearAllButton - The Clear button (for updateSelectedDisplay).
   * @param {HTMLElement|null} dropdownMenu - The available-options panel (for onDeselectAll).
   * @param {Object} options - Clear options.
   * @param {function(HTMLElement, HTMLElement, HTMLSelectElement)} [options.onDeselectAll] - (dropdownMenu, selectedDisplay, nativeSelect) widget-specific DOM reset when deselecting all.
   * @param {function(HTMLElement): (HTMLElement|null)} [options.focusTarget] - (selectifySelect) return element to focus after clear (e.g. selected display or dual available list).
   */
  Drupal.selectify.executeClear = (type, selectifySelect, nativeSelect, selectedDisplay, clearAllButton, dropdownMenu, options) => {
    const targetValues = Drupal.selectify.getClearTargetValues(nativeSelect, selectifySelect);
    if (targetValues.length > 0) {
      nativeSelect.querySelectorAll('option').forEach((opt) => {
        opt.selected = (opt.value === 'All');
      });
      Drupal.selectify.syncHiddenSelect(nativeSelect, targetValues);
    } else {
      nativeSelect.querySelectorAll('option').forEach((opt) => {
        opt.selected = false;
      });
      if (options.onDeselectAll && dropdownMenu) {
        options.onDeselectAll(dropdownMenu, selectedDisplay, nativeSelect);
      }
      Drupal.selectify.syncHiddenSelect(nativeSelect, []);
    }
    Drupal.selectify.updateSelectedDisplay(type, nativeSelect, selectedDisplay, clearAllButton);
    setTimeout(() => {
      nativeSelect.dispatchEvent(new Event('change', { bubbles: true }));
      nativeSelect.dispatchEvent(new Event('input', { bubbles: true }));
    }, 0);
    const constants = Drupal.selectify?.constants || {};
    const focusDelay = constants.FOCUS_DELAY || 100;
    if (options.focusTarget) {
      const el = options.focusTarget(selectifySelect);
      if (el && typeof el.focus === 'function') {
        setTimeout(() => el.focus(), focusDelay);
      }
    }
  };
  /**
   * Handles selection limits for multi-select components.
   * Shows a message with animation when the max selection limit is reached.
   *
   * @param {string} type - The type of selectify component (e.g., "checkbox", "dual", "dropdown").
   * @param {HTMLElement} selectifySelect - The main Selectify wrapper element.
   * @param {HTMLElement} nativeSelect - The corresponding hidden <select> element.
   * @param {number|null} maxSelections - The maximum number of selections allowed.
   * @param {string} itemSelector - The CSS selector for selectable options.
   */
  Drupal.selectify.handleSelectionLimit = function(type, selectifySelect, nativeSelect, maxSelections, itemSelector) {
    if (!Number.isInteger(maxSelections) || maxSelections <= 0) {
      return;
    }
    if (!nativeSelect) {
      return;
    }
    let selectedDisplay = type === 'dual' ?
      selectifySelect.querySelector('.selectify-selected-display-dual') :
      selectifySelect.querySelector('.selectify-selected-display');
    const clearAllButton = selectifySelect.querySelector('.selectify-clear-all');
    const maxSelectionMessage = selectifySelect.querySelector('.selectify-max-selection-message');
    const items = selectifySelect.querySelectorAll(itemSelector);
    let selectedCount = 0;

    function showMaxSelectionMessage() {
      if (maxSelectionMessage) {
        const constants = Drupal.selectify?.constants || {};
        const dismissDelay = constants.MESSAGE_AUTO_DISMISS || 3000;
        maxSelectionMessage.classList.remove('hide');
        maxSelectionMessage.classList.add('show');
        setTimeout(() => {
          maxSelectionMessage.classList.remove('show');
          maxSelectionMessage.classList.add('hide');
        }, dismissDelay);
      }
    }
    if (type === 'checkbox') {
      const items = selectifySelect.querySelectorAll('.selectify-available-one-option input[type="checkbox"]');
      once('selectifyCheckboxLimit', items).forEach((item) => {
        item.addEventListener('change', function() {
          // For maxSelections=1, uncheck all other checkboxes when checking a new one (click-to-switch, like other widgets)
          if (maxSelections === 1 && this.checked) {
            items.forEach(i => {
              if (i !== this && i.checked) {
                i.checked = false;
                const parent = i.closest('.selectify-available-one-option');
                if (parent) {
                  parent.classList.remove('s-selected');
                }
              }
            });
          }

          let selectedItems = [...items].filter(i => i.checked);
          let selectedCount = selectedItems.length;
          // Prevent selection if max is reached (except for maxSelections=1 where we replace)
          if (this.checked && selectedCount > maxSelections && maxSelections !== 1) {
            this.checked = false;
            selectedCount--;
            return;
          }
          const selectedValues = selectedItems.map(i => i.value);
          // **Disable unchecked checkboxes only when max selection is reached (and multi-select). For single (maxSelections=1), never disable so user can click another to switch.**
          let shouldShowMessage = false;
          items.forEach(i => {
            if (!i.checked) {
              const wasEnabled = !i.disabled;
              const isDisabled = maxSelections > 1 && selectedCount >= maxSelections;
              i.disabled = isDisabled;

              // Show message when first disabling (transition from enabled to disabled)
              if (wasEnabled && isDisabled && maxSelections > 1) {
                shouldShowMessage = true;
              }

              // Add aria-disabled and title for screen reader context
              if (isDisabled) {
                i.setAttribute('aria-disabled', 'true');
                const parentOption = i.closest('.selectify-available-one-option');
                if (parentOption) {
                  parentOption.setAttribute('aria-disabled', 'true');
                  parentOption.setAttribute('title', Drupal.t('Maximum selections reached'));
                }
              } else {
                i.removeAttribute('aria-disabled');
                const parentOption = i.closest('.selectify-available-one-option');
                if (parentOption) {
                  parentOption.removeAttribute('aria-disabled');
                  parentOption.removeAttribute('title');
                }
              }
            }
          });

          // Show message when reaching max limit (only for maxSelections > 1)
          if (shouldShowMessage) {
            showMaxSelectionMessage();
          }
          // Sync hidden select field & update display
          Drupal.selectify.syncHiddenSelect(nativeSelect, selectedValues);
          Drupal.selectify.updateSelectedDisplay(type, nativeSelect, selectedDisplay, clearAllButton);
        });
      });
      } else {
      once('selectifyOptionLimit', items).forEach((item) => {
        item.addEventListener('click', function(event) {
          event.preventDefault();
          event.stopPropagation();
          let selectedItems = [...items].filter(i => i.classList.contains('s-selected'));
          selectedCount = selectedItems.length;
         if (!item.classList.contains('s-selected') && selectedCount >= maxSelections && maxSelections !== 1) {
            showMaxSelectionMessage();
            return;
          }
          // Determine the value and toggle the hidden select's state directly.
          const value = item.getAttribute('data-value') || item.textContent.trim();
          const option = nativeSelect.querySelector(`option[value="${value}"]`);
          if (option) {
            // Check if field is single-value by looking at data-multiple attribute
            const isMultiple = nativeSelect.getAttribute('data-multiple') === 'true';

            // For single-value fields (maxSelections=1), deselect all other options first
            if (maxSelections === 1 && !isMultiple) {
              nativeSelect.querySelectorAll('option').forEach(opt => {
                if (opt !== option) {
                  opt.selected = false;
                }
              });
              option.selected = true;
            } else {
              // Normal toggle for multi-select
              option.selected = !option.selected;
            }
          }
          // After toggling, recalculate selected values (fresh from native select).
          const selectedValues = Array.from(nativeSelect.options)
            .filter(opt => opt.selected)
            .map(opt => opt.value);
          Drupal.selectify.syncHiddenSelect(nativeSelect, selectedValues);
          Drupal.selectify.updateSelectedDisplay(type, nativeSelect, selectedDisplay, clearAllButton);
          // Ensure the select field updates before dispatching the event.
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
    }
  };
  /**
   * Opens the dropdown while closing other open dropdowns.
   *
   * @param {HTMLElement} dropdown - The dropdown menu to open.
   * @param {HTMLElement|null} triggerElement - The element that triggered the dropdown opening.
   */
  Drupal.selectify.openDropdown = (dropdown, triggerElement = null) => {
    if (!dropdown) return;

    // Close all other open dropdowns except the one being opened.
    document.querySelectorAll('.selectify-available-display.toggled').forEach(otherDropdown => {
      if (otherDropdown !== dropdown) {
        Drupal.selectify.closeDropdown(otherDropdown);
      }
    });

    if (typeof Drupal.selectify.slideDown === 'function') {
      Drupal.selectify.slideDown(dropdown);
    }

    const combobox = dropdown.closest('[role="combobox"]');
    if (combobox) {
      combobox.setAttribute('aria-expanded', 'true');
    }

    const parentSelect = triggerElement?.closest('.selectify-select');
    if (parentSelect) {
      parentSelect.classList.add('selectify-opened');
    }
  };
  /**
   * Closes the dropdown using the existing `slideUp` function.
   *
   * @param {HTMLElement} dropdown - The dropdown menu to close.
   * @param {HTMLElement|null} triggerElement - The element that triggered the dropdown closing.
   */
  Drupal.selectify.closeDropdown = (dropdown, triggerElement = null) => {
    if (!dropdown) return;

    if (typeof Drupal.selectify.slideUp === 'function') {
      Drupal.selectify.slideUp(dropdown);
    }

    const combobox = dropdown.closest('[role="combobox"]');
    if (combobox) {
      combobox.setAttribute('aria-expanded', 'false');
    }
    const trigger = triggerElement || dropdown.previousElementSibling;
    if (trigger && typeof trigger.focus === 'function') {
      trigger.focus();
    }

    const parentSelect = dropdown.closest('.selectify-select');
    if (parentSelect) {
      parentSelect.classList.remove('selectify-opened');
      // Remove direction classes from parent only
      parentSelect.classList.remove('opens-up', 'opens-down');
    }
  };
  /**
   * Synchronizes the hidden <select> field with the selected values.
   *
   * @param {HTMLElement} nativeSelect - The hidden select element.
   * @param {string[]} selectedValues - The selected option values.
   */
  Drupal.selectify.syncHiddenSelect = (nativeSelect, selectedValues) => {
    if (!nativeSelect) return;
    const noneOption = nativeSelect.querySelector('option[value="_none"]');
    nativeSelect.querySelectorAll("option").forEach(option => {
      if (option.value === '_none') {
        // Only handle _none if it exists (don't invent it).
        if (noneOption) {
          option.selected = (selectedValues.length === 0);
          option.toggleAttribute("selected", option.selected);
          option.classList.toggle("s-selected", option.selected);
        }
      } else {
        option.selected = selectedValues.includes(option.value);
        option.toggleAttribute("selected", option.selected);
        option.classList.toggle("s-selected", option.selected);
      }
      if (!option.classList.length) {
        option.removeAttribute("class");
      }
    });
    nativeSelect.dispatchEvent(new Event("change", {
      bubbles: true
    }));
    nativeSelect.dispatchEvent(new Event("input", {
      bubbles: true
    }));
  };
  /**
   * Adjusts dropdown height dynamically based on available space.
   *
   * @param {HTMLElement} selectifySelect - The selectify component.
   * @param {HTMLElement} dropdownMenu - The dropdown menu to adjust.
   */
  Drupal.selectify.adjustDropdownHeight = (selectifySelect, dropdownMenu) => {
    const windowHeight = window.innerHeight;
    const dropdownRect = selectifySelect.getBoundingClientRect();
    const spaceBelow = windowHeight - dropdownRect.bottom;
    const spaceAbove = dropdownRect.top;
    let maxHeight = Math.min(spaceBelow - 20, 300);
    if (maxHeight < 150 && spaceAbove > spaceBelow) {
      // Open upward - not enough space below
      maxHeight = Math.min(spaceAbove - 20, 300);
      dropdownMenu.style.top = 'auto';
      dropdownMenu.style.bottom = '100%';
      // Add direction classes to parent only
      selectifySelect.classList.add('opens-up');
      selectifySelect.classList.remove('opens-down');
    } else {
      // Open downward - default behavior
      dropdownMenu.style.top = '100%';
      dropdownMenu.style.bottom = 'auto';
      // Add direction classes to parent only
      selectifySelect.classList.add('opens-down');
      selectifySelect.classList.remove('opens-up');
    }
    dropdownMenu.style.maxHeight = `${maxHeight}px`;
    dropdownMenu.style.overflowY = 'auto';
  };
  /**
   * Toggles option selection.
   *
   * @param {string} type - The type of selectify component.
   * @param {HTMLElement} nativeSelect - The hidden <select> element.
   * @param {HTMLElement} optionElement - The selected option element.
   * @param {HTMLElement} selectedDisplay - The display area for selected options.
   * @param {HTMLElement} clearAllButton - The button to clear all selected options.
   */
Drupal.selectify.toggleMultiSelectOption = function(type, nativeSelect, optionElement, selectedDisplay, clearAllButton) {
    if (!type || !nativeSelect || !optionElement || !selectedDisplay) {
      return;
    }
    const targetElement = optionElement.closest('.selectify-available-one-option');
    if (!targetElement) return;
    const value = targetElement.dataset.value;
    const option = nativeSelect.querySelector(`option[value="${value}"]`);
    const isExposedFilter = nativeSelect.closest('.views-exposed-form') !== null;
    if (!option) {
      return; // Safety check — invalid option should do nothing.
    }
    const noneOption = nativeSelect.querySelector('option[value="_none"]');
    if (value === '_none' && noneOption) {
      if (isExposedFilter) {
        // Views filters should ignore _none entirely.
        return;
      }
      // Regular fields — treat _none like "clear all."
      nativeSelect.querySelectorAll('option').forEach(opt => opt.selected = (opt.value === '_none'));
      Drupal.selectify.syncHiddenSelect(nativeSelect, ['_none']);
      Drupal.selectify.updateSelectedDisplay(type, nativeSelect, selectedDisplay, clearAllButton);
      return;
    }
    // For single-value fields (maxSelections=1 or !multiple), deselect all other options first
    const isMultiple = nativeSelect.hasAttribute('multiple');
    if (!isMultiple) {
      // Deselect all other options
      nativeSelect.querySelectorAll('option').forEach(opt => {
        if (opt !== option) {
          opt.selected = false;
        }
      });
      // Select this option
      option.selected = true;
    } else {
      // Normal option toggle for multi-select
      option.selected = !option.selected;
    }
    // If regular option is selected, unselect _none (if present).
    if (option.selected && noneOption) {
      noneOption.selected = false;
    }
    const selectedValues = Array.from(nativeSelect.options)
      .filter(opt => opt.selected)
      .map(opt => opt.value);
    Drupal.selectify.syncHiddenSelect(nativeSelect, selectedValues);
    Drupal.selectify.updateSelectedDisplay(type, nativeSelect, selectedDisplay, clearAllButton);
  };
  /**
   * Removes a selected tag.
   *
   * @param {string} type - The type of selectify component.
   * @param {HTMLElement} nativeSelect - The hidden <select> element.
   * @param {HTMLElement} tagElement - The tag element to remove.
   * @param {HTMLElement} selectedDisplay - The display area for selected options.
   * @param {HTMLElement} clearAllButton - The button to clear all selected options.
   */
  Drupal.selectify.removeTag = (type, nativeSelect, tagElement, selectedDisplay, clearAllButton) => {
    if (!tagElement || !nativeSelect || !selectedDisplay) return;

    const value = tagElement.dataset.value;
    if (!value) return;
    const option = nativeSelect.querySelector(`option[value="${value}"]`);
    if (option) {
      option.selected = false;
    }
    const wrapper = selectedDisplay.closest('.selectify-select');
    const dropdownMenu = wrapper ? wrapper.querySelector('.selectify-available-display') : null;
    if (!dropdownMenu) return;
    const optionElement = dropdownMenu.querySelector(`.selectify-available-one-option[data-value="${value}"]`);
    if (optionElement) {
      optionElement.classList.remove('s-selected', 's-hidden'); // Remove both classes correctly
    }
    // Sync the hidden select field.
    const selectedValues = Array.from(nativeSelect.options)
      .filter(opt => opt.selected)
      .map(opt => opt.value);
    // **Reinsert _none if no selections are left (only if field is not required)**
    if (selectedValues.length === 0 && nativeSelect.hasAttribute("data-has-none")) {
      const noneOption = nativeSelect.querySelector('option[value="_none"]');
      if (noneOption) {
        noneOption.selected = true;
      }
    }
    Drupal.selectify.syncHiddenSelect(nativeSelect, selectedValues);
    Drupal.selectify.updateSelectedDisplay(type, nativeSelect, selectedDisplay, clearAllButton);
    tagElement.remove();
  };
  /**
   * Retrieves selected values from a hidden `<select>`.
   *
   * @param {HTMLElement} nativeSelect - The hidden <select> element.
   * @returns {string[]} - An array of selected option values.
   */
  Drupal.selectify.getSelectedValues = (nativeSelect) => {
    let selectedValues = Array.from(nativeSelect.options)
      .filter(option => option.selected)
      .map(option => option.value)
      .filter(value => value !== '_none');
    // **Ensure _none is reinserted when no other values are selected**
    if (selectedValues.length === 0 && nativeSelect.dataset.hasNone === "true") {
      const noneOption = nativeSelect.querySelector('option[value="_none"]');
      if (noneOption) {
        noneOption.selected = true;
        selectedValues = ['_none'];
      }
    }
    return selectedValues;
  };
  /**
   * For "Clear All": single-select with an "All" option should reset to "All", not empty.
   *
   * @param {HTMLSelectElement} nativeSelect - The hidden <select> element.
   * @param {HTMLElement} selectWidget - The Selectify widget wrapper (for data-multiple).
   * @returns {string[]} [] to deselect all, or ['All'] to select the "All" option.
   */
  Drupal.selectify.getClearTargetValues = (nativeSelect, selectWidget) => {
    const isMultiple = selectWidget && selectWidget.getAttribute('data-multiple') === 'true';
    if (isMultiple) {
      return [];
    }
    const hasAll = Array.from(nativeSelect.options).some(opt => opt.value === 'All');
    return hasAll ? ['All'] : [];
  };
  /**
   * Close any open dropdown when clicking outside.
   * Bound at module level (IIFE), so it only runs once per page load.
   */
  document.addEventListener('click', (event) => {
    if (!event.target.closest('.selectify-available-display') && !event.target.closest('.selectify-selected-display')) {
      document.querySelectorAll('.selectify-available-display.toggled').forEach(dropdown => {
        Drupal.selectify.closeDropdown(dropdown);
      });
    }
  });
})(Drupal, drupalSettings);
