<?php
/**
 * Token Manager class.
 *
 * @package Nextfly_Domain_Restricted_Access
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Nextfly_Domain_Restricted_Access_Token_Manager
 *
 * Handles token generation, validation, and deletion.
 *
 * @since 1.0.0
 */
class Nextfly_Domain_Restricted_Access_Token_Manager {

	/**
	 * Generate a unique token.
	 *
	 * @since 1.0.0
	 *
	 * @return string Unique token.
	 */
	public static function generate_unique_token() {
		$max_attempts = 10;
		$attempt      = 0;

		do {
			// Generate a random token with high entropy.
			$token = wp_generate_password( 32, false, false );
			$attempt++;

			// Check if token already exists.
			if ( ! Nextfly_Domain_Restricted_Access_Database::token_exists( $token ) ) {
				return $token;
			}
		} while ( $attempt < $max_attempts );

		// Fallback: add a unique identifier if we couldn't generate a unique token.
		return $token . '_' . uniqid();
	}

	/**
	 * Validate a token.
	 *
	 * @since 1.0.0
	 *
	 * @param string $token Token to validate.
	 * @return array Validation result with 'valid', 'message', and 'data' keys.
	 */
	public static function validate_token( $token ) {
		if ( empty( $token ) ) {
			return array(
				'valid'   => false,
				'message' => __( 'Invalid token.', 'nextfly-domain-restricted-access' ),
				'data'    => null,
			);
		}

		// Get token from database.
		$token_data = Nextfly_Domain_Restricted_Access_Database::get_token( $token );

		if ( ! $token_data ) {
			return array(
				'valid'   => false,
				'message' => __( 'Token not found or already used.', 'nextfly-domain-restricted-access' ),
				'data'    => null,
			);
		}

		// Check if token is expired (older than 24 hours).
		$created_time = strtotime( $token_data->created_at . ' UTC' );
		$current_time = time();
		$expiry_time  = $created_time + ( 24 * HOUR_IN_SECONDS );

		if ( $current_time > $expiry_time ) {
			// Delete expired token.
			Nextfly_Domain_Restricted_Access_Database::delete_token( $token );

			return array(
				'valid'   => false,
				'message' => __( 'Token has expired.', 'nextfly-domain-restricted-access' ),
				'data'    => null,
			);
		}

		// Token is valid.
		return array(
			'valid'   => true,
			'message' => __( 'Token is valid.', 'nextfly-domain-restricted-access' ),
			'data'    => $token_data,
		);
	}

	/**
	 * Use a token (delete it after validation).
	 *
	 * @since 1.0.0
	 *
	 * @param string $token Token to use.
	 * @return bool True on success, false on failure.
	 */
	public static function use_token( $token ) {
		$validation = self::validate_token( $token );

		if ( ! $validation['valid'] ) {
			return false;
		}

		// Delete the token after use.
		return Nextfly_Domain_Restricted_Access_Database::delete_token( $token ) !== false;
	}

	/**
	 * Get token data.
	 *
	 * @since 1.0.0
	 *
	 * @param string $token Token.
	 * @return object|null Token data or null.
	 */
	public static function get_token_data( $token ) {
		return Nextfly_Domain_Restricted_Access_Database::get_token( $token );
	}
}
