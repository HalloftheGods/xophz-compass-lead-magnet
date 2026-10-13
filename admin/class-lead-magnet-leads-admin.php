<?php
/**
 * wp-admin for leads (manage_options only): list columns, a read-only consent record on each lead,
 * and a CSV export of every consent record.
 *
 * @package    Xophz_Compass_Lead_Magnet
 * @subpackage Xophz_Compass_Lead_Magnet/admin
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Lead_Magnet_Leads_Admin {

	const EXPORT_ACTION = 'xophz_lead_magnet_export';

	public function init(): void {
		$type = Lead_Magnet_Leads::POST_TYPE;
		add_filter( "manage_{$type}_posts_columns", array( $this, 'columns' ) );
		add_action( "manage_{$type}_posts_custom_column", array( $this, 'column_value' ), 10, 2 );
		add_action( "add_meta_boxes_{$type}", array( $this, 'add_meta_box' ) );
		add_action( 'manage_posts_extra_tablenav', array( $this, 'export_button' ) );
		add_action( 'admin_post_' . self::EXPORT_ACTION, array( $this, 'export' ) );
	}

	public static function export_url(): string {
		return wp_nonce_url( admin_url( 'admin-post.php?action=' . self::EXPORT_ACTION ), self::EXPORT_ACTION );
	}

	public static function can_export(): bool {
		return current_user_can( 'manage_options' );
	}

	public function columns( array $columns ): array {
		return array(
			'cb'         => $columns['cb'] ?? '',
			'title'      => __( 'Lead', 'xophz-compass-lead-magnet' ),
			'lm_phone'   => __( 'Phone', 'xophz-compass-lead-magnet' ),
			'lm_state'   => __( 'State', 'xophz-compass-lead-magnet' ),
			'lm_consent' => __( 'Consent', 'xophz-compass-lead-magnet' ),
			'lm_source'  => __( 'Source', 'xophz-compass-lead-magnet' ),
			'lm_offer'   => __( 'Offer', 'xophz-compass-lead-magnet' ),
			'date'       => $columns['date'] ?? __( 'Date', 'xophz-compass-lead-magnet' ),
		);
	}

	public function column_value( string $column, int $post_id ): void {
		$meta = static function ( string $field ) use ( $post_id ): string {
			return (string) get_post_meta( $post_id, Lead_Magnet_Leads::META_PREFIX . $field, true );
		};
		switch ( $column ) {
			case 'lm_phone':
				echo esc_html( $meta( 'phone' ) );
				break;
			case 'lm_state':
				echo esc_html( $meta( 'state' ) );
				break;
			case 'lm_consent':
				echo '1' === $meta( 'consent_given' ) ? esc_html__( 'Yes', 'xophz-compass-lead-magnet' ) : esc_html__( 'No (email only)', 'xophz-compass-lead-magnet' );
				break;
			case 'lm_source':
				echo esc_html( trim( $meta( 'utm_source' ) . ' / ' . $meta( 'utm_medium' ), ' /' ) );
				break;
			case 'lm_offer':
				echo esc_html( $meta( 'offer' ) );
				break;
		}
	}

	public function add_meta_box(): void {
		add_meta_box(
			'lead_magnet_consent_record',
			__( 'Lead and consent record', 'xophz-compass-lead-magnet' ),
			array( $this, 'render_meta_box' ),
			Lead_Magnet_Leads::POST_TYPE,
			'normal',
			'high'
		);
	}

	/** Every stored field, read-only. The consent text is shown exactly as stored. */
	public function render_meta_box( WP_Post $post ): void {
		$record = Lead_Magnet_Leads::get( (int) $post->ID );
		if ( null === $record ) {
			return;
		}
		echo '<table class="widefat striped"><tbody>';
		foreach ( $record as $field => $value ) {
			if ( is_bool( $value ) ) {
				$value = $value ? 'yes' : 'no';
			} elseif ( is_array( $value ) ) {
				$value = implode( ', ', $value );
			}
			printf( '<tr><th scope="row">%s</th><td style="white-space:pre-wrap">%s</td></tr>', esc_html( (string) $field ), esc_html( (string) $value ) );
		}
		echo '</tbody></table>';
	}

	public function export_button( string $which ): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( 'top' !== $which || null === $screen || Lead_Magnet_Leads::POST_TYPE !== $screen->post_type || ! self::can_export() ) {
			return;
		}
		printf(
			'<div class="alignleft actions"><a class="button" href="%s">%s</a></div>',
			esc_url( self::export_url() ),
			esc_html__( 'Export consent records (CSV)', 'xophz-compass-lead-magnet' )
		);
	}

	/**
	 * The export as rows: a header row (id, then every stored field), then one row per lead, newest
	 * first. Cells that a spreadsheet would run as a formula are prefixed with a quote.
	 *
	 * @return array<int, array<int, string>>
	 */
	public static function csv_rows(): array {
		$rows = array( array_merge( array( 'id' ), Lead_Magnet_Leads::FIELDS ) );
		for ( $page = 1; ; $page++ ) {
			$ids = Lead_Magnet_Leads::ids( $page );
			foreach ( $ids as $id ) {
				$record = Lead_Magnet_Leads::get( $id );
				if ( null !== $record ) {
					$rows[] = array_map( array( __CLASS__, 'cell' ), array_values( $record ) );
				}
			}
			if ( count( $ids ) < 200 ) {
				break;
			}
		}
		return $rows;
	}

	public function export(): void {
		if ( ! self::can_export() ) {
			wp_die( esc_html__( 'You are not allowed to export leads.', 'xophz-compass-lead-magnet' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( self::EXPORT_ACTION );

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="lead-magnet-consent-records-' . gmdate( 'Ymd-His' ) . '.csv"' );
		$out = fopen( 'php://output', 'w' );
		foreach ( self::csv_rows() as $row ) {
			fputcsv( $out, $row, ',', '"', '' );
		}
		fclose( $out );
		exit;
	}

	/**
	 * One CSV cell: bools as yes/no, lists comma-joined, formula-leading text neutralized. A stored
	 * phone (+1 and digits) cannot carry a formula, so it is left as is.
	 */
	private static function cell( $value ): string {
		if ( is_bool( $value ) ) {
			return $value ? 'yes' : 'no';
		}
		if ( is_array( $value ) ) {
			$value = implode( ',', $value );
		}
		$value = (string) $value;
		if ( '' === $value || preg_match( '/^\+\d+$/', $value ) ) {
			return $value;
		}
		return in_array( $value[0], array( '=', '+', '-', '@', "\t", "\r" ), true ) ? "'" . $value : $value;
	}
}
