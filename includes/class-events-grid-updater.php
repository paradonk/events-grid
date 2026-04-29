<?php
/**
 * Self-hosted automatic updater for the Events Grid plugin.
 *
 * Hooks into WordPress's native update pipeline. No external dependencies.
 * Checks a remote JSON file every 12 hours and surfaces any new version on
 * the Plugins page exactly like a wordpress.org plugin update.
 *
 * @package EventsGrid
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Events_Grid_Updater {

	/** Absolute path to the main plugin file (events-grid.php). */
	private $plugin_file;

	/** Plugin slug — matches the plugin folder name: "events-grid". */
	private $plugin_slug;

	/** Currently installed version string. */
	private $version;

	/** Full URL to the remote JSON metadata file. */
	private $update_url;

	/** Transient key used to cache the remote response. */
	private $cache_key = 'eg_remote_update_info';

	/** How long to cache the remote response, in seconds (12 hours). */
	private $cache_ttl = 43200;

	/**
	 * @param string $plugin_file  Absolute path to the plugin's main file (__FILE__).
	 * @param string $version      Current plugin version (e.g. "1.0.0").
	 * @param string $update_url   URL to the remote JSON metadata file.
	 */
	public function __construct( $plugin_file, $version, $update_url ) {
		$this->plugin_file = $plugin_file;
		$this->plugin_slug = dirname( plugin_basename( $plugin_file ) ); // "events-grid"
		$this->version     = $version;
		$this->update_url  = $update_url;

		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_update' ) );
		add_filter( 'plugins_api', array( $this, 'plugin_info' ), 20, 3 );
		add_action( 'upgrader_process_complete', array( $this, 'clear_cache_after_update' ), 10, 2 );
		add_filter( 'upgrader_pre_download', array( $this, 'verify_package' ), 10, 3 );
	}

	// -------------------------------------------------------------------------
	// Remote metadata
	// -------------------------------------------------------------------------

	/**
	 * Fetch (and cache) the remote JSON metadata.
	 *
	 * @return object|false Decoded JSON object, or false on failure.
	 */
	private function get_remote_info() {
		$cached = get_transient( $this->cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$response = wp_remote_get(
			$this->update_url,
			array(
				'timeout' => 10,
				'headers' => array( 'Accept' => 'application/json' ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ) );

		if ( empty( $data ) || ! isset( $data->version ) || ! isset( $data->download_url ) ) {
			return false;
		}

		set_transient( $this->cache_key, $data, $this->cache_ttl );
		return $data;
	}

	// -------------------------------------------------------------------------
	// WordPress hooks
	// -------------------------------------------------------------------------

	/**
	 * Filter: pre_set_site_transient_update_plugins
	 *
	 * Injects update data into WordPress's update-check transient so the
	 * Plugins page shows an "Update now" link when a newer version exists.
	 *
	 * @param  object $transient The existing update_plugins transient value.
	 * @return object            Possibly modified transient.
	 */
	public function check_for_update( $transient ) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$info = $this->get_remote_info();
		if ( ! $info ) {
			return $transient;
		}

		$plugin_basename = plugin_basename( $this->plugin_file ); // "events-grid/events-grid.php"

		if ( version_compare( $this->version, $info->version, '<' ) ) {
			$transient->response[ $plugin_basename ] = (object) array(
				'id'            => $this->plugin_slug . '/' . $this->plugin_slug . '.php',
				'slug'          => $this->plugin_slug,
				'plugin'        => $plugin_basename,
				'new_version'   => $info->version,
				'url'           => isset( $info->author_profile ) ? $info->author_profile : '',
				'package'       => $info->download_url,
				'icons'         => array(),
				'banners'       => array(),
				'banners_rtl'   => array(),
				'tested'        => isset( $info->tested )       ? $info->tested       : '',
				'requires_php'  => isset( $info->requires_php ) ? $info->requires_php : '',
				'compatibility' => new stdClass(),
			);

			unset( $transient->no_update[ $plugin_basename ] );

		} else {
			if ( ! isset( $transient->response[ $plugin_basename ] ) ) {
				$transient->no_update[ $plugin_basename ] = (object) array(
					'id'          => $this->plugin_slug . '/' . $this->plugin_slug . '.php',
					'slug'        => $this->plugin_slug,
					'plugin'      => $plugin_basename,
					'new_version' => $this->version,
					'url'         => '',
					'package'     => '',
					'icons'       => array(),
					'banners'     => array(),
					'banners_rtl' => array(),
				);
			}
		}

		return $transient;
	}

	/**
	 * Filter: plugins_api
	 *
	 * Provides plugin metadata for the "View version x.x.x details" modal.
	 *
	 * @param  false|object $result  Existing result.
	 * @param  string       $action  API action being requested.
	 * @param  object       $args    Arguments for the API call.
	 * @return false|object          Plugin info object, or original $result.
	 */
	public function plugin_info( $result, $action, $args ) {
		if ( 'plugin_information' !== $action ) {
			return $result;
		}

		if ( ! isset( $args->slug ) || $args->slug !== $this->plugin_slug ) {
			return $result;
		}

		$info = $this->get_remote_info();
		if ( ! $info ) {
			return $result;
		}

		return (object) array(
			'name'           => isset( $info->name )           ? $info->name           : '',
			'slug'           => $this->plugin_slug,
			'version'        => $info->version,
			'author'         => isset( $info->author )         ? $info->author         : '',
			'author_profile' => isset( $info->author_profile ) ? $info->author_profile : '',
			'requires'       => isset( $info->requires )       ? $info->requires       : '',
			'tested'         => isset( $info->tested )         ? $info->tested         : '',
			'requires_php'   => isset( $info->requires_php )   ? $info->requires_php   : '',
			'download_link'  => $info->download_url,
			'trunk'          => $info->download_url,
			'last_updated'   => isset( $info->last_updated )   ? $info->last_updated   : '',
			'sections'       => isset( $info->sections )       ? (array) $info->sections : array(
				'description' => isset( $info->description ) ? $info->description : '',
			),
			'banners'        => isset( $info->banners )        ? (array) $info->banners  : array(),
		);
	}

	/**
	 * Filter: upgrader_pre_download
	 *
	 * Downloads the plugin zip and verifies its SHA-256 checksum (when the
	 * remote JSON provides a "sha256" field) before handing the local file
	 * path back to the upgrader. If the hash does not match, the update is
	 * aborted and the temp file is deleted.
	 *
	 * Servers that do not publish a sha256 field fall back to the default
	 * WordPress download flow (no verification, same as before).
	 *
	 * @param  bool|WP_Error $reply    Pre-filtered reply — pass through if not our package.
	 * @param  string        $package  URL of the package being downloaded.
	 * @param  WP_Upgrader   $upgrader Upgrader instance (unused here).
	 * @return bool|string|WP_Error    Local temp file path, WP_Error on failure, or $reply.
	 */
	public function verify_package( $reply, $package, $upgrader ) {
		$info = $this->get_remote_info();

		// Not our package, or no metadata available — leave the reply unchanged.
		if ( ! $info || $package !== $info->download_url ) {
			return $reply;
		}

		// No checksum in the metadata — skip verification.
		if ( empty( $info->sha256 ) ) {
			return $reply;
		}

		// Download to a temporary file.
		$temp_file = download_url( $package );
		if ( is_wp_error( $temp_file ) ) {
			return $temp_file;
		}

		// Verify the SHA-256 hash.
		$actual_hash = hash_file( 'sha256', $temp_file );
		if ( ! hash_equals( (string) $info->sha256, (string) $actual_hash ) ) {
			@unlink( $temp_file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
			return new WP_Error(
				'deg_integrity_fail',
				__( 'Events Grid update aborted: package checksum does not match.', 'events-grid' )
			);
		}

		// Return the already-downloaded temp file; WordPress skips its own download.
		return $temp_file;
	}

	/**
	 * Action: upgrader_process_complete
	 *
	 * Clears the cached remote info after this plugin is updated so the next
	 * page load fetches fresh data rather than the old version string.
	 *
	 * @param WP_Upgrader $upgrader Upgrader instance.
	 * @param array       $options  Bulk item/action data.
	 */
	public function clear_cache_after_update( $upgrader, $options ) {
		if (
			'update' !== $options['action'] ||
			'plugin' !== $options['type']   ||
			empty( $options['plugins'] )
		) {
			return;
		}

		$plugin_basename = plugin_basename( $this->plugin_file );
		foreach ( $options['plugins'] as $updated_plugin ) {
			if ( $updated_plugin === $plugin_basename ) {
				delete_transient( $this->cache_key );
				break;
			}
		}
	}
}
