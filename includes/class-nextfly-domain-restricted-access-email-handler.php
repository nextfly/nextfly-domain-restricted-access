<?php
/**
 * Email Handler class.
 *
 * @package Nextfly_Domain_Restricted_Access
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Nextfly_Domain_Restricted_Access_Email_Handler
 *
 * Handles email composition and sending.
 *
 * @since 1.0.0
 */
class Nextfly_Domain_Restricted_Access_Email_Handler {

	/**
	 * Send access link email.
	 *
	 * @since 1.0.0
	 *
	 * @param string $email   Recipient email address.
	 * @param string $token   Access token.
	 * @param int    $post_id Post ID.
	 * @return bool True on success, false on failure.
	 */
	public static function send_access_email( $email, $token, $post_id ) {
		/**
		 * Action triggered before sending the access email.
		 *
		 * @since 1.0.0
		 *
		 * @param string $email   Recipient email.
		 * @param int    $post_id Post ID.
		 */
		do_action( 'nfdra_before_send_email', $email, $post_id );

		// Get email settings.
		$subject  = get_option( 'nfdra_email_subject', 'Your Access Link' );
		$body     = get_option( 'nfdra_email_body', 'Click the link below to access the page: %access_link%' );

		// Build access link.
		$access_link = self::build_access_link( $token, $post_id );

		// Replace placeholder with actual link.
		$body = str_replace( '%access_link%', $access_link, $body );

		// Set headers for HTML email.
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );

		/**
		 * Filter email headers.
		 *
		 * @since 1.0.0
		 *
		 * @param array $headers Email headers.
		 */
		$headers = apply_filters( 'nfdra_email_headers', $headers );

		// Convert newlines to <br> for HTML email.
		$body = nl2br( $body );

		// Send email.
		$sent = wp_mail( $email, $subject, $body, $headers );

		if ( $sent ) {
			/**
			 * Action triggered after sending the access email.
			 *
			 * @since 1.0.0
			 *
			 * @param string $email   Recipient email.
			 * @param int    $post_id Post ID.
			 */
			do_action( 'nfdra_after_send_email', $email, $post_id );
		}

		return $sent;
	}

	/**
	 * Build access link with token as HTML hyperlink.
	 *
	 * @since 1.0.0
	 *
	 * @param string $token   Access token.
	 * @param int    $post_id Post ID.
	 * @return string HTML hyperlink with title attribute.
	 */
	private static function build_access_link( $token, $post_id ) {
		$post_url = get_permalink( $post_id );

		// Add token as query parameter.
		$access_link = add_query_arg( 'access_token', $token, $post_url );

		// Get post title for link text and title attribute.
		$post_title = get_the_title( $post_id );

		// Build HTML hyperlink with title attribute.
		$html_link = sprintf(
			'<a href="%s" title="%s">View %s</a>',
			esc_url( $access_link ),
			esc_attr( $post_title ),
			esc_html( $post_title )
		);

		return $html_link;
	}

	/**
	 * Validate email domain against authorized domains.
	 *
	 * @since 1.0.0
	 *
	 * @param string $email   Email address to validate.
	 * @param int    $post_id Post ID.
	 * @return bool True if valid, false otherwise.
	 */
	public static function validate_email_domain( $email, $post_id ) {
		// Extract domain from email.
		$email_parts = explode( '@', $email );

		if ( count( $email_parts ) !== 2 ) {
			return false;
		}

		$email_domain = strtolower( trim( $email_parts[1] ) );

		// Get authorized domains for this post.
		$authorized_domains = Nextfly_Domain_Restricted_Access_Database::get_authorized_domains( $post_id );

		if ( empty( $authorized_domains ) ) {
			return false;
		}

		// Check if email domain matches any authorized domain.
		foreach ( $authorized_domains as $domain ) {
			$domain = strtolower( trim( $domain ) );

			if ( $email_domain === $domain ) {
				return true;
			}
		}

		return false;
	}
}
