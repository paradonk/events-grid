<?php
/**
 * Custom post type and taxonomy registration.
 *
 * @package EventsGrid
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles event CPT, taxonomy, and meta field registration.
 */
class Events_Grid_CPT {

	/**
	 * Hook setup — called on the init action.
	 *
	 * @return void
	 */
	public static function init() {
		self::register_post_type();
		self::register_taxonomy();
		self::register_meta_fields();
	}

	/**
	 * Register the event post type.
	 *
	 * Also called directly from the activation hook so rewrite rules flush correctly.
	 *
	 * @return void
	 */
	public static function register_post_type() {
		$labels = array(
			'name'                  => __( 'Events', 'events-grid' ),
			'singular_name'         => __( 'Event', 'events-grid' ),
			'menu_name'             => __( 'Events', 'events-grid' ),
			'name_admin_bar'        => __( 'Event', 'events-grid' ),
			'add_new'               => __( 'Add New', 'events-grid' ),
			'add_new_item'          => __( 'Add New Event', 'events-grid' ),
			'new_item'              => __( 'New Event', 'events-grid' ),
			'edit_item'             => __( 'Edit Event', 'events-grid' ),
			'view_item'             => __( 'View Event', 'events-grid' ),
			'all_items'             => __( 'All Events', 'events-grid' ),
			'search_items'          => __( 'Search Events', 'events-grid' ),
			'not_found'             => __( 'No events found.', 'events-grid' ),
			'not_found_in_trash'    => __( 'No events found in Trash.', 'events-grid' ),
			'featured_image'        => __( 'Event Image', 'events-grid' ),
			'set_featured_image'    => __( 'Set event image', 'events-grid' ),
			'remove_featured_image' => __( 'Remove event image', 'events-grid' ),
			'use_featured_image'    => __( 'Use as event image', 'events-grid' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'has_archive'        => true,
			'rewrite'            => array( 'slug' => 'events' ),
			'menu_icon'          => 'dashicons-calendar-alt',
			'supports'           => array( 'title', 'editor', 'thumbnail' ),
			'show_in_rest'       => true,
			'publicly_queryable' => true,
		);

		register_post_type( 'event', $args );
	}

	/**
	 * Register the Event Category taxonomy.
	 *
	 * Hierarchical (like categories), shown as an admin column, and exposed
	 * to the REST API so Gutenberg and headless clients can use it.
	 *
	 * @return void
	 */
	public static function register_taxonomy() {
		$labels = array(
			'name'              => __( 'Event Categories', 'events-grid' ),
			'singular_name'     => __( 'Event Category',  'events-grid' ),
			'search_items'      => __( 'Search Event Categories', 'events-grid' ),
			'all_items'         => __( 'All Event Categories',    'events-grid' ),
			'parent_item'       => __( 'Parent Category',         'events-grid' ),
			'parent_item_colon' => __( 'Parent Category:',        'events-grid' ),
			'edit_item'         => __( 'Edit Event Category',      'events-grid' ),
			'update_item'       => __( 'Update Event Category',    'events-grid' ),
			'add_new_item'      => __( 'Add New Event Category',   'events-grid' ),
			'new_item_name'     => __( 'New Event Category Name',  'events-grid' ),
			'menu_name'         => __( 'Categories',               'events-grid' ),
		);

		register_taxonomy(
			'deg_event_category',
			'event',
			array(
				'labels'            => $labels,
				'hierarchical'      => true,
				'show_ui'           => true,
				'show_admin_column' => true,   // adds a Category column to the list table
				'show_in_rest'      => true,   // Gutenberg panel + REST API
				'rewrite'           => array( 'slug' => 'event-category' ),
			)
		);
	}

	/**
	 * Register all event meta fields with the REST API.
	 *
	 * Without this, the REST endpoint returns events with no custom field data
	 * even though the CPT has show_in_rest => true.
	 *
	 * @return void
	 */
	public static function register_meta_fields() {
		$fields = array(
			'_deg_start_date'      => 'Event start date (Y-m-d)',
			'_deg_end_date'        => 'Event end date (Y-m-d)',
			'_deg_country'         => 'Country',
			'_deg_venue'           => 'Venue name',
			'_deg_booth_number'    => 'Booth number',
			'_deg_speaker_enabled' => 'Speaker badge enabled (1 or empty string)',
			'_deg_speaker_topic'   => 'Speaker topic',
			'_deg_external_url'    => 'External event URL',
		);

		foreach ( $fields as $key => $description ) {
			register_post_meta(
				'event',
				$key,
				array(
					'show_in_rest'  => true,
					'single'        => true,
					'type'          => 'string',
					'description'   => $description,
					'auth_callback' => function () {
						return current_user_can( 'edit_posts' );
					},
				)
			);
		}
	}
}
