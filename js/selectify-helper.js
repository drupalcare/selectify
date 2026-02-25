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
          // For maxSelections=1, uncheck all other checkboxes when checking a new one
          if (maxSelections === 1 && this.checked) {
            items.forEach(i => {
              if (i !== this && i.checked) {
                i.checked = false;
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
          // **Ensure unchecked checkboxes get disabled when max selection is reached**
          let shouldShowMessage = false;
          items.forEach(i => {
            if (!i.checked) {
              const wasEnabled = !i.disabled;
              const isDisabled = selectedCount >= maxSelections;
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
   * Updates the displayed selected options.
   *
   * @param {string} type - The type of selectify component (e.g., "tags", "searchable", "dropdown", "checkbox", "dual").
   * @param {HTMLElement} nativeSelect - The hidden <select> element.
   * @param {HTMLElement} selectedDisplay - The display area for selected options.
   * @param {HTMLElement|null} clearAllButton - The button to clear all selected options.
   */
  Drupal.selectify.updateSelectedDisplay = (type, nativeSelect, selectedDisplay, clearAllButton) => {
    const selectedValues = Drupal.selectify.getSelectedValues(nativeSelect);
    if (!clearAllButton || !clearAllButton.closest('.selectify-select')) {
      return;
    }

    // Announce widget is updating
    const selectWidget = selectedDisplay.closest('.selectify-select');
    if (selectWidget) {
      selectWidget.setAttribute('aria-busy', 'true');
    }
    if (type !== 'dual') {
      // Remove existing .selectify-selected-options while keeping the arrow.
      const existingWrapper = selectedDisplay.querySelector('.selectify-selected-options');
      if (existingWrapper) {
        existingWrapper.remove();
      }
    }
    // Handle tags & searchable types.
    if (type === 'tags' || type === 'searchable') {
      const placeholder = selectedDisplay.querySelector('.placeholder-text');
      const arrowIcon = selectedDisplay.querySelector('.selectify-dorpdown-arrow');
      Drupal.selectify.renderSelectifySearchTags(nativeSelect, selectedDisplay, placeholder, arrowIcon, selectedValues);
    }
    // Handle tags & searchable types.
    if (type === 'dropdown') {
      const placeholder = selectedDisplay.querySelector('.placeholder-text');
      const arrowIcon = selectedDisplay.querySelector('.selectify-dorpdown-arrow');
      Drupal.selectify.renderSelectifyDropdown(nativeSelect, selectedDisplay, placeholder, arrowIcon, selectedValues);
    }
    // Handle checkbox-based multi-select.
    if (type === 'checkbox') {
      const placeholder = selectedDisplay.querySelector('.placeholder-text');
      const arrowIcon = selectedDisplay.querySelector('.selectify-dorpdown-arrow');
      Drupal.selectify.renderSelectifyDropdownCheckbox(nativeSelect, selectedDisplay, arrowIcon, selectedValues);
    }
    // Handle dual-list multi-select.
    if (type === 'dual') {
      const dualSelectedArrow = selectedDisplay.querySelector('.dual-selected-arrow');
      Drupal.selectify.renderSelectifyDual(nativeSelect, selectedDisplay, dualSelectedArrow, selectedValues);
    }
    const wrapper = selectedDisplay.closest('.selectify-select');
    if (wrapper) {
      const availableOptions = wrapper.querySelectorAll('.selectify-available-one-option');
      availableOptions.forEach(option => {
        const value = option.dataset.value;
        const isSelected = selectedValues.includes(value);
        Drupal.selectify.updateOptionState(option, isSelected, type);
      });
    }
    // Toggle "has-selections" on parent — CSS controls Clear All visibility.
    // Exclude placeholder values: _none (entity fields) and All (Views exposed filters).
    if (selectWidget) {
      const hasRealSelections = selectedValues.filter(v => v !== '_none' && v !== 'All' && v !== '').length > 0;
      selectWidget.classList.toggle('has-selections', hasRealSelections);
    }
    // Announce selection change to screen readers
    if (selectWidget) {
      Drupal.selectify.announceSelectionChange(selectWidget, selectedValues.length);
      selectWidget.setAttribute('aria-busy', 'false');
    }
  };
  /**
   * Renders the selected values inside the multi-select header (for tags & searchable).
   *
   * @param {HTMLElement} nativeSelect - The hidden <select> element.
   * @param {HTMLElement} selectedDisplay - The display area for selected options.
   * @param {HTMLElement|null} placeholder - The placeholder text element.
   * @param {HTMLElement|null} arrowIcon - The dropdown arrow element (may be null if not in DOM).
   * @param {string[]} selectedValues - The selected option values.
   */
  Drupal.selectify.renderSelectifyDropdown = (nativeSelect, selectedDisplay, placeholder, arrowIcon, selectedValues) => {
    if (selectedValues.length > 0) {
      const wrapper = document.createElement('div');
      wrapper.className = 'selectify-selected-options';

      selectedValues.forEach(value => {
        const option = nativeSelect.querySelector(`option[value="${value}"]`);
        if (option) {
          const itemDiv = document.createElement('div');
          itemDiv.className = 'selectify-selected-one-option';
          itemDiv.setAttribute('data-value', value);

          const span = document.createElement('span');
          span.className = 'option-label';
          span.textContent = option.textContent;

          itemDiv.appendChild(span);
          wrapper.appendChild(itemDiv);
        }
      });

      if (arrowIcon && arrowIcon.parentNode) {
        arrowIcon.parentNode.insertBefore(wrapper, arrowIcon);
      } else {
        selectedDisplay.appendChild(wrapper);
      }
    }
  };

  /**
   * Renders the selected values inside the multi-select header (for tags & searchable).
   *
   * @param {HTMLElement} nativeSelect - The hidden <select> element.
   * @param {HTMLElement} selectedDisplay - The display area for selected options.
   * @param {HTMLElement|null} placeholder - The placeholder text element.
   * @param {HTMLElement|null} arrowIcon - The dropdown arrow element (may be null if not in DOM).
   * @param {string[]} selectedValues - The selected option values.
   */
  Drupal.selectify.renderSelectifySearchTags = (nativeSelect, selectedDisplay, placeholder, arrowIcon, selectedValues) => {
    if (selectedValues.length > 0) {
      const wrapper = document.createElement('div');
      wrapper.className = 'selectify-selected-options';

      selectedValues.forEach(value => {
        const option = nativeSelect.querySelector(`option[value="${value}"]`);
        if (option) {
          const label = option.textContent.trim();

          const itemDiv = document.createElement('div');
          itemDiv.className = 'selectify-selected-one-option';
          itemDiv.setAttribute('data-value', value);

          const span = document.createElement('span');
          span.className = 'option-label';
          span.textContent = label;

          const removeBtn = document.createElement('button');
          removeBtn.className = 'remove-tag';
          removeBtn.type = 'button';
          removeBtn.setAttribute('aria-label', Drupal.t('Remove @label', { '@label': label }));

          const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
          svg.setAttribute('fill', 'var(--selectify-select-arrow-color, var(--selectify-select-color))');
          svg.setAttribute('class', 'icon-delete');
          svg.setAttribute('height', '24px');
          svg.setAttribute('viewBox', '0 -960 960 960');
          svg.setAttribute('width', '24px');

          const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
          path.setAttribute('d', 'M280-120q-33 0-56.5-23.5T200-200v-520h-40v-80h200v-40h240v40h200v80h-40v520q0 33-23.5 56.5T680-120H280Zm400-600H280v520h400v-520ZM360-280h80v-360h-80v360Zm160 0h80v-360h-80v360ZM280-720v520-520Z');

          svg.appendChild(path);
          removeBtn.appendChild(svg);
          itemDiv.appendChild(span);
          itemDiv.appendChild(removeBtn);
          wrapper.appendChild(itemDiv);
        }
      });

      if (arrowIcon && arrowIcon.parentNode) {
        arrowIcon.parentNode.insertBefore(wrapper, arrowIcon);
      } else {
        selectedDisplay.appendChild(wrapper);
      }
    }
  };

  /**
   * Renders the selected values inside the multi-select header (for checkbox-based selects).
   *
   * @param {HTMLElement} nativeSelect - The hidden <select> element.
   * @param {HTMLElement} selectedDisplay - The display area for selected options.
   * @param {HTMLElement|null} arrowIcon - The dropdown arrow element (may be null if not in DOM).
   * @param {string[]} selectedValues - The selected option values.
   */
  Drupal.selectify.renderSelectifyDropdownCheckbox = (nativeSelect, selectedDisplay, arrowIcon, selectedValues) => {
    selectedDisplay.innerHTML = '';

    if (selectedValues.length > 0) {
      const wrapper = document.createElement('div');
      wrapper.className = 'selectify-selected-options';

      selectedValues.forEach(value => {
        const option = nativeSelect.querySelector(`option[value="${value}"]`);
        if (option) {
          const span = document.createElement('span');
          span.className = 'selectify-selected-one-option';
          span.setAttribute('data-value', value);
          span.textContent = option.textContent;
          wrapper.appendChild(span);
        }
      });

      selectedDisplay.appendChild(wrapper);
    }

    // Clone and append the dropdown arrow.
    if (arrowIcon) {
      const arrowClone = arrowIcon.cloneNode(true);
      arrowClone.className = 'selectify-dorpdown-arrow';
      selectedDisplay.appendChild(arrowClone);
    }
  };
  /**
   * Renders the selected values inside the multi-select header (for dual-list selects).
   *
   * @param {HTMLElement} nativeSelect - The hidden <select> element.
   * @param {HTMLElement} selectedDisplay - The display area for selected options.
   * @param {HTMLElement|null} dualSelectedArrow - The arrow for dual-selected elements.
   * @param {string[]} selectedValues - The selected option values.
   */
  Drupal.selectify.renderSelectifyDual = (nativeSelect, selectedDisplay, dualSelectedArrow, selectedValues) => {
    selectedDisplay.innerHTML = '';

    if (selectedValues.length > 0) {
      const wrapper = document.createElement('div');
      wrapper.className = 'selectify-selected-options dual-inner';

      selectedValues.forEach(value => {
        const option = nativeSelect.querySelector(`option[value="${value}"]`);
        if (option) {
          const itemDiv = document.createElement('div');
          itemDiv.className = 'selectify-selected-one-option dual-item';
          itemDiv.setAttribute('data-value', value);

          const label = document.createElement('label');
          label.className = 'label-option';
          label.textContent = option.textContent;

          const arrowSpan = document.createElement('span');
          arrowSpan.className = 'dual-selected-arrow';

          const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
          svg.setAttribute('fill', 'var(--selectify-select-arrow-color, var(--selectify-select-color))');
          svg.setAttribute('class', 'arrow-circle-left');
          svg.setAttribute('height', '24px');
          svg.setAttribute('viewBox', '0 -960 960 960');
          svg.setAttribute('width', '24px');

          const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
          path.setAttribute('d', 'm480-320 56-56-64-64h168v-80H472l64-64-56-56-160 160 160 160Zm0 240q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z');

          svg.appendChild(path);
          arrowSpan.appendChild(svg);
          itemDiv.appendChild(label);
          itemDiv.appendChild(arrowSpan);
          wrapper.appendChild(itemDiv);
        }
      });

      selectedDisplay.appendChild(wrapper);
    }
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
   * Handles keyboard navigation for multi-select components.
   *
   * @param {HTMLElement} selectifySelect - The main Selectify wrapper element.
   * @param {HTMLElement} dropdownMenu - The dropdown menu element.
   * @param {HTMLElement} selectedDisplay - The display area for selected options.
   * @param {string} type - The type of selectify component.
   */
  Drupal.selectify.handleKeyboardNavigation = (selectifySelect, dropdownMenu, selectedDisplay, type) => {
    // Ensure keyboard navigation event is only bound once per dropdown.
    once('selectifyMultiSelectKeyboard', selectifySelect).forEach(() => {
      selectifySelect.addEventListener('keydown', (event) => {
        const key = event.key;
        const isOpen = dropdownMenu.classList.contains('toggled');
        const options = dropdownMenu.querySelectorAll('.selectify-available-one-option');
        if ((key === 'Enter' || key === ' ') && !isOpen) {
          event.preventDefault();
          Drupal.selectify.openDropdown(dropdownMenu, selectedDisplay);

          // Wait for dropdown animation to complete before focusing
          setTimeout(() => {
            const firstOption = options[0];
            if (firstOption) {
              // Set roving tabindex on first option
              options.forEach(opt => opt.setAttribute('tabindex', '-1'));
              firstOption.setAttribute('tabindex', '0');
              firstOption.focus();
              selectifySelect.setAttribute('aria-activedescendant', firstOption.id);
              Drupal.selectify.updateOptionState(firstOption, true, type);
            }
          }, 50);
        } else if (key === 'Escape' && isOpen) {
          Drupal.selectify.closeDropdown(dropdownMenu, selectedDisplay);
        } else if ((key === 'Enter' || key === ' ') && isOpen) {
          event.preventDefault();
          const currentFocused = document.activeElement.closest('.selectify-available-one-option');
          if (currentFocused) {
            currentFocused.click();
          }
        } else if (key === 'ArrowDown' || key === 'ArrowUp') {
          event.preventDefault();
          const currentFocused = document.activeElement.closest('.selectify-available-one-option');
          let newIndex = -1;
          if (!currentFocused) {
            // Start at first/last option if nothing is currently focused.
            newIndex = key === 'ArrowDown' ? 0 : options.length - 1;
          } else {
            const currentIndex = Array.from(options).indexOf(currentFocused);
            newIndex = key === 'ArrowDown' ? currentIndex + 1 : currentIndex - 1;
          }
          if (newIndex >= 0 && newIndex < options.length) {
            const newFocusedOption = options[newIndex];
            // Implement roving tabindex pattern
            options.forEach(opt => opt.setAttribute('tabindex', '-1'));
            newFocusedOption.setAttribute('tabindex', '0');
            newFocusedOption.focus();
            // Update activedescendant and aria-selected
            selectifySelect.setAttribute('aria-activedescendant', newFocusedOption.id);
            options.forEach((option, index) => {
              Drupal.selectify.updateOptionState(option, index === newIndex, type);
            });
         }
        } else if (key === 'Tab' && isOpen) {
          event.preventDefault();
          const focusableElements = dropdownMenu.querySelectorAll(
            'input:not([aria-hidden="true"]), button:not([aria-hidden="true"]), .selectify-available-one-option[tabindex="0"]'
          );
          if (focusableElements.length === 0) return;

          const currentIndex = Array.from(focusableElements).indexOf(document.activeElement);
          let nextIndex;

          if (event.shiftKey) {
            nextIndex = currentIndex <= 0 ? focusableElements.length - 1 : currentIndex - 1;
          } else {
            nextIndex = currentIndex >= focusableElements.length - 1 ? 0 : currentIndex + 1;
          }

          focusableElements[nextIndex].focus();

          // Update aria-activedescendant if focusing an option
          if (focusableElements[nextIndex].classList.contains('selectify-available-one-option')) {
            selectifySelect.setAttribute('aria-activedescendant', focusableElements[nextIndex].id);
          }
        }
      });
    });
  };
  /**
   * Updates selected state and applies "hidden" (for types like tags that remove selected items).
   *
   * @param {HTMLElement} optionElement - The option element to update.
   * @param {boolean} isSelected - Whether the option is selected.
   * @param {string} type - The type of selectify component.
   */
  Drupal.selectify.updateOptionState = (optionElement, isSelected, type) => {
    if (!optionElement || !type) {
      return;
    }

    const value = optionElement.dataset.value;
    if (!value) return;

    const isNoneOrAll = (value === '_none' || value === 'All');
    const isExposedFilter = optionElement.closest('.views-exposed-form') !== null;

    // Update aria-activedescendant on parent widget when option is focused
    if (document.activeElement === optionElement || optionElement.contains(document.activeElement)) {
      const selectWidget = optionElement.closest('.selectify-select');
      if (selectWidget && optionElement.id) {
        selectWidget.setAttribute('aria-activedescendant', optionElement.id);
      }
    }

    if (type === 'checkbox') {
      const checkbox = optionElement.querySelector('input[type="checkbox"]');
      if (checkbox) {
        // For views exposed form, trust the actual checkbox state.
        if (isExposedFilter) {
          isSelected = checkbox.checked;
        }
        checkbox.setAttribute('aria-checked', isSelected ? 'true' : 'false');
        checkbox.checked = isSelected;
      }
      optionElement.classList.toggle('s-selected', isSelected);
    } else {
      if (!(isExposedFilter && isNoneOrAll)) {
        if (isSelected) {
          optionElement.setAttribute('aria-selected', 'true');
        } else {
          optionElement.removeAttribute('aria-selected');
        }
        optionElement.classList.toggle('s-selected', isSelected);
      }
      if (type === 'searchable' || type === 'tags' || type === 'dual') {
        optionElement.classList.toggle('s-hidden', isSelected);
      }
    }
  };

  /**
   * Ensures aria-labelledby is added when a <label> exists before .wrapper-selectify.
   *
   * This function scans all Selectify widgets and adds appropriate aria-labelledby
   * attributes when a label element is found preceding the widget wrapper.
   */
  Drupal.selectify.injectAriaLabelledBy = function () {
    document.querySelectorAll('.selectify-select').forEach(widget => {
      const wrapper = widget.closest('.wrapper-selectify');
      if (!wrapper) return;

      const label = wrapper.previousElementSibling;
      if (!label || label.tagName.toLowerCase() !== 'label') return;

      let labelId = label.getAttribute('id');
      if (!labelId) {
        const forAttr = label.getAttribute('for');
        if (forAttr) {
          labelId = `${forAttr}-label`;
        } else {
          labelId = 'selectify-label-' + Math.random().toString(36).substring(2, 10);
        }
        label.setAttribute('id', labelId);
      }

      if (!widget.hasAttribute('aria-labelledby')) {
        widget.setAttribute('aria-labelledby', labelId);
      }
    });
  };

  /**
   * Announces selection changes to screen readers.
   *
   * Debounced to prevent announcement spam during rapid selections.
   * Uses a live region to announce the current selection count.
   *
   * @param {HTMLElement} selectWidget - The main Selectify widget element.
   * @param {number} count - Number of selected items.
   */
  Drupal.selectify.announceSelectionChange = (function() {
    let announcementTimer = null;

    return function(selectWidget, count) {
      const liveRegion = selectWidget.querySelector('[role="status"][aria-live="polite"]');
      if (!liveRegion) return;

      // Clear previous timer to debounce
      if (announcementTimer) {
        clearTimeout(announcementTimer);
      }

      // Wait for debounce delay after last change before announcing
      const constants = Drupal.selectify?.constants || {};
      const debounceDelay = constants.ANNOUNCEMENT_DEBOUNCE || 500;
      announcementTimer = setTimeout(() => {
        liveRegion.textContent = Drupal.t('@count selected', {'@count': count});
      }, debounceDelay);
    };
  })();

  /**
   * Normalizes text for diacritics-tolerant, case-insensitive comparison.
   *
   * Decomposes Unicode characters (NFD), strips combining marks (accents),
   * and lowercases the result. This allows "cafe" to match "Café".
   *
   * @param {string} text - The text to normalize.
   * @returns {string} - The normalized text.
   */
  Drupal.selectify.normalizeText = function(text) {
    if (typeof text !== 'string') {
      return '';
    }
    return text.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
  };

  /**
   * Filters available options based on a search term.
   *
   * Categorizes matching options by type (text, number, email, date, entity)
   * and sorts text options alphabetically. Non-matching options are hidden
   * using the s-search-hidden class.
   *
   * Also updates the empty-state message and search status count if the
   * corresponding elements exist in the container.
   *
   * @param {HTMLElement} container - The widget container to search within.
   * @param {string} searchTerm - The search term to filter by.
   */
  Drupal.selectify.filterSearchOptions = function(container, searchTerm) {
    const search = typeof searchTerm === 'string' ? searchTerm : '';
    const normalizedSearch = Drupal.selectify.normalizeText(search);
    let optionsArray = Array.from(container.querySelectorAll('.selectify-available-one-option'));
    // Filter options based on normalized search input.
    let filteredOptions = optionsArray.filter(option =>
      Drupal.selectify.normalizeText(option.textContent.trim()).includes(normalizedSearch)
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
    optionsArray.forEach(option => option.classList.add('s-search-hidden'));
    // Show sorted text, then original order for other types.
    const sortedVisible = [...textOptions, ...numberOptions, ...dateOptions, ...emailOptions, ...entityOptions, ...otherOptions];
    sortedVisible.forEach(option => {
      option.classList.remove('s-search-hidden');
    });

    // Count only non-hidden options (exclude items already selected via s-hidden).
    const visibleCount = sortedVisible.filter(opt => !opt.classList.contains('s-hidden')).length;
    const totalCount = optionsArray.filter(opt => !opt.classList.contains('s-hidden')).length;

    // Show/hide empty state message.
    const noResults = container.querySelector('.selectify-no-results');
    if (noResults) {
      noResults.classList.toggle('s-search-hidden', visibleCount > 0 || !normalizedSearch);
    }

    // Update search status for screen readers (aria-live region).
    Drupal.selectify.announceSearchStatus(container, visibleCount, totalCount, normalizedSearch);
  };
  /**
   * Announces search/filter status to screen readers.
   *
   * Debounced to prevent announcement spam during rapid typing.
   * Uses an aria-live region to announce how many results are visible.
   *
   * @param {HTMLElement} container - The widget container.
   * @param {number} visibleCount - Number of visible (filtered) options.
   * @param {number} totalCount - Total number of available (non-selected) options.
   * @param {string} searchTerm - The current normalized search term.
   */
  Drupal.selectify.announceSearchStatus = (function() {
    let statusTimer = null;

    return function(container, visibleCount, totalCount, searchTerm) {
      const statusRegion = container.querySelector('.selectify-search-status');
      if (!statusRegion) return;

      if (statusTimer) {
        clearTimeout(statusTimer);
      }

      const constants = Drupal.selectify?.constants || {};
      const debounceDelay = constants.ANNOUNCEMENT_DEBOUNCE || 500;
      statusTimer = setTimeout(() => {
        if (!searchTerm) {
          statusRegion.textContent = '';
        } else {
          statusRegion.textContent = Drupal.t('Showing @visible of @total options', {
            '@visible': visibleCount,
            '@total': totalCount
          });
        }
      }, debounceDelay);
    };
  })();

  /**
   * Resets the search filter by clearing the input and removing s-search-hidden.
   *
   * @param {HTMLElement} container - The container with filterable options.
   * @param {HTMLElement|null} searchInput - The search input element.
   */
  Drupal.selectify.resetSearchFilter = function(container, searchInput) {
    if (searchInput) {
      searchInput.value = '';
    }
    container.querySelectorAll('.selectify-available-one-option.s-search-hidden').forEach(option => {
      option.classList.remove('s-search-hidden');
    });
    // Hide empty state and clear search status on reset.
    const noResults = container.querySelector('.selectify-no-results');
    if (noResults) {
      noResults.classList.add('s-search-hidden');
    }
    const statusRegion = container.querySelector('.selectify-search-status');
    if (statusRegion) {
      statusRegion.textContent = '';
    }
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
