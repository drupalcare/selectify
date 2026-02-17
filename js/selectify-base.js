/**
 * @file
 * Contains utility functions for Selectify module.
 *
 * Filename: selectify-base.js
 * Website: https://www.flashwebcenter.com
 * Developer: Alaa Haddad https://www.alaahaddad.com.
 */
((Drupal, drupalSettings, once) => {
  'use strict';
  /**
   * selectify helper functions.
   *
   * @namespace
   */
  Drupal.selectify = Drupal.selectify || {};
  const cssStyles = {
    overflow: 'hidden',
    height: '0',
    paddingTop: '0',
    paddingBottom: '0',
    marginTop: '0',
    marginBottom: '0'
  };
  const removeStyles = (target) => {
    const stylesToRemove = ['height', 'paddingTop', 'paddingBottom', 'marginTop', 'marginBottom', 'overflow', 'transitionDuration', 'transitionProperty', 'transitionTimingFunction', 'boxSizing'];
    stylesToRemove.forEach(st => { target.style[st] = ''; });
  }
  Drupal.selectify.removeStyles = removeStyles;
  const slideUp = (target, duration = 300) => {
    const trigger = target.previousElementSibling;
    target.style.transitionProperty = 'height, margin, padding';
    target.style.transitionDuration = `${duration}ms`;
    target.style.transitionTimingFunction = 'ease-in-out';
    target.style.boxSizing = 'border-box';
    target.style.height = `${target.offsetHeight}px`;
    target.offsetHeight; // Trigger reflow
    target.classList.remove('toggled');
    target.setAttribute('aria-hidden', 'true');
    if (trigger) {
      trigger.setAttribute('aria-expanded', 'false');
      setTimeout(() => trigger.focus(), duration);
    }
    Object.keys(cssStyles).forEach(style => {
      target.style[style] = cssStyles[style];
    });
    setTimeout(() => {
      target.style.display = 'none';
      removeStyles(target);
    }, duration);
  };
  Drupal.selectify.slideUp = slideUp;
  const slideDown = (target, menuDisplay = 'block', duration = 300) => {
    const trigger = target.previousElementSibling;
    target.style.removeProperty('display');
    let currentDisplay = window.getComputedStyle(target).display;
    if (currentDisplay === 'none') {
      target.style.display = menuDisplay;
    }
    let height = target.offsetHeight;
    height = Math.round(height);
    Object.keys(cssStyles).forEach(style => {
      target.style[style] = cssStyles[style];
    });
    target.offsetHeight; // Trigger reflow
    target.style.boxSizing = 'border-box';
    target.style.transitionProperty = 'height, margin, padding';
    target.style.transitionDuration = `${duration}ms`;
    target.style.transitionTimingFunction = 'ease-in-out';
    target.style.height = `${height}px`;
    target.classList.add('toggled');
    target.setAttribute('aria-hidden', 'false');
    if (trigger) {
      trigger.setAttribute('aria-expanded', 'true');
    }
    ['paddingTop', 'paddingBottom', 'marginTop', 'marginBottom'].forEach(property => {
      target.style[property] = '';
    });
    setTimeout(() => {
      ['height', 'overflow', 'transitionDuration', 'transitionProperty', 'transitionTimingFunction', 'boxSizing'].forEach(property => {
        target.style[property] = '';
      });
    }, duration);
  };
  Drupal.selectify.slideDown = slideDown;
})(Drupal, drupalSettings, once);
