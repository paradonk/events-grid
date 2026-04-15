<?php
/**
 * Plugin settings page.
 *
 * @package EventsGrid
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the Settings admin page and persists plugin options.
 */
class Events_Grid_Settings {

	/** wp_options key for the past-event fade-out period (days). */
	const OPTION_FADEOUT = 'deg_past_fadeout_days';

	/**
	 * Hook setup.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu',  array( __CLASS__, 'register_menu' ) );
		add_action( 'admin_init',  array( __CLASS__, 'register_settings' ) );

		// Bust shortcode output cache whenever the setting changes (covers both
		// first-time save via add_option and subsequent saves via update_option).
		add_action( 'add_option_'    . self::OPTION_FADEOUT, array( 'Events_Grid_Shortcode', 'bust_cache' ) );
		add_action( 'update_option_' . self::OPTION_FADEOUT, array( 'Events_Grid_Shortcode', 'bust_cache' ) );
	}

	/**
	 * Register Settings submenu under the Events post-type menu.
	 *
	 * @return void
	 */
	public static function register_menu() {
		add_submenu_page(
			'edit.php?post_type=event',
			__( 'Events Grid Settings', 'events-grid' ),
			__( 'Settings', 'events-grid' ),
			'manage_options',
			'deg-settings',
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Register settings, sections, and fields via the Settings API.
	 *
	 * @return void
	 */
	public static function register_settings() {
		register_setting(
			'deg_settings_group',
			self::OPTION_FADEOUT,
			array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'default'           => 0,
			)
		);

		add_settings_section(
			'deg_display_section',
			__( 'Display', 'events-grid' ),
			null,
			'deg-settings'
		);

		add_settings_field(
			'deg_past_fadeout_days_field',
			__( 'Hide past events after', 'events-grid' ),
			array( __CLASS__, 'render_fadeout_field' ),
			'deg-settings',
			'deg_display_section'
		);
	}

	/**
	 * Render the fade-out days input field.
	 *
	 * @return void
	 */
	public static function render_fadeout_field() {
		$value = self::get_past_fadeout_days();
		?>
		<input
			type="number"
			id="<?php echo esc_attr( self::OPTION_FADEOUT ); ?>"
			name="<?php echo esc_attr( self::OPTION_FADEOUT ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
			min="0"
			step="1"
			class="small-text"
		/>
		<span><?php esc_html_e( 'days', 'events-grid' ); ?></span>
		<p class="description">
			<?php esc_html_e( 'Past events whose end date (or start date when no end date is set) is older than this many days will no longer appear in the grid. Set to 0 to always show all past events.', 'events-grid' ); ?>
		</p>
		<?php
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Events Grid Settings', 'events-grid' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'deg_settings_group' );
				do_settings_sections( 'deg-settings' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Return the configured fade-out period in days (0 = disabled).
	 *
	 * @return int
	 */
	public static function get_past_fadeout_days() {
		return absint( get_option( self::OPTION_FADEOUT, 0 ) );
	}
}
