<?php
/**
 * Tests for the numeric page list built by post_crafts_pagination().
 *
 * Acceptance criteria: tests/acceptance/pagination-php.md (PAGP-01 to PAGP-10).
 *
 * @package post-crafts
 */

// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOMDocument API property names.

namespace PostCrafts\Blocks\Tests;

/**
 * Numeric pagination markup.
 */
class Test_Pagination_Markup extends TestCase {

	/**
	 * Loads markup into an XPath object.
	 *
	 * @param string $html HTML fragment.
	 * @return \DOMXPath
	 */
	private function xpath( $html ) {
		$previous = libxml_use_internal_errors( true );
		$dom      = new \DOMDocument();
		$dom->loadHTML( '<?xml encoding="utf-8"?><div id="root">' . $html . '</div>' );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous );

		return new \DOMXPath( $dom );
	}

	/**
	 * Whether an element has a class.
	 *
	 * @param \DOMElement $element Element.
	 * @param string      $name    Class name.
	 * @return bool
	 */
	private function has_class( \DOMElement $element, $name ) {
		return in_array( $name, preg_split( '/\s+/', trim( $element->getAttribute( 'class' ) ) ), true );
	}

	/**
	 * The li items of the page list.
	 *
	 * @param int             $max_page     Max page.
	 * @param int             $current_page Current page.
	 * @param \DOMXPath|null  $xpath        Already parsed markup of the same list, so its nodes can be queried further.
	 * @return \DOMElement[]
	 */
	private function items( $max_page, $current_page, $xpath = null ) {
		$xpath = $xpath ? $xpath : $this->xpath( post_crafts_pagination( $max_page, $current_page ) );
		$items = array();

		foreach ( $xpath->query( '//ul[@class="pcrafts-pages"]/li' ) as $li ) {
			$items[] = $li;
		}

		return $items;
	}

	/**
	 * Visible items as tokens: page numbers, "[n]" for the current page, "...", "Prev", "Next".
	 *
	 * @param int $max_page     Max page.
	 * @param int $current_page Current page.
	 * @return string[]
	 */
	private function visible_tokens( $max_page, $current_page ) {
		$tokens = array();

		foreach ( $this->items( $max_page, $current_page ) as $li ) {
			if ( $this->has_class( $li, 'hide' ) ) {
				continue;
			}

			$text     = trim( $li->textContent );
			$tokens[] = $this->has_class( $li, 'current' ) ? '[' . $text . ']' : $text;
		}

		return $tokens;
	}

	/**
	 * No markup for a single page or less.
	 *
	 * @covers-ac PAGP-01
	 * @dataProvider data_single_page
	 *
	 * @param int $max_page Max page.
	 */
	public function test_returns_nothing_for_one_page_or_less( $max_page ) {
		$this->assertEmpty( post_crafts_pagination( $max_page, 1 ) );
	}

	/**
	 * Max page values that need no pagination.
	 *
	 * @return array
	 */
	public function data_single_page() {
		return array(
			'one'      => array( 1 ),
			'zero'     => array( 0 ),
			'negative' => array( -3 ),
			'string 1' => array( '1' ),
		);
	}

	/**
	 * Every clickable item is an li.page-numbers with data-page wrapping one button.
	 *
	 * @covers-ac PAGP-02
	 * @dataProvider data_page_sequences
	 *
	 * @param int $max_page     Max page.
	 * @param int $current_page Current page.
	 */
	public function test_items_wrap_buttons( $max_page, $current_page ) {
		$html  = post_crafts_pagination( $max_page, $current_page );
		$xpath = $this->xpath( $html );

		$this->assertSame( 1, $xpath->query( '//ul' )->length );
		$this->assertSame( 1, $xpath->query( '//div[@id="root"]/ul[@class="pcrafts-pages"]' )->length, 'The list is the only top-level element.' );
		$this->assertSame( 0, $xpath->query( '//ul/*[not(self::li)]' )->length );

		$items = $this->items( $max_page, $current_page, $xpath );
		$this->assertNotEmpty( $items );

		foreach ( $items as $li ) {
			if ( $this->has_class( $li, 'page-dots' ) ) {
				continue;
			}

			$this->assertTrue( $this->has_class( $li, 'page-numbers' ), 'Item class: ' . $li->getAttribute( 'class' ) );
			$this->assertTrue( $li->hasAttribute( 'data-page' ) );
			$this->assertMatchesRegularExpression( '/^\d+$/', $li->getAttribute( 'data-page' ) );

			$buttons = $xpath->query( './button', $li );
			$this->assertSame( 1, $buttons->length );
			$this->assertSame( 1, $li->childNodes->length, 'The button is the only child of the li.' );
			$this->assertSame( 'button', $buttons->item( 0 )->getAttribute( 'type' ) );
			$this->assertSame( 'pcrafts-page-btn', $buttons->item( 0 )->getAttribute( 'class' ) );
		}
	}

	/**
	 * Exactly one current item, whose button alone has aria-current="page".
	 *
	 * @covers-ac PAGP-03
	 * @dataProvider data_page_sequences
	 *
	 * @param int $max_page     Max page.
	 * @param int $current_page Current page.
	 */
	public function test_marks_current_page( $max_page, $current_page ) {
		$xpath   = $this->xpath( post_crafts_pagination( $max_page, $current_page ) );
		$current = $xpath->query( '//li[contains(concat(" ", normalize-space(@class), " "), " current ")]' );

		$this->assertSame( 1, $current->length );
		$this->assertStringContainsString( 'middle-pages', $current->item( 0 )->getAttribute( 'class' ) );
		$this->assertSame( (string) $current_page, $current->item( 0 )->getAttribute( 'data-page' ) );
		$this->assertSame( (string) $current_page, trim( $current->item( 0 )->textContent ) );

		$aria_current = $xpath->query( '//*[@aria-current]' );
		$this->assertSame( 1, $aria_current->length );
		$this->assertSame( 'button', $aria_current->item( 0 )->nodeName );
		$this->assertSame( 'page', $aria_current->item( 0 )->getAttribute( 'aria-current' ) );
		$this->assertSame( $current->item( 0 ), $aria_current->item( 0 )->parentNode );
	}

	/**
	 * Prev and Next sit at the ends, have aria-labels and hide on the first/last page.
	 *
	 * @covers-ac PAGP-04
	 * @dataProvider data_page_sequences
	 *
	 * @param int $max_page     Max page.
	 * @param int $current_page Current page.
	 */
	public function test_prev_and_next( $max_page, $current_page ) {
		$xpath = $this->xpath( post_crafts_pagination( $max_page, $current_page ) );
		$items = $this->items( $max_page, $current_page, $xpath );
		$prev  = $items[0];
		$next  = end( $items );

		$this->assertTrue( $this->has_class( $prev, 'prev' ) );
		$this->assertTrue( $this->has_class( $prev, 'page-numbers' ) );
		$this->assertSame( (string) ( $current_page - 1 ), $prev->getAttribute( 'data-page' ) );
		$this->assertSame( 'Previous page', $xpath->query( './button', $prev )->item( 0 )->getAttribute( 'aria-label' ) );
		$this->assertSame( 'Prev', trim( $prev->textContent ) );
		$this->assertSame( 1 === $current_page, $this->has_class( $prev, 'hide' ) );

		$this->assertTrue( $this->has_class( $next, 'next' ) );
		$this->assertTrue( $this->has_class( $next, 'page-numbers' ) );
		$this->assertSame( (string) ( $current_page + 1 ), $next->getAttribute( 'data-page' ) );
		$this->assertSame( 'Next page', $xpath->query( './button', $next )->item( 0 )->getAttribute( 'aria-label' ) );
		$this->assertSame( 'Next', trim( $next->textContent ) );
		$this->assertSame( $max_page === $current_page, $this->has_class( $next, 'hide' ) );
	}

	/**
	 * Dots are decorative: aria-hidden, no button, no data-page.
	 *
	 * @covers-ac PAGP-05
	 * @dataProvider data_page_sequences
	 *
	 * @param int $max_page     Max page.
	 * @param int $current_page Current page.
	 */
	public function test_dots_are_not_interactive( $max_page, $current_page ) {
		$xpath = $this->xpath( post_crafts_pagination( $max_page, $current_page ) );
		$dots  = $xpath->query( '//li[contains(@class, "page-dots")]' );

		$this->assertGreaterThan( 0, $dots->length );

		foreach ( $dots as $li ) {
			$this->assertSame( '...', $li->textContent );
			$this->assertSame( 'true', $li->getAttribute( 'aria-hidden' ) );
			$this->assertSame( 0, $xpath->query( './/button', $li )->length );
			$this->assertFalse( $li->hasAttribute( 'data-page' ) );
			$this->assertFalse( $this->has_class( $li, 'page-numbers' ) );
		}
	}

	/**
	 * The visible items for known (max, current) pairs.
	 *
	 * @covers-ac PAGP-06
	 * @dataProvider data_page_sequences
	 *
	 * @param int    $max_page     Max page.
	 * @param int    $current_page Current page.
	 * @param string $expected     Visible items, space separated.
	 */
	public function test_visible_page_sequence( $max_page, $current_page, $expected ) {
		$this->assertSame( $expected, implode( ' ', $this->visible_tokens( $max_page, $current_page ) ) );
	}

	/**
	 * Known page sequences.
	 *
	 * @return array
	 */
	public function data_page_sequences() {
		return array(
			'2/1' => array( 2, 1, '[1] 2 Next' ),
			'2/2' => array( 2, 2, 'Prev 1 [2]' ),
			'3/2' => array( 3, 2, 'Prev 1 [2] 3 Next' ),
			'3/3' => array( 3, 3, 'Prev 1 2 [3]' ),
			'4/1' => array( 4, 1, '[1] 2 3 4 Next' ),
			'4/3' => array( 4, 3, 'Prev 1 2 [3] 4 Next' ),
			'4/4' => array( 4, 4, 'Prev 1 2 3 [4]' ),
			'6/1' => array( 6, 1, '[1] 2 3 ... 6 Next' ),
			'6/3' => array( 6, 3, 'Prev 1 2 [3] 4 ... 6 Next' ),
			'6/4' => array( 6, 4, 'Prev 1 ... 3 [4] 5 6 Next' ),
			'6/6' => array( 6, 6, 'Prev 1 ... 4 5 [6]' ),
			'7/4' => array( 7, 4, 'Prev 1 ... 3 [4] 5 ... 7 Next' ),
		);
	}

	/**
	 * For every combination: numbers increase within range, first/last/current visible, dots exactly at gaps.
	 *
	 * @covers-ac PAGP-07
	 */
	public function test_every_combination_is_navigable() {
		for ( $max_page = 2; $max_page <= 8; $max_page++ ) {
			for ( $current_page = 1; $current_page <= $max_page; $current_page++ ) {
				$label   = "{$max_page}/{$current_page}";
				$tokens  = array_values( array_diff( $this->visible_tokens( $max_page, $current_page ), array( 'Prev', 'Next' ) ) );
				$numbers = array();

				foreach ( $tokens as $index => $token ) {
					if ( '...' === $token ) {
						$this->assertArrayHasKey( $index - 1, $tokens, "$label: dots can't come first." );
						$this->assertArrayHasKey( $index + 1, $tokens, "$label: dots can't come last." );

						$before = (int) trim( $tokens[ $index - 1 ], '[]' );
						$after  = (int) trim( $tokens[ $index + 1 ], '[]' );
						$this->assertGreaterThan( 1, $after - $before, "$label: dots without a skipped page." );
						continue;
					}

					$number = (int) trim( $token, '[]' );

					if ( $numbers ) {
						$previous_token = $tokens[ $index - 1 ];
						$gap            = $number - end( $numbers );

						$this->assertGreaterThan( 0, $gap, "$label: numbers must increase." );
						if ( '...' !== $previous_token ) {
							$this->assertSame( 1, $gap, "$label: a page is skipped without dots." );
						}
					}

					$this->assertGreaterThanOrEqual( 1, $number, $label );
					$this->assertLessThanOrEqual( $max_page, $number, $label );
					$numbers[] = $number;
				}

				$this->assertContains( 1, $numbers, "$label: page 1 must be reachable." );
				$this->assertContains( $max_page, $numbers, "$label: the last page must be reachable." );
				$this->assertContains( '[' . $current_page . ']', $tokens, "$label: the current page must be visible." );
			}
		}
	}

	/**
	 * Item order and class names that src/scripts/pagination.js relies on.
	 *
	 * @covers-ac PAGP-08
	 * @dataProvider data_structure
	 *
	 * @param int      $max_page Max page.
	 * @param string[] $expected Class marker of each li, in order.
	 */
	public function test_item_order_and_classes( $max_page, array $expected ) {
		$markers = array();

		foreach ( $this->items( $max_page, 1 ) as $li ) {
			foreach ( array( 'prev', 'first-page', 'first', 'middle-pages', 'last', 'last-page', 'next' ) as $marker ) {
				if ( $this->has_class( $li, $marker ) ) {
					$markers[] = $marker;
					break;
				}
			}
		}

		$this->assertSame( $expected, $markers );
	}

	/**
	 * Expected item order per max page ('first'/'last' are the dots).
	 *
	 * @return array
	 */
	public function data_structure() {
		$middle = array( 'middle-pages', 'middle-pages', 'middle-pages' );

		return array(
			'2 pages' => array( 2, array( 'prev', 'middle-pages', 'middle-pages', 'last', 'next' ) ),
			'3 pages' => array( 3, array_merge( array( 'prev' ), $middle, array( 'last', 'next' ) ) ),
			'4 pages' => array( 4, array_merge( array( 'prev', 'first-page', 'first' ), $middle, array( 'last', 'last-page', 'next' ) ) ),
			'9 pages' => array( 9, array_merge( array( 'prev', 'first-page', 'first' ), $middle, array( 'last', 'last-page', 'next' ) ) ),
		);
	}

	/**
	 * Out-of-range current pages are clamped.
	 *
	 * @covers-ac PAGP-09
	 * @dataProvider data_out_of_range
	 *
	 * @param int    $max_page     Max page.
	 * @param int    $current_page Current page.
	 * @param string $expected     Visible items, space separated.
	 */
	public function test_clamps_current_page( $max_page, $current_page, $expected ) {
		$this->assertSame( $expected, implode( ' ', $this->visible_tokens( $max_page, $current_page ) ) );

		foreach ( $this->items( $max_page, $current_page ) as $li ) {
			if ( ! $li->hasAttribute( 'data-page' ) || $this->has_class( $li, 'hide' ) ) {
				continue;
			}

			$page = (int) $li->getAttribute( 'data-page' );
			$this->assertGreaterThanOrEqual( 1, $page );
			$this->assertLessThanOrEqual( $max_page, $page );
		}
	}

	/**
	 * Out-of-range current pages.
	 *
	 * @return array
	 */
	public function data_out_of_range() {
		return array(
			'zero'           => array( 6, 0, '[1] 2 3 ... 6 Next' ),
			'negative'       => array( 3, -2, '[1] 2 3 Next' ),
			'past the end'   => array( 3, 5, 'Prev 1 2 [3]' ),
			'far past end'   => array( 6, 9, 'Prev 1 ... 4 5 [6]' ),
			'numeric string' => array( '6', '4', 'Prev 1 ... 3 [4] 5 6 Next' ),
		);
	}

	/**
	 * Prev/Next labels are translatable and escaped.
	 *
	 * @covers-ac PAGP-10
	 */
	public function test_labels_are_translated_and_escaped() {
		$translate = static function ( $translation, $text, $domain ) {
			if ( 'post-crafts' !== $domain ) {
				return $translation;
			}

			$map = array(
				'Prev'          => '<b>Zurück</b>',
				'Next'          => 'Weiter"><script>x</script>',
				'Previous page' => 'Vorherige "Seite"',
				'Next page'     => 'Nächste <Seite>',
			);

			return isset( $map[ $text ] ) ? $map[ $text ] : $translation;
		};
		add_filter( 'gettext', $translate, 10, 3 );

		$html = post_crafts_pagination( 3, 2 );

		remove_filter( 'gettext', $translate, 10 );

		$this->assertStringNotContainsString( '<b>', $html );
		$this->assertStringNotContainsString( '<script>', $html );
		$this->assertStringContainsString( '&lt;b&gt;Zurück&lt;/b&gt;', $html );
		$this->assertStringContainsString( 'aria-label="Vorherige &quot;Seite&quot;"', $html );
		$this->assertStringContainsString( 'aria-label="Nächste &lt;Seite&gt;"', $html );

		$items = $this->items( 3, 2 );
		$this->assertSame( 'Prev', trim( $items[0]->textContent ), 'The filter is removed again.' );

		$xpath = $this->xpath( $html );
		$this->assertSame( 6, $xpath->query( '//li' )->length, 'Translations cannot inject elements.' );
		$this->assertSame( 0, $xpath->query( '//script|//b' )->length );
	}
}
