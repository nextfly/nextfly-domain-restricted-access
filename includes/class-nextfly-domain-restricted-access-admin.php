<?php
/**
 * Admin class.
 *
 * @package Nextfly_Domain_Restricted_Access
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Nextfly_Domain_Restricted_Access_Admin
 *
 * Handles admin functionality.
 *
 * @since 1.0.0
 */
class Nextfly_Domain_Restricted_Access_Admin {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// Add metabox to posts and pages.
		add_action( 'add_meta_boxes', array( $this, 'add_metabox' ) );

		// Save metabox data.
		add_action( 'save_post', array( $this, 'save_metabox' ) );

		// Add settings page.
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );

		// Register settings.
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// Enqueue admin styles.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );

		// Add settings link to plugins page.
		add_filter( 'plugin_action_links_' . NFDRA_PLUGIN_BASENAME, array( $this, 'add_plugin_action_links' ) );
	}

	/**
	 * Add settings link to plugins page.
	 *
	 * @since 1.0.0
	 *
	 * @param array $links Plugin action links.
	 * @return array Modified plugin action links.
	 */
	public function add_plugin_action_links( $links ) {
		$settings_link = '<a href="' . admin_url( 'options-general.php?page=nextfly-domain-restricted-access-settings' ) . '">' . __( 'Settings', 'nextfly-domain-restricted-access' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Add metabox to posts and pages.
	 *
	 * @since 1.0.0
	 */
	public function add_metabox() {
		$post_types = array( 'post', 'page' );

		/**
		 * Filter the post types where the metabox should appear.
		 *
		 * @since 1.0.0
		 *
		 * @param array $post_types Array of post types.
		 */
		$post_types = apply_filters( 'nfdra_post_types', $post_types );

		foreach ( $post_types as $post_type ) {
			add_meta_box(
				'nfdra_authorized_domains',
				__( 'Nextfly Domain Restricted Access', 'nextfly-domain-restricted-access' ),
				array( $this, 'render_metabox' ),
				$post_type,
				'side',
				'low'
			);
		}
	}

	/**
	 * Render metabox content.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post $post Post object.
	 */
	public function render_metabox( $post ) {
		// Get current authorized domains.
		$authorized_domains = get_post_meta( $post->ID, '_nfdra_authorized_domains', true );

		// Load template.
		include NFDRA_PLUGIN_DIR . 'templates/admin/metabox-authorized-domains.php';
	}

	/**
	 * Save metabox data.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Post ID.
	 */
	public function save_metabox( $post_id ) {
		// Check if nonce is set.
		if ( ! isset( $_POST['nfdra_metabox_nonce'] ) ) {
			return;
		}

		// Verify nonce.
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nfdra_metabox_nonce'] ) ), 'nfdra_save_metabox' ) ) {
			return;
		}

		// Check if autosave.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check user permissions.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Save authorized domains.
		if ( isset( $_POST['nfdra_authorized_domains'] ) ) {
			$domains = sanitize_textarea_field( wp_unslash( $_POST['nfdra_authorized_domains'] ) );
			Nextfly_Domain_Restricted_Access_Database::save_authorized_domains( $post_id, $domains );
		} else {
			delete_post_meta( $post_id, '_nfdra_authorized_domains' );
		}
	}

	/**
	 * Add settings page under Settings menu.
	 *
	 * @since 1.0.0
	 */
	public function add_settings_page() {
		add_options_page(
			__( 'Nextfly Domain Restricted Access Settings', 'nextfly-domain-restricted-access' ),
			__( 'Nextfly Domain Restricted Access', 'nextfly-domain-restricted-access' ),
			'manage_options',
			'nextfly-domain-restricted-access-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Render settings page.
	 *
	 * @since 1.0.0
	 */
	public function render_settings_page() {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Load template.
		include NFDRA_PLUGIN_DIR . 'templates/admin/settings-page.php';
	}

	/**
	 * Register plugin settings.
	 *
	 * @since 1.0.0
	 */
	public function register_settings() {
		// Register settings.
		register_setting( 'nfdra_settings_group', 'nfdra_email_subject', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'Your Access Link for ' . get_bloginfo( 'name' ),
		) );

		register_setting( 'nfdra_settings_group', 'nfdra_email_body', array(
			'type'              => 'string',
			'sanitize_callback' => array( $this, 'sanitize_email_body' ),
			'default'           => "Hello,\n\nYou have requested access to protected content.\n\nClick the link below to access the page:\n\n%access_link%\n\nThis link will expire after one use or 24 hours, whichever comes first.",
		) );

		register_setting( 'nfdra_settings_group', 'nfdra_cookie_duration', array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 7,
		) );

		register_setting( 'nfdra_settings_group', 'nfdra_redirect_page', array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 0,
		) );

		// Add settings section.
		add_settings_section(
			'nfdra_main_settings',
			__( 'Email & Access Settings', 'nextfly-domain-restricted-access' ),
			array( $this, 'settings_section_callback' ),
			'nextfly-domain-restricted-access-settings'
		);

		// Add settings fields.
		add_settings_field(
			'nfdra_email_subject',
			__( 'Email Subject', 'nextfly-domain-restricted-access' ),
			array( $this, 'email_subject_field_callback' ),
			'nextfly-domain-restricted-access-settings',
			'nfdra_main_settings'
		);

		add_settings_field(
			'nfdra_email_body',
			__( 'Email Body Template', 'nextfly-domain-restricted-access' ),
			array( $this, 'email_body_field_callback' ),
			'nextfly-domain-restricted-access-settings',
			'nfdra_main_settings'
		);

		add_settings_field(
			'nfdra_cookie_duration',
			__( 'Cookie Duration (Days)', 'nextfly-domain-restricted-access' ),
			array( $this, 'cookie_duration_field_callback' ),
			'nextfly-domain-restricted-access-settings',
			'nfdra_main_settings'
		);

		add_settings_field(
			'nfdra_redirect_page',
			__( 'Redirect Page', 'nextfly-domain-restricted-access' ),
			array( $this, 'redirect_page_field_callback' ),
			'nextfly-domain-restricted-access-settings',
			'nfdra_main_settings'
		);
	}

	/**
	 * Settings section callback.
	 *
	 * @since 1.0.0
	 */
	public function settings_section_callback() {
		echo '<p>' . esc_html__( 'Configure email templates and access settings for the plugin.', 'nextfly-domain-restricted-access' ) . '</p>';
	}

	/**
	 * Email subject field callback.
	 *
	 * @since 1.0.0
	 */
	public function email_subject_field_callback() {
		$value = get_option( 'nfdra_email_subject', 'Your Access Link for ' . get_bloginfo( 'name' ) );
		echo '<input type="text" name="nfdra_email_subject" value="' . esc_attr( $value ) . '" class="regular-text" />';
	}

	/**
	 * Email body field callback.
	 *
	 * @since 1.0.0
	 */
	public function email_body_field_callback() {
		$value = get_option( 'nfdra_email_body', "Hello,\n\nYou have requested access to protected content.\n\nClick the link below to access the page:\n\n%access_link%\n\nThis link will expire after one use or 24 hours, whichever comes first." );
		echo '<textarea name="nfdra_email_body" rows="10" class="large-text">' . esc_textarea( $value ) . '</textarea>';
		echo '<p class="description">' . esc_html__( 'Use %access_link% as a placeholder for the access link.', 'nextfly-domain-restricted-access' ) . '</p>';
	}

	/**
	 * Cookie duration field callback.
	 *
	 * @since 1.0.0
	 */
	public function cookie_duration_field_callback() {
		$value = get_option( 'nfdra_cookie_duration', 7 );
		echo '<input type="number" name="nfdra_cookie_duration" value="' . esc_attr( $value ) . '" min="1" max="365" class="small-text" />';
		echo '<p class="description">' . esc_html( __( 'Number of days the access cookie should remain valid.', 'nextfly-domain-restricted-access' ) ) . '</p>';
	}

	/**
	 * Redirect page field callback.
	 *
	 * @since 1.0.0
	 */
	public function redirect_page_field_callback() {
		$value = get_option( 'nfdra_redirect_page', 0 );
		wp_dropdown_pages( array(
			'name'              => 'nfdra_redirect_page',
			'selected'          => absint( $value ),
			'show_option_none'  => esc_html__( '— Select Page —', 'nextfly-domain-restricted-access' ),
			'option_none_value' => 0,
		) );
		echo '<p class="description">' . esc_html__( 'Page containing the [nextfly_domain_restricted_access] shortcode where users will be redirected to enter their email.', 'nextfly-domain-restricted-access' ) . '</p>';
	}

	/**
	 * Enqueue admin styles.
	 *
	 * @since 1.0.0
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_styles( $hook ) {
		// Load on post edit screen and settings page.
		if ( 'post.php' === $hook || 'post-new.php' === $hook || 'settings_page_nextfly-domain-restricted-access-settings' === $hook ) {
			wp_enqueue_style(
				'nfdra-admin-styles',
				NFDRA_PLUGIN_URL . 'admin/css/admin-style.css',
				array(),
				NFDRA_VERSION
			);
		}
	}

	/**
	 * Sanitize email body while preserving placeholders.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value Email body content.
	 * @return string Sanitized email body.
	 */
	public function sanitize_email_body( $value ) {
		// Use wp_kses_post to allow basic HTML and preserve special characters.
		// Then strip tags to keep it as plain text but preserve placeholders.
		$sanitized = wp_strip_all_tags( $value );
		
		return $sanitized;
	}
}
