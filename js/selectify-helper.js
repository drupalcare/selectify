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
  Drupal.selectify.handleSelectionLimit = function (type, selectifySelect, nativeSelect, maxSelections, itemSelector) {
    if (!Number.isInteger(maxSelections) || maxSelections <= 0) {
      return;
    }

    if (!nativeSelect) {
      return;
    }

    let selectedDisplay = type === 'dual'
      ? selectifySelect.querySelector('.selectify-selected-display-dual')
      : selectifySelect.querySelector('.selectify-selected-display');

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
        item.addEventListener('change', function () {
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
          clearAllButton.classList.toggle('hidden', selectedValues.length === 0);

          // Sync hidden select field & update display
          Drupal.selectify.syncHiddenSelect(nativeSelect, selectedValues);
          Drupal.selectify.updateSelectedDisplay(type, nativeSelect, selectedDisplay, clearAllButton);
        });
      });
    }
    else {
      items.forEach((item) => {
        item.addEventListener('click', function (event) {
          event.preventDefault();
          event.stopPropagation();

          let selectedItems = [...items].filter(i => i.classList.contains('selected'));
          selectedCount = selectedItems.length;

          if (!item.classList.contains('selected')) {
            if (selectedCount >= maxSelections) {
              showMaxSelectionMessage();
              return; // Stop if max selection limit reached.
            }
            item.classList.add('selected', 'hidden');
          } else {
            item.classList.remove('selected', 'hidden');
          }

          // Update selected items list.
          selectedItems = [...items].filter(i => i.classList.contains('selected'));
          selectedCount = selectedItems.length;
          const selectedValues = selectedItems.map(i => i.getAttribute('data-value') || i.textContent.trim());

          // Sync hidden select field.
          nativeSelect.querySelectorAll('option').forEach(option => {
            option.selected = selectedValues.includes(option.value);
          });

          if (clearAllButton) {
            clearAllButton.classList.toggle('hidden', selectedValues.length === 0);
          }

          Drupal.selectify.syncHiddenSelect(nativeSelect, selectedValues);
          Drupal.selectify.updateSelectedDisplay(type, nativeSelect, selectedDisplay, clearAllButton);

          // Ensure the select field updates before dispatching the event.
          setTimeout(() => {
            nativeSelect.dispatchEvent(new Event('change', { bubbles: true }));
            nativeSelect.dispatchEvent(new Event('input', { bubbles: true }));
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

    // console.log("Syncing hidden select:", selectedValues);

    nativeSelect.querySelectorAll("option").forEach(option => {
        if (option.value === "_none") {
            option.selected = false;
            option.removeAttribute("selected");
            option.classList.remove("selected");
        } else {
            if (selectedValues.includes(option.value)) {
                option.selected = true;
                option.setAttribute("selected", "selected");
                option.classList.add("selected");
            } else {
                option.selected = false;
                option.removeAttribute("selected");
                option.classList.remove("selected");

                // Fully remove the `class` attribute if it's empty
                if (!option.classList.length) {
                    option.removeAttribute("class");
                }
            }
        }
    });

    // Ensure the field updates properly
    nativeSelect.dispatchEvent(new Event("change", { bubbles: true }));
    nativeSelect.dispatchEvent(new Event("input", { bubbles: true }));
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

    // Ensure "Clear All" button visibility.
    clearAllButton.classList.toggle('hidden', selectedValues.length === 0);

    // Reattach event listeners for tag removal.
    selectedDisplay.querySelectorAll('.remove-tag').forEach(button => {
      button.addEventListener('click', function (event) {
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

    if (option) {
      // Toggle selection in the hidden <select> field.
      option.selected = !option.selected;

      // Apply or remove classes based on selection state.
      if (option.selected) {
        targetElement.classList.add("selected", "hidden");
      } else {
        targetElement.classList.remove("selected", "hidden");
      }

      // Apply selected class to the native select option.
      if (option.selected) {
        option.classList.add("selected");
      } else {
        option.classList.remove("selected");
      }
    }

    // Sync the UI
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
      optionElement.classList.remove('selected', 'hidden'); // Remove both classes correctly
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
  Drupal.selectify.handleKeyboardNavigation = (selectifySelect, dropdownMenu, selectedDisplay) => {
    // Ensure keyboard navigation event is only bound once per dropdown.
    once('selectifyMultiSelectKeyboard', selectifySelect).forEach(() => {
      selectifySelect.addEventListener('keydown', (event) => {
        const key = event.key;
        const isOpen = dropdownMenu.classList.contains('toggled');

        if ((key === 'Enter' || key === ' ') && !isOpen) {
          event.preventDefault();
          Drupal.selectify.openDropdown(dropdownMenu, selectedDisplay);
        } else if (key === 'Escape' && isOpen) {
          Drupal.selectify.closeDropdown(dropdownMenu, selectedDisplay);
        } else if (key === 'ArrowDown' || key === 'ArrowUp') {
          event.preventDefault();
          const options = dropdownMenu.querySelectorAll('.selectify-available-one-option');
          const selected = dropdownMenu.querySelector('.selected');
          let newIndex;

          if (!selected) {
            // Start at first enabled option
            newIndex = key === 'ArrowDown' ? 0 : options.length - 1;
          } else {
            let currentIndex = Array.from(options).indexOf(selected);
            do {
              newIndex = key === 'ArrowDown' ? currentIndex + 1 : currentIndex - 1;
            } while (options[newIndex] && options[newIndex].classList.contains('disabled'));
          }

          if (newIndex >= 0 && newIndex < options.length) {
            options[newIndex].focus();
          }
        }
      });
    });
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
