=== Events Grid ===
Contributors: openai
Tags: events, elementor, shortcode, grid
Requires at least: 6.0
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 1.0.0
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

= 1.0.0 =
* Initial release.
