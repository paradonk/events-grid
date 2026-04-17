<?php
/**
 * Main plugin bootstrap.
 *
 * @package EventsGrid
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once EVENTS_GRID_PATH . 'includes/class-events-grid-helpers.php';
require_once EVENTS_GRID_PATH . 'includes/class-events-grid-cpt.php';
require_once EVENTS_GRID_PATH . 'includes/class-events-grid-meta-boxes.php';
require_once EVENTS_GRID_PATH . 'includes/class-events-grid-shortcode.php';
require_once EVENTS_GRID_PATH . 'includes/class-events-grid-importer.php';
require_once EVENTS_GRID_PATH . 'includes/class-events-grid-help.php';
require_once EVENTS_GRID_PATH . 'includes/class-events-grid-settings.php';

if ( is_admin() ) {
	require_once EVENTS_GRID_PATH . 'includes/class-events-grid-admin-columns.php';
}

/**
 * Main plugin class.
 */
class Events_Grid {

	/**
	 * Instance.
	 *
	 * @var Events_Grid|null
	 */
	private static $instance = null;

	/**
	 * Gets singleton instance.
	 *
	 * @return Events_Grid
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	private function init_hooks() {
		add_action( 'plugins_loaded',    array( $this, 'load_textdomain' ) );
		add_action( 'init',              array( $this, 'init_components' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
		add_action( 'admin_post_deg_download_template', array( 'Events_Grid_Importer', 'handle_template_download' ) );

		// Bust shortcode output cache whenever an event is saved or deleted.
		add_action( 'save_post_event',   array( 'Events_Grid_Shortcode', 'bust_cache' ) );
		add_action( 'before_delete_post', array( 'Events_Grid_Shortcode', 'maybe_bust_cache' ) );
		add_action( 'wp_trash_post',     array( 'Events_Grid_Shortcode', 'maybe_bust_cache' ) );

		// Also bust cache when event categories are changed.
		add_action( 'edited_deg_event_category',  array( 'Events_Grid_Shortcode', 'bust_cache' ) );
		add_action( 'created_deg_event_category', array( 'Events_Grid_Shortcode', 'bust_cache' ) );
		add_action( 'deleted_deg_event_category', array( 'Events_Grid_Shortcode', 'bust_cache' ) );

		new Events_Grid_Updater( EVENTS_GRID_FILE, EVENTS_GRID_VERSION, EVENTS_GRID_UPDATE_URL );
	}

	/**
	 * Load text domain.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'events-grid', false, dirname( plugin_basename( EVENTS_GRID_FILE ) ) . '/languages' );
	}

	/**
	 * Initialize plugin components.
	 *
	 * @return void
	 */
	public function init_components() {
		Events_Grid_CPT::init();
		Events_Grid_Meta_Boxes::init();
		Events_Grid_Shortcode::init();
		Events_Grid_Importer::init();
		Events_Grid_Help::init();
		Events_Grid_Settings::init();

		if ( is_admin() ) {
			Events_Grid_Admin_Columns::init();
		}
	}

	/**
	 * Register front-end assets.
	 *
	 * Google Fonts is registered as a separate handle and declared as a
	 * dependency of the main stylesheet so WordPress enqueues it correctly
	 * in the <head> — no render-blocking CSS @import needed.
	 *
	 * The Google Fonts request can be disabled from Events > Settings so
	 * site owners who self-host Roboto or prefer a system font can opt out
	 * of the external HTTP request entirely.
	 *
	 * @return void
	 */
	public function register_assets() {
		$google_fonts_deps = array();

		if ( Events_Grid_Settings::get_google_fonts_enabled() ) {
			wp_register_style(
				'deg-roboto',
				'https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;600;700&display=swap',
				array(),
				null   // external URL — no file version
			);
			$google_fonts_deps = array( 'deg-roboto' );
		}

		wp_register_style(
			'events-grid',
			EVENTS_GRID_URL . 'assets/css/events-grid.css',
			$google_fonts_deps,
			EVENTS_GRID_VERSION
		);
	}

	/**
	 * Activation hook.
	 *
	 * Registers both the CPT and taxonomy before flushing rewrite rules so
	 * all slugs resolve correctly immediately after activation.
	 *
	 * @return void
	 */
	public static function activate() {
		Events_Grid_CPT::register_post_type();
		Events_Grid_CPT::register_taxonomy();
		flush_rewrite_rules();
	}

	/**
	 * Deactivation hook.
	 *
	 * @return void
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}
}
