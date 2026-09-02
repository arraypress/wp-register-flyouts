<?php
/**
 * Component search tests.
 *
 * @package ArrayPress\RegisterFlyouts
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterFlyouts\Tests;

use ArrayPress\FieldKit\Search\Sources;
use ArrayPress\RegisterFlyouts\Components\LineItems;
use ArrayPress\RegisterFlyouts\Manager;
use PHPUnit\Framework\TestCase;

/**
 * This library used to ship a second way to search.
 *
 * Its own REST route, its own callback registry, its own copy of select2 —
 * seventy-three kilobytes of it — and its own picker markup, all so that the
 * one component with an embedded search could have one. The kit already had a
 * search endpoint and a combobox answering it, and the two had drifted: the
 * flyout's picker could not be cleared, did not create, and looked nothing
 * like the identical control on a settings page.
 *
 * So a component that searches now registers its callback as a kit source and
 * renders the kit's combobox against the kit's endpoint. What is asserted here
 * is that arrangement, and that the second one has not crept back.
 */
final class ComponentSearchTest extends TestCase {

	/**
	 * A search callback, standing in for a product catalogue.
	 *
	 * @param string     $term Search term.
	 * @param array|null $ids  Ids to resolve rather than search for.
	 *
	 * @return array<int, string>
	 */
	public static function catalogue( string $term, ?array $ids = null ): array {
		$products = [
			1 => 'Plugin licence',
			2 => 'Priority support',
		];

		if ( ! empty( $ids ) ) {
			return array_intersect_key( $products, array_flip( $ids ) );
		}

		return array_filter( $products, static fn( $one ) => false !== stripos( $one, $term ) );
	}

	/**
	 * Render a line-items component.
	 *
	 * @param array<string, mixed> $config Component configuration.
	 *
	 * @return string
	 */
	private function render( array $config = [] ): string {
		return ( new LineItems(
			array_merge(
				[
					'id'            => 'items',
					'search_key'    => 'product',
					'search_source' => 'demo-order-product',
				],
				$config
			)
		) )->render();
	}

	/**
	 * The product picker is the kit's combobox, pointed at the kit's endpoint.
	 *
	 * All three attributes, because the control is inert without any one of
	 * them: no endpoint and it searches nothing, no source and the endpoint
	 * refuses it, no nonce and the request is rejected.
	 */
	public function test_the_product_picker_is_the_kits_combobox(): void {
		$html = $this->render();

		$this->assertStringContainsString( 'field-kit__select--enhanced', $html );
		$this->assertStringContainsString( 'data-search-endpoint="https://example.test/wp-json/field-kit/v1/search"', $html );
		$this->assertStringContainsString( 'data-search-source="demo-order-product"', $html );
		$this->assertMatchesRegularExpression( '/data-search-nonce="[^"]+"/', $html );
	}

	/**
	 * A component with nothing to search against renders no picker.
	 *
	 * Rather than an enhanced select wired to an empty source, which is a
	 * search box that answers every term with nothing.
	 */
	public function test_no_search_key_renders_no_picker(): void {
		$html = $this->render( [ 'search_key' => '' ] );

		$this->assertStringNotContainsString( 'field-kit__select--enhanced', $html );
		$this->assertStringNotContainsString( 'line-items-selector', $html );
	}

	/**
	 * A component's callback is registered as a kit search source.
	 *
	 * Named for the manager, the flyout and the field, because two flyouts on
	 * one page each searching their own catalogue is the ordinary case and a
	 * shared name would give them each other's results.
	 */
	public function test_a_components_callback_becomes_a_kit_source(): void {
		$name = ( new Manager( 'shop' ) )->register_search_source(
			'order',
			'product',
			[
				'type'       => 'line_items',
				'search_key' => 'product',
				'callback'   => [ self::class, 'catalogue' ],
			]
		);

		$this->assertSame( 'shop-order-product', $name );
		$this->assertTrue( Sources::shared()->has( $name ) );

		// And it answers: registering a source that cannot be searched is the
		// failure this is meant to catch.
		$this->assertSame(
			[ [ 'id' => '1', 'text' => 'Plugin licence' ] ],
			Sources::shared()->get( $name )->search( 'licence', [], 1, 20 )['results']
		);
	}

	/**
	 * A component with no callback registers nothing and says so.
	 *
	 * The empty name is what the picker checks before rendering, so a
	 * component configured without a search does not get a dead one.
	 */
	public function test_no_callback_registers_nothing(): void {
		$manager = new Manager( 'shop' );

		$this->assertSame( '', $manager->register_search_source( 'order', 'product', [ 'type' => 'ajax_select' ] ) );
		$this->assertSame(
			'',
			$manager->register_search_source( 'order', 'product', [ 'type' => 'ajax_select', 'callback' => 'not_a_function' ] )
		);
	}

	/**
	 * An action's handler is not a search, and is not published as one.
	 *
	 * This library has spelled several things `callback`, and a field with an
	 * `action` uses it for the handler that runs when its button is pressed.
	 * Registering that as a search source would put it behind a GET endpoint
	 * anyone with `edit_posts` could call with any term — a refund handler
	 * reachable by typing in a search box.
	 */
	public function test_an_action_handler_is_not_registered_as_a_search(): void {
		$manager = new Manager( 'shop' );

		$this->assertSame(
			'',
			$manager->register_search_source(
				'order',
				'refund',
				[
					'type'     => 'refund_form',
					'action'   => 'process_refund',
					'callback' => [ self::class, 'catalogue' ],
				]
			)
		);

		$this->assertFalse( Sources::shared()->has( 'shop-order-refund' ) );
	}

	/**
	 * A field that says what its callback is for is taken at its word.
	 *
	 * `search_callback` is the kit's spelling and names what it is, so it
	 * counts on any field type rather than only the ones that search by
	 * definition.
	 */
	public function test_a_named_search_callback_counts_on_any_type(): void {
		$name = ( new Manager( 'shop' ) )->register_search_source(
			'order',
			'anything',
			[
				'type'            => 'something_bespoke',
				'search_callback' => [ self::class, 'catalogue' ],
			]
		);

		$this->assertSame( 'shop-order-anything', $name );
		$this->assertTrue( Sources::shared()->has( $name ) );
	}

	/**
	 * A source asks for what its flyout asks for.
	 *
	 * A search source is a REST endpoint returning rows from somebody's
	 * database. It defaulted to `edit_posts` whatever the panel demanded, so
	 * a flyout only a shop manager could open had a product search any
	 * author could query. The flyout's capability is the one thing already
	 * known to be right for it.
	 */
	public function test_a_source_defaults_to_the_flyouts_capability(): void {
		$manager = new Manager( 'shop_caps' );

		$manager->register_flyout(
			'order',
			[
				'title'      => 'Order',
				'capability' => 'manage_woocommerce',
				'fields'     => [
					'product' => [ 'type' => 'ajax_select', 'callback' => [ self::class, 'catalogue' ] ],
				],
			]
		);

		$this->assertSame( 'manage_woocommerce', Sources::shared()->get( 'shop_caps-order-product' )->capability() );
	}

	/**
	 * A capability can still be demanded of the source alone.
	 *
	 * And one registered for a flyout nobody has described yet asks for the
	 * library's own default rather than for nothing.
	 */
	public function test_a_source_is_capability_gated(): void {
		$manager = new Manager( 'shop' );

		$manager->register_search_source(
			'order',
			'default',
			[ 'type' => 'ajax_select', 'callback' => [ self::class, 'catalogue' ] ]
		);
		$this->assertSame( 'manage_options', Sources::shared()->get( 'shop-order-default' )->capability() );

		$manager->register_search_source(
			'order',
			'strict',
			[
				'type'              => 'ajax_select',
				'callback'          => [ self::class, 'catalogue' ],
				'search_capability' => 'manage_woocommerce',
			]
		);
		$this->assertSame( 'manage_woocommerce', Sources::shared()->get( 'shop-order-strict' )->capability() );
	}

	/**
	 * There is one search control in this library, not two.
	 *
	 * select2 came back once already, as a dependency of a component nobody
	 * had noticed still declared it. This fails the moment any of it returns —
	 * the vendored copy, an enqueue, a stylesheet rule, or a `.select2()` call
	 * in a component's own script.
	 *
	 * Comments are stripped first, deliberately: the notes explaining why it
	 * went are worth keeping, and a test that forbids writing the word is a
	 * test against documentation.
	 */
	public function test_the_library_carries_one_search_control(): void {
		$found = [];

		foreach ( [ 'src', 'assets' ] as $directory ) {
			$files = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator( dirname( __DIR__ ) . '/' . $directory )
			);

			foreach ( $files as $file ) {
				if ( ! $file->isFile() ) {
					continue;
				}

				$code = self::without_comments( $file->getPathname() );

				if ( false !== stripos( $code, 'select2' ) ) {
					$found[] = $directory . '/' . $file->getFilename();
				}
			}
		}

		$this->assertSame( [], $found, 'select2 is back: ' . implode( ', ', $found ) );
	}

	/**
	 * A file's code, without its comments.
	 *
	 * @param string $path Absolute path.
	 *
	 * @return string
	 */
	private static function without_comments( string $path ): string {
		$source = (string) file_get_contents( $path );

		if ( 'php' === pathinfo( $path, PATHINFO_EXTENSION ) ) {
			$code = '';

			foreach ( token_get_all( $source ) as $token ) {
				if ( is_array( $token ) && in_array( $token[0], [ T_COMMENT, T_DOC_COMMENT ], true ) ) {
					continue;
				}

				$code .= is_array( $token ) ? $token[1] : $token;
			}

			return $code;
		}

		// CSS and JS: block comments, and line comments in JS. Neither
		// language allows either inside a string that matters here.
		return (string) preg_replace( [ '{/\*.*?\*/}s', '{^\s*//.*$}m' ], '', $source );
	}
}
