# Events Grid

A WordPress plugin that replaces hardcoded HTML event cards with dynamic event posts managed from the admin area. Outputs a responsive CSS Grid via a shortcode — works anywhere, including Elementor's shortcode widget.

**Version:** 1.2.3 | **Requires:** WordPress 6.0+, PHP 7.4+ | **License:** GPLv2

---

## Installation

1. Upload the `events-grid` folder to `/wp-content/plugins/`.
2. Activate the plugin in **WordPress Admin → Plugins**.
3. Add events under the new **Events** menu.
4. Place `[events_grid]` in any page, post, or Elementor shortcode widget.

---

## Shortcode

```
[events_grid]
```

### Attributes

| Attribute | Default | Description |
|-----------|---------|-------------|
| `columns` | `3` | Desktop column count |
| `tablet_columns` | `2` | Tablet column count |
| `mobile_columns` | `1` | Mobile column count |
| `posts_per_page` | `24` | Max events to show |
| `show_past` | `no` | `no` · `yes` · `only` · `merged` |
| `orderby` | `start_date` | Sort field |
| `order` | `ASC` | `ASC` or `DESC` |
| `category` | _(all)_ | Filter by event category slug |
| `upcoming_label` | _(default)_ | Custom heading for upcoming section |
| `past_label` | _(default)_ | Custom heading for past section |

### `show_past` values

| Value | Behaviour |
|-------|-----------|
| `no` | Upcoming events only (default) |
| `yes` | Upcoming section, then past section below |
| `only` | Past events only |
| `merged` | Upcoming then past in a single grid |

### Examples

```
[events_grid]
[events_grid columns="4" tablet_columns="2" mobile_columns="1"]
[events_grid posts_per_page="6" show_past="yes"]
[events_grid category="conference" upcoming_label="Upcoming Conferences"]
```

---

## Event Fields

Each event post supports the following meta:

| Field | Description |
|-------|-------------|
| Start Date | Event start date |
| End Date | Event end date |
| Venue | Location / venue name |
| Booth Number | Stand or booth reference |
| Speaker Badge | Badge label for speakers |
| URL | External link for the event card |

---

## Settings

**Events → Settings** exposes:

| Setting | Description |
|---------|-------------|
| Hide past events after N days | Auto-hide past events after N days (0 = never hide) |
| Load Roboto from Google Fonts | Toggle the external Google Fonts request |

---

## CSV Importer

Bulk-import events from a CSV file via **Events → Import**.

- Validates file content (not just extension)
- 5 MB file size limit
- 2,000-row import cap
- Duplicate detection via pre-loaded hash set
- Cache invalidation flushed once at the end of the import

---

## Performance & Caching

- Shortcode output is cached as a 1-hour transient, keyed by attributes, date, locale, and a version counter.
- Cache is auto-invalidated whenever any event is saved or deleted.
- All event meta loaded in a single query per grid (no N+1).

---

## Files

```
events-grid/
├── events-grid.php              # Plugin entry point
├── uninstall.php                # Cleanup on uninstall
├── includes/
│   ├── class-events-grid.php              # Core singleton
│   ├── class-events-grid-cpt.php          # Custom post type & taxonomy
│   ├── class-events-grid-meta-boxes.php   # Event meta fields
│   ├── class-events-grid-shortcode.php    # Shortcode rendering & cache
│   ├── class-events-grid-settings.php     # Settings page
│   ├── class-events-grid-importer.php     # CSV importer
│   ├── class-events-grid-admin-columns.php # Admin list columns
│   ├── class-events-grid-helpers.php      # Shared utilities
│   ├── class-events-grid-help.php         # Admin help tab
│   └── class-events-grid-updater.php      # Auto-updater (SHA-256 verified)
└── assets/css/                  # Plugin stylesheets
```

---

## Changelog

### 1.2.3
- Fix: Reverted stylesheet registration to `wp_register_style()` so CSS is only enqueued when the shortcode renders.
- Fix: Shortcode now injects an inline `<link>` when `wp_head` has already fired (covers AJAX / NitroPack lazy-load contexts).

### 1.2.2
- Fix: Cache key includes the current date so past events are excluded after midnight without a manual save.

### 1.2.0
- Security: CSV upload validates actual file content via `finfo`/`mime_content_type`.
- Security: 5 MB file size limit and 2,000-row cap on CSV imports.
- Security: Auto-updater verifies SHA-256 checksum from update server.
- Performance: CSV duplicate detection uses a single pre-loaded hash set.
- Performance: Bulk import suspends cache invalidation during insert, flushes once at end.
- Performance: `show_past="merged"` uses a single database query.
- New: "Load Roboto from Google Fonts" toggle in Settings.

### 1.1.2
- Added **Hide past events after N days** setting.

### 1.1.1
- Added **Modified By** column to the Events admin list table.

### 1.1.0
- Added Event Categories taxonomy with `category` shortcode attribute.
- Added `upcoming_label` and `past_label` shortcode attributes.
- Fixed N+1 database query — all meta loaded in a single query.
- Added shortcode output caching (1-hour transient).
- Added Start Date, End Date, Venue, and Country admin columns.
- Registered all meta fields with the REST API.

### 1.0.0
- Initial release.
