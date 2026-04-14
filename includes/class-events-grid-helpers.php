<?php
/**
 * Helper functions.
 *
 * @package EventsGrid
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Helper class.
 */
class Events_Grid_Helpers {

	/**
	 * Sanitize Y-m-d date.
	 *
	 * @param string $date Raw date.
	 * @return string
	 */
	public static function sanitize_date( $date ) {
		$date = trim( (string) $date );

		if ( empty( $date ) ) {
			return '';
		}

		$dt = \DateTime::createFromFormat( 'Y-m-d', $date );
		$errors = \DateTime::getLastErrors();

		if ( false === $dt || ! empty( $errors['warning_count'] ) || ! empty( $errors['error_count'] ) ) {
			return '';
		}

		return $dt->format( 'Y-m-d' );
	}

	/**
	 * Format event date range.
	 *
	 * @param string $start_date Start date in Y-m-d.
	 * @param string $end_date   End date in Y-m-d.
	 * @return string
	 */
	public static function format_date_range( $start_date, $end_date ) {
		if ( empty( $start_date ) ) {
			return '';
		}

		$start = date_create( $start_date );
		$end   = ! empty( $end_date ) ? date_create( $end_date ) : false;

		if ( ! $start ) {
			return '';
		}

		if ( ! $end || $start_date === $end_date ) {
			return wp_date( 'j F Y', $start->getTimestamp() );
		}

		if ( wp_date( 'F Y', $start->getTimestamp() ) === wp_date( 'F Y', $end->getTimestamp() ) ) {
			return sprintf(
				/* translators: 1: start day, 2: end day, 3: month year */
				__( '%1$s–%2$s %3$s', 'events-grid' ),
				wp_date( 'j', $start->getTimestamp() ),
				wp_date( 'j', $end->getTimestamp() ),
				wp_date( 'F Y', $start->getTimestamp() )
			);
		}

		return sprintf(
			/* translators: 1: formatted start date, 2: formatted end date */
			__( '%1$s – %2$s', 'events-grid' ),
			wp_date( 'j F Y', $start->getTimestamp() ),
			wp_date( 'j F Y', $end->getTimestamp() )
		);
	}

	/**
	 * Build location line.
	 *
	 * @param string $venue   Venue.
	 * @param string $country Country.
	 * @return string
	 */
	public static function format_location( $venue, $country ) {
		$parts = array_filter(
			array_map(
				'trim',
				array( (string) $venue, (string) $country )
			)
		);

		return implode( ', ', $parts );
	}
}
