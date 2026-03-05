/**
 * @file
 * Accessibility: keyboard navigation, option state, aria-labelledby.
 * Loaded after selectify-helper.js (uses openDropdown, closeDropdown).
 *
 * Filename: selectify-accessibility.js
 * Website: https://www.flashwebcenter.com
 * Developer: Alaa Haddad https://www.alaahaddad.com
 */
((Drupal, once) => {
  'use strict';
  Drupal.selectify = Drupal.selectify || {};

  /**
   * Handles keyboard navigation for multi-select components.
   *
   * @param {HTMLElement} selectifySelect - The main Selectify wrapper element.
   * @param {HTMLElement} dropdownMenu - The dropdown menu element.
   * @param {HTMLElement} selectedDisplay - The display area for selected options.
   * @param {string} type - The type of selectify component.
   */
  Drupal.selectify.handleKeyboardNavigation = (selectifySelect, dropdownMenu, selectedDisplay, type) => {
    once('selectifyMultiSelectKeyboard', selectifySelect).forEach(() => {
      selectifySelect.addEventListener('keydown', (event) => {
        const key = event.key;
        const isOpen = dropdownMenu.classList.contains('toggled');
        const options = dropdownMenu.querySelectorAll('.selectify-available-one-option');
        if ((key === 'Enter' || key === ' ') && !isOpen) {
          event.preventDefault();
          Drupal.selectify.openDropdown(dropdownMenu, selectedDisplay);

          setTimeout(() => {
            const firstOption = options[0];
            if (firstOption) {
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
            newIndex = key === 'ArrowDown' ? 0 : options.length - 1;
          } else {
            const currentIndex = Array.from(options).indexOf(currentFocused);
            newIndex = key === 'ArrowDown' ? currentIndex + 1 : currentIndex - 1;
          }
          if (newIndex >= 0 && newIndex < options.length) {
            const newFocusedOption = options[newIndex];
            options.forEach(opt => opt.setAttribute('tabindex', '-1'));
            newFocusedOption.setAttribute('tabindex', '0');
            newFocusedOption.focus();
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

    if (document.activeElement === optionElement || optionElement.contains(document.activeElement)) {
      const selectWidget = optionElement.closest('.selectify-select');
      if (selectWidget && optionElement.id) {
        selectWidget.setAttribute('aria-activedescendant', optionElement.id);
      }
    }

    if (type === 'checkbox') {
      const checkbox = optionElement.querySelector('input[type="checkbox"]');
      if (checkbox) {
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
})(Drupal, once);
