<?php
/**
 * Meta box management.
 *
 * @package EventsGrid
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles admin meta boxes.
 */
class Events_Grid_Meta_Boxes {

	/**
	 * Hook setup.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'register_meta_boxes' ) );
		add_action( 'save_post_event', array( __CLASS__, 'save_meta_boxes' ) );
	}

	/**
	 * Register meta box.
	 *
	 * @return void
	 */
	public static function register_meta_boxes() {
		add_meta_box(
			'deg_event_details',
			__( 'Event Details', 'events-grid' ),
			array( __CLASS__, 'render_meta_box' ),
			'event',
			'normal',
			'default'
		);
	}

	/**
	 * Render meta box.
	 *
	 * @param WP_Post $post Post object.
	 * @return void
	 */
	public static function render_meta_box( $post ) {
		wp_nonce_field( 'deg_save_event_details', 'deg_event_nonce' );

		$fields = self::get_fields( $post->ID );
		?>
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><label for="deg_start_date"><?php esc_html_e( 'Start Date', 'events-grid' ); ?></label></th>
					<td><input type="date" id="deg_start_date" name="deg_start_date" value="<?php echo esc_attr( $fields['start_date'] ); ?>" class="regular-text" placeholder="e.g. 2026-06-15" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="deg_end_date"><?php esc_html_e( 'End Date', 'events-grid' ); ?></label></th>
					<td><input type="date" id="deg_end_date" name="deg_end_date" value="<?php echo esc_attr( $fields['end_date'] ); ?>" class="regular-text" placeholder="e.g. 2026-06-18" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="deg_country"><?php esc_html_e( 'Country', 'events-grid' ); ?></label></th>
					<td><input type="text" id="deg_country" name="deg_country" value="<?php echo esc_attr( $fields['country'] ); ?>" class="regular-text" placeholder="e.g. Thailand" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="deg_venue"><?php esc_html_e( 'Venue', 'events-grid' ); ?></label></th>
					<td><input type="text" id="deg_venue" name="deg_venue" value="<?php echo esc_attr( $fields['venue'] ); ?>" class="regular-text" placeholder="e.g. Bangkok Convention Centre" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="deg_booth_number"><?php esc_html_e( 'Booth Number', 'events-grid' ); ?></label></th>
					<td><input type="text" id="deg_booth_number" name="deg_booth_number" value="<?php echo esc_attr( $fields['booth_number'] ); ?>" class="regular-text" placeholder="e.g. Hall 5, B12" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="deg_speaker_enabled"><?php esc_html_e( 'Speaker Badge', 'events-grid' ); ?></label></th>
					<td>
						<label>
							<input type="checkbox" id="deg_speaker_enabled" name="deg_speaker_enabled" value="1" <?php checked( '1', $fields['speaker_enabled'] ); ?> />
							<?php esc_html_e( 'Show speaker badge', 'events-grid' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="deg_speaker_topic"><?php esc_html_e( 'Speaker Topic', 'events-grid' ); ?></label></th>
					<td><input type="text" id="deg_speaker_topic" name="deg_speaker_topic" value="<?php echo esc_attr( $fields['speaker_topic'] ); ?>" class="regular-text" placeholder="e.g. Innovations in Concrete Technology" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="deg_external_url"><?php esc_html_e( 'External Event URL', 'events-grid' ); ?></label></th>
					<td><input type="url" id="deg_external_url" name="deg_external_url" value="<?php echo esc_attr( $fields['external_url'] ); ?>" class="regular-text" placeholder="https://example.com" /></td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Get current fields.
	 *
	 * @param int $post_id Post ID.
	 * @return array<string,string>
	 */
	private static function get_fields( $post_id ) {
		return array(
			'start_date'      => (string) get_post_meta( $post_id, '_deg_start_date', true ),
			'end_date'        => (string) get_post_meta( $post_id, '_deg_end_date', true ),
			'country'         => (string) get_post_meta( $post_id, '_deg_country', true ),
			'venue'           => (string) get_post_meta( $post_id, '_deg_venue', true ),
			'booth_number'    => (string) get_post_meta( $post_id, '_deg_booth_number', true ),
			'speaker_enabled' => (string) get_post_meta( $post_id, '_deg_speaker_enabled', true ),
			'speaker_topic'   => (string) get_post_meta( $post_id, '_deg_speaker_topic', true ),
			'external_url'    => (string) get_post_meta( $post_id, '_deg_external_url', true ),
		);
	}

	/**
	 * Save meta box values.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public static function save_meta_boxes( $post_id ) {
		if ( ! isset( $_POST['deg_event_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['deg_event_nonce'] ) ), 'deg_save_event_details' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$start_date = isset( $_POST['deg_start_date'] ) ? Events_Grid_Helpers::sanitize_date( wp_unslash( $_POST['deg_start_date'] ) ) : '';
		$end_date   = isset( $_POST['deg_end_date'] ) ? Events_Grid_Helpers::sanitize_date( wp_unslash( $_POST['deg_end_date'] ) ) : '';
		$country    = isset( $_POST['deg_country'] ) ? sanitize_text_field( wp_unslash( $_POST['deg_country'] ) ) : '';
		$venue      = isset( $_POST['deg_venue'] ) ? sanitize_text_field( wp_unslash( $_POST['deg_venue'] ) ) : '';
		$booth      = isset( $_POST['deg_booth_number'] ) ? sanitize_text_field( wp_unslash( $_POST['deg_booth_number'] ) ) : '';
		$speaker    = isset( $_POST['deg_speaker_enabled'] ) ? '1' : '';
		$topic      = isset( $_POST['deg_speaker_topic'] ) ? sanitize_text_field( wp_unslash( $_POST['deg_speaker_topic'] ) ) : '';
		$url        = isset( $_POST['deg_external_url'] ) ? esc_url_raw( wp_unslash( $_POST['deg_external_url'] ) ) : '';

		update_post_meta( $post_id, '_deg_start_date', $start_date );
		update_post_meta( $post_id, '_deg_end_date', $end_date );
		update_post_meta( $post_id, '_deg_country', $country );
		update_post_meta( $post_id, '_deg_venue', $venue );
		update_post_meta( $post_id, '_deg_booth_number', $booth );
		update_post_meta( $post_id, '_deg_speaker_enabled', $speaker );
		update_post_meta( $post_id, '_deg_speaker_topic', $topic );
		update_post_meta( $post_id, '_deg_external_url', $url );
	}
}
