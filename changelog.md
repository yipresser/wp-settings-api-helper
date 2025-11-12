# Changelog

All notable changes to `wp-settings` will be documented in this file

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

