# wp-settings-api-helper

An abstract class that simplifies the process of creating WordPress admin settings pages using the WordPress Settings API.

## Installation

You can install this package via Composer:

```bash
composer require yipresser/wp-settings-api-helper
```

Alternatively, you can use this class in your WordPress plugins or themes to easily define settings sections, fields, and handle their saving process.

## Usage

To use the helper, create a new class that extends `Yipresser\WpSettingsApiHelper\WP_Settings_API_Helper`.

### 1. Extend the Class and initialize the Settings

You need to define `$settings_options` for your database options and `$settings_sections` for the visual sections and fields you want to show on the page. Lastly, you need to run the `setup()` method.

```php
namespace MyPlugin\Admin;

use Yipresser\WpSettingsApiHelper\WP_Settings_API_Helper;

class My_Settings extends WP_Settings_API_Helper {

    public function __construct() {
        add_action( 'admin_init', [ $this, 'init' ] );
	}
      
    /**
	 * Start the engine running
	 *
	 * @return void
	 */
	public function init() {
        // Define your options
        $this->settings_options = [
            [
                'option_group' => 'my_plugin_settings_group',
                'option_name'  => 'my_plugin_settings',
                // Optional args for register_setting():
                // 'args' => [ 'sanitize_callback' => [$this, 'sanitize_settings'] ]
            ]
        ];

        // Define your sections and fields
        $this->settings_sections = [
            [
                'id'          => 'my_plugin_general_section',
                'title'       => 'General Settings',
                'description' => 'These are the general settings for the plugin.',
                'menu_slug'   => 'my_plugin_slug', // Slug of the settings page
                'option_name' => 'my_plugin_settings',
                'fields'      => [
                    [
                        'type'    => 'text',
                        'title'   => 'API Key',
                        'id'      => 'api_key',
                        'name'    => 'api_key',
                        'default' => '',
                        'desc'    => 'Enter your API key here.',
                    ],
                    [
                        'type'    => 'checkbox',
                        'title'   => 'Enable Feature',
                        'id'      => 'enable_feature',
                        'name'    => 'enable_feature',
                        'label'   => 'Check to enable',
                        'default' => 0,
                    ]
                ]
            ]
        ];
        $this->setup();
    }
}
```

### 2. Display the Settings Form

When rendering your options page HTML, call the `render_settings_on_page()` method so it can generate the settings form, fields, and submit button.

```php
// Assuming this is inside your admin page callback function
public function my_plugin_options_page() {
    echo '<div class="wrap">';
    echo '<h1>My Plugin Options</h1>';
    
    // Pass the menu_slug used in your $settings_sections
    $my_settings->render_settings_on_page( 'my_plugin_slug' );
    
    echo '</div>';
}
```

## Supported Field Types

The `type` key in your field configuration supports the following values:

- `text`
- `number` (supports `min` and `max` attributes)
- `email`
- `password`
- `textarea`
- `select` (requires a `choices` array `['value' => 'Label']`)
- `radio` (requires a `choices` array)
- `checkbox` (optional `label` for text next to checkbox)
- `checkboxes` (requires a `choices` array)
- `slider-checkbox`
- `dropdown_pages`
- `hidden`
- `callback` (requires `callback` and optional `param` keys to render custom HTML)

### Example Select Field
```php
[
    'type'    => 'select',
    'title'   => 'Select Mode',
    'id'      => 'mode',
    'name'    => 'mode',
    'choices' => [
        'light' => 'Light Mode',
        'dark'  => 'Dark Mode'
    ],
    'default' => 'light'
]
```

## Sanitization and Validation

By default, a placeholder `sanitize_settings` method is provided which returns the options untouched. You need to override `sanitize_settings($option)` in your child class to safely sanitize your data before saving them to the database.

```php
public function sanitize_settings( $option ) {
    if ( isset( $option['api_key'] ) ) {
        $option['api_key'] = sanitize_text_field( $option['api_key'] );
    }
    return $option;
}
```
