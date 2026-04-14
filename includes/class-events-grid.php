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
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'init', array( $this, 'init_components' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
		add_action( 'admin_post_deg_download_template', array( 'Events_Grid_Importer', 'handle_template_download' ) );
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
	}

	/**
	 * Register front-end assets.
	 *
	 * @return void
	 */
	public function register_assets() {
		wp_register_style(
			'events-grid',
			EVENTS_GRID_URL . 'assets/css/events-grid.css',
			array(),
			EVENTS_GRID_VERSION
		);
	}

	/**
	 * Activation hook.
	 *
	 * @return void
	 */
	public static function activate() {
		Events_Grid_CPT::register_post_type();
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
