<?php
/**
 * Settings page template.
 *
 * @package Nextfly_Domain_Restricted_Access
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<?php settings_errors(); ?>

	<form method="post" action="options.php">
		<?php
		settings_fields( 'nfdra_settings_group' );
		do_settings_sections( 'nextfly-domain-restricted-access-settings' );
		submit_button();
		?>
	</form>

	<hr>

	<div class="nfdra-settings-info">
		<h2><?php esc_html_e( 'How to Use', 'nextfly-domain-restricted-access' ); ?></h2>
		<ol>
            <li>
                <?php
				echo wp_kses_post(
					sprintf(
						/* translators: %s: shortcode markup */
						__( 'Create a page and add the %s shortcode to display the email input form.', 'nextfly-domain-restricted-access' ),
						'<code>[nextfly_domain_restricted_access]</code>'
					)
				);
                ?>
            </li>
			<li><?php esc_html_e( 'Select that page in the "Redirect Page" dropdown above.', 'nextfly-domain-restricted-access' ); ?></li>
			<li><?php esc_html_e( 'Edit any post or page you want to protect.', 'nextfly-domain-restricted-access' ); ?></li>
			<li><?php esc_html_e( 'In the "Nextfly Domain Restricted Access" metabox, add the authorized email domains (one per line).', 'nextfly-domain-restricted-access' ); ?></li>
			<li><?php esc_html_e( 'When users try to access the protected page, they will be redirected to enter their email address.', 'nextfly-domain-restricted-access' ); ?></li>
			<li><?php esc_html_e( 'If their email domain matches, they will receive an access link via email.', 'nextfly-domain-restricted-access' ); ?></li>
		</ol>

		<h3><?php esc_html_e( 'Email Template Variables', 'nextfly-domain-restricted-access' ); ?></h3>
		<ul>
			<li><code>%access_link%</code> - <?php esc_html_e( 'The unique access link that will be sent to the user.', 'nextfly-domain-restricted-access' ); ?></li>
		</ul>

		<h3><?php esc_html_e( 'Shortcode', 'nextfly-domain-restricted-access' ); ?></h3>
		<p><code>[nextfly_domain_restricted_access]</code> - <?php esc_html_e( 'Displays the email input form for requesting access.', 'nextfly-domain-restricted-access' ); ?></p>
	</div>
</div>
