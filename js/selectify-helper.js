/**
 * @file
 * Contains utility functions for Solo module multi-select.
 *
 * Filename: selectify-multi-select.js
 * Website: https://www.flashwebcenter.com
 * Developer: Alaa Haddad https://www.alaahaddad.com.
 */
((Drupal) => {
  'use strict';
  // Ensure Drupal.selectify namespace exists
  Drupal.selectify = Drupal.selectify || {};
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
        maxSelectionMessage.classList.remove('hide');
        maxSelectionMessage.classList.add('show');
        setTimeout(() => {
          maxSelectionMessage.classList.remove('show');
          maxSelectionMessage.classList.add('hide');
        }, 3000);
      }
    }
    if (type === 'checkbox') {
      const items = selectifySelect.querySelectorAll('.selectify-available-one-option input[type="checkbox"]');
      items.forEach((item) => {
        item.addEventListener('change', function() {
          let selectedItems = [...items].filter(i => i.checked);
          let selectedCount = selectedItems.length;
          // Prevent selection if max is reached
          if (this.checked && selectedCount > maxSelections) {
            this.checked = false;
            selectedCount--;
            return;
          }
          const selectedValues = selectedItems.map(i => i.value);
          // **Ensure unchecked checkboxes get disabled when max selection is reached**
          items.forEach(i => {
            if (!i.checked) {
              i.disabled = selectedCount >= maxSelections;
            }
          });
          if (selectedCount === maxSelections) {
            showMaxSelectionMessage();
          }
          // Toggle "Clear All" button visibility
          clearAllButton.classList.toggle('s-hidden', selectedValues.length === 0);
          // Sync hidden select field & update display
          Drupal.selectify.syncHiddenSelect(nativeSelect, selectedValues);
          Drupal.selectify.updateSelectedDisplay(type, nativeSelect, selectedDisplay, clearAllButton);
        });
      });
    } else {
      items.forEach((item) => {
        item.addEventListener('click', function(event) {
          event.preventDefault();
          event.stopPropagation();
          let selectedItems = [...items].filter(i => i.classList.contains('s-selected'));
          selectedCount = selectedItems.length;
          if (!item.classList.contains('s-selected') && selectedCount >= maxSelections) {
            showMaxSelectionMessage();
            return; // Stop if max selection limit reached.
          }
          // Determine the value and toggle the hidden select's state directly.
          const value = item.getAttribute('data-value') || item.textContent.trim();
          const option = nativeSelect.querySelector(`option[value="${value}"]`);
          if (option) {
            option.selected = !option.selected;
          }
          // After toggling, recalculate selected values (fresh from native select).
          const selectedValues = Array.from(nativeSelect.options)
            .filter(opt => opt.selected)
            .map(opt => opt.value);
          if (clearAllButton) {
            clearAllButton.classList.toggle('s-hidden', selectedValues.length === 0);
          }
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
    // Close all other open dropdowns except the one being opened.
    document.querySelectorAll('.selectify-available-display.toggled').forEach(otherDropdown => {
      if (otherDropdown !== dropdown) {
        Drupal.selectify.closeDropdown(otherDropdown);
      }
    });
    Drupal.selectify.slideDown(dropdown);
    const trigger = triggerElement || dropdown.previousElementSibling;
    if (trigger) {
      trigger.setAttribute('aria-expanded', 'true');
    }
    // Add "selectify-opened" class to the parent select element.
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
    Drupal.selectify.slideUp(dropdown);
    const trigger = triggerElement || dropdown.previousElementSibling;
    if (trigger) {
      trigger.setAttribute('aria-expanded', 'false');
      trigger.focus();
    }
    // Remove "selectify-opened" class from the parent select element.
    const parentSelect = triggerElement?.closest('.selectify-select');
    if (parentSelect) {
      parentSelect.classList.remove('selectify-opened');
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
      maxHeight = Math.min(spaceAbove - 20, 300);
      dropdownMenu.style.top = 'auto';
      dropdownMenu.style.bottom = '100%';
    } else {
      dropdownMenu.style.top = '100%';
      dropdownMenu.style.bottom = 'auto';
    }
    dropdownMenu.style.maxHeight = `${maxHeight}px`;
    dropdownMenu.style.overflowY = 'auto';
  };
  /**
   * Updates the displayed selected options.
   */
  /**
   * Updates the displayed selected options.
   *
   * @param {string} type - The type of selectify component (e.g., "tags", "searchable", "dropdown", "checkbox", "dual").
   * @param {HTMLElement} nativeSelect - The hidden <select> element.
   * @param {HTMLElement} selectedDisplay - The display area for selected options.
   * @param {HTMLElement} clearAllButton - The button to clear all selected options.
   */
  Drupal.selectify.updateSelectedDisplay = (type, nativeSelect, selectedDisplay, clearAllButton) => {
    const selectedValues = Drupal.selectify.getSelectedValues(nativeSelect);
    if (!clearAllButton || !clearAllButton.closest('.selectify-select')) {
      return;
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
    // Ensure "Clear All" button visibility.
    clearAllButton.classList.toggle('s-hidden', selectedValues.length === 0);
    // Reattach event listeners for tag removal.
    selectedDisplay.querySelectorAll('.remove-tag').forEach(button => {
      button.addEventListener('click', function(event) {
        event.preventDefault();
        event.stopPropagation();
        Drupal.selectify.removeTag(type, nativeSelect, this.parentElement, selectedDisplay, clearAllButton);
      });
    });
  };
  /**
   * Renders the selected values inside the multi-select header (for tags & searchable).
   *
   * @param {HTMLElement} nativeSelect - The hidden <select> element.
   * @param {HTMLElement} selectedDisplay - The display area for selected options.
   * @param {HTMLElement|null} placeholder - The placeholder text element.
   * @param {HTMLElement} arrowIcon - The dropdown arrow element.
   * @param {string[]} selectedValues - The selected option values.
   */
  Drupal.selectify.renderSelectifyDropdown = (nativeSelect, selectedDisplay, placeholder, arrowIcon, selectedValues) => {
    if (selectedValues.length > 0) {
      let selectedItemsHTML = '';
      selectedValues.forEach(value => {
        const option = nativeSelect.querySelector(`option[value="${value}"]`);
        if (option) {
          selectedItemsHTML += `
            <div class="selectify-selected-one-option" data-value="${value}">
              <span>${option.textContent}</span>
            </div>`;
        }
      });
      // Create the .selectify-selected-options wrapper
      const wrapperHTML = `<div class="selectify-selected-options">${selectedItemsHTML}</div>`;
      // Insert the wrapper before the arrow
      arrowIcon.insertAdjacentHTML('beforebegin', wrapperHTML);
    }
  };
  /**
   * Renders the selected values inside the multi-select header (for tags & searchable).
   *
   * @param {HTMLElement} nativeSelect - The hidden <select> element.
   * @param {HTMLElement} selectedDisplay - The display area for selected options.
   * @param {HTMLElement|null} placeholder - The placeholder text element.
   * @param {HTMLElement} arrowIcon - The dropdown arrow element.
   * @param {string[]} selectedValues - The selected option values.
   */
  Drupal.selectify.renderSelectifySearchTags = (nativeSelect, selectedDisplay, placeholder, arrowIcon, selectedValues) => {
    if (selectedValues.length > 0) {
      let selectedItemsHTML = '';
      selectedValues.forEach(value => {
        const option = nativeSelect.querySelector(`option[value="${value}"]`);
        if (option) {
          selectedItemsHTML += `
            <div class="selectify-selected-one-option" data-value="${value}">
              <span>${option.textContent}</span>
              <button class="remove-tag">
                <svg fill="var(--selectify-select-arrow-color, var(--selectify-select-color))" class="icon-delete" xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px">
                  <path d="M280-120q-33 0-56.5-23.5T200-200v-520h-40v-80h200v-40h240v40h200v80h-40v520q0 33-23.5 56.5T680-120H280Zm400-600H280v520h400v-520ZM360-280h80v-360h-80v360Zm160 0h80v-360h-80v360ZM280-720v520-520Z"/>
                </svg>
              </button>
            </div>`;
        }
      });
      // Create the .selectify-selected-options wrapper
      const wrapperHTML = `<div class="selectify-selected-options">${selectedItemsHTML}</div>`;
      // Insert the wrapper before the arrow
      arrowIcon.insertAdjacentHTML('beforebegin', wrapperHTML);
    }
  };
  /**
   * Renders the selected values inside the multi-select header (for checkbox-based selects).
   *
   * @param {HTMLElement} nativeSelect - The hidden <select> element.
   * @param {HTMLElement} selectedDisplay - The display area for selected options.
   * @param {HTMLElement} arrowIcon - The dropdown arrow element.
   * @param {string[]} selectedValues - The selected option values.
   */
  Drupal.selectify.renderSelectifyDropdownCheckbox = (nativeSelect, selectedDisplay, arrowIcon, selectedValues) => {
    let dropdownHTML = '';
    if (selectedValues.length > 0) {
      dropdownHTML += `<div class="selectify-selected-options">`;
      selectedValues.forEach(value => {
        const option = nativeSelect.querySelector(`option[value="${value}"]`);
        if (option) {
          dropdownHTML += `<span class="selectify-selected-one-option" data-value="${value}">${option.textContent}</span>`;
        }
      });
      dropdownHTML += `</div>`; // Close .selectify-selected-options div
    }
    // Append the dropdown arrow (expansion icon)
    dropdownHTML += `<span class="selectify-dorpdown-arrow">${arrowIcon.innerHTML}</span>`;
    // Apply the new HTML inside `selectedDisplay`
    selectedDisplay.innerHTML = dropdownHTML;
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
    selectedDisplay.innerHTML = ''; // Clear previous content before re-rendering.
    if (selectedValues.length > 0) {
      let dropdownHTML = `<div class="selectify-selected-options dual-inner">`;
      selectedValues.forEach(value => {
        const option = nativeSelect.querySelector(`option[value="${value}"]`);
        if (option) {
          dropdownHTML += `
            <div class="selectify-selected-one-option dual-item" data-value="${value}">
              <label>${option.textContent}</label>
              <span class="dual-selected-arrow">
                <svg fill="var(--selectify-select-arrow-color, var(--selectify-select-color))" class="arrow-circle-left" xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px">
                  <path d="m480-320 56-56-64-64h168v-80H472l64-64-56-56-160 160 160 160Zm0 240q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/>
                </svg>
              </span>
            </div>`;
        }
      });
      dropdownHTML += `</div>`; // Close .selectify-selected-options
      selectedDisplay.innerHTML = dropdownHTML;
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
      clearAllButton.classList.add('s-hidden');
      return;
    }
    // Normal option toggle.
    option.selected = !option.selected;
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
    if (!tagElement) return;
    const value = tagElement.dataset.value;
    const option = nativeSelect.querySelector(`option[value="${value}"]`);
    if (option) {
      option.selected = false;
    }
    const dropdownMenu = selectedDisplay.closest('.selectify-select').querySelector('.selectify-available-display');
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
        selectedValues = ['_none']; // Return _none so the field isn't completely empty.
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
          // Focus the first selectable option when opening.
          const firstOption = options[0];
          if (firstOption) {
            firstOption.focus();
            selectifySelect.setAttribute('aria-activedescendant', firstOption.id);
            Drupal.selectify.updateOptionState(firstOption, true, type);
          }
        } else if (key === 'Escape' && isOpen) {
          Drupal.selectify.closeDropdown(dropdownMenu, selectedDisplay);
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
            newFocusedOption.focus();
            // Update activedescendant and aria-selected
            selectifySelect.setAttribute('aria-activedescendant', newFocusedOption.id);
            options.forEach((option, index) => {
              Drupal.selectify.updateOptionState(option, index === newIndex, type);
            });
          }
        }
      });
    });
  };
  /**
   * Updates selected state and applies "hidden" (for types like tags that remove selected items).
   */
  Drupal.selectify.updateOptionState = (optionElement, isSelected, type) => {
    if (!optionElement) {
      return;
    }
    const value = optionElement.dataset.value;
    const isNoneOrAll = (value === '_none' || value === 'All');
    const isExposedFilter = optionElement.closest('.views-exposed-form') !== null;
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
   * Ensures dropdown close event is only bound once.
   */
  once('selectifyMultiSelectCloseDropdown', document).forEach(() => {
    document.addEventListener('click', (event) => {
      if (!event.target.closest('.selectify-available-display') && !event.target.closest('.selectify-selected-display')) {
        document.querySelectorAll('.selectify-available-display.toggled').forEach(dropdown => {
          Drupal.selectify.closeDropdown(dropdown);
        });
      }
    });
  });
})(Drupal);
