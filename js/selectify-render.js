/**
 * @file
 * Render and display: updateSelectedDisplay, announceSelectionChange, and the four render* functions.
 * Loaded after selectify-helper.js and selectify-accessibility.js.
 *
 * Filename: selectify-render.js
 * Website: https://www.flashwebcenter.com
 * Developer: Alaa Haddad https://www.alaahaddad.com
 */
((Drupal) => {
  'use strict';
  Drupal.selectify = Drupal.selectify || {};

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

    const selectWidget = selectedDisplay.closest('.selectify-select');
    if (selectWidget) {
      selectWidget.setAttribute('aria-busy', 'true');
    }
    if (type !== 'dual') {
      const existingWrapper = selectedDisplay.querySelector('.selectify-selected-options');
      if (existingWrapper) {
        existingWrapper.remove();
      }
    }
    if (type === 'tags' || type === 'searchable') {
      const placeholder = selectedDisplay.querySelector('.placeholder-text');
      const arrowIcon = selectedDisplay.querySelector('.selectify-dorpdown-arrow');
      Drupal.selectify.renderSelectifySearchTags(nativeSelect, selectedDisplay, placeholder, arrowIcon, selectedValues);
    }
    if (type === 'dropdown') {
      const placeholder = selectedDisplay.querySelector('.placeholder-text');
      const arrowIcon = selectedDisplay.querySelector('.selectify-dorpdown-arrow');
      Drupal.selectify.renderSelectifyDropdown(nativeSelect, selectedDisplay, placeholder, arrowIcon, selectedValues);
    }
    if (type === 'checkbox') {
      const placeholder = selectedDisplay.querySelector('.placeholder-text');
      const arrowIcon = selectedDisplay.querySelector('.selectify-dorpdown-arrow');
      Drupal.selectify.renderSelectifyDropdownCheckbox(nativeSelect, selectedDisplay, arrowIcon, selectedValues);
    }
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
    if (selectWidget) {
      const hasRealSelections = selectedValues.filter(v => v !== '_none' && v !== 'All' && v !== '').length > 0;
      selectWidget.classList.toggle('has-selections', hasRealSelections);
    }
    if (selectWidget) {
      Drupal.selectify.announceSelectionChange(selectWidget, selectedValues.length);
      selectWidget.setAttribute('aria-busy', 'false');
    }
  };

  /**
   * Announces selection changes to screen readers (debounced).
   *
   * @param {HTMLElement} selectWidget - The main Selectify widget element.
   * @param {number} count - Number of selected items.
   */
  Drupal.selectify.announceSelectionChange = (function() {
    let announcementTimer = null;

    return function(selectWidget, count) {
      const liveRegion = selectWidget.querySelector('[role="status"][aria-live="polite"]');
      if (!liveRegion) return;

      if (announcementTimer) {
        clearTimeout(announcementTimer);
      }

      const constants = Drupal.selectify?.constants || {};
      const debounceDelay = constants.ANNOUNCEMENT_DEBOUNCE || 500;
      announcementTimer = setTimeout(() => {
        liveRegion.textContent = Drupal.t('@count selected', {'@count': count});
      }, debounceDelay);
    };
  })();

  /**
   * Renders the selected values inside the multi-select header (dropdown).
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

          const optionLabel = document.createElement('span');
          optionLabel.className = 'option-label';
          optionLabel.textContent = option.textContent;

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
          itemDiv.appendChild(optionLabel);
          itemDiv.appendChild(arrowSpan);
          wrapper.appendChild(itemDiv);
        }
      });

      selectedDisplay.appendChild(wrapper);
    }
  };
})(Drupal);
