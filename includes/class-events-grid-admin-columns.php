<?php
/**
 * Admin list table columns for the event post type.
 *
 * Adds Start Date, End Date, Venue, and Country columns to the Events list
 * table. Start Date and End Date are sortable.
 *
 * @package EventsGrid
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages admin list-table columns for events.
 */
class Events_Grid_Admin_Columns {

	/**
	 * Hook setup.
	 *
	 * @return void
	 */
	public static function init() {
		add_filter( 'manage_event_posts_columns',         array( __CLASS__, 'add_columns' ) );
		add_action( 'manage_event_posts_custom_column',   array( __CLASS__, 'render_column' ), 10, 2 );
		add_filter( 'manage_edit-event_sortable_columns', array( __CLASS__, 'sortable_columns' ) );
		add_action( 'pre_get_posts',                      array( __CLASS__, 'handle_admin_sort' ) );

		// Record who saved the post every time an event is updated or created.
		add_action( 'save_post_event', array( __CLASS__, 'save_last_modified_by' ) );
	}

	/**
	 * Insert custom columns after Title and remove the irrelevant publish Date.
	 *
	 * @param  array<string,string> $columns Existing columns.
	 * @return array<string,string>
	 */
	public static function add_columns( $columns ) {
		$new = array();

		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;

			if ( 'title' === $key ) {
				$new['deg_start_date']   = __( 'Start Date',   'events-grid' );
				$new['deg_end_date']     = __( 'End Date',     'events-grid' );
				$new['deg_venue']        = __( 'Venue',        'events-grid' );
				$new['deg_country']      = __( 'Country',      'events-grid' );
				$new['deg_modified_by']  = __( 'Modified By',  'events-grid' );
			}
		}

		// Remove the default publish-date column — not meaningful for events.
		unset( $new['date'] );

		return $new;
	}

	/**
	 * Render cell content for each custom column.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	public static function render_column( $column, $post_id ) {
		switch ( $column ) {
			case 'deg_start_date':
				$val = (string) get_post_meta( $post_id, '_deg_start_date', true );
				echo $val ? esc_html( Events_Grid_Helpers::format_date_range( $val, '' ) ) : '—';
				break;

			case 'deg_end_date':
				$val = (string) get_post_meta( $post_id, '_deg_end_date', true );
				echo $val ? esc_html( Events_Grid_Helpers::format_date_range( $val, '' ) ) : '—';
				break;

			case 'deg_venue':
				$val = (string) get_post_meta( $post_id, '_deg_venue', true );
				echo $val ? esc_html( $val ) : '—';
				break;

			case 'deg_country':
				$val = (string) get_post_meta( $post_id, '_deg_country', true );
				echo $val ? esc_html( $val ) : '—';
				break;

			case 'deg_modified_by':
				$user_id = (int) get_post_meta( $post_id, '_deg_last_modified_by', true );
				if ( $user_id ) {
					$user = get_userdata( $user_id );
					echo $user ? esc_html( $user->display_name ) : '—';
				} else {
					// Fallback for events saved before this feature existed:
					// show the original post author.
					$post = get_post( $post_id );
					if ( $post ) {
						$user = get_userdata( (int) $post->post_author );
						echo $user ? esc_html( $user->display_name ) : '—';
					} else {
						echo '—';
					}
				}
				break;
		}
	}

	/**
	 * Declare which columns support sorting.
	 *
	 * @param  array<string,string> $columns Existing sortable columns.
	 * @return array<string,string>
	 */
	public static function sortable_columns( $columns ) {
		$columns['deg_start_date'] = 'deg_start_date';
		$columns['deg_end_date']   = 'deg_end_date';
		return $columns;
	}

	/**
	 * Modify the admin query when sorting by our custom columns.
	 *
	 * @param WP_Query $query Current query.
	 * @return void
	 */
	/**
	 * Store the current user ID as the last modifier whenever an event is saved.
	 *
	 * Skips autosaves and revisions. For events that predate this feature,
	 * the Modified By column falls back to the original post author.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public static function save_last_modified_by( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}
		update_post_meta( $post_id, '_deg_last_modified_by', get_current_user_id() );
	}

	public static function handle_admin_sort( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( 'event' !== $query->get( 'post_type' ) ) {
			return;
		}

		$orderby = $query->get( 'orderby' );

		if ( 'deg_start_date' === $orderby ) {
			$query->set( 'meta_key',  '_deg_start_date' );
			$query->set( 'orderby',   'meta_value' );
			$query->set( 'meta_type', 'DATE' );
		} elseif ( 'deg_end_date' === $orderby ) {
			$query->set( 'meta_key',  '_deg_end_date' );
			$query->set( 'orderby',   'meta_value' );
			$query->set( 'meta_type', 'DATE' );
		}
	}
}
