<?php
/**
 * Register Settings
 *
 * Based on register-settings.php in Easy Digital Downloads.
 *
 * @package   book-database
 * @copyright Copyright (c) 2016, Ashley GIbson
 * @license   GPL2+
 * @since     1.0.0 (Was a class since 3.0.0, then converted to functions.)
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get an Option
 *
 * Looks to see if the specified setting exists, returns the default if not.
 *
 * @param string $key     Key to retrieve
 * @param mixed  $default Default option
 *
 * @global       $bdb_options
 *
 * @since 1.0.0
 * @return mixed
 */
function bdb_get_option( $key = '', $default = false ) {
	global $bdb_options;

	$value = ! empty( $bdb_options[ $key ] ) ? $bdb_options[ $key ] : $default;
	$value = apply_filters( 'book-database/options/get', $value, $key, $default );

	return apply_filters( 'book-database/options/get/' . $key, $value, $key, $default );
}

/**
 * Update an Option
 *
 * Updates an existing setting value in both the DB and the global variable.
 * Passing in an empty, false, or null string value will remove the key from the bdb_settings array.
 *
 * @param string $key   Key to update
 * @param mixed  $value The value to set the key to
 *
 * @global       $bdb_options
 *
 * @since 1.0.0
 * @return bool True if updated, false if not
 */
function bdb_update_option( $key = '', $value = false ) {
	// If no key, exit
	if ( empty( $key ) ) {
		return false;
	}

	if ( empty( $value ) ) {
		$remove_option = bdb_delete_option( $key );

		return $remove_option;
	}

	// First let's grab the current settings
	$options = get_option( 'bdb_settings' );

	// Let's let devs alter that value coming in
	$value = apply_filters( 'book-database/options/update', $value, $key );

	// Next let's try to update the value
	$options[ $key ] = $value;
	$did_update      = update_option( 'bdb_settings', $options );

	// If it updated, let's update the global variable
	if ( $did_update ) {
		global $bdb_options;
		$bdb_options[ $key ] = $value;
	}

	return $did_update;
}

/**
 * Remove an Option
 *
 * Removes an setting value in both the DB and the global variable.
 *
 * @param string $key The key to delete.
 *
 * @global       $bdb_options
 *
 * @since 1.0.0
 * @return boolean True if updated, false if not.
 */
function bdb_delete_option( $key = '' ) {
	// If no key, exit
	if ( empty( $key ) ) {
		return false;
	}

	// First let's grab the current settings
	$options = get_option( 'bdb_settings' );

	// Next let's try to update the value
	if ( isset( $options[ $key ] ) ) {
		unset( $options[ $key ] );
	}

	$did_update = update_option( 'bdb_settings', $options );

	// If it updated, let's update the global variable
	if ( $did_update ) {
		global $bdb_options;
		$bdb_options = $options;
	}

	return $did_update;
}

/**
 * Get Settings
 *
 * Retrieves all plugin settings
 *
 * @since 1.0.0
 * @return array Novelist settings
 */
function bdb_get_settings() {
	$settings = get_option( 'bdb_settings' );

	return apply_filters( 'book-database/get-settings', $settings );
}

/**
 * Add all settings sections and fields.
 *
 * @since 1.0.0
 * @return void
 */
function bdb_register_settings() {

	if ( false == get_option( 'bdb_settings' ) ) {
		add_option( 'bdb_settings' );
	}

	foreach ( bdb_get_registered_settings() as $tab => $sections ) {
		foreach ( $sections as $section => $settings ) {
			add_settings_section(
				'bdb_settings_' . $tab . '_' . $section,
				__return_null(),
				'__return_false',
				'bdb_settings_' . $tab . '_' . $section
			);

			foreach ( $settings as $option ) {
				// For backwards compatibility
				if ( empty( $option['id'] ) ) {
					continue;
				}

				$name = isset( $option['name'] ) ? $option['name'] : '';

				add_settings_field(
					'bdb_settings[' . $option['id'] . ']',
					$name,
					function_exists( 'bdb_' . $option['type'] . '_callback' ) ? 'bdb_' . $option['type'] . '_callback' : 'bdb_missing_callback',
					'bdb_settings_' . $tab . '_' . $section,
					'bdb_settings_' . $tab . '_' . $section,
					array(
						'section'     => $section,
						'id'          => isset( $option['id'] ) ? $option['id'] : null,
						'desc'        => ! empty( $option['desc'] ) ? $option['desc'] : '',
						'name'        => isset( $option['name'] ) ? $option['name'] : null,
						'size'        => isset( $option['size'] ) ? $option['size'] : null,
						'options'     => isset( $option['options'] ) ? $option['options'] : '',
						'std'         => isset( $option['std'] ) ? $option['std'] : '',
						'min'         => isset( $option['min'] ) ? $option['min'] : null,
						'max'         => isset( $option['max'] ) ? $option['max'] : null,
						'step'        => isset( $option['step'] ) ? $option['step'] : null,
						'chosen'      => isset( $option['chosen'] ) ? $option['chosen'] : null,
						'placeholder' => isset( $option['placeholder'] ) ? $option['placeholder'] : null
					)
				);
			}
		}
	}

	// Creates our settings in the options table
	register_setting( 'bdb_settings', 'bdb_settings', 'bdb_settings_sanitize' );

}

add_action( 'admin_init', 'bdb_register_settings' );

/**
 * Registered Settings
 *
 * @todo  Add old filters to deprecated.php.
 *
 * Sets and returns the array of all plugin settings.
 * Developers can use the following filters to add their own settings or
 * modify existing ones:
 *
 *  + ubb/settings/{key} - Where {key} is a specific tab. Used to modify a single tab/section.
 *  + ubb/settings/registered-settings - Includes the entire array of all settings.
 *
 * @since 1.0.0
 * @return array
 */
function bdb_get_registered_settings() {

	$bdb_settings = array(
		/* General Settings */
		'general' => apply_filters( 'book-database/settings/general', array(
			'main' => array(
				// @todo maybe remove review_cat
				'review_cat'    => array(
					'name' => esc_html__( 'Review Category', 'book-database' ),
					'desc' => __( 'Select the category that you place all of your book reviews in.	This value will be used in review indexes and widgets.	If it\'s not filled out correctly, some features may not work.', 'book-database' ),
					'id'   => 'review_cat',
					'type' => 'categories',
					'std'  => ''
				),
				'giveaway_cat'  => array(
					'name' => esc_html__( 'Giveaway Category', 'book-database' ),
					'desc' => __( 'Select the category that you place all of your giveaways in.	This value will be used in widgets and the giveaway page shortcode.	If it\'s not filled out correctly, some features may not work.', 'book-database' ),
					'id'   => 'giveaway_cat',
					'type' => 'categories',
					'std'  => ''
				),
				'google_api'    => array(
					'name' => esc_html__( 'Google API Key', 'book-database' ),
					'desc' => sprintf( __( 'You need to sign up for a Google API Key in order to retrieve book info from Google Books. For instructions on how to do this, please read <a href="%s" target="_blank">the documentation</a>.', 'book-database' ), esc_url( 'http://docs.ultimatebookblogger.com/article/36-google-api-key' ) ),
					'id'   => 'google_api',
					'type' => 'text',
					'std'  => ''
				),
				'goodreads_api' => array(
					'name' => esc_html__( 'Goodreads API Key', 'book-database' ),
					'desc' => sprintf( __( 'You need to sign up for a Goodreads API Key in order to retrieve book info from Goodreads. For instructions on how to do this, please read <a href="%s" target="_blank">the documentation</a>.', 'book-database' ), esc_url( 'http://docs.ultimatebookblogger.com/article/37-goodreads-api-key' ) ),
					'id'   => 'goodreads_api',
					'type' => 'text',
					'std'  => ''
				),
			)
		) ),
		'license' => array()
	);

	return apply_filters( 'book-database/settings/registered-settings', $bdb_settings );

}

/**
 * Sanitize Settings
 *
 * Adds a settings error for the updated message.
 *
 * @param array  $input       The value inputted in the field
 *
 * @global array $bdb_options Array of all the Novelist options
 *
 * @since 1.0.0
 * @return array New, sanitized settings.
 */
function bdb_settings_sanitize( $input = array() ) {

	global $bdb_options;

	if ( empty( $_POST['_wp_http_referer'] ) ) {
		return $input;
	}

	parse_str( $_POST['_wp_http_referer'], $referrer );

	$settings = bdb_get_registered_settings();
	$tab      = ( isset( $referrer['tab'] ) && $referrer['tab'] != 'import_export' ) ? $referrer['tab'] : 'book';
	$section  = isset( $referrer['section'] ) ? $referrer['section'] : 'main';

	$input = $input ? $input : array();
	$input = apply_filters( 'book-database/settings/sanitize/' . $tab . '/' . $section, $input );

	// Loop through each setting being saved and pass it through a sanitization filter
	foreach ( $input as $key => $value ) {
		// Get the setting type (checkbox, select, etc)
		$type = isset( $settings[ $tab ][ $section ][ $key ]['type'] ) ? $settings[ $tab ][ $section ][ $key ]['type'] : false;
		if ( $type ) {
			// Field type specific filter
			$input[ $key ] = apply_filters( 'book-database/settings/sanitize/' . $type, $value, $key );
		}
		// General filter
		$input[ $key ] = apply_filters( 'book-database/settings/sanitize', $input[ $key ], $key );
	}

	// Loop through the whitelist and unset any that are empty for the tab being saved
	$main_settings    = $section == 'main' ? $settings[ $tab ] : array();
	$section_settings = ! empty( $settings[ $tab ][ $section ] ) ? $settings[ $tab ][ $section ] : array();
	$found_settings   = array_merge( $main_settings, $section_settings );

	if ( ! empty( $found_settings ) ) {
		foreach ( $found_settings as $key => $value ) {
			if ( empty( $input[ $key ] ) || ! array_key_exists( $key, $input ) ) {
				unset( $bdb_options[ $key ] );
			}
		}
	}

	// Merge our new settings with the existing
	$output = array_merge( $bdb_options, $input );

	add_settings_error( 'ubb-notices', '', __( 'Settings updated.', 'book-database' ), 'updated' );

	return $output;

}

/**
 * Display "Default settings restored" message.
 * This gets displayed after the default settings have been restored and
 * the page has been redirected.
 *
 * @since 1.0.0
 * @return void
 */
function bdb_defaults_restored_message() {
	if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'ultimatebb' ) {
		return;
	}

	if ( ! isset( $_GET['defaults-restored'] ) || $_GET['defaults-restored'] !== 'true' ) {
		return;
	}

	add_settings_error( 'ubb-notices', '', __( 'Default settings restored.', 'book-database' ), 'updated' );
}

add_action( 'admin_init', 'bdb_defaults_restored_message' );

/**
 * Restore Defaults
 *
 * Ajax callback that restores the default settings for a specific tab.
 *
 * @since 1.0.0
 * @return void
 */
function bdb_restore_default_settings() {
	// Permission check.
	if ( ! current_user_can( 'activate_plugins' ) ) {
		wp_die( __( 'Bugger off! You don\'t have permission to do this.', 'book-database' ) );
	}

	global $bdb_options;
	$tab              = strip_tags( $_POST['tab'] );
	$section          = strip_tags( $_POST['section'] );
	$default_settings = bdb_get_registered_settings();

	// Tab is missing.
	if ( ! array_key_exists( $tab, $default_settings ) ) {
		wp_send_json_error( __( 'Error: Tab missing.', 'book-database' ) );
	}

	// Loop through each section.
	foreach ( $default_settings[ $tab ] as $section_id => $settings ) {
		if ( ! is_array( $settings ) ) {
			continue;
		}

		foreach ( $settings as $key => $options ) {
			// Special circumstances for the 'book_layout' field.
			if ( $key == 'book_layout' ) {
				$bdb_options[ $key ] = bdb_get_default_book_field_values();

				continue;
			}

			if ( ! array_key_exists( 'std', $options ) ) {
				continue;
			}

			$bdb_options[ $key ] = apply_filters( 'book-database/settings/restore-defaults/' . $key, $options['std'], $options );
		}
	}

	// Update options.
	update_option( 'bdb_settings', apply_filters( 'book-database/settings/restore-defaults', $bdb_options ) );

	// Build our URL
	$url    = admin_url( 'edit.php' ); // @todo fix this
	$params = array(
		'post_type'         => 'book',
		'page'              => 'ubb-settings',
		'tab'               => urlencode( $tab ),
		'section'           => urlencode( $section ),
		'defaults-restored' => 'true'
	);
	$url    = add_query_arg( $params, $url );

	wp_send_json_success( $url );
}

add_action( 'wp_ajax_bdb_restore_default_settings', 'bdb_restore_default_settings' );

/**
 * Sanitize Text Field
 *
 * @param string $input
 *
 * @since 1.0.0
 * @return string
 */
function bdb_settings_sanitize_text_field( $input ) {
	return wp_kses_post( $input );
}

add_filter( 'book-database/settings/sanitize/text', 'bdb_settings_sanitize_text_field' );

/**
 * Sanitize Number Field
 *
 * @param int $input
 *
 * @since 1.0.0
 * @return int
 */
function bdb_settings_sanitize_number_field( $input ) {
	return intval( $input );
}

add_filter( 'book-database/settings/sanitize/number', 'bdb_settings_sanitize_number_field' );

/**
 * @todo Add more santizations.
 */

/**
 * Retrieve settings tabs
 *
 * @since 1.0.0
 * @return array $tabs
 */
function bdb_get_settings_tabs() {
	$settings = bdb_get_registered_settings();

	$tabs            = array();
	$tabs['general'] = esc_html__( 'General', 'book-database' );
	$tabs['reviews'] = esc_html__( 'Reviews', 'book-database' );
	$tabs['misc']    = esc_html__( 'Misc', 'book-database' );

	if ( ! empty( $settings['addons'] ) ) {
		$tabs['addons'] = esc_html__( 'Add-Ons', 'book-database' );
	}

	if ( ! empty( $settings['license'] ) ) {
		$tabs['license'] = esc_html__( 'Licenses', 'book-database' );
	}

	return apply_filters( 'book-database/settings/tabs', $tabs );
}


/**
 * Retrieve settings tabs
 *
 * @since 1.0.0
 * @return array $section
 */
function bdb_get_settings_tab_sections( $tab = false ) {
	$tabs     = false;
	$sections = bdb_get_registered_settings_sections();

	if ( $tab && ! empty( $sections[ $tab ] ) ) {
		$tabs = $sections[ $tab ];
	} else if ( $tab ) {
		$tabs = false;
	}

	return $tabs;
}

/**
 * Get the settings sections for each tab
 * Uses a static to avoid running the filters on every request to this function
 *
 * @since  1.0.0
 * @return array Array of tabs and sections
 */
function bdb_get_registered_settings_sections() {
	static $sections = false;

	if ( false !== $sections ) {
		return $sections;
	}

	$sections = array(
		'general'     => apply_filters( 'book-database/settings/sections/general', array(
			'main'           => esc_html__( 'General Settings', 'book-database' )
		) ),
		'misc'     => apply_filters( 'book-database/settings/sections/misc', array(
			'main' => __( 'Misc', 'book-database' ),
		) ),
		'addons'   => apply_filters( 'book-database/settings/sections/addons', array() ),
		'license' => apply_filters( 'book-database/settings/sections/license', array() )
	);

	$sections = apply_filters( 'book-database/settings/sections', $sections );

	return $sections;
}

/**
 * Sanitizes a string key for Ultimate Book Blogger Settings
 *
 * Keys are used as internal identifiers. Alphanumeric characters, dashes, underscores, stops, colons and slashes are
 * allowed
 *
 * @param  string $key String key
 *
 * @since 1.0.0
 * @return string Sanitized key
 */
function bdb_sanitize_key( $key ) {
	$raw_key = $key;
	$key     = preg_replace( '/[^a-zA-Z0-9_\-\.\:\/]/', '', $key );

	return apply_filters( 'book-database/sanitize-key', $key, $raw_key );
}

/**
 * Callbacks
 */

/**
 * Missing Callback
 *
 * If a function is missing for settings callbacks alert the user.
 *
 * @param array $args Arguments passed by the setting
 *
 * @since 1.0.0
 * @return void
 */
function bdb_missing_callback( $args ) {
	printf(
		__( 'The callback function used for the %s setting is missing.', 'book-database' ),
		'<strong>' . $args['id'] . '</strong>'
	);
}