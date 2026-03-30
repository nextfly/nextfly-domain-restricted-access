/**
 * Public JavaScript for Nextfly Domain Restricted Access plugin.
 *
 * @package Nextfly_Domain_Restricted_Access
 */

(function ($) {
	'use strict';

	/**
	 * Initialize on document ready.
	 */
	$(document).ready(function () {
		// Handle email form submission.
		handleEmailFormSubmission();
	});

	/**
	 * Handle email form submission via AJAX.
	 */
	function handleEmailFormSubmission() {
		var $form = $('#nfdra-email-form');

		if (!$form.length) {
			return;
		}

		$form.on('submit', function (e) {
			e.preventDefault();

			var $submitButton = $form.find('.nfdra-form-submit');
			var $message = $form.find('.nfdra-form-message');
			var $loading = $form.find('.nfdra-form-loading');
			var $emailInput = $form.find('#nfdra-email-input');
			var $postIdInput = $form.find('input[name="nfdra_post_id"]');

			var email = $emailInput.val().trim();
			var postId = $postIdInput.val();

			// Reset messages.
			$message.hide().removeClass('success error').text('');

			// Validate email.
			if (!email || !isValidEmail(email)) {
				showMessage($message, 'error', nfdraData.messages.invalidEmail);
				return;
			}

			// Validate post ID.
			if (!postId || postId === '0') {
				showMessage($message, 'error', nfdraData.messages.invalidPage);
				return;
			}

			// Disable form.
			$submitButton.prop('disabled', true);
			$emailInput.prop('disabled', true);
			$loading.show();

			// Send AJAX request.
			$.ajax({
				url: nfdraData.ajaxUrl,
				type: 'POST',
				data: {
					action: 'nfdra_submit_email',
					nonce: nfdraData.emailFormNonce,
					email: email,
					post_id: postId
				},
				success: function (response) {
					if (response.success) {
						showMessage($message, 'success', response.data.message);
						$emailInput.val(''); // Clear input on success.
					} else {
						showMessage($message, 'error', response.data.message);
					}
				},
				error: function () {
					showMessage($message, 'error', nfdraData.messages.ajaxError);
				},
				complete: function () {
					// Re-enable form.
					$submitButton.prop('disabled', false);
					$emailInput.prop('disabled', false);
					$loading.hide();
				}
			});
		});
	}

	/**
	 * Show message in form.
	 *
	 * @param {jQuery} $element Message element.
	 * @param {string} type     Message type (success/error).
	 * @param {string} message  Message text.
	 */
	function showMessage($element, type, message) {
		$element
			.removeClass('success error')
			.addClass(type)
			.text(message)
			.fadeIn();
	}

	/**
	 * Validate email format.
	 *
	 * @param {string} email Email address.
	 * @return {boolean} True if valid, false otherwise.
	 */
	function isValidEmail(email) {
		var regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
		return regex.test(email);
	}

})(jQuery);
