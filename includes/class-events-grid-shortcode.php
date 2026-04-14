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
		add_shortcode( 'dextra_events', array( __CLASS__, 'render_shortcode' ) );
	}

	/**
	 * Render shortcode.
	 *
	 * show_past accepts:
	 *   "no"   – upcoming events only (default)
	 *   "yes"  – upcoming section, then past section on a new row
	 *   "only" – past events only
	 *
	 * @param array<string,string> $atts Shortcode attributes.
	 * @return string
	 */
	public static function render_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'columns'        => '3',
				'tablet_columns' => '2',
				'mobile_columns' => '1',
				'posts_per_page' => '12',
				'show_past'      => 'no',
				'orderby'        => 'start_date',
				'order'          => 'ASC',
			),
			$atts,
			'dextra_events'
		);

		wp_enqueue_style( 'events-grid' );

		$columns        = max( 1, min( 6, absint( $atts['columns'] ) ) );
		$tablet_columns = max( 1, min( 4, absint( $atts['tablet_columns'] ) ) );
		$mobile_columns = max( 1, min( 2, absint( $atts['mobile_columns'] ) ) );
		$posts_per_page = max( 1, absint( $atts['posts_per_page'] ) );
		$order          = 'DESC' === strtoupper( (string) $atts['order'] ) ? 'DESC' : 'ASC';
		$orderby        = strtolower( (string) $atts['orderby'] );

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

		// upcoming_first orderby implicitly shows both sections (unless past-only or merged).
		$two_sections = ( 'both' === $show_mode || 'upcoming_first' === $orderby ) && 'past' !== $show_mode && 'merged' !== $show_mode;

		$style = sprintf(
			'--deg-columns:%1$d;--deg-tablet-columns:%2$d;--deg-mobile-columns:%3$d;',
			$columns,
			$tablet_columns,
			$mobile_columns
		);

		ob_start();

		if ( $two_sections ) {
			$upcoming_ids = self::fetch_event_ids( $posts_per_page, $orderby, 'ASC', 'upcoming' );
			$past_ids     = self::fetch_event_ids( $posts_per_page, $orderby, 'DESC', 'past' );
			?>
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
			<div class="deg-events-grid deg-events-grid--past" style="<?php echo esc_attr( $style ); ?>">
				<?php foreach ( $past_ids as $post_id ) : ?>
					<?php self::render_card( $post_id ); ?>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>
			<?php
		} else {
			if ( 'merged' === $show_mode ) {
				$upcoming_ids = self::fetch_event_ids( $posts_per_page, $orderby, 'ASC', 'upcoming' );
				$past_ids     = self::fetch_event_ids( $posts_per_page, $orderby, 'DESC', 'past' );
				$event_ids    = array_merge( $upcoming_ids, $past_ids );
			} else {
				$filter    = ( 'past' === $show_mode ) ? 'past' : 'upcoming';
				$event_ids = self::fetch_event_ids( $posts_per_page, $orderby, $order, $filter );
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

		return (string) ob_get_clean();
	}

	/**
	 * Fetch event post IDs for a given filter (upcoming or past).
	 *
	 * @param int    $limit   Max number of posts.
	 * @param string $orderby 'start_date', 'title', or 'upcoming_first'.
	 * @param string $order   'ASC' or 'DESC'.
	 * @param string $filter  'upcoming' or 'past'.
	 * @return int[]
	 */
	private static function fetch_event_ids( $limit, $orderby, $order, $filter ) {
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

		$ids = ( new WP_Query( $args ) )->posts;

		// Apply secondary end_date sort when start dates tie (not applicable for title order).
		if ( 'title' !== $orderby && count( $ids ) > 1 ) {
			$ids = self::sort_ids_by_dates( $ids, $order );
		}

		return $ids;
	}

	/**
	 * Sort an array of post IDs by start_date then end_date.
	 *
	 * Events with no end_date are treated as single-day (end = start).
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
}
