<?php
/**
 * Metabox template for authorized domains.
 *
 * @package Nextfly_Domain_Restricted_Access
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Add nonce field for security.
wp_nonce_field( 'nfdra_save_metabox', 'nfdra_metabox_nonce' );
?>

<div class="nfdra-metabox">
	<div>
		<label for="nfdra_authorized_domains">
			<strong><?php esc_html_e( 'Authorized Email Domains', 'nextfly-domain-restricted-access' ); ?></strong>
		</label>
	</div>
	<p class="description">
		<?php esc_html_e( 'Enter one domain per line (e.g., company.com). Only users with email addresses from these domains can request access to this page.', 'nextfly-domain-restricted-access' ); ?>
	</p>
	<textarea
		id="nfdra_authorized_domains"
		name="nfdra_authorized_domains"
		rows="5"
		style="width: 100%;"
		placeholder="example.com&#10;company.org&#10;domain.net"
	><?php echo esc_textarea( $authorized_domains ); ?></textarea>
	<p class="description">
		<?php esc_html_e( 'Leave empty to disable email validation for this page.', 'nextfly-domain-restricted-access' ); ?>
	</p>
</div>
