<?php
/**
 * What a field filter can actually change.
 *
 * @package ArrayPress\RegisterFlyouts
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterFlyouts\Tests;

use ArrayPress\RegisterFlyouts\Manager;
use PHPUnit\Framework\TestCase;

/**
 * A filter that changes a field has to change the field.
 *
 * The set the controls are rendered from used to be built before any of
 * these filters ran, from the untouched configuration. Only the label and
 * description came from the filtered array -- so a filter could restyle the
 * chrome around a control and never the control, silently.
 *
 * It looked correct from every angle except the page: the filter fired, the
 * array it returned held the change, and the rendered input did not.
 */
final class FieldFiltersTest extends TestCase {

	/**
	 * A clean hook registry between tests.
	 */
	protected function setUp(): void {
		$GLOBALS['fk_filters'] = [];
	}

	/**
	 * Render a one-field panel through the manager.
	 *
	 * @param array<string, mixed> $field The field.
	 *
	 * @return string
	 */
	private function render( array $field ): string {
		$manager = new Manager( 'probe' );

		$method = new \ReflectionMethod( $manager, 'render_fields' );

		return (string) $method->invoke( $manager, [ 'thing' => $field ], (object) [ 'thing' => '' ] );
	}

	/**
	 * The per-field filter reaches the control.
	 */
	public function test_a_field_filter_changes_the_control(): void {
		$GLOBALS['fk_filters']['wp_flyout_render_field_probe_thing'][] = static function ( array $field ): array {
			$field['placeholder'] = 'Changed by the filter';

			return $field;
		};

		$this->assertStringContainsString(
			'Changed by the filter',
			$this->render( [ 'type' => 'text', 'label' => 'Thing' ] )
		);
	}

	/**
	 * So does the broader one.
	 */
	public function test_the_every_field_filter_changes_the_control(): void {
		$GLOBALS['fk_filters']['wp_flyout_render_field_probe'][] = static function ( array $field ): array {
			$field['placeholder'] = 'Changed for every field';

			return $field;
		};

		$this->assertStringContainsString(
			'Changed for every field',
			$this->render( [ 'type' => 'text', 'label' => 'Thing' ] )
		);
	}

	/**
	 * And the one that runs over the whole set.
	 */
	public function test_the_before_render_filter_changes_the_control(): void {
		$GLOBALS['fk_filters']['wp_flyout_before_render_fields_probe'][] = static function ( array $fields ): array {
			$fields['thing']['placeholder'] = 'Changed before rendering';

			return $fields;
		};

		$this->assertStringContainsString(
			'Changed before rendering',
			$this->render( [ 'type' => 'text', 'label' => 'Thing' ] )
		);
	}

	/**
	 * A field nobody filtered still renders.
	 */
	public function test_an_unfiltered_field_is_untouched(): void {
		$markup = $this->render( [ 'type' => 'text', 'label' => 'Thing', 'placeholder' => 'Original' ] );

		$this->assertStringContainsString( 'Original', $markup );
	}
}
