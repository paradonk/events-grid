<?php
/**
 * CSV event importer.
 *
 * @package EventsGrid
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles CSV import of events.
 */
class Events_Grid_Importer {

	/** Maximum CSV file size accepted (5 MB). */
	const MAX_FILE_SIZE = 5242880;

	/** Maximum number of data rows processed per upload. */
	const MAX_ROWS = 2000;

	/**
	 * Hook setup.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
		add_action( 'admin_post_deg_import_events', array( __CLASS__, 'handle_import' ) );
	}

	/**
	 * Register admin submenu.
	 *
	 * @return void
	 */
	public static function register_menu() {
		add_submenu_page(
			'edit.php?post_type=event',
			__( 'Import Events', 'events-grid' ),
			__( 'Import Events', 'events-grid' ),
			'manage_options',
			'deg-import-events',
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Render import admin page.
	 *
	 * @return void
	 */
	public static function render_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Import Events', 'events-grid' ); ?></h1>
			<p><?php esc_html_e( 'Upload a CSV file to bulk-import events. Images are not imported — attach them manually after import.', 'events-grid' ); ?></p>

			<h2><?php esc_html_e( 'CSV Format', 'events-grid' ); ?></h2>
			<p><?php esc_html_e( 'The first row must be the header. Required column: title. All other columns are optional.', 'events-grid' ); ?></p>
			<code>title, start_date, end_date, country, venue, booth_number, speaker_enabled, speaker_topic, external_url</code>
			<p>
				<a href="<?php echo esc_url( self::get_template_url() ); ?>" class="button">
					<?php esc_html_e( 'Download CSV Template', 'events-grid' ); ?>
				</a>
			</p>

			<?php self::render_notices(); ?>

			<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'deg_import_events', 'deg_import_nonce' ); ?>
				<input type="hidden" name="action" value="deg_import_events" />
				<table class="form-table">
					<tr>
						<th scope="row"><label for="deg_csv_file"><?php esc_html_e( 'CSV File', 'events-grid' ); ?></label></th>
						<td>
							<input type="file" id="deg_csv_file" name="deg_csv_file" accept=".csv" required />
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Import Events', 'events-grid' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render result notices from query string.
	 *
	 * @return void
	 */
	private static function render_notices() {
		if ( ! empty( $_GET['deg_imported'] ) ) {
			$imported = absint( $_GET['deg_imported'] );
			$skipped  = absint( isset( $_GET['deg_skipped'] ) ? $_GET['deg_skipped'] : 0 );
			echo '<div class="notice notice-success is-dismissible"><p>';
			echo esc_html(
				sprintf(
					/* translators: 1: imported count, 2: skipped count */
					__( 'Import complete: %1$d event(s) imported, %2$d row(s) skipped.', 'events-grid' ),
					$imported,
					$skipped
				)
			);
			echo '</p></div>';
		}

		if ( ! empty( $_GET['deg_import_error'] ) ) {
			$error = sanitize_text_field( wp_unslash( $_GET['deg_import_error'] ) );
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $error ) . '</p></div>';
		}
	}

	/**
	 * Handle CSV upload and import.
	 *
	 * @return void
	 */
	public static function handle_import() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'events-grid' ) );
		}

		check_admin_referer( 'deg_import_events', 'deg_import_nonce' );

		$redirect_base = admin_url( 'edit.php?post_type=event&page=deg-import-events' );

		if ( empty( $_FILES['deg_csv_file']['tmp_name'] ) ) {
			wp_safe_redirect( add_query_arg( 'deg_import_error', rawurlencode( __( 'No file uploaded.', 'events-grid' ) ), $redirect_base ) );
			exit;
		}

		$file = $_FILES['deg_csv_file'];

		// File size check — reject uploads larger than MAX_FILE_SIZE.
		if ( $file['size'] > self::MAX_FILE_SIZE ) {
			wp_safe_redirect( add_query_arg( 'deg_import_error', rawurlencode( __( 'File too large. Maximum size is 5 MB.', 'events-grid' ) ), $redirect_base ) );
			exit;
		}

		// Validate file extension.
		$file_ext = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
		if ( 'csv' !== $file_ext ) {
			wp_safe_redirect( add_query_arg( 'deg_import_error', rawurlencode( __( 'Invalid file type. Please upload a .csv file.', 'events-grid' ) ), $redirect_base ) );
			exit;
		}

		// Validate actual file content using finfo/mime_content_type when available.
		// This catches renamed binaries or executables disguised as .csv files.
		$real_mime = self::get_file_mime( $file['tmp_name'] );
		if ( $real_mime && 0 !== strpos( $real_mime, 'text/' ) ) {
			wp_safe_redirect( add_query_arg( 'deg_import_error', rawurlencode( __( 'Invalid file type. Please upload a .csv file.', 'events-grid' ) ), $redirect_base ) );
			exit;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen
		$handle = fopen( $file['tmp_name'], 'r' );
		if ( false === $handle ) {
			wp_safe_redirect( add_query_arg( 'deg_import_error', rawurlencode( __( 'Could not read the uploaded file.', 'events-grid' ) ), $redirect_base ) );
			exit;
		}

		$header    = null;
		$imported  = 0;
		$skipped   = 0;
		$row_count = 0;

		// Pre-load all existing (title, start_date) pairs in one query so
		// duplicate detection is O(1) instead of one WP_Query per row.
		$existing_keys = self::get_existing_event_keys();

		// Suspend object-cache invalidation for the duration of the import so
		// each wp_insert_post / update_post_meta call does not thrash the cache.
		// A single flush at the end brings the cache back in sync.
		wp_suspend_cache_invalidation( true );

		while ( ( $row = fgetcsv( $handle ) ) !== false ) { // phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition
			if ( null === $header ) {
				$header = array_map( 'trim', $row );
				$header = array_map( 'strtolower', $header );
				continue;
			}

			// Hard cap: skip any rows beyond MAX_ROWS.
			if ( $row_count >= self::MAX_ROWS ) {
				$skipped++;
				continue;
			}

			if ( count( $row ) !== count( $header ) ) {
				$skipped++;
				continue;
			}

			$data = array_combine( $header, $row );

			$title = isset( $data['title'] ) ? sanitize_text_field( trim( $data['title'] ) ) : '';
			if ( empty( $title ) ) {
				$skipped++;
				continue;
			}

			$start_date_check = self::parse_csv_date( isset( $data['start_date'] ) ? $data['start_date'] : '' );

			// O(1) duplicate check against the pre-loaded set.
			if ( isset( $existing_keys[ $title . '|' . $start_date_check ] ) ) {
				$skipped++;
				continue;
			}

			$post_id = wp_insert_post(
				array(
					'post_title'  => $title,
					'post_type'   => 'event',
					'post_status' => 'publish',
				),
				true
			);

			if ( is_wp_error( $post_id ) ) {
				$skipped++;
				continue;
			}

			update_post_meta( $post_id, '_deg_start_date', $start_date_check );
			update_post_meta( $post_id, '_deg_end_date', self::parse_csv_date( isset( $data['end_date'] ) ? $data['end_date'] : '' ) );
			update_post_meta( $post_id, '_deg_country', sanitize_text_field( isset( $data['country'] ) ? $data['country'] : '' ) );
			update_post_meta( $post_id, '_deg_venue', sanitize_text_field( isset( $data['venue'] ) ? $data['venue'] : '' ) );
			update_post_meta( $post_id, '_deg_booth_number', sanitize_text_field( isset( $data['booth_number'] ) ? $data['booth_number'] : '' ) );
			update_post_meta( $post_id, '_deg_speaker_enabled', ( isset( $data['speaker_enabled'] ) && '1' === trim( $data['speaker_enabled'] ) ) ? '1' : '' );
			update_post_meta( $post_id, '_deg_speaker_topic', sanitize_text_field( isset( $data['speaker_topic'] ) ? $data['speaker_topic'] : '' ) );
			update_post_meta( $post_id, '_deg_external_url', esc_url_raw( isset( $data['external_url'] ) ? $data['external_url'] : '' ) );

			// Mark as existing so duplicate rows within the same CSV are also caught.
			$existing_keys[ $title . '|' . $start_date_check ] = true;
			$row_count++;
			$imported++;
		}

		wp_suspend_cache_invalidation( false );
		wp_cache_flush();

		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose

		wp_safe_redirect(
			add_query_arg(
				array(
					'deg_imported' => $imported,
					'deg_skipped'  => $skipped,
				),
				$redirect_base
			)
		);
		exit;
	}

	/**
	 * Detect the MIME type of a file using finfo or mime_content_type.
	 *
	 * Returns an empty string when neither extension is available so callers
	 * can decide to skip verification rather than block a legitimate upload.
	 *
	 * @param string $path Absolute path to the file.
	 * @return string Detected MIME type, or '' if detection is not available.
	 */
	private static function get_file_mime( $path ) {
		if ( function_exists( 'finfo_open' ) ) {
			$finfo = finfo_open( FILEINFO_MIME_TYPE );
			$mime  = (string) finfo_file( $finfo, $path );
			finfo_close( $finfo );
			return $mime;
		}

		if ( function_exists( 'mime_content_type' ) ) {
			return (string) mime_content_type( $path );
		}

		return '';
	}

	/**
	 * Parse a date string from various common formats into Y-m-d.
	 * Handles Excel-reformatted dates in addition to the standard Y-m-d.
	 *
	 * @param string $date Raw date string from CSV.
	 * @return string Y-m-d on success, empty string on failure.
	 */
	private static function parse_csv_date( $date ) {
		$date = trim( (string) $date );

		if ( empty( $date ) ) {
			return '';
		}

		$formats = array(
			'Y-m-d',     // 2026-06-15  (standard)
			'd/m/Y',     // 15/06/2026  (EU/Thai Excel)
			'm/d/Y',     // 06/15/2026  (US Excel)
			'Y/m/d',     // 2026/06/15
			'd-m-Y',     // 15-06-2026
			'm-d-Y',     // 06-15-2026
			'j F Y',     // 15 June 2026
			'F j, Y',    // June 15, 2026
		);

		foreach ( $formats as $format ) {
			$dt = \DateTime::createFromFormat( $format, $date );
			if ( false !== $dt ) {
				$errors = \DateTime::getLastErrors();
				if ( empty( $errors['warning_count'] ) && empty( $errors['error_count'] ) ) {
					return $dt->format( 'Y-m-d' );
				}
			}
		}

		return '';
	}

	/**
	 * Fetch all existing event (title, start_date) pairs in a single query.
	 *
	 * Returns a hash set keyed by "{title}|{start_date}" so callers can do
	 * O(1) duplicate checks instead of running a WP_Query per CSV row.
	 *
	 * @return array<string,true>
	 */
	private static function get_existing_event_keys() {
		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.post_title, pm.meta_value AS start_date
				 FROM {$wpdb->posts} p
				 INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = %s
				 WHERE p.post_type = %s
				   AND p.post_status != 'trash'",
				'_deg_start_date',
				'event'
			),
			ARRAY_A
		);

		$keys = array();
		foreach ( $rows as $row ) {
			$keys[ $row['post_title'] . '|' . $row['start_date'] ] = true;
		}

		return $keys;
	}

	/**
	 * Return URL for downloading the CSV template.
	 *
	 * @return string
	 */
	private static function get_template_url() {
		return add_query_arg(
			array(
				'action'         => 'deg_download_template',
				'deg_tmpl_nonce' => wp_create_nonce( 'deg_download_template' ),
			),
			admin_url( 'admin-post.php' )
		);
	}

	/**
	 * Output CSV template file for download.
	 *
	 * @return void
	 */
	public static function handle_template_download() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'events-grid' ) );
		}

		check_admin_referer( 'deg_download_template', 'deg_tmpl_nonce' );

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="events-import-template.csv"' );
		header( 'Pragma: no-cache' );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen
		$out = fopen( 'php://output', 'w' );
		fputcsv( $out, array( 'title', 'start_date', 'end_date', 'country', 'venue', 'booth_number', 'speaker_enabled', 'speaker_topic', 'external_url' ) );
		fputcsv( $out, array( 'Example Event', '2026-06-15', '2026-06-18', 'Thailand', 'Bangkok Convention Centre', 'Hall 5, B12', '1', 'Innovations in Steel Technology', 'https://example.com' ) );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose
		fclose( $out );
		exit;
	}
}
