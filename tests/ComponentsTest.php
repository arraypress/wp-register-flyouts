<?php
/**
 * Component data resolution tests.
 *
 * @package ArrayPress\RegisterFlyouts
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterFlyouts\Tests;

use ArrayPress\RegisterFlyouts\Components;
use PHPUnit\Framework\TestCase;

/**
 * How a flyout finds a field's value in whatever the `load` callback handed
 * back. It accepts several shapes, and the one it did not accept is the one
 * everybody uses: a plain object's property.
 */
final class ComponentsTest extends TestCase {

	/**
	 * A value is found on a plain object's property.
	 *
	 * Which is what a `load` callback usually returns — a row cast to an
	 * object, or a WP_Post, whose post_title is a property and not a getter.
	 * Without this the flyout opened with every field empty, and this
	 * library's own README example — `'load' => fn( $id ) => get_post( $id )`
	 * — could not have worked.
	 */
	public function test_a_value_is_read_from_a_property(): void {
		$row = (object) [ 'name' => 'Ada Lovelace', 'amount' => 0 ];

		$this->assertSame( 'Ada Lovelace', Components::resolve_value( 'name', $row ) );

		// Zero is a value.
		$this->assertSame( 0, Components::resolve_value( 'amount', $row ) );

		$this->assertNull( Components::resolve_value( 'nothing', $row ) );
	}

	/**
	 * A getter still wins over a property of the same name.
	 */
	public function test_a_getter_is_preferred_to_a_property(): void {
		$object = new class {
			public $name = 'from the property';

			public function get_name(): string {
				return 'from the getter';
			}
		};

		$this->assertSame( 'from the getter', Components::resolve_value( 'name', $object ) );
	}

	/**
	 * An array still resolves by key.
	 */
	public function test_an_array_resolves_by_key(): void {
		$this->assertSame( 'blue', Components::resolve_value( 'colour', [ 'colour' => 'blue' ] ) );
		$this->assertNull( Components::resolve_value( 'missing', [ 'colour' => 'blue' ] ) );
	}
	/**
	 * Every registered component names a class that exists.
	 *
	 * The map is a constant, so a renamed or deleted class is not an error
	 * until someone puts that component on a panel — at which point it is a
	 * fatal on their screen rather than a failure here.
	 */
	public function test_every_component_names_a_real_class(): void {
		$missing = [];

		foreach ( Components::all() as $type => $component ) {
			if ( ! class_exists( $component['class'] ) ) {
				$missing[] = sprintf( '%s => %s', $type, $component['class'] );
			}
		}

		$this->assertSame( [], $missing, 'Missing: ' . implode( ', ', $missing ) );
	}

	/**
	 * A component is told apart from a field.
	 *
	 * Which is the one question the whole registry exists to answer: a field
	 * goes through the kit's field set, a component draws itself.
	 */
	public function test_a_component_is_told_apart_from_a_field(): void {
		$this->assertTrue( Components::is_component( 'timeline' ) );
		$this->assertFalse( Components::is_component( 'text' ) );

		// Unregistered on purpose — it is a kit type now, reached through
		// FormField's aliases rather than a second implementation.
		$this->assertFalse( Components::is_component( 'gallery' ) );
		$this->assertFalse( Components::is_component( 'feature_list' ) );
	}

	/**
	 * A consumer can register one of their own.
	 */
	public function test_a_consumer_can_register_a_component(): void {
		Components::register(
			'demo_thing',
			[ 'class' => DemoComponent::class, 'data' => 'rows' ]
		);

		$this->assertTrue( Components::is_component( 'demo_thing' ) );
		$this->assertInstanceOf( DemoComponent::class, Components::create( 'demo_thing', [] ) );
	}

	/**
	 * A component that does not exist is refused at registration.
	 *
	 * Rather than at render, which is a fatal on somebody's screen.
	 */
	public function test_a_missing_class_is_refused(): void {
		$this->expectException( \InvalidArgumentException::class );

		Components::register( 'nope', [ 'class' => 'Not\\A\\Class' ] );
	}

	/**
	 * A single-key component takes whatever was at the field's key.
	 */
	public function test_a_single_key_component_takes_what_was_there(): void {
		$this->assertSame(
			[ 'items' => [ 'a', 'b' ] ],
			Components::resolve_data( 'timeline', 'history', (object) [ 'history' => [ 'a', 'b' ] ] )
		);
	}

	/**
	 * A whole configuration at the field's own key is used as it stands.
	 *
	 * Which is what a `{key}_data()` method returns: the component's
	 * configuration, assembled, rather than one column.
	 */
	public function test_a_whole_configuration_is_used_as_it_stands(): void {
		$row = (object) [ 'summary' => [ 'total' => 900, 'currency' => 'GBP' ] ];

		$this->assertSame(
			[ 'total' => 900, 'currency' => 'GBP' ],
			Components::resolve_data( 'price_summary', 'summary', $row )
		);
	}

	/**
	 * Otherwise each key is looked up in its own right.
	 *
	 * Which is what a plain database row looks like — one column per key.
	 */
	public function test_separate_keys_are_looked_up_separately(): void {
		$row = (object) [ 'total' => 900, 'currency' => 'GBP' ];

		$resolved = Components::resolve_data( 'price_summary', 'summary', $row );

		$this->assertSame( 900, $resolved['total'] );
		$this->assertSame( 'GBP', $resolved['currency'] );
		$this->assertNull( $resolved['tax'] );
	}

	/**
	 * A header needs the media frame only when it can be edited.
	 *
	 * The one asset that depends on configuration rather than type, so it is
	 * the one that would be quietly lost by a simpler lookup.
	 */
	public function test_a_header_loads_the_media_frame_only_when_editable(): void {
		$this->assertNull( Components::get_asset( 'header' ) );
		$this->assertSame( 'image-picker', Components::get_asset( 'header', [ 'editable' => true ] ) );
	}

	/**
	 * A component with no asset says so.
	 */
	public function test_a_component_without_an_asset_says_so(): void {
		$this->assertNull( Components::get_asset( 'info_grid' ) );
		$this->assertNull( Components::get_asset( 'not_a_component' ) );
	}
}

/**
 * A component to register, for the registration test.
 */
final class DemoComponent implements \ArrayPress\RegisterFlyouts\Renderable {

	/**
	 * Construct.
	 *
	 * @param array<string, mixed> $config Component configuration.
	 */
	public function __construct( array $config = [] ) {
	}

	/**
	 * Render.
	 *
	 * @return string
	 */
	public function render(): string {
		return '';
	}
}
