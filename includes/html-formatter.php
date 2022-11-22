<?php

require_once WPCF7_PLUGIN_DIR . '/includes/html-iterator.php';

class WPCF7_HTMLFormatter {

	const void_elements = array(
		'area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input',
		'keygen', 'link', 'meta', 'param', 'source', 'track', 'wbr',
	);

	private $input = '';

	public function __construct( string $input ) {
		$this->input = $input;
	}

	public function format() {
		$input = &$this->input;

		$iterator = new WPCF7_HTMLIterator( $input );

		$allblocks = '(?:table|thead|tfoot|caption|col|colgroup|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre|form|map|area|blockquote|address|math|style|p|h[1-6]|hr|fieldset|legend|section|article|aside|hgroup|header|footer|nav|figure|figcaption|details|menu|summary)';

		$elements = array();
		$output = '';

		foreach ( $iterator->iterate() as $chunk ) {
			$position = $chunk['position'];
			$type = $chunk['type'];
			$content = $chunk['content'];

			// Standardize newline characters to "\n".
			$content = str_replace( array( "\r\n", "\r" ), "\n", $content );

			// Pre tags shouldn't be touched by autop.
			if ( false !== array_search( 'pre', $elements ) ) {
				$output .= $content;
				continue;
			}

			if ( $type === WPCF7_HTMLIterator::text ) {
				$inline_ancestors = preg_grep(
					'/^' . $allblocks . '$/i',
					$elements,
					PREG_GREP_INVERT
				);

				if ( $inline_ancestors ) {
					$content = preg_replace( '/\n+/', '<br />', $content );
				} else {
				}

				$output .= $content;
			}

			if ( $type === WPCF7_HTMLIterator::opening_tag ) {
				// Replace newlines to whitespace.
				$content = preg_replace( '/\n+/', ' ', $content );

				preg_match( '/<(.+?)[\s\/>]/', $content, $matches );
				$tag_name = strtolower( $matches[1] );

				// Normalize void element.
				if ( in_array( $tag_name, WPCF7_HTMLFormatter::void_elements ) ) {
					$content = preg_replace( '/\s*\/?>/', ' />', $content );
				} else {
					array_unshift( $elements, $tag_name );
				}

				$output .= $content;
			}

			if ( $type === WPCF7_HTMLIterator::closing_tag ) {
				// Remove whitespaces.
				$content = preg_replace( '/\s+/', '', $content );

				preg_match( '/<\/(.+?)(?:\s|>)/', $content, $matches );
				$tag_name = strtolower( $matches[1] );
				$opening_tag_offset = array_search( $tag_name, $elements );

				if ( false !== $opening_tag_offset ) {
					$elements = array_slice( $elements, $opening_tag_offset + 1 );
					$output .= $content;
				}
			}

			if ( $type === WPCF7_HTMLIterator::comment ) {
				$output .= $content;
			}

		}

		return $output;
	}

}
