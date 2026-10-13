<?php
/**
 * The private Lead post type and the stored shape of one lead and its consent record.
 *
 * Leads hold personal data and consent evidence, so every capability, including opening one lead in
 * wp-admin, maps to manage_options (map_meta_cap off), as Hookshot does for its webhook posts (#5955).
 * Leads are created only by the capture route; the UI cannot add one.
 *
 * @package    Xophz_Compass_Lead_Magnet
 * @subpackage Xophz_Compass_Lead_Magnet/includes
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Lead_Magnet_Leads {

	const POST_TYPE   = 'compass_lead';
	const META_PREFIX = '_lm_';

	/** Every stored field, in export column order. Each is post meta under META_PREFIX. */
	const FIELDS = array(
		'name',
		'email',
		'phone',
		'state',
		'question',
		'offer',
		'consent_given',
		'consent_text',
		'consent_version',
		'seller',
		'privacy_url',
		'contact_channels',
		'captured_at',
		'ip',
		'user_agent',
		'page_url',
		'utm_source',
		'utm_medium',
		'utm_campaign',
		'fm_thread',
		'fm_reply',
	);

	public function init(): void {
		add_action( 'init', array( $this, 'register_post_type' ), 0 );
	}

	public function register_post_type(): void {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => array(
					'name'               => __( 'Leads', 'xophz-compass-lead-magnet' ),
					'singular_name'      => __( 'Lead', 'xophz-compass-lead-magnet' ),
					'menu_name'          => __( 'Leads', 'xophz-compass-lead-magnet' ),
					'all_items'          => __( 'Leads', 'xophz-compass-lead-magnet' ),
					'edit_item'          => __( 'Lead', 'xophz-compass-lead-magnet' ),
					'view_item'          => __( 'View Lead', 'xophz-compass-lead-magnet' ),
					'search_items'       => __( 'Search Leads', 'xophz-compass-lead-magnet' ),
					'not_found'          => __( 'No leads yet.', 'xophz-compass-lead-magnet' ),
					'not_found_in_trash' => __( 'No leads in Trash.', 'xophz-compass-lead-magnet' ),
				),
				'description'         => __( 'Leads captured by the Lead Magnet form, with their consent records.', 'xophz-compass-lead-magnet' ),
				'supports'            => array( 'title' ),
				'hierarchical'        => false,
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => 'xophz-compass',
				'show_in_admin_bar'   => false,
				'show_in_nav_menus'   => false,
				'show_in_rest'        => false,
				'can_export'          => false,
				'has_archive'         => false,
				'exclude_from_search' => true,
				'publicly_queryable'  => false,
				'query_var'           => false,
				'rewrite'             => false,
				'capability_type'     => 'post',
				'capabilities'        => self::admin_only_capabilities(),
				'map_meta_cap'        => false,
			)
		);
	}

	/** All caps map to manage_options; nobody creates a lead by hand. */
	private static function admin_only_capabilities(): array {
		$caps = array_fill_keys(
			array(
				'edit_post', 'read_post', 'delete_post', 'edit_posts', 'edit_others_posts', 'delete_posts',
				'publish_posts', 'read_private_posts', 'create_posts', 'delete_private_posts',
				'delete_published_posts', 'delete_others_posts', 'edit_private_posts', 'edit_published_posts',
			),
			'manage_options'
		);
		$caps['create_posts'] = 'do_not_allow';
		return $caps;
	}

	/**
	 * Store one validated lead as a private post. Values are slashed on the way in because
	 * wp_insert_post and update_post_meta unslash, which would otherwise alter the consent text.
	 *
	 * @param array<string, mixed> $lead Every key in FIELDS, already sanitized.
	 * @return array<string, mixed>|null The stored record with its id, or null when the insert failed.
	 */
	public static function store( array $lead ): ?array {
		$meta = array();
		foreach ( self::FIELDS as $field ) {
			$meta[ self::META_PREFIX . $field ] = wp_slash( self::to_meta( $field, $lead[ $field ] ?? '' ) );
		}

		$post_id = wp_insert_post(
			array(
				'post_type'   => self::POST_TYPE,
				'post_status' => 'private',
				'post_author' => 0,
				'post_title'  => wp_slash( sprintf( '%s (%s)', (string) $lead['name'], (string) $lead['email'] ) ),
				'meta_input'  => $meta,
			),
			true
		);

		if ( is_wp_error( $post_id ) || ! $post_id ) {
			return null;
		}
		return self::get( (int) $post_id );
	}

	/**
	 * One stored lead as a flat record: id, then every field. consent_given is a bool and
	 * contact_channels a list. Null when the id is not a lead.
	 *
	 * @return array<string, mixed>|null
	 */
	public static function get( int $post_id ): ?array {
		if ( self::POST_TYPE !== get_post_type( $post_id ) ) {
			return null;
		}
		$record = array( 'id' => $post_id );
		foreach ( self::FIELDS as $field ) {
			$record[ $field ] = (string) get_post_meta( $post_id, self::META_PREFIX . $field, true );
		}
		$record['consent_given']    = '1' === $record['consent_given'];
		$record['contact_channels'] = '' === $record['contact_channels'] ? array() : explode( ',', $record['contact_channels'] );
		return $record;
	}

	/** Every lead id, newest first, in pages so a large export never loads all posts at once. */
	public static function ids( int $page, int $per_page = 200 ): array {
		$query = new WP_Query(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'any',
				'fields'         => 'ids',
				'orderby'        => 'ID',
				'order'          => 'DESC',
				'posts_per_page' => $per_page,
				'paged'          => max( 1, $page ),
				'no_found_rows'  => true,
			)
		);
		return array_map( 'intval', $query->posts );
	}

	/** Meta storage form of one field: bools as '1'/'0', lists comma-joined, the rest as strings. */
	private static function to_meta( string $field, $value ): string {
		if ( 'consent_given' === $field ) {
			return $value ? '1' : '0';
		}
		if ( is_array( $value ) ) {
			return implode( ',', array_map( 'strval', $value ) );
		}
		return (string) $value;
	}
}
