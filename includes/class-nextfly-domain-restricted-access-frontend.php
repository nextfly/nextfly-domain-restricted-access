<?php
/**
 * Frontend class.
 *
 * @package Nextfly_Domain_Restricted_Access
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Nextfly_Domain_Restricted_Access_Frontend
 *
 * Handles frontend functionality.
 *
 * @since 1.0.0
 */
class Nextfly_Domain_Restricted_Access_Frontend {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// Register shortcode.
		add_shortcode( 'nextfly_domain_restricted_access', array( $this, 'render_shortcode' ) );

		// Check access on template redirect.
		add_action( 'template_redirect', array( $this, 'handle_access_control' ) );

		// Register AJAX handler for email submission.
		add_action( 'wp_ajax_nfdra_submit_email', array( $this, 'ajax_submit_email' ) );
		add_action( 'wp_ajax_nopriv_nfdra_submit_email', array( $this, 'ajax_submit_email' ) );

		// Enqueue frontend scripts and styles.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Handle all access control logic.
	 *
	 * @since 1.0.0
	 */
	public function handle_access_control() {
		// Only check on singular posts/pages.
		if ( ! is_singular() ) {
			return;
		}

		global $post;
		$post_id = get_the_ID();

		// Check if current page is protected and requires access validation.
		$authorized_domains = Nextfly_Domain_Restricted_Access_Database::get_authorized_domains( $post_id );

		// If no authorized domains, no protection needed.
		if ( empty( $authorized_domains ) ) {
			return;
		}

		// Allow administrators to bypass access restrictions.
		if ( current_user_can( 'manage_options' ) ) {
			return;
		}

		// Check if user has valid cookie.
		if ( $this->has_valid_cookie( $post_id ) ) {
			return;
		}

		// Check if access token is present in URL and validate it server-side.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['access_token'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$token = sanitize_text_field( wp_unslash( $_GET['access_token'] ) );
			$this->validate_and_process_token( $token, $post_id );
			return;
		}

		// No valid access, redirect to email form page.
		$this->redirect_to_email_form( $post_id );
	}

	/**
	 * Check if user has a valid access cookie.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Post ID.
	 * @return bool True if valid cookie exists, false otherwise.
	 */
	private function has_valid_cookie( $post_id ) {
		$cookie_name = 'nfdra_access_' . $post_id;

		if ( ! isset( $_COOKIE[ $cookie_name ] ) ) {
			return false;
		}

		$cookie_value = sanitize_text_field( (string) wp_unslash( $_COOKIE[ $cookie_name ] ) );
		$parts        = explode( '.', $cookie_value, 2 );

		if ( 2 !== count( $parts ) ) {
			return false;
		}

		list( $payload, $signature ) = $parts;

		$expected_signature = hash_hmac( 'sha256', $payload, wp_salt( 'auth' ) );
		if ( ! hash_equals( $expected_signature, $signature ) ) {
			return false;
		}

		$decoded_payload = base64_decode( $payload, true );
		if ( false === $decoded_payload ) {
			return false;
		}

		$cookie_data = json_decode( $decoded_payload, true );
		if ( ! is_array( $cookie_data ) ) {
			return false;
		}

		if ( ! isset( $cookie_data['post_id'], $cookie_data['exp'], $cookie_data['email'] ) ) {
			return false;
		}

		if ( (int) $cookie_data['post_id'] !== (int) $post_id ) {
			return false;
		}

		if ( (int) $cookie_data['exp'] < time() ) {
			return false;
		}

		return is_email( $cookie_data['email'] );
	}

	/**
	 * Set access cookie.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $post_id Post ID.
	 * @param string $email   Email address.
	 */
	private function set_access_cookie( $post_id, $email ) {
		$cookie_name     = 'nfdra_access_' . $post_id;
		$cookie_duration = get_option( 'nfdra_cookie_duration', 7 );
		$expiry          = time() + ( $cookie_duration * DAY_IN_SECONDS );
		$cookie_payload  = wp_json_encode(
			array(
				'post_id' => (int) $post_id,
				'email'   => sanitize_email( $email ),
				'exp'     => (int) $expiry,
			)
		);
		$payload         = base64_encode( $cookie_payload );
		$signature       = hash_hmac( 'sha256', $payload, wp_salt( 'auth' ) );
		$cookie_value    = $payload . '.' . $signature;

		setcookie(
			$cookie_name,
			$cookie_value,
			array(
				'expires'  => $expiry,
				'path'     => COOKIEPATH,
				'domain'   => COOKIE_DOMAIN,
				'secure'   => is_ssl(),
				'httponly' => true,
				'samesite' => 'Lax',
			)
		);
	}

	/**
	 * Validate and process access token server-side.
	 *
	 * @since 1.0.0
	 *
	 * @param string $token   Access token.
	 * @param int    $post_id Post ID.
	 */
	private function validate_and_process_token( $token, $post_id ) {
		// Validate token.
		$validation = Nextfly_Domain_Restricted_Access_Token_Manager::validate_token( $token );

		if ( ! $validation['valid'] ) {
			/**
			 * Action triggered when access is denied (invalid/expired token).
			 *
			 * @since 1.0.0
			 *
			 * @param string $token   Invalid token.
			 * @param int    $post_id Post ID.
			 */
			do_action( 'nfdra_access_denied', $token, $post_id );

			// Invalid token, redirect to email form page.
			$this->redirect_to_email_form( $post_id );
			return;
		}

		// Token is valid, get token data.
		$token_data = $validation['data'];

		// Verify the token is for this specific post.
		if ( (int) $token_data->post_id !== (int) $post_id ) {
			// Token is for a different post, redirect to email form.
			$this->redirect_to_email_form( $post_id );
			return;
		}

		// Delete token before granting access to avoid concurrent double-use.
		$deleted = Nextfly_Domain_Restricted_Access_Database::delete_token( $token );
		if ( 1 !== (int) $deleted ) {
			$this->redirect_to_email_form( $post_id );
			return;
		}

		// Set access cookie.
		$this->set_access_cookie( $token_data->post_id, $token_data->email );

		/**
		 * Action triggered when access is granted.
		 *
		 * @since 1.0.0
		 *
		 * @param string $email   User email.
		 * @param int    $post_id Post ID.
		 */
		do_action( 'nfdra_access_granted', $token_data->email, $post_id );

		// Redirect to clean URL (without token parameter).
		$clean_url = get_permalink( $post_id );
		wp_safe_redirect( $clean_url );
		exit;
	}

	/**
	 * Redirect to email form page.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Post ID.
	 */
	private function redirect_to_email_form( $post_id ) {
		$redirect_page_id = get_option( 'nfdra_redirect_page', 0 );

		if ( ! $redirect_page_id ) {
			wp_die( esc_html__( 'The redirect page has not been configured in the settings. Please select a redirect page.', 'nextfly-domain-restricted-access' ) );
		}

		$redirect_url = get_permalink( $redirect_page_id );

		// Add return URL parameter.
		$redirect_url = add_query_arg( 'return_post_id', $post_id, $redirect_url );

		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Render shortcode for email form.
	 *
	 * @since 1.0.0
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function render_shortcode( $atts ) {
		// Get return post ID from URL.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$return_post_id = isset( $_GET['return_post_id'] ) ? absint( $_GET['return_post_id'] ) : 0;

		// If no return_post_id, trigger 404.
		if ( ! $return_post_id ) {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
			nocache_headers();
			return '<p>' . esc_html__( 'Invalid access. Please use the proper link.', 'nextfly-domain-restricted-access' ) . '</p>';
		}

		// Check if the post exists.
		$return_post = get_post( $return_post_id );
		if ( ! $return_post ) {
			return '<p>' . esc_html__( 'The requested page does not exist.', 'nextfly-domain-restricted-access' ) . '</p>';
		}

		// Check if the post is restricted (has authorized domains).
		$authorized_domains = Nextfly_Domain_Restricted_Access_Database::get_authorized_domains( $return_post_id );
		if ( empty( $authorized_domains ) ) {
			// Post is not restricted, redirect to the post.
			wp_safe_redirect( get_permalink( $return_post_id ) );
			exit;
		}

		// Check if user already has valid access cookie for this page.
		if ( $this->has_valid_cookie( $return_post_id ) ) {
			// User already has access, redirect directly to the page.
			wp_safe_redirect( get_permalink( $return_post_id ) );
			exit;
		}

		// All validation passed, render the form.
		ob_start();
		include NFDRA_PLUGIN_DIR . 'templates/public/email-form.php';
		return ob_get_clean();
	}

	/**
	 * AJAX handler for email submission.
	 *
	 * @since 1.0.0
	 */
	public function ajax_submit_email() {
		// Verify nonce.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'nfdra_email_form' ) ) {
			wp_send_json_error( array(
				'message' => __( 'Security check failed.', 'nextfly-domain-restricted-access' ),
			) );
		}

		// Get email and post ID.
		$email   = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

		// Validate inputs.
		if ( empty( $email ) || ! is_email( $email ) ) {
			wp_send_json_error( array(
				'message' => __( 'Please enter a valid email address.', 'nextfly-domain-restricted-access' ),
			) );
		}

		if ( ! $post_id || ! get_post( $post_id ) ) {
			wp_send_json_error( array(
				'message' => __( 'Invalid post ID.', 'nextfly-domain-restricted-access' ),
			) );
		}

		$client_ip      = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
		$rate_limit_key = 'nfdra_rate_' . md5( $client_ip . '|' . $post_id );

		if ( get_transient( $rate_limit_key ) ) {
			wp_send_json_error( array(
				'message' => __( 'Too many requests. Please wait before trying again.', 'nextfly-domain-restricted-access' ),
			) );
		}

		// Validate email domain.
		if ( ! Nextfly_Domain_Restricted_Access_Email_Handler::validate_email_domain( $email, $post_id ) ) {
			wp_send_json_error( array(
				'message' => __( 'Your email is not authorized to access this page.', 'nextfly-domain-restricted-access' ),
			) );
		}

		set_transient( $rate_limit_key, 1, MINUTE_IN_SECONDS );

		// Generate unique token.
		$token = Nextfly_Domain_Restricted_Access_Token_Manager::generate_unique_token();

		// Store token in database.
		$inserted = Nextfly_Domain_Restricted_Access_Database::insert_token( $post_id, $email, $token );

		if ( ! $inserted ) {
			wp_send_json_error( array(
				'message' => __( 'Failed to generate access token. Please try again.', 'nextfly-domain-restricted-access' ),
			) );
		}

		// Send access email.
		$sent = Nextfly_Domain_Restricted_Access_Email_Handler::send_access_email( $email, $token, $post_id );

		if ( ! $sent ) {
			wp_send_json_error( array(
				'message' => __( 'Failed to send email. Please try again later.', 'nextfly-domain-restricted-access' ),
			) );
		}

		// Success.
		wp_send_json_success( array(
			'message' => __( 'Access link sent! Please check your email.', 'nextfly-domain-restricted-access' ),
		) );
	}

	/**
	 * Enqueue frontend scripts and styles.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		if ( ! is_singular() ) {
			return;
		}

		$redirect_page_id = absint( get_option( 'nfdra_redirect_page', 0 ) );
		$current_post_id  = get_queried_object_id();

		if ( $redirect_page_id > 0 ) {
			if ( (int) $current_post_id !== (int) $redirect_page_id ) {
				return;
			}
		} else {
			global $post;
			if ( ! ( $post instanceof WP_Post ) || ! has_shortcode( $post->post_content, 'nextfly_domain_restricted_access' ) ) {
				return;
			}
		}

		// Enqueue styles.
		wp_enqueue_style(
			'nfdra-public-styles',
			NFDRA_PLUGIN_URL . 'public/css/public-style.css',
			array(),
			NFDRA_VERSION
		);

		// Enqueue scripts.
		wp_enqueue_script(
			'nfdra-public-scripts',
			NFDRA_PLUGIN_URL . 'public/js/public-script.js',
			array( 'jquery' ),
			NFDRA_VERSION,
			true
		);

		// Localize script with AJAX URL and nonce.
		wp_localize_script(
			'nfdra-public-scripts',
			'nfdraData',
			array(
				'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
				'emailFormNonce' => wp_create_nonce( 'nfdra_email_form' ),
				'messages'       => array(
					'processing'   => __( 'Processing...', 'nextfly-domain-restricted-access' ),
					'sending'      => __( 'Sending email...', 'nextfly-domain-restricted-access' ),
					'invalidEmail' => __( 'Please enter a valid email address.', 'nextfly-domain-restricted-access' ),
					'invalidPage'  => __( 'Invalid page reference.', 'nextfly-domain-restricted-access' ),
					'ajaxError'    => __( 'An error occurred. Please try again.', 'nextfly-domain-restricted-access' ),
				),
			)
		);
	}
}
