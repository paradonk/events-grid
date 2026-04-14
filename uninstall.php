<?php
/**
 * Uninstall handler.
 *
 * @package EventsGrid
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Safe default: keep data on uninstall.
// Delete event posts and meta manually only if you explicitly want destructive cleanup.
