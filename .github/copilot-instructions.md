# Copilot Instructions for Nextfly Domain Restricted Access

## Build, test, and lint commands

- This repository does not include project-defined automation for build, lint, or tests (`composer.json`, `package.json`, `phpunit.xml*`, `phpcs.xml*`, and `Makefile` are not present).
- There is no full-suite or single-test command checked into this repository.
- For PHP syntax validation during edits, run:
  - `php -l nextfly-domain-restricted-access.php`
  - `php -l includes/class-nextfly-domain-restricted-access-frontend.php` (swap in the file you changed)

## High-level architecture

- `nextfly-domain-restricted-access.php` is the plugin bootstrap: defines constants, loads the main orchestrator class, registers activation/deactivation hooks, and schedules the hourly `nfdra_cleanup_tokens` cron event.
- `includes/class-nextfly-domain-restricted-access.php` is the orchestrator: loads all dependencies, instantiates admin logic only in wp-admin, and always instantiates frontend logic.
- Admin flow:
  - `includes/class-nextfly-domain-restricted-access-admin.php` registers a post/page metabox for authorized domains, plugin settings, and admin styles.
  - Templates are in `templates/admin/` (`metabox-authorized-domains.php`, `settings-page.php`).
- Frontend access flow (`includes/class-nextfly-domain-restricted-access-frontend.php`):
  1. On `template_redirect`, restricted singular content is checked via `_nfdra_authorized_domains`.
  2. If no valid access cookie and no valid token, user is redirected to configured `nfdra_redirect_page` with `return_post_id`.
  3. The shortcode `[nextfly_domain_restricted_access]` renders `templates/public/email-form.php`.
  4. `public/js/public-script.js` submits AJAX `nfdra_submit_email`.
  5. AJAX validates input/domain, generates token, stores it, and sends email with `%access_link%`.
  6. Token validation sets `nfdra_access_{post_id}` cookie, deletes token, and redirects to the clean permalink.
- Data/storage:
  - Token table: `{$wpdb->prefix}nfdra_access_tokens` (created on activation; dropped on uninstall).
  - Post meta: `_nfdra_authorized_domains` (one domain per line).
  - Options: `nfdra_email_subject`, `nfdra_email_body`, `nfdra_cookie_duration`, `nfdra_redirect_page`.
- Supporting classes:
  - `includes/class-nextfly-domain-restricted-access-token-manager.php` handles token generation/expiry validation.
  - `includes/class-nextfly-domain-restricted-access-email-handler.php` validates domains and sends access email.
  - `includes/class-nextfly-domain-restricted-access-database.php` is the storage layer for tokens/options/domain meta helpers.

## Key repository conventions

- `.github/instructions/wordpress.instructions.md` exists for broad WordPress guidance; keep this file focused on repository-specific behavior and commands.
- Naming/prefix conventions are strict:
  - Hooks/options/meta/actions use `nfdra_` prefixes.
  - Classes use `Nextfly_Domain_Restricted_Access_*` names.
- Always use the text domain `nextfly-domain-restricted-access` for translation functions.
- Keep access flow wired through existing identifiers:
  - Shortcode: `[nextfly_domain_restricted_access]`
  - AJAX action: `nfdra_submit_email`
  - URL params: `access_token`, `return_post_id`
  - Cron hook: `nfdra_cleanup_tokens`
- Reuse existing extension points before adding new ones:
  - Actions: `nfdra_before_send_email`, `nfdra_after_send_email`, `nfdra_access_granted`, `nfdra_access_denied`
  - Filters: `nfdra_email_headers`, `nfdra_post_types`
- Follow existing security/sanitization patterns:
  - Guard files with `if ( ! defined( 'ABSPATH' ) ) { exit; }`
  - Verify nonces on form/AJAX handlers and sanitize via `wp_unslash()` + `sanitize_*`.
  - Use `wp_safe_redirect()` and `exit` for redirects.
- Keep templates presentational and load them via `include NFDRA_PLUGIN_DIR . 'templates/...';`; keep business logic in `includes/` classes.
