<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://realcoder.com.au
 * @since             1.0.0
 * @package           Rcreviews
 *
 * @wordpress-plugin
 * Plugin Name:       Sync Reviews from realestate.com.au
 * Plugin URI:        https://stafflink.com.au
 * Description:       Sync your realestate.com.au reviews to your real estate website via the REA API.
 * Version:           1.0.1
 * Author:            Julius Genetia
 * Author URI:        https://realcoder.com.au/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       rcreviews
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'RCREVIEWS_VERSION', '1.0.1' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-rcreviews-activator.php
 */
function activate_rcreviews() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-rcreviews-activator.php';
	Rcreviews_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-rcreviews-deactivator.php
 */
function deactivate_rcreviews() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-rcreviews-deactivator.php';
	Rcreviews_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_rcreviews' );
register_deactivation_hook( __FILE__, 'deactivate_rcreviews' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-rcreviews.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_rcreviews() {

	$plugin = new Rcreviews();
	$plugin->run();

}
run_rcreviews();
