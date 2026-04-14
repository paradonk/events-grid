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

		// Validate mime type.
		$file_type = wp_check_filetype( $file['name'], array( 'csv' => 'text/csv' ) );
		if ( empty( $file_type['ext'] ) ) {
			wp_safe_redirect( add_query_arg( 'deg_import_error', rawurlencode( __( 'Invalid file type. Please upload a .csv file.', 'events-grid' ) ), $redirect_base ) );
			exit;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen
		$handle = fopen( $file['tmp_name'], 'r' );
		if ( false === $handle ) {
			wp_safe_redirect( add_query_arg( 'deg_import_error', rawurlencode( __( 'Could not read the uploaded file.', 'events-grid' ) ), $redirect_base ) );
			exit;
		}

		$header   = null;
		$imported = 0;
		$skipped  = 0;

		while ( ( $row = fgetcsv( $handle ) ) !== false ) { // phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition
			if ( null === $header ) {
				$header = array_map( 'trim', $row );
				$header = array_map( 'strtolower', $header );
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

			if ( self::event_exists( $title, $start_date_check ) ) {
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

			$imported++;
		}

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
	 * Check if an event with the same title and start date already exists.
	 *
	 * @param string $title      Event title.
	 * @param string $start_date Start date in Y-m-d.
	 * @return bool
	 */
	private static function event_exists( $title, $start_date ) {
		$query = new WP_Query(
			array(
				'post_type'      => 'event',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'title'          => $title,
				'meta_query'     => array(
					array(
						'key'   => '_deg_start_date',
						'value' => $start_date,
					),
				),
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);

		return $query->have_posts();
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
