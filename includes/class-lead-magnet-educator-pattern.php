<?php
/**
 * The "Annuity educator landing page" block pattern (category "Lead Magnet"): a hero, three YouTube
 * video slots, a "Questions people ask" section, the [lead_magnet_capture] form and a compliance
 * footer. The owner inserts it into a page and edits it in the block editor.
 *
 * Core blocks only, so any theme renders it. Every piece of copy is a bracketed instruction to
 * replace: the pattern states nothing about a real person, no credentials, rates or guarantees.
 *
 * Unset slots do not reach the public page: a video slot with no URL, the section when no slot has a
 * URL, and the editor note are dropped when the page renders. Bracketed text the editor leaves in
 * place is shown as written, so replace every bracket before publishing.
 *
 * @package    Xophz_Compass_Lead_Magnet
 * @subpackage Xophz_Compass_Lead_Magnet/includes
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Lead_Magnet_Educator_Pattern {

	const PATTERN  = 'xophz-lead-magnet/annuity-educator';
	const CATEGORY = 'lead-magnet';
	const OFFER    = 'annuity-checklist';
	const SLOTS    = 3;

	/** Marker classes the render filter looks for. */
	const CLASS_VIDEOS = 'lead-magnet-educator__videos';
	const CLASS_VIDEO  = 'lead-magnet-educator__video';
	const CLASS_NOTE   = 'lead-magnet-educator__editor-note';

	public function init(): void {
		add_action( 'init', array( $this, 'register' ) );
		add_filter( 'render_block', array( $this, 'hide_unset_slots' ), 10, 2 );
	}

	public function register(): void {
		register_block_pattern_category(
			self::CATEGORY,
			array( 'label' => __( 'Lead Magnet', 'xophz-compass-lead-magnet' ) )
		);

		register_block_pattern(
			self::PATTERN,
			array(
				'title'       => __( 'Annuity educator landing page', 'xophz-compass-lead-magnet' ),
				'description' => __( 'A landing page for an annuity educator: hero, three YouTube video slots, three common questions, the Lead Magnet capture form and a compliance footer. Every bracketed line is an instruction to replace.', 'xophz-compass-lead-magnet' ),
				'categories'  => array( self::CATEGORY ),
				'keywords'    => array( 'annuity', 'educator', 'landing page', 'lead magnet' ),
				'content'     => serialize_blocks( $this->blocks() ),
			)
		);
	}

	/**
	 * Front-end filter: drop an editor note, a video slot with no URL, and the video section when no
	 * slot has a URL. The editor never runs this, so the owner always sees every slot.
	 *
	 * @param string $content Rendered block.
	 * @param array  $block   Parsed block.
	 * @return string
	 */
	public function hide_unset_slots( $content, $block ) {
		if ( ! is_array( $block ) ) {
			return $content;
		}

		$classes = $this->classes( $block );
		if ( in_array( self::CLASS_NOTE, $classes, true ) ) {
			return '';
		}

		$is_slot = in_array( self::CLASS_VIDEO, $classes, true ) || in_array( self::CLASS_VIDEOS, $classes, true );
		if ( $is_slot && ! $this->has_video( $block ) ) {
			return '';
		}

		return $content;
	}

	/** @return string[] The block's custom classes. */
	private function classes( array $block ): array {
		$class = $block['attrs']['className'] ?? '';
		return is_string( $class ) ? preg_split( '/\s+/', trim( $class ) ) : array();
	}

	/** True when the block, or a block inside it, is an embed with a URL. */
	private function has_video( array $block ): bool {
		if ( 'core/embed' === ( $block['blockName'] ?? '' ) && ! empty( $block['attrs']['url'] ) ) {
			return true;
		}
		foreach ( $block['innerBlocks'] ?? array() as $inner ) {
			if ( is_array( $inner ) && $this->has_video( $inner ) ) {
				return true;
			}
		}
		return false;
	}

	/** @return array[] The pattern as parsed blocks, ready for serialize_blocks(). */
	private function blocks(): array {
		return array(
			$this->hero(),
			$this->videos(),
			$this->faq(),
			$this->capture(),
			$this->footer(),
		);
	}

	private function hero(): array {
		return $this->group(
			'lead-magnet-educator__hero',
			array(
				$this->heading( esc_html__( '[Your name], answering real retirement questions', 'xophz-compass-lead-magnet' ), 1 ),
				$this->paragraph( esc_html__( '[One sentence on what a visitor gets from this page. No rates, returns or guarantees.]', 'xophz-compass-lead-magnet' ) ),
			)
		);
	}

	/** Three empty YouTube embeds. An embed with no URL cannot hold a caption, so each caption is a paragraph beside it. */
	private function videos(): array {
		$columns = array();
		for ( $slot = 1; $slot <= self::SLOTS; $slot++ ) {
			$columns[] = $this->column(
				self::CLASS_VIDEO,
				array(
					$this->node( 'core/embed', array( 'providerNameSlug' => 'youtube', 'responsive' => true ), '' ),
					$this->paragraph(
						/* translators: %d: video slot number, 1 to 3 */
						esc_html( sprintf( __( '[Video %d caption: one plain sentence on the question this video answers]', 'xophz-compass-lead-magnet' ), $slot ) ),
						array(),
						true
					),
				)
			);
		}

		return $this->group(
			self::CLASS_VIDEOS,
			array(
				$this->heading( esc_html__( '[Video section heading, for example what these videos cover]', 'xophz-compass-lead-magnet' ) ),
				$this->node(
					'core/columns',
					array( 'align' => 'wide' ),
					array( '<div class="wp-block-columns alignwide">', '</div>' ),
					$columns
				),
			)
		);
	}

	/** Three FAQ items. The editor note is dropped from the public page. */
	private function faq(): array {
		$items = array(
			$this->heading( esc_html__( 'Questions people ask', 'xophz-compass-lead-magnet' ) ),
			$this->paragraph(
				esc_html__( '[Editor note, not shown on the published page: every answer below needs compliance review before this page goes live. Replace each bracketed question and answer with your own.]', 'xophz-compass-lead-magnet' ),
				array( self::CLASS_NOTE )
			),
		);

		for ( $item = 1; $item <= 3; $item++ ) {
			$items[] = $this->details(
				/* translators: %d: question number, 1 to 3 */
				esc_html( sprintf( __( '[Question %d: a question people really ask you]', 'xophz-compass-lead-magnet' ), $item ) ),
				/* translators: %d: question number, 1 to 3 */
				esc_html( sprintf( __( '[Answer %d: your answer in plain words, reviewed by compliance before publishing]', 'xophz-compass-lead-magnet' ), $item ) )
			);
		}

		return $this->group( 'lead-magnet-educator__faq', $items );
	}

	/** The capture form: the shortcode owns the fields and the consent wording. */
	private function capture(): array {
		$shortcode = '[' . Lead_Magnet_Form::SHORTCODE . ' offer="' . self::OFFER . '"]';

		return $this->group(
			'lead-magnet-educator__capture',
			array(
				$this->heading( esc_html__( '[Name the free resource visitors get, for example the annuity checklist]', 'xophz-compass-lead-magnet' ) ),
				$this->paragraph( esc_html__( '[One or two sentences on what happens after a visitor submits the form]', 'xophz-compass-lead-magnet' ) ),
				$this->node( 'core/shortcode', array(), $shortcode ),
			)
		);
	}

	private function footer(): array {
		return $this->group(
			'lead-magnet-educator__footer',
			array(
				$this->node( 'core/separator', array(), '<hr class="wp-block-separator has-alpha-channel-opacity"/>' ),
				$this->paragraph(
					esc_html__( '[Licensed insurance agent disclosure, license numbers and states, required by your compliance reviewer]', 'xophz-compass-lead-magnet' ),
					array( 'lead-magnet-educator__compliance' ),
					true
				),
			)
		);
	}

	/**
	 * One parsed block. Leaf blocks pass their markup as a string; blocks with children pass
	 * [ opening markup, closing markup ] and the child blocks. Markup sits on its own lines between
	 * the block comments, as the editor saves it. A leaf with no markup is saved self-closing.
	 *
	 * @param string          $name     Block name.
	 * @param array           $attrs    Block comment attributes.
	 * @param string|string[] $html     Leaf markup, or [ open, close ].
	 * @param array[]         $children Inner blocks.
	 */
	private function node( string $name, array $attrs, $html, array $children = array() ): array {
		if ( ! $children ) {
			$content = array( '' === $html ? '' : "\n" . $html . "\n" );
		} else {
			$content = array( "\n" . $html[0] );
			foreach ( array_keys( $children ) as $i ) {
				if ( $i ) {
					$content[] = "\n\n";
				}
				$content[] = null;
			}
			$content[] = $html[1] . "\n";
		}

		return array(
			'blockName'    => $name,
			'attrs'        => $attrs,
			'innerBlocks'  => $children,
			'innerHTML'    => implode( '', array_filter( $content, 'is_string' ) ),
			'innerContent' => $content,
		);
	}

	private function group( string $class, array $children ): array {
		return $this->node(
			'core/group',
			array(
				'className' => $class,
				'layout'    => array( 'type' => 'constrained' ),
			),
			array( '<div class="wp-block-group ' . $class . '">', '</div>' ),
			$children
		);
	}

	private function column( string $class, array $children ): array {
		return $this->node(
			'core/column',
			array( 'className' => $class ),
			array( '<div class="wp-block-column ' . $class . '">', '</div>' ),
			$children
		);
	}

	private function heading( string $text, int $level = 2 ): array {
		$attrs = 2 === $level ? array() : array( 'level' => $level );
		return $this->node( 'core/heading', $attrs, "<h{$level} class=\"wp-block-heading\">{$text}</h{$level}>" );
	}

	/**
	 * @param string   $text   Escaped text.
	 * @param string[] $custom Custom classes.
	 * @param bool     $small  Use the theme's small font size.
	 */
	private function paragraph( string $text, array $custom = array(), bool $small = false ): array {
		$attrs   = array();
		$classes = $custom;
		if ( $small ) {
			$attrs['fontSize'] = 'small';
			$classes[]         = 'has-small-font-size';
		}
		if ( $custom ) {
			$attrs['className'] = implode( ' ', $custom );
		}

		$tag = $classes ? '<p class="' . implode( ' ', $classes ) . '">' : '<p>';
		return $this->node( 'core/paragraph', $attrs, $tag . $text . '</p>' );
	}

	/** A core/details (FAQ) block: the summary is the question, the paragraph inside is the answer. */
	private function details( string $summary, string $answer ): array {
		return $this->node(
			'core/details',
			array(),
			array( '<details class="wp-block-details"><summary>' . $summary . '</summary>', '</details>' ),
			array( $this->paragraph( $answer ) )
		);
	}
}
