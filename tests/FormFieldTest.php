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

}
