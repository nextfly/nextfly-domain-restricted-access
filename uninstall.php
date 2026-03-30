<?php
/**
 * Uninstall script for Nextfly Domain Restricted Access plugin.
 *
 * This file is executed when the plugin is deleted via the WordPress admin.
 *
 * @package Nextfly_Domain_Restricted_Access
 */

// Exit if uninstall not called from WordPress.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Define table name.
$nfdra_table_name = $wpdb->prefix . 'nfdra_access_tokens';

// Drop custom table.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
$wpdb->query( "DROP TABLE IF EXISTS {$nfdra_table_name}" );

// Delete all plugin options.
delete_option( 'nfdra_email_subject' );
delete_option( 'nfdra_email_body' );
delete_option( 'nfdra_cookie_duration' );
delete_option( 'nfdra_redirect_page' );

// Delete all post meta created by plugin.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.SlowDBQuery.slow_db_query_meta_key
$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => '_nfdra_authorized_domains' ) );

// Clear any scheduled cron jobs.
$nfdra_timestamp = wp_next_scheduled( 'nfdra_cleanup_tokens' );
if ( $nfdra_timestamp ) {
	wp_unschedule_event( $nfdra_timestamp, 'nfdra_cleanup_tokens' );
}

// Flush rewrite rules.
flush_rewrite_rules();
