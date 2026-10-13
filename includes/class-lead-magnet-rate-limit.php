<?php
/**
 * Fixed-window submission counters for the capture route, kept in transients.
 *
 * Buckets: 'ip' counts every nonce-valid post from one REMOTE_ADDR; 'email' counts posts that passed
 * validation for one email address. Transient names carry a keyed hash, never the raw address.
 *
 * @package    Xophz_Compass_Lead_Magnet
 * @subpackage Xophz_Compass_Lead_Magnet/includes
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Lead_Magnet_Rate_Limit {

	/** Default [max hits, window seconds] per bucket. Filter: xophz_lead_magnet_rate_limits. */
	const DEFAULTS = array(
		'ip'    => array( 10, 3600 ),
		'email' => array( 3, 3600 ),
	);

	/**
	 * Limits for one bucket after the filter, never below one hit per one-second window.
	 *
	 * @return array{0: int, 1: int}
	 */
	public static function limits( string $bucket ): array {
		$all   = apply_filters( 'xophz_lead_magnet_rate_limits', self::DEFAULTS );
		$pair  = is_array( $all ) && isset( $all[ $bucket ] ) && is_array( $all[ $bucket ] ) ? $all[ $bucket ] : self::DEFAULTS[ $bucket ];
		$max   = max( 1, (int) ( $pair[0] ?? 1 ) );
		$window = max( 1, (int) ( $pair[1] ?? 1 ) );
		return array( $max, $window );
	}

	/** Count one hit for $value in $bucket. Returns true when this hit is over the limit. */
	public static function hit( string $bucket, string $value ): bool {
		list( $max, $window ) = self::limits( $bucket );
		$key   = self::key( $bucket, $value, $window );
		$count = (int) get_transient( $key ) + 1;
		set_transient( $key, $count, $window );
		return $count > $max;
	}

	/** Transient name for the current window: bucket, keyed hash of the value, window index. */
	public static function key( string $bucket, string $value, int $window ): string {
		$hash  = substr( hash_hmac( 'sha256', strtolower( $value ), wp_salt( 'nonce' ) ), 0, 24 );
		$index = (int) floor( time() / max( 1, $window ) );
		return 'xlm_rl_' . $bucket . '_' . $hash . '_' . $index;
	}
}
