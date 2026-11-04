# Selectify – Transform Drupal Forms with Modern, Accessible Components

## Overview

Selectify is a comprehensive Drupal module that revolutionizes form
interactions by transforming standard HTML form elements into modern,
accessible, and highly customizable components. Whether you're working with
Radio buttons, Checkboxes, or Select lists, Selectify provides elegant
solutions that improve both visual appeal and usability without compromising
accessibility or functionality.

This module transforms default form elements into custom-styled, interactive
components that serve users effectively. Forms become more intuitive,
selections become clearer, and the overall user experience improves
dramatically while maintaining full WCAG 2.1 Level AA accessibility
compliance.

The idea for Selectify originally started as a feature within the Solo theme
(https://www.drupal.org/project/solo). By evolving it into a standalone
module, Selectify became available to any Drupal site, regardless of theme,
making professional-grade form enhancements accessible to the entire Drupal
community.

**Live Demo:** https://unitedstarsofamerica.com/selectify

## Why Choose Selectify?

Forms are critical touchpoints for user interaction on any website. A
polished, intuitive form experience significantly impacts user satisfaction,
completion rates, and overall site perception. Selectify addresses common
form usability challenges by providing:

- **Modern Visual Design** – Replace browser-default form elements with
  professionally styled components
- **Improved usability** – Intuitive interactions that make complex
  selections simple and enjoyable
- **Full Accessibility** – WCAG-compliant with comprehensive ARIA support
  and keyboard navigation
- **Zero jQuery Dependency** – Built with vanilla JavaScript for optimal
  performance and faster page loads
- **Flexible Configuration** – Granular control from global settings to
  per-field customization
- **Theme Integration** – Automatic theming support with CSS variables for
  easy customization
- **Drupal 10 & 11 Ready** – Follows current best practices with strict
  typing and modern PHP standards

## How Selectify Works

**In short:** Selectify styles **select** elements in four places—Manage Form
Display (field widgets), Views exposed filters, Webform submission forms, and
any other Form API select. It styles **radio buttons and checkboxes** globally.
For both selects and radios/checkboxes you can disable Selectify on any page
using path patterns (e.g. `admin/config/*`, `node/*/edit`).

Selectify applies beautiful, functional enhancements to:

- **Radio buttons and Checkboxes** – Global styling with configuration at
  `/admin/config/selectify/settings`
- **Field Widgets** – Five specialized widgets for entity fields configured
  through Manage Form Display
- **Views Exposed Filters** – Site-wide or per-View configuration for exposed
  select filters
- **Form API Selects** – Per-form configuration for plain Drupal forms with
  automatic form discovery
- **Webform Elements** – Site-wide or per-Webform configuration for Webform
  submission forms (requires selectify_webform submodule)
- **Disable on any page** – "Additional pages to disable Selectify" (path
  patterns) disables all Selectify features on matching pages—selects, radios,
  and checkboxes
- **Admin page exclusion** – Optional disabling on administrative routes to
  preserve backend familiarity

## Features & Configuration

### Radio & Checkbox Enhancements

Transform traditional radio buttons and checkboxes with extensive styling
options:

**Visual Styles:**
- **Toggle Switch** – Modern sliding switch design for contemporary
  interfaces
- **Traditional with Indicators** – Checkbox/radio with X/Checkmark
  feedback

**Shape Options:**
- **Circle** – Classic round shape maintaining familiar conventions
- **Square** – Contemporary rectangular design for modern aesthetics

**Size Variants:**
- **Small** – Compact design ideal for dense forms
- **Medium** – Recommended default for most use cases
- **Large** – Bigger targets for accessibility and touch interfaces

**Advanced Features:**
- **No jQuery** – Vanilla JavaScript for better performance and faster load
  times
- **Deselectable Radios** – Unlike standard HTML, radio buttons can be
  deselected by clicking again
- **7 Accent Colors** – Professional color themes with 3D depth effects
- **Light & Dark Modes** – Optimized for both light and dark theme contexts
- **State Management** – Real-time visual feedback for checked, focused,
  hover, and disabled states
- **Indeterminate Support** – Full support for indeterminate checkbox state
- **Group Detection** – Automatically identifies and manages radio/checkbox
  groups
- **Admin Page Exclusion** – Optional disabling on administrative routes

### Select List Enhancements

Selectify offers **five specialized select widgets**, each designed to solve
specific usability challenges:

#### 1. Regular Dropdown

Standard dropdown with modern styling and smooth animations:
- Click to open/close dropdown menu
- Single or multi-select support
- Displays selected option(s) in the trigger
- Smooth expand/collapse animations
- Clear visual feedback for selected items
- "Clear All" button for quick deselection

#### 2. Taggable Dropdown

Transform selections into removable tags for improved multi-select
visualization:
- Selected options display as individual removable tags
- Each tag includes a close button (✕) for individual removal
- Tags appear in the selected display area above dropdown
- Selected options automatically hide from dropdown to prevent duplicates
- Keyboard-accessible tag removal (Enter/Space key support)
- Dynamic tag creation with real-time updates
- Event delegation for efficient tag removal handling

#### 3. Searchable Dropdown

Combines tagging with powerful search/filter capabilities:

**Search Features:**
- Real-time filtering as you type with instant visual feedback
- Search input automatically focuses when dropdown opens
- Intelligent multi-type detection and categorization:
  - Text options – Alphabetically sorted for easy scanning
  - Number options – Preserved in original order
  - Email options – Auto-detected by @ and domain patterns
  - Date options – Identified by date format (YYYY-MM-DD)
  - Entity options – Recognized patterns (Node:, User:, Term:)
- Smart sorting maintains logical order for different data types
- All features of Taggable Dropdown plus search functionality

#### 4. Checkbox Dropdown

Dropdown interface with explicit checkbox controls:
- Each option includes a visible checkbox for clear selection state
- Checkboxes with `aria-checked` for accessibility
- Visual distinction between checked and unchecked states
- Disabled state management when selection limits reached
- Options remain visible when selected (unlike tag variants)
- Ideal for users who prefer explicit selection indicators
- Full keyboard support for checkbox interaction

#### 5. Dual-List Selector

Side-by-side list interface for intuitive multi-select operations:

**Layout:**
- Available List (Left) – Shows unselected options with right arrow
  indicators (→)
- Selected List (Right) – Displays chosen options with left arrow for
  removal (←)
- Responsive side-by-side layout optimized for various screen sizes

**Interaction Model:**
- Add Items – Click any available option to move to selected list
- Remove Items – Click selected items to return to available list
- Clear All – Single button to deselect all items at once
- Required Field Protection – Prevents removing last item from required
  single-value fields
- Skip Link – Accessibility feature for keyboard users to jump directly to
  selected options

**Visual Features:**
- Both lists implemented as `role="listbox"` with ARIA labels
- Clear visual separation between available and selected lists
- Arrow indicators showing movement direction
- Consistent styling with other Selectify widgets

### Selection Limit Management

Selectify provides intelligent handling of maximum selection limits across
all widgets:

**Limit Enforcement:**
- Automatically reads `max_selected` from field configuration
- Single-Select Behavior (max=1) – Special handling that replaces selection
  instead of preventing it
- Multi-Select Limits (max>1) – Prevents over-selection with clear user
  feedback
- Checkbox Widget Disabling – Unchecked options become disabled when limit
  reached with `aria-disabled="true"` and helpful title attributes

**User Feedback:**
- Alert messages with `role="alert"` for screen reader announcement
- Smooth show/hide animations that auto-dismiss after 3 seconds
- Debounced announcements (500ms delay) prevent announcement spam
- Clear message: "You can only select up to X options"

### Color Theme System

Choose from 7 professional accent colors with independent color
control for admin and front-end:

**Color Palette:**
- **Neutral** – Professional grayscale for universal appeal
- **Slate** – Cool, modern blue-gray tones
- **Blue** – Classic, trustworthy corporate blue
- **Indigo** – Deep, sophisticated purple-blue
- **Teal** – Fresh, modern blue-green
- **Gold** – Warm, prestigious yellow tones
- **Coral** – Bright, friendly orange-pink

**Color Scopes:**
- **Admin** – Controls Selectify colors on admin pages
  (`admin_accent_color`, `admin_accent_color_mode`)
- **Front-end** – Controls Selectify colors on public-facing pages
  (`accent_color`, `accent_color_mode`)

Each scope is detected automatically via Drupal's admin-route
context, so admin and front-end can use completely different
palettes and modes without additional configuration.

**Mode Options (per scope):**
- **Light** – Optimized for standard light backgrounds with 3D
  depth effects
- **Dark** – Designed for dark themes with inverted 3D effects
  and proper contrast
- **None** – Strips Selectify colors so form elements inherit
  the theme's native styling

**Additional Features:**
- Respects field settings for selection limits
- Admin page exclusion option
- Clear error messages for over-selection
- Theme-specific CSS classes automatically added
- Comprehensive CSS custom properties for easy customization

## Configuration & Setup

### Global Settings (/admin/config/selectify/settings)

Navigate to the Selectify settings page to configure:

**1. Page Control Settings:**
- Disable on admin routes checkbox
- Custom disabled pages with wildcard pattern matching:
  - `admin/appearance/settings/gin` – Exact path match
  - `admin/appearance/settings/*` – All theme settings pages
  - `*/node/*/edit` – All node edit pages
  - `admin/config/*` – All configuration pages

**2. Radio Button Styling:**
- Enable/disable toggle
- Style selection (Toggle Switch / Traditional with Indicators)
- Shape selection (Circle / Square)
- Size selection (Small / Medium / Large)
- Settings dynamically show/hide based on enable state

**3. Checkbox Styling:**
- Enable/disable toggle
- Style selection (Toggle Switch / Traditional with Indicators)
- Shape selection (Circle / Square)
- Size selection (Small / Medium / Large)
- Settings dynamically show/hide based on enable state

**4. Front-end Color Theme:**
- 7 accent color options with descriptions
- Light / Dark / None mode selection
- Applies to radio/checkbox and select widgets on public pages
- 3D effects automatically adjust for selected mode

**4b. Admin Color Theme:**
- Same 7 accent color options, configured independently
- Light / Dark / None mode selection
- Applies to radio/checkbox and select widgets on admin pages
- Allows a different palette and mode from the front-end

**5. Views Exposed Filters:**

Three configuration modes:
- **Disable Completely** – Turn off Selectify for all Views filters
- **Apply Site-Wide** – Choose one global widget for all Views exposed
  select filters
- **Per-View Configuration** – Granular control with accordion interface:
  - Expandable sections per View
  - Table listing all displays with exposed filters
  - Columns: Display Title, Display Type, Display ID, Exposed Form ID
  - Individual widget selection per display
  - Clear visibility of current assignments
  - Preserves settings between form submissions

**6. Form API Select Elements:**

Per-form configuration for plain Drupal forms (not Views or Webforms):
- **Enable/Disable Toggle** – Master switch for Form API integration
- **Automatic Form Discovery** – Forms with eligible select elements are
  automatically detected when visited
- **Per-Form Widget Selection** – Table listing discovered forms with:
  - Form ID (sanitized and validated)
  - Last seen timestamp
  - Individual widget selection per form
- **Built-in Safety** – Automatic exclusion of:
  - AJAX-dependent forms (Field UI, Views UI, Layout Builder, etc.)
  - Forms with `#ajax` callbacks on select elements
  - Field widget-managed selects (identified by `#key_column`)
- **Additional Exclusions** – Textarea for custom form ID patterns using
  wildcard matching
- **Internal Blocklist** – Always-excluded admin forms displayed for
  reference

**7. Webform Select Elements** (requires selectify_webform submodule):

Three configuration modes matching Views pattern:
- **Disable Completely** – Turn off Selectify for all Webform submissions
- **Apply Site-Wide** – Choose one global widget for all Webforms with
  select elements
- **Per-Webform Configuration** – Granular control with table interface:
  - Lists only Webforms that contain select-like elements
  - Columns: Webform Label, Machine Name, Widget Selection
  - Automatic discovery of Webforms with eligible selects
  - Supports composite elements (Select Other, Entity Select, etc.)
- **Composite Element Support** – Handles nested selects in:
  - webform_select_other
  - webform_entity_select
  - webform_term_select
  - webform_likert
  - webform_mapping
  - webform_tableselect
  - webform_composite

### Field Widget Configuration

For content entity fields, configure widgets through Manage Form Display:

1. Navigate to **Manage Form Display** for your entity type
   (e.g., `/admin/structure/types/manage/article/form-display`)
2. Locate your select or entity reference field
3. Change widget from "Select list" to one of Selectify's widgets:
   - Selectify Dropdown
   - Selectify Dropdown Taggable
   - Selectify Dropdown With Search
   - Selectify Dropdown With Checkbox
   - Selectify Dual List
4. Save the form display configuration
5. Test the field in actual content editing

**Widget Selection Guidelines:**
- Large vocabularies (100+ terms)? Use Searchable Dropdown
- Multi-select with visual feedback? Use Taggable Dropdown
- Explicit selection state needed? Use Checkbox Dropdown
- Bulk selection management? Use Dual-List Selector
- Simple enhancement? Use Regular Dropdown

### Form API Integration

Selectify can upgrade plain Drupal Form API select elements that aren't
handled by field widgets, Views, or Webforms. This includes custom forms,
third-party module forms, and any form using `#type => 'select'`.

**How It Works:**
1. Enable Form API integration in settings
2. Visit pages containing forms with select elements
3. Selectify automatically discovers and records eligible forms
4. Return to settings page to configure widgets per form
5. Only forms with non-AJAX, non-field-widget selects are discovered

**Safety Features:**
- Built-in blocklist protects AJAX-dependent admin forms
- Individual selects with `#ajax` callbacks are automatically skipped
- Field widget selects (with `#key_column`) are excluded
- User-defined exclusion patterns via wildcard matching
- Form ID sanitization prevents storage abuse

**Discovery Process:**
- Forms are discovered at runtime when pages are visited
- Discovery data stored in `Drupal::state()` (not config)
- Limited to 1000 most recently seen forms
- Form IDs sanitized and validated before storage
- "Last seen" timestamps help identify active forms

### Webform Integration

The `selectify_webform` submodule extends Selectify to work on Webform
submission forms. Install it separately if you use the Webform module.

**Installation:**
```bash
composer require drupal/selectify_webform
drush en selectify_webform -y
drush cr
```

**Configuration Options:**
- **Site-Wide Application** – One widget for all Webforms
- **Per-Webform Configuration** – Different widgets per Webform
- **Automatic Discovery** – Only Webforms with select elements are listed

## Extending Selectify

Selectify provides three alter hooks that allow other modules to customize
widget selection and element processing:

### hook_selectify_widget_alter()

Change which widget is applied to a form or element before Selectify processes
it. Useful for context-aware widget selection (e.g., use searchable dropdowns
for forms with many options).

```php
function mymodule_selectify_widget_alter(string &$widget, array $context): void {
  if ($context['integration_type'] === 'form_api' && 
      $context['form_id'] === 'my_custom_form') {
    // Change widget based on form context.
    $widget = 'selectify_searchable';
  }
}
```

### hook_selectify_element_alter()

Modify form elements before Selectify processes them. Add custom attributes,
change options, or set `#selectify_skip => TRUE` to skip Selectify entirely
for specific elements.

```php
function mymodule_selectify_element_alter(array &$element, array $context): void {
  // Add custom data attributes.
  $element['#attributes']['data-custom-tracking'] = 'enabled';
  
  // Skip Selectify for simple selects.
  if (count($element['#options'] ?? []) < 3) {
    $element['#selectify_skip'] = TRUE;
  }
}
```

### hook_selectify_is_applicable_alter()

Prevent Selectify from applying to entire forms based on custom logic (user
roles, routes, query parameters, etc.).

```php
function mymodule_selectify_is_applicable_alter(bool &$applicable, array $context): void {
  // Disable Selectify for specific user roles.
  if (\Drupal::currentUser()->hasPermission('skip selectify')) {
    $applicable = FALSE;
  }
}
```

See `selectify.api.php` for complete hook documentation and examples.

**Supported Elements:**
- Direct `select` elements
- Composite elements containing nested selects:
  - Select Other (webform_select_other)
  - Entity Select (webform_entity_select)
  - Term Select (webform_term_select)
  - Likert scales (webform_likert)
  - Mapping elements (webform_mapping)
  - Table Select (webform_tableselect)
  - Custom composites (webform_composite)

**Safety:**
- AJAX-dependent selects automatically skipped
- Field widget selects excluded
- Respects admin route exclusion settings
- Respects custom disabled page patterns

## Accessibility & ARIA Compliance

Selectify follows WAI-ARIA 1.2 best practices for custom select widgets,
ensuring full WCAG 2.1 Level AA compliance:

### 1. Semantic Structure and Roles

Proper ARIA roles establish semantic meaning for assistive technologies:
- `role="combobox"` – Applied to dropdown widget containers
- `role="listbox"` – Used for option containers with
  `aria-multiselectable="true"` for multi-select
- `role="option"` – Each selectable option properly identified
- `role="group"` – Dual-list widget container for logical grouping
- `role="presentation"` – Applied to placeholder/none options to hide from
  screen readers

### 2. Dynamic State Management

ARIA attributes update in real-time as users interact:
- `aria-expanded` – Toggles between "true"/"false" based on dropdown state
- `aria-haspopup="listbox"` – Indicates expandable nature of combobox
- `aria-controls` – Links trigger to controlled dropdown by ID
- `aria-activedescendant` – Dynamically tracks keyboard focus within
  dropdown
- `aria-selected` – Added to selected options (except "_none" in Views)
- `aria-checked` – Used for checkbox dropdown options
- `aria-disabled` – Applied when options disabled due to selection limits

### 3. Labeling & Description

Proper labeling ensures screen readers announce widgets correctly:
- `aria-labelledby` – Automatically injected to connect widgets with their
  `<label>` elements
- `aria-label` – Fallback for widgets without visible labels (e.g.,
  "Available options", "Selected options")
- `aria-describedby` – Links to field descriptions when present
- `aria-required` – Indicates required fields
- `aria-invalid` – Preserves validation state from Views

### 4. Live Region Announcements

Hidden live regions provide screen reader feedback:
- `role="status" aria-live="polite"` – Hidden live region for selection
  count announcements
- `role="alert"` – Selection limit messages with immediate screen reader
  notification
- Debounced Announcements – 500ms delay prevents announcement spam during
  rapid selections
- `aria-atomic="true"` – Ensures complete messages are read

### 5. Comprehensive Keyboard Navigation

Full keyboard support for power users and assistive technology:

**Global Keys:**
- Enter / Space – Opens closed dropdown or selects focused option
- Escape – Closes open dropdown and returns focus to trigger
- Tab – Cycles through focusable elements within dropdown
- Shift + Tab – Reverse Tab navigation

**Option Navigation:**
- Arrow Down – Move focus to next option
- Arrow Up – Move focus to previous option
- Roving tabindex – Only focused option has `tabindex="0"`, others use
  `tabindex="-1"`
- Circular Navigation – Arrows wrap around at list boundaries

**Tag Removal:**
- Enter / Space – Removes focused tag in taggable/searchable widgets
- Focus Management – Returns focus to search input or dropdown trigger after
  removal

**Skip Links:**
- Dual-List Widget – Includes visually hidden skip link for keyboard users
  to jump to selected options
- `class="visually-hidden-focusable"` – Hidden until focused for clean
  visual design

### 6. Hidden Native Select

The native `<select>` element remains for form submission:
- `aria-hidden="true"` – Prevents screen readers from announcing hidden
  select
- `tabindex="-1"` – Removes from keyboard navigation flow
- Real-time Synchronization – JavaScript maintains perfect sync between
  visible widget and hidden select

### 7. Focus Management

Predictable focus flow ensures intuitive keyboard navigation:
- Focus moves logically through interface components
- Return Focus Strategies – After closing dropdown or clearing selections,
  focus returns to trigger element
- Search Input Focus – Searchable dropdown automatically focuses search
  input on open
- Visual Focus Indicators – Clear focus styling on all interactive elements

### 8. Separation of Concerns

Clean architecture prevents ARIA conflicts:
- Static ARIA in Twig – Structural ARIA attributes defined in templates
- Dynamic ARIA in JavaScript – State-based attributes managed by behavior
  scripts
- No Conflicts – Clear division prevents duplicate or conflicting attributes

### Testing Accessibility

Always test with actual assistive technologies:
- Keyboard-only navigation (no mouse)
- Screen readers (NVDA, JAWS, VoiceOver)
- Browser zoom (up to 200%)
- High contrast modes
- Mobile screen readers (TalkBack, VoiceOver)

## Performance Optimizations

### Zero jQuery Dependency

Built entirely with vanilla JavaScript ES6+ for optimal performance:
- Reduces page weight significantly
- Eliminates dependency conflicts
- Faster initialization and interaction response times
- Better compatibility with modern frontend frameworks

### Efficient Event Handling

Performance-conscious JavaScript patterns throughout:
- Event Delegation – Single listeners handle multiple dynamic elements
- Drupal.once() – Prevents duplicate event binding
- Throttled/Debounced Events – Search filtering and announcements optimized
- MutationObserver – Efficient DOM change detection for dynamic content

### Smart Resource Loading

Modular library structure loads only required components:
- Theme-specific CSS automatically loaded based on active theme
- Conditional loading based on configuration (radio/checkbox enabled state)
- Minimal initial payload with progressive enhancement

### Minimal DOM Manipulation

Efficient DOM updates prevent layout thrashing:
- Batch DOM updates to reduce reflow/repaint cycles
- CSS class toggles for state changes instead of inline styles
- Efficient option visibility toggling using single class changes

## Theme Integration & Customization

### Automatic Theme Detection

Each widget automatically receives CSS classes for theme-specific styling:
- Current Theme Class – e.g., `theme-olivero`, `theme-gin`
- Base Theme Class – e.g., `base-theme-claro` for theme inheritance
- Enables theme-specific overrides without complex selectors

### Built-in Theme Support

Integration with popular Drupal themes:
- **Claro** – Drupal core admin theme styling
- **Gin** – Modern admin theme with auto/light/dark mode support
- **Olivero** – Drupal core frontend theme optimizations

### CSS Custom Properties (Variables)

Override these variables in your theme for instant customization:

**Radio Button Variables:**
```css
--selectify-radio-border-radius   /* Border radius */
--selectify-radio-padding          /* Internal padding */
```

**Checkbox Variables:**
```css
/* Base States */
--selectify-checkbox-bg                    /* Background color (default) */
--selectify-checkbox-checked-bg            /* Checked background */
--selectify-checkbox-check-circle          /* Circle indicator color */
--selectify-checkbox-checked-circle        /* Checked circle color */
--selectify-checkbox-disabled-bg           /* Disabled background */
--selectify-checkbox-disabled-circle       /* Disabled circle */

/* Interactive States */
--selectify-checkbox-hover-border-color    /* Border on hover */
--selectify-checkbox-hover-bg              /* Background gradient on hover */
--selectify-checkbox-active-border-color   /* Border when clicking */
--selectify-checkbox-active-bg             /* Background during click */

/* Indeterminate State */
--selectify-checkbox-indeterminate-border-color  /* Border for partial */
--selectify-checkbox-indeterminate-bg            /* Background gradient */
--selectify-checkbox-indeterminate-bar           /* Horizontal bar color */

/* Validation States */
--selectify-checkbox-invalid-border-color  /* Border for errors */
--selectify-checkbox-invalid-bg            /* Background for invalid */
```

**Select Widget Variables:**
```css
--selectify-select-border-radius   /* Corner rounding */
--selectify-select-padding         /* Internal spacing */
--selectify-select-font-size       /* Text size */
--selectify-select-bg              /* Background color */
--selectify-select-border-color    /* Border color */
--selectify-select-color           /* Text color */
--selectify-select-hover-bg        /* Hover state background */
--selectify-select-focus-border    /* Focus state border */
--selectify-select-disabled-bg     /* Disabled state */
--selectify-select-arrow-color     /* Dropdown arrow color */
--selectify-dropdown-shadow        /* Box shadow */
--selectify-option-hover-bg        /* Option hover state */
--selectify-option-active-bg       /* Active option background */
```

## Developer Features

### Twig Extension

**selectify_clean_id Filter:**
- Sanitizes option values for use in HTML IDs
- Handles special characters, spaces, and edge cases
- Ensures valid, unique IDs for ARIA relationships

Example usage:
```twig
id="option-{{ clean_field_name }}-{{ option.value|selectify_clean_id }}"
```

### JavaScript API (Drupal.selectify namespace)

Comprehensive helper functions for custom integration:

**Selection Management:**
- `handleSelectionLimit()` – Enforces max selection rules
- `toggleMultiSelectOption()` – Core selection toggling logic
- `getSelectedValues()` – Retrieves current selections
- `syncHiddenSelect()` – Synchronizes widget with hidden select
- `updateSelectedDisplay()` – Refreshes visual display of selections

**Dropdown Control:**
- `openDropdown()` – Opens dropdown with ARIA updates
- `closeDropdown()` – Closes dropdown and manages focus
- `slideDown()` – Smooth expand animation
- `slideUp()` – Smooth collapse animation
- `adjustDropdownHeight()` – Calculates optimal dropdown height

**Tag Management:**
- `removeTag()` – Handles individual tag removal
- `createTag()` – Generates tag markup with proper attributes

**Keyboard & Accessibility:**
- `handleKeyboardNavigation()` – Complete keyboard support
- `injectAriaLabelledBy()` – Ensures proper label association
- `announceSelectionChange()` – Screen reader announcements
- `updateOptionState()` – Manages ARIA attributes on options

### Plugin Architecture

Clean, extensible architecture:
- Five separate field widget plugins extending `OptionsSelectWidget`
- Proper dependency injection for configuration
- Constructor typing and strict mode compliance
- Compatible with list fields and entity reference fields

## Installation

### Via Composer (Recommended)

```bash
composer require drupal/selectify
drush en selectify -y
drush cr
```

### Manual Installation

1. Download from https://www.drupal.org/project/selectify
2. Extract to `/modules/contrib/selectify`
3. Enable at `/admin/modules` or via Drush: `drush en selectify -y`
4. Clear cache: `drush cr`

## Requirements

- Drupal 10.2 or higher
- Drupal 11.x fully supported
- PHP 8.1+ with strict typing support
- Modern browser with JavaScript enabled

## Supported Field Types

- list_string
- list_integer
- list_float
- entity_reference

## Integration Types

Selectify supports four distinct integration paths:

1. **Field Widgets** – For entity fields configured through Manage Form
   Display
2. **Views Exposed Filters** – For Views exposed select filters with
   site-wide or per-View configuration
3. **Form API Selects** – For plain Drupal forms with per-form
   configuration and automatic discovery
4. **Webform Elements** – For Webform submission forms (requires
   selectify_webform submodule) with site-wide or per-Webform
   configuration

Each integration type operates independently with its own configuration
scope, ensuring no overlap or conflicts between different form contexts.

## Browser Compatibility

- Chrome/Edge (latest 2 versions)
- Firefox (latest 2 versions)
- Safari (latest 2 versions)
- Modern mobile browsers (iOS Safari, Chrome Mobile)

## Support & Contributing

### Issue Queue

Report bugs and request features:
https://www.drupal.org/project/issues/selectify

Please search existing issues before creating new ones and include:
- Drupal, PHP, and browser versions
- Detailed steps to reproduce
- Expected vs actual behavior

### Contributing

Contributions welcome! Please:
- Follow Drupal coding standards
- Include tests for new features
- Update documentation as needed
- Submit patches via Drupal.org

### Maintainer

**Alaa Haddad**
- Drupal.org: https://www.drupal.org/u/alaa-haddad
- Website: https://www.alaahaddad.com
- Projects: 27+ contributed Drupal modules and themes

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for a complete list of changes.

### Recent changes

**Version 2.0.2+ (Current Development):**
- Form API integration with per-form configuration and automatic
  discovery
- Webform submodule with site-wide and per-Webform configuration
- Views integration with improved per-View configuration UI
- Comprehensive safety features including AJAX detection and built-in
  blocklists
- Runtime form discovery with state storage management
- Improved defensive programming and error handling

**Version 1.0.0:**
- Initial release with five field widgets
- Radio/checkbox styling with toggle and traditional styles
- Views exposed filter integration
- Seven accent color themes with light/dark modes
- WCAG 2.1 Level AA accessibility compliance

## License

This project is licensed under the GNU General Public License, version 2 or
later: http://www.gnu.org/licenses/gpl-2.0.html
