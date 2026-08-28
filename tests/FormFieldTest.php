<?php
/**
 * Form field tests.
 *
 * @package ArrayPress\RegisterFlyouts
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterFlyouts\Tests;

use ArrayPress\RegisterFlyouts\Components\FormField;
use ArrayPress\RegisterFlyouts\Manager;
use PHPUnit\Framework\TestCase;

/**
 * This component was seventeen render methods rendering the same seventeen
 * types wp-field-kit already rendered — the third copy of that job in this
 * codebase, and the one that had drifted furthest.
 *
 * What is asserted here is the seam: the flyout's own configuration shape
 * still works, the flyout's own wrapper is still there for its stylesheet and
 * its conditional script, and what comes out is now the kit's markup, with
 * the labelling and associations that the hand-written renderer never had.
 */
final class FormFieldTest extends TestCase {

	/**
	 * Render one field.
	 *
	 * @param array<string, mixed> $config Field configuration.
	 *
	 * @return string
	 */
	private function render( array $config ): string {
		return ( new FormField( $config ) )->render();
	}

	/**
	 * The flyout's own wrapper survives.
	 *
	 * Its stylesheet keys on the class and its conditional script keys on the
	 * data attribute; the kit's wrapper sits inside it.
	 */
	public function test_the_flyout_wrapper_survives(): void {
		$html = $this->render(
			[
				'type'          => 'text',
				'name'          => 'colour',
				'label'         => 'Colour',
				'wrapper_class' => 'is-wide',
			]
		);

		$this->assertStringContainsString( 'class="wp-flyout-field field-type-text is-wide"', $html );
		$this->assertStringContainsString( 'field-kit__field', $html );
	}

	/**
	 * A wrapper attribute is carried through.
	 *
	 * data-depends holds JSON and is written with single quotes, which is why
	 * it is not simply escaped alongside the rest.
	 */
	public function test_wrapper_attributes_are_carried(): void {
		$html = $this->render(
			[
				'type'          => 'text',
				'name'          => 'colour',
				'wrapper_attrs' => [
					'id'           => 'colour-wrap',
					'data-depends' => '{"other":1}',
				],
			]
		);

		$this->assertStringContainsString( 'id="colour-wrap"', $html );
		$this->assertStringContainsString( "data-depends='", $html );
	}

	/**
	 * The field's name and id reach the control.
	 */
	public function test_the_name_and_id_reach_the_control(): void {
		$html = $this->render(
			[
				'type'  => 'text',
				'name'  => 'colour',
				'label' => 'Colour',
			]
		);

		$this->assertStringContainsString( 'name="colour"', $html );
		$this->assertStringContainsString( 'id="colour"', $html );
		$this->assertStringContainsString( '<label for="colour">Colour</label>', $html );
	}

	/**
	 * A description is associated, which the old renderer never did.
	 *
	 * It printed a <p class="description"> next to the control and nothing
	 * referenced it, so it was text on the screen and nothing to a screen
	 * reader working through the form.
	 */
	public function test_a_description_is_associated(): void {
		$html = $this->render(
			[
				'type'        => 'text',
				'name'        => 'colour',
				'label'       => 'Colour',
				'description' => 'Help text.',
			]
		);

		$this->assertStringContainsString( 'aria-describedby="colour__description"', $html );
		$this->assertStringContainsString( 'id="colour__description"', $html );
	}

	/**
	 * This library's own spellings still resolve.
	 */
	public function test_the_libraries_own_type_names_still_work(): void {
		$this->assertStringContainsString(
			'field-kit__relational',
			$this->render(
				[
					'type' => 'ajax_select',
					'name' => 'thing',
				]
			)
		);
	}

	/**
	 * A value resolved at render time still is.
	 *
	 * The kit has no notion of one — a field set reads from a context — so it
	 * is resolved before the field is built.
	 */
	public function test_a_data_callback_supplies_the_value(): void {
		$html = $this->render(
			[
				'type'          => 'text',
				'name'          => 'colour',
				'data_callback' => static fn() => 'from the callback',
			]
		);

		$this->assertStringContainsString( 'value="from the callback"', $html );
	}

	/**
	 * An unknown type renders nothing rather than a broken control.
	 */
	public function test_an_unknown_type_renders_nothing(): void {
		$this->assertSame( '', $this->render( [ 'type' => 'not_a_type' ] ) );
	}

	/**
	 * Every type the kit knows is available, not seventeen.
	 */
	public function test_every_kit_type_is_available(): void {
		$types = FormField::types();

		$this->assertGreaterThan( 50, count( $types ) );

		// The ones the old renderer had, still there.
		foreach ( [ 'text', 'select', 'toggle', 'radio', 'tags', 'color', 'group', 'separator', 'ajax_select' ] as $type ) {
			$this->assertContains( $type, $types );
		}

		// And the ones it did not.
		foreach ( [ 'repeater', 'gallery', 'email_editor', 'select2', 'user' ] as $type ) {
			$this->assertContains( $type, $types );
		}
	}

	/**
	 * Sanitizing goes through the same type that rendered.
	 *
	 * A separate sanitizer table drifts from the renderer — a value drawn one
	 * way and cleaned another — which is what this library had: seven hundred
	 * lines deciding for a second time what a number or a checkbox is. It is
	 * the field set's job now, so the coercion a field gets here is the one
	 * the same field gets on a settings page.
	 */
	public function test_sanitizing_goes_through_the_kit(): void {
		$manager = new Manager( 'sanitize_test' );

		$values = $manager->sanitize(
			[
				'count'  => [ 'type' => 'number', 'name' => 'count', 'max' => 10 ],
				'choice' => [
					'type'    => 'select',
					'name'    => 'choice',
					'options' => [ 'a' => 'A' ],
				],
			],
			[
				'count'  => '9999',
				'choice' => 'not an option',
			]
		);

		// Clamped by the field's own max.
		$this->assertSame( 10, $values['count'] );

		// A select's options are its allow-list, which a separate table had
		// no way of knowing. Nothing survives it, so the field is reported as
		// cleared rather than as holding an empty string.
		$this->assertArrayHasKey( 'choice', $values );
		$this->assertNull( $values['choice'] );
	}

	/**
	 * A key the flyout does not declare is dropped.
	 *
	 * Which is what stops a crafted submission being more powerful than the
	 * panel it pretends to come from.
	 */
	public function test_an_undeclared_key_is_dropped(): void {
		$manager = new Manager( 'sanitize_test' );

		$values = $manager->sanitize(
			[ 'name' => [ 'type' => 'text', 'name' => 'name' ] ],
			[ 'name' => 'Ada', 'role' => 'administrator' ]
		);

		$this->assertArrayHasKey( 'name', $values );
		$this->assertArrayNotHasKey( 'role', $values );
	}

	/**
	 * A custom sanitizer still wins.
	 */
	public function test_a_custom_sanitizer_still_wins(): void {
		$manager = new Manager( 'sanitize_test' );

		$values = $manager->sanitize(
			[
				'count' => [
					'type'              => 'number',
					'name'              => 'count',
					'sanitize_callback' => static fn() => 'overridden',
				],
			],
			[ 'count' => 'anything' ]
		);

		$this->assertSame( 'overridden', $values['count'] );
	}

	/**
	 * A field's own `value` becomes the kit's default.
	 *
	 * This library has always let a field carry a literal value; the kit has
	 * no notion of one — a field set reads from a context and falls back to a
	 * default. Without the translation every field written that way rendered
	 * empty, which is what the demo's own coupon code and weight did, in a
	 * panel whose whole job was to show them.
	 */
	public function test_a_literal_value_becomes_the_default(): void {
		$kit = FormField::to_kit( [ 'type' => 'text', 'value' => 'SUMMER-2026' ] );

		$this->assertSame( 'SUMMER-2026', $kit['default'] );
		$this->assertArrayNotHasKey( 'value', $kit );
	}

	/**
	 * An explicit default wins, and an empty value is not one.
	 */
	public function test_an_explicit_default_wins(): void {
		$kit = FormField::to_kit( [ 'type' => 'text', 'value' => 'v', 'default' => 'd' ] );
		$this->assertSame( 'd', $kit['default'] );

		$kit = FormField::to_kit( [ 'type' => 'text', 'value' => '' ] );
		$this->assertArrayNotHasKey( 'default', $kit );
	}

	/**
	 * A price configuration becomes a group of kit fields.
	 *
	 * It was a component: 292 lines of PHP, a stylesheet with a hand-rolled
	 * chevron SVG, and a script to show one control when another changed —
	 * which is what `depends` does. The submitted shape is unchanged, because
	 * a group stores its children under one key, which is what the component
	 * already did.
	 */
	/**
	 * The kit is handed what it reads and nothing else.
	 *
	 * It reports configuration nothing consumes under WP_DEBUG, once per
	 * field per render -- so a key this library owns and forgets to keep to
	 * itself is a notice on every panel, and enough of them bury the real
	 * one.
	 */
	public function test_a_field_does_not_hand_the_kit_its_own_keys(): void {
		$component = new FormField(
			[
				'type'          => 'text',
				'name'          => 'title',
				'label'         => 'Title',
				'value'         => 'Kit',
				'wrapper_class' => 'mine',
			]
		);

		// Asked of the kit field itself rather than of a _doing_it_wrong
		// stub: the warning only fires under WP_DEBUG, so a test that watched
		// for the notice would pass whether or not the keys were stripped.
		$field = ( new \ReflectionMethod( FormField::class, 'field' ) )->invoke( $component );

		$this->assertNotNull( $field );
		$this->assertSame( [], $field->unknown_keys() );

		// And the two that were translated rather than dropped still arrive.
		$html = $component->render();

		$this->assertStringContainsString( 'name="title"', $html );
		$this->assertStringContainsString( 'value="Kit"', $html );
	}

	public function test_a_price_configuration_becomes_a_group(): void {
		$kit = FormField::to_kit(
			[
				'type'       => 'price_config',
				'name'       => 'price',
				'amount'     => 1999,
				'currency'   => 'GBP',
				'currencies' => [ 'GBP', 'USD' ],
			]
		);

		$this->assertSame( 'group', $kit['type'] );
		$this->assertSame( 'price', $kit['name'] );
		$this->assertSame(
			[ 'amount', 'recurring_interval', 'recurring_interval_count' ],
			array_keys( $kit['fields'] )
		);

		$amount = $kit['fields']['amount'];

		$this->assertSame( 'amount_type', $amount['type'] );
		$this->assertSame( 1999, $amount['default'] );
		$this->assertSame( 'currency', $amount['type_meta_key'] );
		$this->assertSame( [ 'GBP' => 'GBP', 'USD' => 'USD' ], $amount['type_options'] );
	}

	/**
	 * A discount configuration likewise, and its months field depends.
	 *
	 * A script used to show and hide that field by setting an inline style,
	 * which left it in the tab order and readable by a screen reader while
	 * invisible. The kit's conditions do it properly.
	 */
	public function test_a_discount_configuration_becomes_a_group(): void {
		$kit = FormField::to_kit(
			[
				'type'             => 'discount_config',
				'name'             => 'discount',
				'amount'           => 10,
				'rate_type'        => 'percent',
				'currency_symbol'  => '£',
				'show_redemptions' => true,
			]
		);

		$this->assertSame( 'group', $kit['type'] );
		$this->assertSame(
			[ 'amount', 'duration', 'duration_in_months', 'max_redemptions' ],
			array_keys( $kit['fields'] )
		);

		$this->assertSame( 'rate_type', $kit['fields']['amount']['type_meta_key'] );
		$this->assertSame( [ 'percent' => '%', 'fixed' => '£' ], $kit['fields']['amount']['type_options'] );
		$this->assertSame( [ 'duration' => 'repeating' ], $kit['fields']['duration_in_months']['depends'] );
	}

	/**
	 * Redemptions are not shown unless asked for.
	 */
	public function test_redemptions_are_opt_in(): void {
		$kit = FormField::to_kit( [ 'type' => 'discount_config', 'name' => 'discount' ] );

		$this->assertArrayNotHasKey( 'max_redemptions', $kit['fields'] );
	}

	/**
	 * A unit input is the kit's amount type, in the kit's spelling.
	 */
	public function test_a_unit_input_becomes_an_amount(): void {
		$kit = FormField::to_kit(
			[
				'type'       => 'unit_input',
				'name'       => 'weight',
				'unit_value' => 'kg',
				'units'      => [ 'kg' => 'kg', 'lb' => 'lb' ],
			]
		);

		$this->assertSame( 'amount_type', $kit['type'] );
		$this->assertSame( [ 'kg' => 'kg', 'lb' => 'lb' ], $kit['type_options'] );
		$this->assertSame( 'kg', $kit['current_type'] );

		// And the old spellings do not travel on as keys nothing reads.
		$this->assertArrayNotHasKey( 'units', $kit );
		$this->assertArrayNotHasKey( 'unit_value', $kit );
	}

	/**
	 * One unit is a fixed unit, not a select of one option.
	 *
	 * A dropdown you cannot change is a control that looks like a decision
	 * and is not one.
	 */
	public function test_a_single_unit_is_fixed_rather_than_a_select(): void {
		$kit = FormField::to_kit(
			[ 'type' => 'unit_input', 'name' => 'weight', 'units' => [ 'kg' => 'kg' ] ]
		);

		$this->assertSame( 'kg', $kit['unit'] );
		$this->assertArrayNotHasKey( 'type_options', $kit );
	}
}