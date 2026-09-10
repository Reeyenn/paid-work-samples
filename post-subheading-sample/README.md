# Editable post subheading — working sample

Fresh AI-built sample for the proposed WordPress repair milestone. This is not a completed Enfold integration or a previous client project.

## What works

- Native WordPress post-editor meta box stores an optional plain-text subheading.
- WordPress REST metadata supports authorized edits.
- `[post_subheading]` renders the current post's escaped subheading. Blank text produces no markup.
- Unauthorized saves, invalid nonces and unrelated saves preserve existing data.
- Draft/private and password-protected post subheadings are protected when rendered by this plugin.

## Installation and use

Install the folder as a plugin and activate **Post Subheading — Working Sample** on staging. Edit a post, find **Post subheading**, and save. Insert `[post_subheading]` in the desired post content location. A theme adapter can call `\IncomeSample\Subheading\render($post_id)` after the title where needed.

The plugin does not automatically insert content or change the theme. Exact placement under a single-post title and inside Enfold Magazine requires a separately tested adapter for the installed Enfold version. The footer shortcode issue is a separate diagnosis. No Enfold, WooCommerce, or live customer environment was used in this sample.

## Verification

19 checks passed against a fresh WordPress 7.1 installation using the official SQLite integration 3.0.1 and PHP 8.5.10. See test-results.txt. This does not establish MySQL, commercial-theme, cache-plugin, multisite, or older WordPress/PHP compatibility.

To reproduce in a disposable WordPress sandbox, activate the plugin and run:

`wp eval-file /absolute/path/to/test.php`

The test creates and removes temporary posts and a subscriber, and assumes local administrator user ID 1. Use only a disposable sandbox. Editor markup is checked programmatically; no claim of browser interaction testing is made.

## Deployment and rollback

Back up staging before installing. Test with the actual editor, theme, caches and representative content. Deactivating stops the shortcode and field UI; stored metadata remains. Remove inserted shortcodes during rollback. There are no external requests, payments, telemetry or scheduled jobs.
