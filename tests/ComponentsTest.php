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
}
