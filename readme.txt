=== Events Grid ===
Contributors: OpenAI
Tags: events, elementor, shortcode, grid
Requires at least: 6.0
Tested up to: 6.9.4
Requires PHP: 7.4
Stable tag: 1.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A dynamic events grid plugin for WordPress with shortcode support for Elementor.

== Description ==

Events Grid replaces hardcoded HTML event cards with dynamic event posts managed from the WordPress admin area.

Features:
- Custom post type for events
- Meta boxes for dates, venue, booth number, speaker badge, and URL
- Responsive CSS Grid layout
- Shortcode support for Elementor shortcode widget
- Upcoming-only view by default

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`.
2. Activate the plugin in WordPress admin.
3. Add events under the new `Events` menu.
4. Place `[events_grid]` in a page, post, or Elementor shortcode widget.

== Shortcode ==

`[events_grid]`

Examples:
- `[events_grid]`
- `[events_grid columns="4" tablet_columns="2" mobile_columns="1"]`
- `[events_grid posts_per_page="6" show_past="yes"]`

== Changelog ==

= 1.2.0 =
* Security: CSV upload now validates actual file content (finfo/mime_content_type), not just the file extension.
* Security: Added 5 MB file size limit on CSV imports.
* Security: Auto-updater now verifies SHA-256 checksum when the update server provides one.
* Security: Added 2,000-row hard cap on CSV imports to prevent memory exhaustion.
* Performance: CSV duplicate detection replaced with a single pre-loaded hash set (was one WP_Query per row).
* Performance: CSV bulk import suspends cache invalidation during insert and flushes once at the end.
* Performance: `show_past="merged"` shortcode now uses a single database query instead of two.
* New: Added "Load Roboto from Google Fonts" toggle under Events > Settings to disable the external font request.

= 1.1.2 =
* Added Events > Settings page with a "Hide past events after N days" option (0 = show all past events, existing default).

= 1.1.1 =
* Added Modified By column to the Events admin list table showing which user last edited each event.

= 1.1.0 =
* Added Event Categories taxonomy with shortcode `category` attribute for filtering.
* Added `upcoming_label` and `past_label` shortcode attributes for section headings.
* Fixed N+1 database query — all event meta loaded in a single query per grid.
* Added shortcode output caching (1-hour transient, auto-invalidated on content change).
* Added Start Date, End Date, Venue, and Country columns to the Events admin list table.
* Registered all meta fields with the REST API.
* Moved Google Fonts from CSS @import to WordPress asset pipeline.
* Removed CSS !important declarations in favour of scoped selectors.

= 1.0.0 =
* Initial release.
