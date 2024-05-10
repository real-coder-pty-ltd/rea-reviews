<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://realcoder.com.au
 * @since      1.0.0
 *
 * @package    Rcreviews
 * @subpackage Rcreviews/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Rcreviews
 * @subpackage Rcreviews/includes
 * @author     Julius Genetia <julius@stafflink.com.au>
 */
class Rcreviews_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		$timestamp = wp_next_scheduled( 'rcreviews_cron_hook' );
		wp_unschedule_event( $timestamp, 'rcreviews_cron_hook' );
	}
}
