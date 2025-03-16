# Selectify – Enhance Your Forms with Beautiful, User-Friendly Select Elements

## Overview
Selectify is a powerful Drupal module designed to enhance form 
elements, including
Radio buttons, Checkboxes, and Select lists. It provides a modern, accessible,
and user-friendly experience, improving both visual appeal and usability.

Selectify transforms default form elements into custom-styled, interactive
components without compromising accessibility. This ensures visitors can
seamlessly interact with forms, making navigation intuitive and enjoyable.

The idea for Selectify originally started as a feature within the Solo theme.
By developing Selectify as a standalone module, it became available to any
Drupal site, regardless of the theme.

## Why Use Selectify?
Forms are essential touchpoints for user interaction. Whether it's a contact
form, search filter, or dropdown, a smooth and visually appealing experience
matters. Selectify enhances aesthetics and usability, making forms more
engaging and easier to use.

## How Selectify Works
Selectify applies custom styles to:

- Radio buttons & Checkboxes (global settings at 
`admin/config/selectify/settings`).
- Select lists in content forms and Views exposed filters (granular 
widget control).
- Admin page exclusion available to keep back-end forms clean.

## Features & Configuration

### Radio & Checkbox Enhancements
- **No jQuery**: Uses vanilla JavaScript for better performance and faster
 load times.
- **7 accent colors** with 3D effects.
- **Optional admin page exclusion**.
- **Custom Radio buttons** allow deselecting a selected option.

### Select List Enhancements
Selectify offers five unique select widgets:

1. **Regular Dropdown**: Standard select list, click to select/deselect.
2. **Taggable Dropdown**: Selected options appear as removable tags.
3. **Searchable Dropdown**: Like Taggable, but with a search bar.
4. **Checkbox Dropdown**: Dropdown with checkboxes for multi-select.
5. **Dual-List Selector**: Two lists: Available & Selected, with options 
moved between.

### Additional Select Features
- **7 accent colors** with 3D effects.
- **Admin page exclusion option**.
- **Respects field settings** for selection limits.
- **Clear error messages** for over-selection.

## How to Enable Selectify

### Radio & Checkbox Elements
Navigate to `admin/config/selectify/settings` to enable/disable custom 
styling and admin exclusion.

### Select Elements
Selectify configuration covers two areas:

- **Content Editing Forms**: Manage Form Display settings allow choosing 
the widget.
- **Views Exposed Filters**: Configure at `admin/config/selectify/settings`.
  Apply globally or per-View using an accordion UI.

### Admin Page Exclusion
A checkbox allows excluding Selectify styling from admin routes.

## Accessibility & ARIA Compliance in Selectify
Selectify follows modern ARIA practices for custom selects, ensuring
accessibility for screen readers and audits.

### 1. Role & Structure Alignment
- `role="combobox"` on container.
- `aria-haspopup="listbox"`.
- `aria-expanded` toggled dynamically.
- `aria-activedescendant` tracks focus.

### 2. Single & Multi-Select Support
- `aria-multiselectable` applied dynamically.

### 3. Option Roles
- Each option has `role="option"`.
- Checkboxes use `aria-checked`.

### 4. Hidden Native Select
- Native select hidden with `aria-hidden="true"`.

### 5. Keyboard Navigation
- Supports Arrow, Enter, Escape, and Space keys.

### 6. Real-Time State Sync
- ARIA attributes sync dynamically.

### 7. Separation of Concerns
- Static ARIA handled by Twig; dynamic handled by JS.

### 8. Accessible Feedback
- Selection limits trigger `role="alert"`.
