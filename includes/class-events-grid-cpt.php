<?php
/**
 * Custom post type registration.
 *
 * @package EventsGrid
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles event CPT.
 */
class Events_Grid_CPT {

	/**
	 * Hook setup.
	 *
	 * @return void
	 */
	public static function init() {
		self::register_post_type();
	}

	/**
	 * Register post type.
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
}
