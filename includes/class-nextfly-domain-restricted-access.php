<?php
/**
 * Main plugin class.
 *
 * @package Nextfly_Domain_Restricted_Access
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Nextfly_Domain_Restricted_Access
 *
 * Main plugin orchestrator class.
 *
 * @since 1.0.0
 */
class Nextfly_Domain_Restricted_Access {

	/**
	 * Plugin instance.
	 *
	 * @since 1.0.0
	 * @var Nextfly_Domain_Restricted_Access
	 */
	private static $instance = null;

	/**
	 * Admin class instance.
	 *
	 * @since 1.0.0
	 * @var Nextfly_Domain_Restricted_Access_Admin
	 */
	public $admin;

	/**
	 * Frontend class instance.
	 *
	 * @since 1.0.0
	 * @var Nextfly_Domain_Restricted_Access_Frontend
	 */
	public $frontend;

	/**
	 * Get plugin instance.
	 *
	 * @since 1.0.0
	 *
	 * @return Nextfly_Domain_Restricted_Access
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		$this->load_dependencies();
		$this->init_hooks();
	}

	/**
	 * Load required dependencies.
	 *
	 * @since 1.0.0
	 */
	private function load_dependencies() {
		// Load database class.
		require_once NFDRA_PLUGIN_DIR . 'includes/class-nextfly-domain-restricted-access-database.php';

		// Load token manager class.
		require_once NFDRA_PLUGIN_DIR . 'includes/class-nextfly-domain-restricted-access-token-manager.php';

		// Load email handler class.
		require_once NFDRA_PLUGIN_DIR . 'includes/class-nextfly-domain-restricted-access-email-handler.php';

		// Load admin class.
		require_once NFDRA_PLUGIN_DIR . 'includes/class-nextfly-domain-restricted-access-admin.php';

		// Load frontend class.
		require_once NFDRA_PLUGIN_DIR . 'includes/class-nextfly-domain-restricted-access-frontend.php';
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 1.0.0
	 */
	private function init_hooks() {
		// Initialize admin functionality.
		if ( is_admin() ) {
			$this->admin = new Nextfly_Domain_Restricted_Access_Admin();
		}

		// Initialize frontend functionality.
		$this->frontend = new Nextfly_Domain_Restricted_Access_Frontend();

		// Register cron job for token cleanup.
		add_action( 'nfdra_cleanup_tokens', array( $this, 'cleanup_expired_tokens' ) );
	}

	/**
	 * Cleanup expired tokens.
	 *
	 * @since 1.0.0
	 */
	public function cleanup_expired_tokens() {
		Nextfly_Domain_Restricted_Access_Database::delete_expired_tokens();
	}
}
