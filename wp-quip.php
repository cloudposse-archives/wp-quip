<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://cloudposse.com
 * @since             1.0.0
 * @package           WP_Quip
 *
 * @wordpress-plugin
 * Plugin Name:       WP Quip
 * Plugin URI:        http://cloudposse.com/
 * Description:       A simple Quip integration for WordPress.
 * Version:           1.0.0
 * Author:            Ryan Cassidy
 * Author URI:        http://cloudposse.com
 * License:           Apache 2.0
 * License URI:       http://www.apache.org/licenses/LICENSE-2.0
 * Text Domain:       wp-quip
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wp-quip-activator.php
 */
function activate_plugin_name() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wp-quip-activator.php';
    WP_Quip_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wp-quip-deactivator.php
 */
function deactivate_plugin_name() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wp-quip-deactivator.php';
    WP_Quip_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_plugin_name' );
register_deactivation_hook( __FILE__, 'deactivate_plugin_name' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-wp-quip.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_plugin_name() {

	$plugin = new WP_Quip();
	$plugin->run();

}
run_plugin_name();
