/**
 * @file
 * Search/filter: normalize text, filter options, reset filter, announce search status.
 * Loaded after selectify-helper.js (uses Drupal.selectify namespace and constants).
 *
 * Filename: selectify-search.js
 * Website: https://www.flashwebcenter.com
 * Developer: Alaa Haddad https://www.alaahaddad.com
 */
((Drupal) => {
  'use strict';
  Drupal.selectify = Drupal.selectify || {};

  /**
   * Normalizes text for diacritics-tolerant, case-insensitive comparison.
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
   * @param {HTMLElement} container - The widget container to search within.
   * @param {string} searchTerm - The search term to filter by.
   */
  Drupal.selectify.filterSearchOptions = function(container, searchTerm) {
    const search = typeof searchTerm === 'string' ? searchTerm : '';
    const normalizedSearch = Drupal.selectify.normalizeText(search);
    let optionsArray = Array.from(container.querySelectorAll('.selectify-available-one-option'));
    let filteredOptions = optionsArray.filter(option =>
      Drupal.selectify.normalizeText(option.textContent.trim()).includes(normalizedSearch)
    );
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
    textOptions.sort((a, b) => a.textContent.trim().localeCompare(b.textContent.trim()));
    optionsArray.forEach(option => option.classList.add('s-search-hidden'));
    const sortedVisible = [...textOptions, ...numberOptions, ...dateOptions, ...emailOptions, ...entityOptions, ...otherOptions];
    sortedVisible.forEach(option => {
      option.classList.remove('s-search-hidden');
    });

    const visibleCount = sortedVisible.filter(opt => !opt.classList.contains('s-hidden')).length;
    const totalCount = optionsArray.filter(opt => !opt.classList.contains('s-hidden')).length;

    const noResults = container.querySelector('.selectify-no-results');
    if (noResults) {
      noResults.classList.toggle('s-search-hidden', visibleCount > 0 || !normalizedSearch);
    }

    Drupal.selectify.announceSearchStatus(container, visibleCount, totalCount, normalizedSearch);
  };

  /**
   * Announces search/filter status to screen readers.
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
    const noResults = container.querySelector('.selectify-no-results');
    if (noResults) {
      noResults.classList.add('s-search-hidden');
    }
    const statusRegion = container.querySelector('.selectify-search-status');
    if (statusRegion) {
      statusRegion.textContent = '';
    }
  };
})(Drupal);
