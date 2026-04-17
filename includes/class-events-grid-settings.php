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

	/** wp_options key for the Google Fonts toggle (1 = load, 0 = skip). */
	const OPTION_GOOGLE_FONTS = 'deg_load_google_fonts';

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
	 * Return whether Google Fonts (Roboto) should be loaded (default: true).
	 *
	 * Allows site owners to disable the external request and use a self-hosted
	 * font or their theme's existing Roboto stack instead.
	 *
	 * @return bool
	 */
	public static function get_google_fonts_enabled() {
		// get_option returns '' when the option has never been saved; treat that
		// as enabled so existing installs keep the same behaviour after upgrade.
		$val = get_option( self::OPTION_GOOGLE_FONTS, '1' );
		return '1' === (string) $val || '' === (string) $val;
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

		register_setting(
			'deg_settings_group',
			self::OPTION_GOOGLE_FONTS,
			array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'default'           => 1,
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

		add_settings_field(
			'deg_google_fonts_field',
			__( 'Google Fonts', 'events-grid' ),
			array( __CLASS__, 'render_google_fonts_field' ),
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
	 * Render the Google Fonts toggle field.
	 *
	 * @return void
	 */
	public static function render_google_fonts_field() {
		$enabled = self::get_google_fonts_enabled();
		?>
		<label>
			<input
				type="checkbox"
				id="<?php echo esc_attr( self::OPTION_GOOGLE_FONTS ); ?>"
				name="<?php echo esc_attr( self::OPTION_GOOGLE_FONTS ); ?>"
				value="1"
				<?php checked( $enabled ); ?>
			/>
			<?php esc_html_e( 'Load Roboto from Google Fonts', 'events-grid' ); ?>
		</label>
		<p class="description">
			<?php esc_html_e( 'Uncheck to skip the external Google Fonts request. Use this when you self-host Roboto, your theme already loads it, or you prefer a system font stack.', 'events-grid' ); ?>
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
