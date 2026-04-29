<?php
/**
 * Plugin Name: Events Grid
 * Plugin URI:  https://www.data-civil.com/calendar/
 * Description: Dynamic event management and responsive event grid shortcode for Elementor and WordPress.
 * Version:     1.2.1
 * Author:      K.Paradorn
 * Author URI:  https://www.data-civil.com
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: events-grid
 * Domain Path: /languages
 *
 * @package EventsGrid
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'EVENTS_GRID_VERSION', '1.2.1' );
define( 'EVENTS_GRID_FILE', __FILE__ );
define( 'EVENTS_GRID_PATH', plugin_dir_path( __FILE__ ) );
define( 'EVENTS_GRID_URL', plugin_dir_url( __FILE__ ) );
define( 'EVENTS_GRID_UPDATE_URL', 'https://www.data-civil.com/updates/events-grid.json' );

require_once EVENTS_GRID_PATH . 'includes/class-events-grid.php';
require_once EVENTS_GRID_PATH . 'includes/class-events-grid-updater.php';

/**
 * Starts the plugin.
 *
 * @return Events_Grid
 */
function events_grid() {
	return Events_Grid::instance();
}

events_grid();

register_activation_hook( __FILE__, array( 'Events_Grid', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Events_Grid', 'deactivate' ) );
