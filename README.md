# Nextfly Domain Restricted Access

**Contributors:** nextfly  
**Tags:** magic link, access control, email verification, restricted access, nextfly
**Requires at least:** 5.8  
**Tested up to:** 7.0
**Requires PHP:** 7.4  
**Stable tag:** 1.0.0  
**License:** GPLv2 or later  
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html

Restrict access to content based on authorized email domains. Users verify identity via secure email links.

## Description

Nextfly Domain Restricted Access allows you to restrict page and post access to specific email domains. Users must validate their email address via a unique magic link sent to their inbox.

This plugin is perfect for internal company portals, client-specific pages, or any content that should be accessible only to users with a specific email domain (e.g., `@company.com`).

**Key Features:**
*   **Domain-Based Restriction:** Easily whitelist email domains for any post or page.
*   **Secure Magic Links:** Users receive a time-sensitive, one-time-use access link via email.
*   **No Passwords Required:** Simplifies user experience by eliminating the need for passwords.
*   **Customizable Emails:** Configure the email subject and body template.
*   **Developer Friendly:** Includes hooks for extending functionality.

## Installation

1.  Upload the `nextfly-domain-restricted-access` folder to the `/wp-content/plugins/` directory.
2.  Activate the plugin through the 'Plugins' menu in WordPress.
3.  Go to **Settings > Nextfly Domain Restricted Access** to configure the plugin.
4.  Create a dedicated page and add the `[nextfly_domain_restricted_access]` shortcode. This page will display the email input form.
5.  In the plugin settings, set this new page as the "Redirect Page".

## Usage

**To Protect a Page/Post:**
1.  Edit any page or post you want to protect.
2.  Look for the **"Nextfly Domain Restricted Access"** metabox in the sidebar.
3.  Enter the authorized email domains (one per line, e.g., `company.com`).
4.  Update or Publish the post.

**To Configure Settings:**
1.  Navigate to **Settings > Nextfly Domain Restricted Access**.
2.  Customize the email subject and body (use `%access_link%` as a placeholder).
3.  Set the cookie duration (how long a user stays logged in).
4.  Select the Redirect Page (where users are sent to login).

## Developers

The plugin includes several hooks to allow developers to extend its functionality.

### Actions

*   `nfdra_before_send_email`
    *   **Description:** Fires before the access email is sent.
    *   **Parameters:** `$email` (string), `$post_id` (int)
*   `nfdra_after_send_email`
    *   **Description:** Fires after the access email is successfully sent.
    *   **Parameters:** `$email` (string), `$post_id` (int)
*   `nfdra_access_granted`
    *   **Description:** Fires when a user successfully validates their token and is granted access.
    *   **Parameters:** `$email` (string), `$post_id` (int)
*   `nfdra_access_denied`
    *   **Description:** Fires when a user attempts to use an invalid or expired token.
    *   **Parameters:** `$token` (string), `$post_id` (int)

### Filters

*   `nfdra_email_headers`
    *   **Description:** Filter the headers for the access email.
    *   **Parameters:** `$headers` (array)
    *   **Returns:** (array) Modified headers.
*   `nfdra_post_types`
    *   **Description:** Filter the post types where the "Nextfly Domain Restricted Access" metabox should appear.
    *   **Parameters:** `$post_types` (array) - default: `['post', 'page']`
    *   **Returns:** (array) Modified array of post types.

## Changelog

**1.0.0**
*   Initial release.
