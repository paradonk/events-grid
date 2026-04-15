<?php
/**
 * Shortcode help/reference admin page.
 *
 * @package EventsGrid
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the Shortcode Guide admin page.
 */
class Events_Grid_Help {

	/**
	 * Hook setup.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
		add_action( 'admin_head', array( __CLASS__, 'inline_styles' ) );
	}

	/**
	 * Register admin submenu.
	 *
	 * @return void
	 */
	public static function register_menu() {
		add_submenu_page(
			'edit.php?post_type=event',
			__( 'Shortcode Guide', 'events-grid' ),
			__( 'Shortcode Guide', 'events-grid' ),
			'edit_posts',
			'deg-shortcode-guide',
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Output scoped admin styles for this page only.
	 *
	 * @return void
	 */
	public static function inline_styles() {
		$screen = get_current_screen();
		if ( ! $screen || 'event_page_deg-shortcode-guide' !== $screen->id ) {
			return;
		}
		?>
		<style>
			.deg-guide-wrap { max-width: 860px; }
			.deg-guide-wrap h2 { margin-top: 2em; }
			.deg-guide-wrap h2:first-of-type { margin-top: 0.5em; }
			.deg-shortcode-badge {
				display: inline-block;
				background: #1d2327;
				color: #f0f0f1;
				font-family: Consolas, Monaco, monospace;
				font-size: 15px;
				padding: 6px 14px;
				border-radius: 4px;
				letter-spacing: 0.02em;
				margin-bottom: 1.2em;
			}
			.deg-attr-table { border-collapse: collapse; width: 100%; margin-bottom: 2em; }
			.deg-attr-table th,
			.deg-attr-table td { padding: 10px 14px; border: 1px solid #c3c4c7; vertical-align: top; }
			.deg-attr-table thead th { background: #f6f7f7; font-weight: 600; white-space: nowrap; }
			.deg-attr-table tbody tr:nth-child(even) { background: #f6f7f7; }
			.deg-attr-table code { font-size: 12px; background: #eef0f1; padding: 2px 5px; border-radius: 3px; white-space: nowrap; }
			.deg-attr-table .deg-default { color: #2271b1; font-weight: 600; }
			.deg-example-block {
				background: #1d2327;
				color: #f0f0f1;
				font-family: Consolas, Monaco, monospace;
				font-size: 13px;
				padding: 14px 18px;
				border-radius: 6px;
				margin: 0 0 1em;
				overflow-x: auto;
				line-height: 1.7;
			}
			.deg-example-block span.attr { color: #79c0ff; }
			.deg-example-block span.val  { color: #a5d6ff; }
			.deg-example-label {
				font-size: 12px;
				font-weight: 600;
				color: #646970;
				text-transform: uppercase;
				letter-spacing: 0.06em;
				margin: 1.4em 0 0.3em;
			}
			.deg-tip { background: #eef9fe; border-left: 4px solid #2271b1; padding: 10px 14px; margin: 1em 0; font-size: 13px; }
		</style>
		<?php
	}

	/**
	 * Render the guide page.
	 *
	 * @return void
	 */
	public static function render_page() {
		?>
		<div class="wrap deg-guide-wrap">
			<h1><?php esc_html_e( 'Events Grid — Shortcode Guide', 'events-grid' ); ?></h1>
			<p><?php esc_html_e( 'Place this shortcode in any page, post, or Elementor shortcode widget to display your events.', 'events-grid' ); ?></p>
			<div class="deg-shortcode-badge">[events_grid]</div>

			<?php /* ── ATTRIBUTES TABLE ── */ ?>
			<h2><?php esc_html_e( 'All Attributes', 'events-grid' ); ?></h2>
			<table class="deg-attr-table widefat">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Attribute', 'events-grid' ); ?></th>
						<th><?php esc_html_e( 'Accepted values', 'events-grid' ); ?></th>
						<th><?php esc_html_e( 'Default', 'events-grid' ); ?></th>
						<th><?php esc_html_e( 'Description', 'events-grid' ); ?></th>
					</tr>
				</thead>
				<tbody>

					<?php /* show_past */ ?>
					<tr>
						<td><code>show_past</code></td>
						<td>
							<code>no</code><br>
							<code>yes</code><br>
							<code>only</code><br>
							<code>merged</code>
						</td>
						<td><span class="deg-default"><code>no</code></span></td>
						<td>
							<strong>no</strong> — show upcoming events only.<br>
							<strong>yes</strong> — show upcoming events first, then past events separated on a new row.<br>
							<strong>only</strong> — show past events only.<br>
							<strong>merged</strong> — upcoming then past in a single grid, no separator row.
						</td>
					</tr>

					<?php /* orderby */ ?>
					<tr>
						<td><code>orderby</code></td>
						<td>
							<code>start_date</code><br>
							<code>title</code><br>
							<code>upcoming_first</code>
						</td>
						<td><span class="deg-default"><code>start_date</code></span></td>
						<td>
							<strong>start_date</strong> — sort by event start date.<br>
							<strong>title</strong> — sort alphabetically by event title.<br>
							<strong>upcoming_first</strong> — upcoming events (ASC) followed by past events (DESC). Implies <code>show_past="yes"</code>.
						</td>
					</tr>

					<?php /* order */ ?>
					<tr>
						<td><code>order</code></td>
						<td>
							<code>ASC</code><br>
							<code>DESC</code>
						</td>
						<td><span class="deg-default"><code>ASC</code></span></td>
						<td>
							<strong>ASC</strong> — earliest / A–Z first.<br>
							<strong>DESC</strong> — latest / Z–A first.<br>
							Not applied to the <code>upcoming_first</code> orderby (each section uses its own fixed order).
						</td>
					</tr>

					<?php /* posts_per_page */ ?>
					<tr>
						<td><code>posts_per_page</code></td>
						<td><?php esc_html_e( 'Any whole number ≥ 1', 'events-grid' ); ?></td>
						<td><span class="deg-default"><code>12</code></span></td>
						<td><?php esc_html_e( 'Maximum number of events to display. When show_past="yes", this limit applies to each section independently.', 'events-grid' ); ?></td>
					</tr>

					<?php /* columns */ ?>
					<tr>
						<td><code>columns</code></td>
						<td>1 – 6</td>
						<td><span class="deg-default"><code>3</code></span></td>
						<td><?php esc_html_e( 'Number of columns on desktop screens (> 1024 px).', 'events-grid' ); ?></td>
					</tr>

					<?php /* tablet_columns */ ?>
					<tr>
						<td><code>tablet_columns</code></td>
						<td>1 – 4</td>
						<td><span class="deg-default"><code>2</code></span></td>
						<td><?php esc_html_e( 'Number of columns on tablet screens (≤ 1024 px).', 'events-grid' ); ?></td>
					</tr>

					<?php /* mobile_columns */ ?>
					<tr>
						<td><code>mobile_columns</code></td>
						<td>1 – 2</td>
						<td><span class="deg-default"><code>1</code></span></td>
						<td><?php esc_html_e( 'Number of columns on mobile screens (≤ 767 px).', 'events-grid' ); ?></td>
					</tr>

					<?php /* category */ ?>
					<tr>
						<td><code>category</code></td>
						<td><?php esc_html_e( 'Category slug(s), comma-separated', 'events-grid' ); ?></td>
						<td><span class="deg-default"><code><?php esc_html_e( '(all)', 'events-grid' ); ?></code></span></td>
						<td><?php esc_html_e( 'Filter events by one or more Event Category slugs. Separate multiple slugs with commas.', 'events-grid' ); ?></td>
					</tr>

					<?php /* upcoming_label */ ?>
					<tr>
						<td><code>upcoming_label</code></td>
						<td><?php esc_html_e( 'Any text', 'events-grid' ); ?></td>
						<td><span class="deg-default"><code><?php esc_html_e( '(none)', 'events-grid' ); ?></code></span></td>
						<td><?php esc_html_e( 'Heading shown above the upcoming events grid when show_past="yes". Leave empty to show no heading.', 'events-grid' ); ?></td>
					</tr>

					<?php /* past_label */ ?>
					<tr>
						<td><code>past_label</code></td>
						<td><?php esc_html_e( 'Any text', 'events-grid' ); ?></td>
						<td><span class="deg-default"><code><?php esc_html_e( '(none)', 'events-grid' ); ?></code></span></td>
						<td><?php esc_html_e( 'Heading shown above the past events grid when show_past="yes". Leave empty to show no heading.', 'events-grid' ); ?></td>
					</tr>

				</tbody>
			</table>

			<?php /* ── EXAMPLES ── */ ?>
			<h2><?php esc_html_e( 'Examples', 'events-grid' ); ?></h2>

			<p class="deg-example-label"><?php esc_html_e( 'Upcoming events only (default)', 'events-grid' ); ?></p>
			<div class="deg-example-block">[events_grid]</div>

			<p class="deg-example-label"><?php esc_html_e( 'Past events only', 'events-grid' ); ?></p>
			<div class="deg-example-block">[events_grid <span class="attr">show_past</span>="<span class="val">only</span>"]</div>

			<p class="deg-example-label"><?php esc_html_e( 'Upcoming + past on a new row', 'events-grid' ); ?></p>
			<div class="deg-example-block">[events_grid <span class="attr">show_past</span>="<span class="val">yes</span>"]</div>

			<p class="deg-example-label"><?php esc_html_e( 'Upcoming + past in one single grid (no separator row)', 'events-grid' ); ?></p>
			<div class="deg-example-block">[events_grid <span class="attr">show_past</span>="<span class="val">merged</span>"]</div>

			<p class="deg-example-label"><?php esc_html_e( 'Upcoming first, then most-recent past — automatic ordering', 'events-grid' ); ?></p>
			<div class="deg-example-block">[events_grid <span class="attr">orderby</span>="<span class="val">upcoming_first</span>"]</div>

			<p class="deg-example-label"><?php esc_html_e( 'Latest events first (newest → oldest)', 'events-grid' ); ?></p>
			<div class="deg-example-block">[events_grid <span class="attr">show_past</span>="<span class="val">yes</span>" <span class="attr">order</span>="<span class="val">DESC</span>"]</div>

			<p class="deg-example-label"><?php esc_html_e( 'Alphabetical, upcoming only', 'events-grid' ); ?></p>
			<div class="deg-example-block">[events_grid <span class="attr">orderby</span>="<span class="val">title</span>" <span class="attr">order</span>="<span class="val">ASC</span>"]</div>

			<p class="deg-example-label"><?php esc_html_e( 'Custom grid layout', 'events-grid' ); ?></p>
			<div class="deg-example-block">[events_grid <span class="attr">columns</span>="<span class="val">4</span>" <span class="attr">tablet_columns</span>="<span class="val">2</span>" <span class="attr">mobile_columns</span>="<span class="val">1</span>"]</div>

			<p class="deg-example-label"><?php esc_html_e( 'Limit how many events appear', 'events-grid' ); ?></p>
			<div class="deg-example-block">[events_grid <span class="attr">posts_per_page</span>="<span class="val">6</span>"]</div>

			<p class="deg-example-label"><?php esc_html_e( 'Filter by category slug', 'events-grid' ); ?></p>
			<div class="deg-example-block">[events_grid <span class="attr">category</span>="<span class="val">trade-show</span>"]</div>

			<p class="deg-example-label"><?php esc_html_e( 'Filter by multiple categories', 'events-grid' ); ?></p>
			<div class="deg-example-block">[events_grid <span class="attr">category</span>="<span class="val">trade-show,conference</span>"]</div>

			<p class="deg-example-label"><?php esc_html_e( 'Upcoming + past with section headings', 'events-grid' ); ?></p>
			<div class="deg-example-block">[events_grid <span class="attr">show_past</span>="<span class="val">yes</span>" <span class="attr">upcoming_label</span>="<span class="val">Upcoming Events</span>" <span class="attr">past_label</span>="<span class="val">Past Events</span>"]</div>

			<p class="deg-example-label"><?php esc_html_e( 'Full example — all options combined', 'events-grid' ); ?></p>
			<div class="deg-example-block">[events_grid <span class="attr">show_past</span>="<span class="val">yes</span>" <span class="attr">orderby</span>="<span class="val">start_date</span>" <span class="attr">order</span>="<span class="val">ASC</span>" <span class="attr">posts_per_page</span>="<span class="val">12</span>" <span class="attr">columns</span>="<span class="val">3</span>" <span class="attr">tablet_columns</span>="<span class="val">2</span>" <span class="attr">mobile_columns</span>="<span class="val">1</span>" <span class="attr">category</span>="<span class="val">trade-show</span>" <span class="attr">upcoming_label</span>="<span class="val">Upcoming Events</span>" <span class="attr">past_label</span>="<span class="val">Past Events</span>"]</div>

			<?php /* ── TIPS ── */ ?>
			<h2><?php esc_html_e( 'Tips', 'events-grid' ); ?></h2>
			<div class="deg-tip">
				<?php esc_html_e( 'When using show_past="yes", the posts_per_page limit applies separately to upcoming and past events. For example, posts_per_page="6" can show up to 6 upcoming and up to 6 past events.', 'events-grid' ); ?>
			</div>
			<div class="deg-tip">
				<?php esc_html_e( 'orderby="upcoming_first" always shows both sections regardless of the show_past setting — it is a shorthand for show_past="yes" with automatic ordering.', 'events-grid' ); ?>
			</div>
			<div class="deg-tip">
				<?php esc_html_e( 'Events without an end date are treated as single-day events. They become "past" once their start date has passed.', 'events-grid' ); ?>
			</div>

		</div>
		<?php
	}
}
