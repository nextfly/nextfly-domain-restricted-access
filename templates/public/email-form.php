<?php
/**
 * Email form template.
 *
 * @package Nextfly_Domain_Restricted_Access
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="nfdra-email-form-wrapper">
	<div class="nfdra-email-form-container">
		<h3 class="nfdra-form-title"><?php esc_html_e( 'Email Verification Required', 'nextfly-domain-restricted-access' ); ?></h3>
		
		<p class="nfdra-form-description">
			<?php esc_html_e( 'Please enter your email address to request access to this page.', 'nextfly-domain-restricted-access' ); ?>
		</p>

		<form id="nfdra-email-form" class="nfdra-email-form" method="post">
			<div class="nfdra-form-group">
				<label for="nfdra-email-input" class="nfdra-form-label">
					<?php esc_html_e( 'Email Address', 'nextfly-domain-restricted-access' ); ?>
				</label>
				<input
					type="email"
					id="nfdra-email-input"
					name="nfdra_email"
					class="nfdra-form-input"
					placeholder="<?php esc_attr_e( 'your.email@company.com', 'nextfly-domain-restricted-access' ); ?>"
					required
				/>
			</div>

			<input type="hidden" name="nfdra_post_id" value="<?php echo esc_attr( $return_post_id ); ?>" />

			<div class="nfdra-form-actions">
				<button type="submit" class="btn btn-primary nfdra-form-submit">
					<?php esc_html_e( 'Request Access', 'nextfly-domain-restricted-access' ); ?>
				</button>
			</div>

			<div class="nfdra-form-message" style="display: none;"></div>
			<div class="nfdra-form-loading" style="display: none;">
				<span class="nfdra-spinner"></span>
				<span class="nfdra-loading-text"><?php esc_html_e( 'Processing...', 'nextfly-domain-restricted-access' ); ?></span>
			</div>
		</form>
	</div>
</div>
