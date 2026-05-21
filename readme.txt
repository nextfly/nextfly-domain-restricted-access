=== Nextfly Domain Restricted Access ===
Contributors: nextfly
Tags: magic link, access control, email verification, restricted access, nextfly
Requires at least: 5.8
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Restrict access to post and page based on authorized email domains. Users verify identity via secure email links.

== Description ==

Nextfly Domain Restricted Access allows you to restrict post and page access to specific email domains. Users must validate their email address via a unique magic link sent to their inbox.

This plugin is perfect for internal company portals, client-specific pages, or any content that should be accessible only to users with a specific email domain (e.g., `@company.com`).

**Key Features:**
*   **Domain-Based Restriction:** Easily whitelist email domains for any post or page.
*   **Secure Magic Links:** Users receive a time-sensitive, one-time-use access link via email.
*   **No Passwords Required:** Simplifies user experience by eliminating the need for passwords.
*   **Customizable Emails:** Configure the email subject and body template.
*   **Cookie-Based Access:** Set how long access remains valid (default: 7 days).
*   **Developer Friendly:** Includes hooks for extending functionality.

== Installation ==

1.  Upload the `nextfly-domain-restricted-access` folder to the `/wp-content/plugins/` directory.
2.  Activate the plugin through the 'Plugins' menu in WordPress.
3.  Go to **Settings > Nextfly Domain Restricted Access** to configure the plugin.
4.  Create a dedicated page and add the `[nextfly_domain_restricted_access]` shortcode. This page will display the email input form.
5.  In the plugin settings, set this new page as the "Redirect Page".

== Usage ==

**To Protect a Post/Page:**
1.  Edit any post or page you want to protect.
2.  Look for the **"Nextfly Domain Restricted Access"** metabox in the sidebar.
3.  Enter the authorized email domains (one per line, e.g., `company.com`).
4.  Update or Publish the post.

**To Configure Settings:**
1.  Navigate to **Settings > Nextfly Domain Restricted Access**.
2.  Customize the email subject and body (use `%access_link%` as a placeholder).
3.  Set the cookie duration (how long a user stays logged in).
4.  Select the Redirect Page (where users are sent to login).

== Developers ==

The plugin includes hooks to extend its functionality.

**Filters:**
`nfdra_post_types` - Add support for custom post types.
`nfdra_email_headers` - Modify email headers (e.g., add BCC).

**Actions:**
`nfdra_before_send_email` - Fires before email is sent.
`nfdra_after_send_email` - Fires after email is sent.
`nfdra_access_granted` - Fires when user is granted access.
`nfdra_access_denied` - Fires when access token is invalid.

== Frequently Asked Questions ==

= Can I use this for multiple domains? =
Yes! You can enter multiple authorized domains for a single post/page, one per line.

= How long does the access link last? =
The magic link itself expires after 24 hours or after a single use. Once used, a cookie is set for the user, which remains valid based on your "Cookie Duration" setting (default 7 days).

= Does this work with custom post types? =
Yes, but you need to enable it. Use the `nfdra_post_types` filter to add your custom post type slug to the supported list. See the "Developers" section for details.

== Screenshots ==

1.  **Settings Page:** Configure email templates and cookie duration.
2.  **Metabox:** Easily add authorized domains to any post or page.
3.  **Frontend Form:** The clean, simple email request form presented to users.

== Changelog ==

= 1.0.0 =
*   Initial release.