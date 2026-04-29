<?php
/**
 * Shortcode rendering.
 *
 * @package EventsGrid
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles shortcode output.
 */
class Events_Grid_Shortcode {

	/**
	 * Hook setup.
	 *
	 * @return void
	 */
	public static function init() {
		add_shortcode( 'events_grid', array( __CLASS__, 'render_shortcode' ) );
	}

	/**
	 * Render shortcode.
	 *
	 * show_past accepts:
	 *   "no"   – upcoming events only (default)
	 *   "yes"  – upcoming section, then past section on a new row
	 *   "only" – past events only
	 *   "merged" – upcoming then past in a single grid
	 *
	 * @param array<string,string> $atts Shortcode attributes.
	 * @return string
	 */
	public static function render_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'columns'         => '3',
				'tablet_columns'  => '2',
				'mobile_columns'  => '1',
				'posts_per_page'  => '24',
				'show_past'       => 'no',
				'orderby'         => 'start_date',
				'order'           => 'ASC',
				'category'        => '',
				'upcoming_label'  => '',
				'past_label'      => '',
			),
			$atts,
			'events_grid'
		);

		// ── Transient cache ───────────────────────────────────────────
		// Cache key includes a version number (bumped on any event save/delete),
		// the fadeout setting, and the current locale so each language gets its
		// own cached output (prevents German text leaking into other languages).
		$cache_version = (int) get_option( 'deg_cache_version', 0 );
		$fadeout_days  = Events_Grid_Settings::get_past_fadeout_days();
		$cache_key     = 'deg_grid_' . $cache_version . '_f' . $fadeout_days . '_' . get_locale() . '_' . current_time( 'Y-m-d' ) . '_' . md5( (string) serialize( $atts ) );
		$cached        = get_transient( $cache_key );

		if ( false !== $cached ) {
			wp_enqueue_style( 'events-grid' );
			return (string) $cached;
		}

		wp_enqueue_style( 'events-grid' );

		// ── Sanitize & validate attributes ───────────────────────────
		$columns        = max( 1, min( 6, absint( $atts['columns'] ) ) );
		$tablet_columns = max( 1, min( 4, absint( $atts['tablet_columns'] ) ) );
		$mobile_columns = max( 1, min( 2, absint( $atts['mobile_columns'] ) ) );
		$posts_per_page = max( 1, absint( $atts['posts_per_page'] ) );
		$order          = 'DESC' === strtoupper( (string) $atts['order'] ) ? 'DESC' : 'ASC';
		$orderby        = strtolower( (string) $atts['orderby'] );
		$category       = sanitize_text_field( (string) $atts['category'] );
		$upcoming_label = sanitize_text_field( (string) $atts['upcoming_label'] );
		$past_label     = sanitize_text_field( (string) $atts['past_label'] );

		$show_past_raw = strtolower( (string) $atts['show_past'] );
		if ( 'only' === $show_past_raw ) {
			$show_mode = 'past';
		} elseif ( 'yes' === $show_past_raw ) {
			$show_mode = 'both';
		} elseif ( 'merged' === $show_past_raw ) {
			$show_mode = 'merged';
		} else {
			$show_mode = 'upcoming';
		}

		// upcoming_first orderby implicitly shows both sections.
		$two_sections = ( 'both' === $show_mode || 'upcoming_first' === $orderby )
			&& 'past' !== $show_mode
			&& 'merged' !== $show_mode;

		$style = sprintf(
			'--deg-columns:%1$d;--deg-tablet-columns:%2$d;--deg-mobile-columns:%3$d;',
			$columns,
			$tablet_columns,
			$mobile_columns
		);

		ob_start();

		if ( $two_sections ) {
			$upcoming_ids = self::fetch_event_ids( $posts_per_page, $orderby, 'ASC',  'upcoming', $category );
			$past_ids     = self::fetch_event_ids( $posts_per_page, $orderby, 'DESC', 'past',     $category );
			?>

			<?php if ( $upcoming_label ) : ?>
				<h3 class="deg-section-heading deg-section-heading--upcoming"><?php echo esc_html( $upcoming_label ); ?></h3>
			<?php endif; ?>

			<div class="deg-events-grid" style="<?php echo esc_attr( $style ); ?>">
				<?php if ( $upcoming_ids ) : ?>
					<?php foreach ( $upcoming_ids as $post_id ) : ?>
						<?php self::render_card( $post_id ); ?>
					<?php endforeach; ?>
				<?php else : ?>
					<p class="deg-no-events"><?php esc_html_e( 'No upcoming events found.', 'events-grid' ); ?></p>
				<?php endif; ?>
			</div>

			<?php if ( $past_ids ) : ?>
				<?php if ( $past_label ) : ?>
					<h3 class="deg-section-heading deg-section-heading--past"><?php echo esc_html( $past_label ); ?></h3>
				<?php endif; ?>
				<div class="deg-events-grid deg-events-grid--past" style="<?php echo esc_attr( $style ); ?>">
					<?php foreach ( $past_ids as $post_id ) : ?>
						<?php self::render_card( $post_id ); ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php
		} else {
			if ( 'merged' === $show_mode ) {
				// Two targeted queries keep the correct display order: upcoming
				// events (soonest first) followed by past events (most recent first).
				// A single ASC/DESC query on start_date cannot achieve this because
				// past dates are numerically smaller than upcoming dates, so ASC
				// would always put past events first.
				$upcoming_ids = self::fetch_event_ids( $posts_per_page, $orderby, 'ASC',  'upcoming', $category );
				$past_ids     = self::fetch_event_ids( $posts_per_page, $orderby, 'DESC', 'past',     $category );
				$event_ids    = array_merge( $upcoming_ids, $past_ids );
			} else {
				$filter    = ( 'past' === $show_mode ) ? 'past' : 'upcoming';
				$event_ids = self::fetch_event_ids( $posts_per_page, $orderby, $order, $filter, $category );
			}
			?>
			<div class="deg-events-grid" style="<?php echo esc_attr( $style ); ?>">
				<?php if ( $event_ids ) : ?>
					<?php foreach ( $event_ids as $post_id ) : ?>
						<?php self::render_card( $post_id ); ?>
					<?php endforeach; ?>
				<?php else : ?>
					<p class="deg-no-events"><?php esc_html_e( 'No events found.', 'events-grid' ); ?></p>
				<?php endif; ?>
			</div>
			<?php
		}

		$output = (string) ob_get_clean();

		// Store in transient for 1 hour.
		set_transient( $cache_key, $output, HOUR_IN_SECONDS );

		return $output;
	}

	/**
	 * Fetch event post IDs for a given filter (upcoming or past).
	 *
	 * After fetching IDs, immediately primes the post meta cache in a single
	 * query so render_card() never triggers individual per-field DB lookups
	 * (fixes the N+1 query problem).
	 *
	 * @param int    $limit    Max number of posts.
	 * @param string $orderby  'start_date', 'title', or 'upcoming_first'.
	 * @param string $order    'ASC' or 'DESC'.
	 * @param string $filter   'upcoming' or 'past'.
	 * @param string $category Comma-separated deg_event_category slugs, or ''.
	 * @return int[]
	 */
	private static function fetch_event_ids( $limit, $orderby, $order, $filter, $category = '' ) {
		$today = current_time( 'Y-m-d' );

		$args = array(
			'post_type'      => 'event',
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'order'          => $order,
		);

		if ( 'title' === $orderby ) {
			$args['orderby'] = 'title';
		} else {
			$args['meta_key']  = '_deg_start_date';
			$args['orderby']   = 'meta_value';
			$args['meta_type'] = 'DATE';
		}

		// Optional category filter.
		if ( ! empty( $category ) ) {
			$slugs = array_filter( array_map( 'trim', explode( ',', $category ) ) );
			if ( ! empty( $slugs ) ) {
				$args['tax_query'] = array(
					array(
						'taxonomy' => 'deg_event_category',
						'field'    => 'slug',
						'terms'    => $slugs,
					),
				);
			}
		}

		if ( 'upcoming' === $filter ) {
			$args['meta_query'] = array(
				'relation' => 'OR',
				array(
					'key'     => '_deg_end_date',
					'value'   => $today,
					'compare' => '>=',
					'type'    => 'DATE',
				),
				array(
					'relation' => 'AND',
					array(
						'key'     => '_deg_end_date',
						'value'   => '',
						'compare' => '=',
					),
					array(
						'key'     => '_deg_start_date',
						'value'   => $today,
						'compare' => '>=',
						'type'    => 'DATE',
					),
				),
			);
		} else {
			// Optionally restrict how far back past events are shown.
			$fadeout_days = Events_Grid_Settings::get_past_fadeout_days();
			$cutoff_date  = $fadeout_days > 0
				? date( 'Y-m-d', strtotime( "-{$fadeout_days} days", (int) current_time( 'timestamp' ) ) )
				: null;

			if ( $cutoff_date ) {
				$args['meta_query'] = array(
					'relation' => 'OR',
					// Events with an end date: it must be in the past but not older than the cutoff.
					array(
						'relation' => 'AND',
						array(
							'key'     => '_deg_end_date',
							'value'   => $today,
							'compare' => '<',
							'type'    => 'DATE',
						),
						array(
							'key'     => '_deg_end_date',
							'value'   => $cutoff_date,
							'compare' => '>=',
							'type'    => 'DATE',
						),
					),
					// Events without an end date: start date must be in the past but within the cutoff.
					array(
						'relation' => 'AND',
						array(
							'key'     => '_deg_end_date',
							'value'   => '',
							'compare' => '=',
						),
						array(
							'key'     => '_deg_start_date',
							'value'   => $today,
							'compare' => '<',
							'type'    => 'DATE',
						),
						array(
							'key'     => '_deg_start_date',
							'value'   => $cutoff_date,
							'compare' => '>=',
							'type'    => 'DATE',
						),
					),
				);
			} else {
				$args['meta_query'] = array(
					'relation' => 'OR',
					array(
						'key'     => '_deg_end_date',
						'value'   => $today,
						'compare' => '<',
						'type'    => 'DATE',
					),
					array(
						'relation' => 'AND',
						array(
							'key'     => '_deg_end_date',
							'value'   => '',
							'compare' => '=',
						),
						array(
							'key'     => '_deg_start_date',
							'value'   => $today,
							'compare' => '<',
							'type'    => 'DATE',
						),
					),
				);
			}
		}

		$ids = ( new WP_Query( $args ) )->posts;

		// Prime all meta in ONE query instead of one per field per card.
		if ( ! empty( $ids ) ) {
			update_meta_cache( 'post', $ids );
		}

		// Apply secondary end_date sort when start dates tie.
		if ( 'title' !== $orderby && count( $ids ) > 1 ) {
			$ids = self::sort_ids_by_dates( $ids, $order );
		}

		return $ids;
	}

	/**
	 * Sort an array of post IDs by start_date then end_date.
	 *
	 * Events with no end_date are treated as single-day (end = start).
	 * Meta cache is already warm at this point, so no extra DB queries occur.
	 *
	 * @param int[]  $ids   Post IDs.
	 * @param string $order 'ASC' or 'DESC'.
	 * @return int[]
	 */
	private static function sort_ids_by_dates( array $ids, $order ) {
		if ( count( $ids ) < 2 ) {
			return $ids;
		}

		$meta = array();
		foreach ( $ids as $id ) {
			$start       = (string) get_post_meta( $id, '_deg_start_date', true );
			$end         = (string) get_post_meta( $id, '_deg_end_date', true );
			$meta[ $id ] = array(
				'start' => $start,
				'end'   => $end ? $end : $start,
			);
		}

		$dir = ( 'ASC' === $order ) ? 1 : -1;

		usort(
			$ids,
			function ( $a, $b ) use ( $meta, $dir ) {
				$start_cmp = strcmp( $meta[ $a ]['start'], $meta[ $b ]['start'] );
				if ( 0 !== $start_cmp ) {
					return $dir * $start_cmp;
				}
				return $dir * strcmp( $meta[ $a ]['end'], $meta[ $b ]['end'] );
			}
		);

		return $ids;
	}

	/**
	 * Render single event card.
	 *
	 * All get_post_meta() calls here are served from the in-memory cache
	 * primed by fetch_event_ids() — zero additional DB queries per card.
	 *
	 * @param int $post_id Event post ID.
	 * @return void
	 */
	private static function render_card( $post_id ) {
		$start_date      = (string) get_post_meta( $post_id, '_deg_start_date', true );
		$end_date        = (string) get_post_meta( $post_id, '_deg_end_date', true );
		$country         = (string) get_post_meta( $post_id, '_deg_country', true );
		$venue           = (string) get_post_meta( $post_id, '_deg_venue', true );
		$booth_number    = (string) get_post_meta( $post_id, '_deg_booth_number', true );
		$speaker_enabled = (string) get_post_meta( $post_id, '_deg_speaker_enabled', true );
		$speaker_topic   = (string) get_post_meta( $post_id, '_deg_speaker_topic', true );
		$external_url    = (string) get_post_meta( $post_id, '_deg_external_url', true );
		$date_range      = Events_Grid_Helpers::format_date_range( $start_date, $end_date );
		$location        = Events_Grid_Helpers::format_location( $venue, $country );
		?>
		<article class="deg-event-card">
			<?php if ( has_post_thumbnail( $post_id ) ) : ?>
				<div class="deg-event-card__image-wrap">
					<a href="<?php echo esc_url( $external_url ? $external_url : get_permalink( $post_id ) ); ?>" <?php echo $external_url ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>>
						<?php echo get_the_post_thumbnail( $post_id, 'large', array( 'class' => 'deg-event-card__image', 'loading' => 'lazy' ) ); ?>
					</a>
				</div>
			<?php endif; ?>
			<div class="deg-event-card__content">
				<?php if ( $date_range || $country ) : ?>
					<p class="deg-event-card__meta deg-event-card__date">
						<?php
						$meta_parts = array_filter( array( $date_range, $country ) );
						echo esc_html( implode( ' | ', $meta_parts ) );
						?>
					</p>
				<?php endif; ?>
				<?php if ( $venue ) : ?>
					<p class="deg-event-card__meta deg-event-card__location"><?php echo esc_html( $venue ); ?></p>
				<?php endif; ?>
				<h3 class="deg-event-card__title"><?php echo esc_html( get_the_title( $post_id ) ); ?></h3>

				<?php if ( '1' === $speaker_enabled && $speaker_topic ) : ?>
					<p class="deg-event-card__speaker-topic"><?php echo esc_html( sprintf( __( 'Speaking Topic : %s', 'events-grid' ), $speaker_topic ) ); ?></p>
				<?php endif; ?>

				<div class="deg-event-card__footer">
					<div class="deg-event-card__badges">
						<?php if ( $booth_number ) : ?>
							<span class="deg-badge deg-badge--primary"><?php echo esc_html( sprintf( __( 'Booth: %s', 'events-grid' ), $booth_number ) ); ?></span>
						<?php endif; ?>
						<?php if ( '1' === $speaker_enabled ) : ?>
							<span class="deg-badge deg-badge--secondary"><?php esc_html_e( 'Speaker', 'events-grid' ); ?></span>
						<?php endif; ?>
					</div>
					<?php if ( $external_url ) : ?>
						<a class="deg-event-site-link" href="<?php echo esc_url( $external_url ); ?>" target="_blank" rel="noopener noreferrer">
							<?php esc_html_e( 'Event site', 'events-grid' ); ?>
						</a>
					<?php endif; ?>
				</div>
			</div>
		</article>
		<?php
	}

	/**
	 * Invalidate all shortcode output caches by bumping the cache version.
	 *
	 * Called on save_post_event and event deletion so visitors never see
	 * stale output after content changes.
	 *
	 * @return void
	 */
	public static function bust_cache() {
		update_option( 'deg_cache_version', time() );
	}

	/**
	 * Bust the cache only when the deleted/trashed post is an event.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public static function maybe_bust_cache( $post_id ) {
		if ( 'event' === get_post_type( $post_id ) ) {
			self::bust_cache();
		}
	}
}
