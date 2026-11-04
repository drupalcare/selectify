/**
 * @file
 * Contains utility functions for Selectify module multi-select dual list.
 *
 * Filename: selectify-dual.js
 * Website: https://www.flashwebcenter.com
 * Developer: Alaa Haddad https://www.alaahaddad.com.
 */
((Drupal, once, drupalSettings) => {
  'use strict';
  const type = 'dual';
  /**
   * Drupal behavior for custom multi-select dual lists.
   */
  Drupal.behaviors.selectifySelectDual = {
    attach: (context) => {
      once('selectifyMultiSelectDual', '.selectify-select-dual-list', context).forEach((selectifySelect) => {
        const constants = Drupal.selectify?.constants || {};
        const initDelay = constants.INIT_DELAY || 10;
        setTimeout(() => {
          // Dual uses .dual-available for the options panel (not .selectify-available-display).
          Drupal.selectify.bootstrapWidget(selectifySelect, 'Dual', type, {
            initFn: (sel, native) => initializeSelectifyDual(sel, native),
            dropdownPanelSelector: '.dual-available',
          });
        }, initDelay);
      });
    }
  };
  /**
   * Initializes the dual list multi-select.
   *
   * @param {HTMLElement} selectifySelect - The Selectify wrapper element.
   * @param {HTMLElement} nativeSelect - The hidden native <select> element.
   */
  function initializeSelectifyDual(selectifySelect, nativeSelect) {
    const selectedDisplay = selectifySelect.querySelector('.dual-selected');
    const dropdownMenu = selectifySelect.querySelector('.dual-available');
    const options = selectifySelect.querySelectorAll('.dual-available-option');
    const clearAllButton = selectifySelect.querySelector('.dual-clear-all');
    const searchInput = selectifySelect.querySelector('.dual-search-filter');
    if (!nativeSelect || nativeSelect.tagName !== 'SELECT') {

      return;
    }
    if (!selectedDisplay || !dropdownMenu || options.length === 0) {
      return;
    }
    // Handle search filtering with debounce for performance.
    if (searchInput) {
      const constants = Drupal.selectify?.constants || {};
      const debounceDelay = constants.SEARCH_DEBOUNCE || 150;
      let searchTimer = null;
      searchInput.addEventListener('input', function() {
        const searchTerm = this.value;
        if (searchTimer) {
          clearTimeout(searchTimer);
        }
        searchTimer = setTimeout(() => {
          Drupal.selectify.filterSearchOptions(selectifySelect, searchTerm);
        }, debounceDelay);
      });
    }
    // Click event to select an item.
    once('selectifyDualEvents', dropdownMenu).forEach(() => {
      dropdownMenu.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();
        if (event.target.classList.contains('selectify-available-one-option')) {
          animateDualMove(selectifySelect, () => {
            Drupal.selectify.toggleMultiSelectOption(type, nativeSelect, event.target, selectedDisplay, clearAllButton);
          });
        }
      });
    });
    // Click event to remove selected items.
    selectedDisplay.addEventListener('click', (event) => {
      if (event.target.classList.contains('selectify-selected-one-option')) {
        event.preventDefault();
        event.stopPropagation();

        // Check if field is required and single-value
        const isRequired = nativeSelect.hasAttribute('required');
        const isMultiple = nativeSelect.getAttribute('data-multiple') === 'true';
        const selectedCount = selectedDisplay.querySelectorAll('.selectify-selected-one-option').length;

        // For required single-value fields, prevent removing the only selected item
        if (isRequired && !isMultiple && selectedCount === 1) {
          return; // Don't allow removal
        }

        const selectedItem = event.target;
        const valueToRemove = selectedItem.getAttribute('data-value');
        animateDualMove(selectifySelect, () => {
          selectedItem.remove();
          const correspondingOption = dropdownMenu.querySelector(`.selectify-available-one-option[data-value="${valueToRemove}"]`);
          if (correspondingOption) {
            correspondingOption.classList.remove('s-hidden', 's-selected');
          }
          const selectOption = nativeSelect.querySelector(`option[value="${valueToRemove}"]`);
          if (selectOption) {
            selectOption.selected = false;
          }
          // Collect all current selected values after the removal.
          const selectedValues = [...selectedDisplay.querySelectorAll('.selectify-selected-one-option')].map(item => item.getAttribute('data-value'));
          Drupal.selectify.syncHiddenSelect(nativeSelect, selectedValues);
          Drupal.selectify.updateSelectedDisplay(type, nativeSelect, selectedDisplay, clearAllButton);
        });
        nativeSelect.dispatchEvent(new Event('change'));
      }
    });
    // "Clear All" Button Logic (dual-specific: remove selected items from right panel, reset available panel, reset search).
    if (clearAllButton) {
      once('clearAllDual', clearAllButton).forEach(() => {
        clearAllButton.addEventListener('click', () => {
          Drupal.selectify.executeClear(type, selectifySelect, nativeSelect, selectedDisplay, clearAllButton, dropdownMenu, {
            onDeselectAll: (menu, selDisplay) => {
              selDisplay.querySelectorAll('.selectify-selected-one-option').forEach((item) => item.remove());
              menu.querySelectorAll('.selectify-available-one-option.s-selected').forEach((opt) => opt.classList.remove('s-selected'));
              menu.querySelectorAll('.selectify-available-one-option.s-hidden').forEach((opt) => opt.classList.remove('s-hidden'));
              Drupal.selectify.resetSearchFilter(menu, searchInput);
            },
            focusTarget: (widget) => widget.querySelector('.dual-available'),
          });
        });
      });
    }
    // Enable keyboard navigation.
    Drupal.selectify.handleKeyboardNavigation(selectifySelect, dropdownMenu, selectedDisplay, type);
    const selectedValues = Drupal.selectify.getSelectedValues(nativeSelect);
    Drupal.selectify.syncHiddenSelect(nativeSelect, selectedValues);
    Drupal.selectify.updateSelectedDisplay(type, nativeSelect, selectedDisplay, clearAllButton);
  }

  /**
   * Moves an option between the two lists with a calm slide (FLIP).
   *
   * The selected list is rebuilt on every change and the chosen option hides
   * at once, so without this both lists jump. The positions of every visible
   * row are read before and after the change: rows that stay on their side
   * glide from the old place to the new one, and the moved row fades in on
   * its new side with a short drift from the direction it came from. No
   * motion when the visitor asks for reduced motion.
   *
   * @param {HTMLElement} widget
   *   The dual-list wrapper.
   * @param {function} mutate
   *   Applies the change to the lists.
   */
  function animateDualMove(widget, mutate) {
    const reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (reduce || typeof Element.prototype.animate !== 'function') {
      mutate();
      return;
    }
    const before = snapshotRows(widget);
    mutate();
    const after = snapshotRows(widget);
    const easing = 'cubic-bezier(0.22, 1, 0.36, 1)';
    after.forEach(({ el, rect }, key) => {
      const old = before.get(key);
      if (old) {
        const dx = old.rect.left - rect.left;
        const dy = old.rect.top - rect.top;
        if (Math.abs(dx) > 0.5 || Math.abs(dy) > 0.5) {
          el.animate(
            [{ transform: `translate(${dx}px, ${dy}px)` }, { transform: 'none' }],
            { duration: 240, easing }
          );
        }
        return;
      }
      // New on this side: drift in from the list it left.
      const fromStart = key.startsWith('R:') ? -12 : 12;
      el.animate(
        [{ opacity: 0, transform: `translateX(${fromStart}px)` }, { opacity: 1, transform: 'none' }],
        { duration: 260, easing }
      );
    });
  }

  /**
   * Records the on-screen box of every visible row in both lists.
   *
   * @param {HTMLElement} widget
   *   The dual-list wrapper.
   *
   * @return {Map<string, {el: HTMLElement, rect: DOMRect}>}
   *   Keyed by side ("L" available, "R" selected) and option value.
   */
  function snapshotRows(widget) {
    const rows = new Map();
    widget.querySelectorAll('.dual-available .selectify-available-one-option, .dual-selected .selectify-selected-one-option').forEach((el) => {
      if (el.getClientRects().length === 0) {
        return;
      }
      const side = el.classList.contains('selectify-selected-one-option') ? 'R' : 'L';
      rows.set(`${side}:${el.dataset.value}`, { el, rect: el.getBoundingClientRect() });
    });
    return rows;
  }
})(Drupal, once, drupalSettings);
