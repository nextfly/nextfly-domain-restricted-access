<?php
/**
 * Plugin Name: Nextfly Domain Restricted Access
 * Description: A WordPress plugin that requires email validation for page/post access.
 * Version: 1.0.0
 * Author: NEXTFLY® Web Design
 * Author URI: https://nextflywebdesign.com/
 * Text Domain: nextfly-domain-restricted-access
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'NFDRA_VERSION', '1.0.0' );
define( 'NFDRA_PLUGIN_FILE', __FILE__ );
define( 'NFDRA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'NFDRA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'NFDRA_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main plugin class loader.
 */
require_once NFDRA_PLUGIN_DIR . 'includes/class-nextfly-domain-restricted-access.php';

/**
 * Initialize the plugin.
 */
function nfdra_init() {
	return Nextfly_Domain_Restricted_Access::get_instance();
}

// Initialize the plugin.
add_action( 'plugins_loaded', 'nfdra_init' );

/**
 * Activation hook.
 */
function nfdra_activate() {
	require_once NFDRA_PLUGIN_DIR . 'includes/class-nextfly-domain-restricted-access-database.php';
	Nextfly_Domain_Restricted_Access_Database::create_tables();
	Nextfly_Domain_Restricted_Access_Database::set_default_options();
	
	// Schedule cron job for token cleanup.
	if ( ! wp_next_scheduled( 'nfdra_cleanup_tokens' ) ) {
		wp_schedule_event( time(), 'hourly', 'nfdra_cleanup_tokens' );
	}
	
	// Flush rewrite rules.
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'nfdra_activate' );

/**
 * Deactivation hook.
 */
function nfdra_deactivate() {
	// Clear scheduled cron job.
	$timestamp = wp_next_scheduled( 'nfdra_cleanup_tokens' );
	if ( $timestamp ) {
		wp_unschedule_event( $timestamp, 'nfdra_cleanup_tokens' );
	}
	
	// Flush rewrite rules.
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'nfdra_deactivate' );
