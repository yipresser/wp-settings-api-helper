# Changelog

All notable changes to `wp-settings` will be documented in this file

## 2.0.0 - 2026-06-25

- Added: `color` field type — renders `<input type="color">` with default value support.
- Added: `range` field type — renders `<input type="range">` using the existing `min`, `max`, and `step` field config, with a live `<output>` readout driven by an inline script.
- Added: `image` field type — renders a WordPress media library picker. Stores either the attachment URL or ID (controlled by `image_return => 'url'|'id'`). Includes a thumbnail preview and a Remove button.
- Added: `step` attribute for `number` field.
- Added: `rows` and `cols` attributes for `textarea` and `code-editor` fields (default: 5/60).
- Added: `multiple` attribute for `select` field — promotes to `<select multiple>` and appends `[]` to the name.
- Added: `image_return` field attribute — controls whether the `image` field stores the attachment URL or ID.
- Fixed: new numeric/range attributes now render as real HTML attributes, `0` values are preserved, and range/image field scripts run directly with their rendered controls.
- Improvement: `sanitize_settings()` is now `abstract`, enforcing implementation in child classes at the language level instead of relying on a warning comment.
- Improvement: `$settings_options` and `$settings_sections` changed from `public` to `protected`.

## 1.1.4 - 2026-06-25

- Improvement: `render_section_description()` now uses an indexed lookup (`$sections_by_id`) instead of a linear scan across all sections on every render.
- Improvement: `$disable_el` is now computed once before the field type switch instead of being repeated in every case branch.
- Improvement: `text`, `url`, `email`, and `password` field cases consolidated into a shared `render_text_input()` helper method.
- Bug fix: `remove_submit_button` attribute no longer leaks into the `<form>` tag's HTML output in `render_settings_on_page()`.
- Bug fix: removed `stripslashes()` calls from all field value output — Magic Quotes has not been active since PHP 5.4 and WordPress has not added slashes to option values since WP 3.6.

## 1.1.3.1 - 2026-04-14
- Bug fix: check for field id before registering field.

## 1.1.3 - 2026-04-06

- Added: code-editor field now supports custom code type and theme. Theme file is not provided though. You will need to add it yourself.

## 1.1.2 - 2026-03-26

- Added: a new URL field type

## 1.1.1 - 2026-02-24

- Bug fix: fixed callback vulnerability 
- Bug fix: added WARNING comment for sanitize_settings

## 1.1.0 - 2025-11-12
- added code-editor field.

## 1.0.3.7 - 2025-11-03

- bug fix: minor sanitization bug fix
- bug fix: added label text for slider-checkbox. Moved description tag for checkbox to its own field

## 1.0.3.5 - 2025-11-03

- bug fix: added label text for checkbox. Moved description tag for checkbox to its own field
- bug fix: removed inproper support for file field

## 1.0.3.4 - 2025-10-31

- bug fix: changed description tag for checkbox field from <p> to <span>

## 1.0.3.3 - 2025-10-30

- changed field description escape mode from 'esc_html' to 'wp_kses_post' to allow html tags in the description.

## 1.0.3.2 - 2025-10-03

- added disabled attribute option to radio field.

## 1.0.3.1 - 2025-10-02

- added attribute option to remove submit button when rendering fields.

## 1.0.3.0 - 2025-06-27

- improved sanitization, updated comments, added new attribute parameter for settings form

## 1.0.2.0 - 2025-05-19

- added new slider-checkbox field

## 1.0.1.4 - 2025-05-14

- added default value for number field

## 1.0.1.3 - 2025-05-08

- added default value and disabled attributes to all fields

## 1.0.1.2 - 2025-05-07

- added min and max attribute to number field

## 1.0.0.2 - 2025-04-23

- Convert it into an abstract class. Removed construct function so that the class doesn't initialize itself

## 1.0.0.0 - 2024-09-10

- initial release
