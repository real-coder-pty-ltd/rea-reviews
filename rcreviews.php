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
 * Plugin URI:        https://realcoder.com.au/
 * Description:       Sync your realestate.com.au reviews to your real estate website via the REA API.
 * Version:           1.1.0
 * Author:            Real Coder
 * Author URI:        https://realcoder.com.au/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       rcreviews
 * Domain Path:       /languages
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'RCREVIEWS_VERSION', '1.1.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-rcreviews-activator.php
 */
function activate_rcreviews() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-rcreviews-activator.php';
	Rcreviews_Activator::activate();
}

function deactivate_rcreviews() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-rcreviews-deactivator.php';
	Rcreviews_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_rcreviews' );
register_deactivation_hook( __FILE__, 'deactivate_rcreviews' );

require plugin_dir_path( __FILE__ ) . 'includes/class-rcreviews.php';

function run_rcreviews() {

	$plugin = new Rcreviews();
	$plugin->run();

}
run_rcreviews();
