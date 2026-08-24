<?php
/**
 * Stylesheet tests.
 *
 * @package ArrayPress\RegisterFlyouts
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterFlyouts\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Two things a stylesheet can get wrong silently.
 *
 * CSS has no errors. A rule naming a custom property nobody defined does not
 * warn — the declaration is simply dropped, so the element renders with
 * whatever it inherited and looks almost right. Renaming one variable and
 * missing a caller is how a delete button ends up with no colour at all.
 *
 * And a colour written as a hex where core publishes a variable is a panel
 * that stays blue when the user picks Midnight.
 */
final class StylesheetTest extends TestCase {

	/**
	 * Every stylesheet this library ships.
	 *
	 * @return array<string, array{0: string}>
	 */
	public static function stylesheetProvider(): array {
		$files = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( dirname( __DIR__ ) . '/assets/css' )
		);

		$cases = [];

		foreach ( $files as $file ) {
			if ( $file->isFile() && 'css' === $file->getExtension() ) {
				$cases[ $file->getFilename() ] = [ $file->getPathname() ];
			}
		}

		ksort( $cases );

		return $cases;
	}

	/**
	 * Every custom property a rule names is defined somewhere.
	 *
	 * @dataProvider stylesheetProvider
	 *
	 * @param string $path The stylesheet.
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider( 'stylesheetProvider' )]
	public function test_every_variable_used_is_defined( string $path ): void {
		$used = [];

		preg_match_all( '/var\(\s*(--wp-flyout-[a-z0-9-]+)/i', (string) file_get_contents( $path ), $matches );

		$used = array_unique( $matches[1] );

		$undefined = array_values( array_diff( $used, self::defined() ) );

		$this->assertSame(
			[],
			$undefined,
			sprintf( '%s names %s, which nothing defines.', basename( $path ), implode( ', ', $undefined ) )
		);
	}

	/**
	 * The accent follows the admin colour scheme.
	 *
	 * Core publishes --wp-admin-theme-color and recolours it per scheme, so a
	 * panel hardcoding #2271b1 is the one thing on the page that does not
	 * change when someone picks Midnight or Ectoplasm.
	 *
	 * @dataProvider stylesheetProvider
	 *
	 * @param string $path The stylesheet.
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider( 'stylesheetProvider' )]
	public function test_no_stylesheet_hardcodes_cores_blue( string $path ): void {
		$css = strtolower( (string) file_get_contents( $path ) );

		// The fallbacks in the variable definitions themselves are the point
		// of a fallback, so the file that defines them is exempt from this
		// one — it is checked by eye and by the test above.
		if ( str_contains( $css, '--wp-flyout-primary:' ) ) {
			$this->assertStringContainsString( '--wp-admin-theme-color', $css );

			return;
		}

		foreach ( [ '#2271b1', '#135e96', '#0a4b78', '#3858e9' ] as $blue ) {
			$this->assertStringNotContainsString(
				$blue,
				$css,
				sprintf( '%s hardcodes %s rather than following the admin colour scheme.', basename( $path ), $blue )
			);
		}
	}

	/**
	 * No stylesheet invents a button core does not have.
	 *
	 * Core has two weights and one destructive treatment. `button-danger`,
	 * `button-success` and `button-warning` were hardcoded red, green and
	 * amber fills — not core's notice palette, and blind to the admin colour
	 * scheme. The kit draws buttons now.
	 *
	 * @dataProvider stylesheetProvider
	 *
	 * @param string $path The stylesheet.
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider( 'stylesheetProvider' )]
	public function test_no_stylesheet_invents_a_button_variant( string $path ): void {
		$css = (string) file_get_contents( $path );

		foreach ( [ 'button-danger', 'button-success', 'button-warning' ] as $invented ) {
			$this->assertDoesNotMatchRegularExpression(
				sprintf( '/\.%s\b/', preg_quote( $invented, '/' ) ),
				$css,
				sprintf( '%s styles .%s, which core does not have.', basename( $path ), $invented )
			);
		}
	}

	/**
	 * Every custom property defined anywhere in the library.
	 *
	 * @return string[]
	 */
	private static function defined(): array {
		static $names = null;

		if ( null !== $names ) {
			return $names;
		}

		$names = [];

		foreach ( self::stylesheetProvider() as [ $path ] ) {
			preg_match_all( '/(--wp-flyout-[a-z0-9-]+)\s*:/i', (string) file_get_contents( $path ), $matches );

			$names = array_merge( $names, $matches[1] );
		}

		$names = array_values( array_unique( $names ) );

		return $names;
	}
}
