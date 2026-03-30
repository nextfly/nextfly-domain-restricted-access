<?php
/**
 * Database operations class.
 *
 * @package Nextfly_Domain_Restricted_Access
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Nextfly_Domain_Restricted_Access_Database
 *
 * Handles all database operations for the plugin.
 *
 * @since 1.0.0
 */
class Nextfly_Domain_Restricted_Access_Database {

	/**
	 * Table name for storing access tokens.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private static $table_name = 'nfdra_access_tokens';

	/**
	 * Create custom database table.
	 *
	 * @since 1.0.0
	 */
	public static function create_tables() {
		global $wpdb;

		$table_name      = $wpdb->prefix . self::$table_name;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			post_id bigint(20) NOT NULL,
			email varchar(255) NOT NULL,
			token varchar(255) NOT NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY post_id (post_id),
			KEY token (token),
			KEY created_at (created_at)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Set default plugin options.
	 *
	 * @since 1.0.0
	 */
	public static function set_default_options() {
		// Email subject.
		if ( ! get_option( 'nfdra_email_subject' ) ) {
			add_option( 'nfdra_email_subject', 'Your Access Link for ' . get_bloginfo( 'name' ) );
		}

		// Email body template.
		if ( ! get_option( 'nfdra_email_body' ) ) {
			$default_body = "Hello,\n\nYou have requested access to protected content on " . get_bloginfo( 'name' ) . ".\n\nClick the link below to access the page:\n\n%access_link%\n\nThis link will expire after one use or 24 hours, whichever comes first.\n\nBest regards,\n" . get_bloginfo( 'name' );
			add_option( 'nfdra_email_body', $default_body );
		}

		// Cookie duration (in days).
		if ( ! get_option( 'nfdra_cookie_duration' ) ) {
			add_option( 'nfdra_cookie_duration', 7 );
		}

		// Redirect page ID.
		if ( ! get_option( 'nfdra_redirect_page' ) ) {
			add_option( 'nfdra_redirect_page', '' );
		}
	}

	/**
	 * Insert a new token into the database.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $post_id Post ID.
	 * @param string $email   Email address.
	 * @param string $token   Unique token.
	 * @return int|false The number of rows inserted, or false on error.
	 */
	public static function insert_token( $post_id, $email, $token ) {
		global $wpdb;

		$table_name = esc_sql( $wpdb->prefix . self::$table_name );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		return $wpdb->insert(
			$table_name,
			array(
				'post_id'    => $post_id,
				'email'      => sanitize_email( $email ),
				'token'      => sanitize_text_field( $token ),
				'created_at' => current_time( 'mysql', 1 ),
			),
			array( '%d', '%s', '%s', '%s' )
		);
	}

	/**
	 * Check if a token exists in the database.
	 *
	 * @since 1.0.0
	 *
	 * @param string $token Token to check.
	 * @return bool True if token exists, false otherwise.
	 */
	public static function token_exists( $token ) {
		global $wpdb;

		$table_name = esc_sql( $wpdb->prefix . self::$table_name );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table_name} WHERE token = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
				$token
			)
		);

		return $count > 0;
	}

	/**
	 * Get token data from the database.
	 *
	 * @since 1.0.0
	 *
	 * @param string $token Token to retrieve.
	 * @return object|null Token data or null if not found.
	 */
	public static function get_token( $token ) {
		global $wpdb;

		$table_name = esc_sql( $wpdb->prefix . self::$table_name );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE token = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
				$token
			)
		);
	}

	/**
	 * Delete a token from the database.
	 *
	 * @since 1.0.0
	 *
	 * @param string $token Token to delete.
	 * @return int|false The number of rows deleted, or false on error.
	 */
	public static function delete_token( $token ) {
		global $wpdb;

		$table_name = esc_sql( $wpdb->prefix . self::$table_name );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->delete(
			$table_name,
			array( 'token' => $token ),
			array( '%s' )
		);
	}

	/**
	 * Delete expired tokens (older than 24 hours).
	 *
	 * @since 1.0.0
	 *
	 * @return int|false The number of rows deleted, or false on error.
	 */
	public static function delete_expired_tokens() {
		global $wpdb;

		$table_name = esc_sql( $wpdb->prefix . self::$table_name );

		$expiry_time = gmdate( 'Y-m-d H:i:s', strtotime( '-24 hours' ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table_name} WHERE created_at < %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
				$expiry_time
			)
		);
	}

	/**
	 * Get authorized domains for a post.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Post ID.
	 * @return array Array of authorized domains.
	 */
	public static function get_authorized_domains( $post_id ) {
		$domains = get_post_meta( $post_id, '_nfdra_authorized_domains', true );

		if ( empty( $domains ) ) {
			return array();
		}

		// Split by newlines and filter empty values.
		$domains_array = array_map( 'trim', explode( "\n", $domains ) );
		$domains_array = array_filter( $domains_array );

		return $domains_array;
	}

	/**
	 * Save authorized domains for a post.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $post_id Post ID.
	 * @param string $domains Domains (one per line).
	 * @return int|bool Meta ID on success, false on failure.
	 */
	public static function save_authorized_domains( $post_id, $domains ) {
		return update_post_meta( $post_id, '_nfdra_authorized_domains', sanitize_textarea_field( $domains ) );
	}
}
