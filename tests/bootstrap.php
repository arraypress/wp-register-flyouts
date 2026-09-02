<?php
/**
 * PHPUnit bootstrap.
 *
 * @package ArrayPress\RegisterFlyouts
 */

declare( strict_types=1 );

/*
 * Dependencies guard their files-autoloaded entrypoints with an ABSPATH
 * check. Composer runs those on require of the autoloader, so the constant
 * has to exist before it or their helpers are never declared.
 */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/*
 * Declared before the kit's stubs, whose `current_user_can` always says yes
 * and whose `is_wp_error` always says no. Fine for a field library; useless
 * for testing that a REST endpoint refuses somebody, which is the one thing
 * about it worth testing.
 *
 * $GLOBALS['wf_caps'] is the list of capabilities the current user has.
 * Null means all of them, which keeps every other test from having to care.
 */
if ( ! function_exists( 'current_user_can' ) ) {
	function current_user_can( $capability, ...$args ) {
		$allowed = $GLOBALS['wf_caps'] ?? null;

		return null === $allowed || in_array( $capability, (array) $allowed, true );
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ) {
		return $thing instanceof WP_Error;
	}
}

/*
 * The REST classes, to the extent the endpoints use them: a request is a bag
 * of parameters, a response carries data, and an error is a code, a message
 * and a status.
 */
if ( ! class_exists( 'WP_REST_Request' ) ) {
	class WP_REST_Request {

		/**
		 * @var array<string, mixed>
		 */
		private array $params = [];

		/**
		 * @param string $method The method.
		 * @param string $route  The route.
		 */
		public function __construct( string $method = 'POST', string $route = '' ) {
		}

		/**
		 * @param string $key   Parameter name.
		 * @param mixed  $value Parameter value.
		 *
		 * @return void
		 */
		public function set_param( string $key, $value ): void {
			$this->params[ $key ] = $value;
		}

		/**
		 * @param string $key Parameter name.
		 *
		 * @return mixed
		 */
		public function get_param( string $key ) {
			return $this->params[ $key ] ?? null;
		}

		/**
		 * @return array<string, mixed>
		 */
		public function get_params(): array {
			return $this->params;
		}

		/**
		 * The body, which in these tests is the parameters.
		 *
		 * @return array<string, mixed>
		 */
		public function get_json_params(): array {
			return $this->params;
		}
	}
}

if ( ! class_exists( 'WP_REST_Response' ) ) {
	class WP_REST_Response {

		/**
		 * @var mixed
		 */
		private $data;

		/**
		 * @param mixed $data   The payload.
		 * @param int   $status HTTP status.
		 */
		public function __construct( $data = null, int $status = 200 ) {
			$this->data = $data;
		}

		/**
		 * @return mixed
		 */
		public function get_data() {
			return $this->data;
		}
	}
}

if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {

		/**
		 * @var string
		 */
		private string $code;

		/**
		 * @var string
		 */
		private string $message;

		/**
		 * @var array<string, mixed>
		 */
		private array $data;

		/**
		 * @param string $code    Error code.
		 * @param string $message Error message.
		 * @param mixed  $data    Error data.
		 */
		public function __construct( string $code = '', string $message = '', $data = [] ) {
			$this->code    = $code;
			$this->message = $message;
			$this->data    = (array) $data;
		}

		/**
		 * @return string
		 */
		public function get_error_code(): string {
			return $this->code;
		}

		/**
		 * @return string
		 */
		public function get_error_message(): string {
			return $this->message;
		}

		/**
		 * @return array<string, mixed>
		 */
		public function get_error_data(): array {
			return $this->data;
		}
	}
}

/*
 * The kit's WordPress stubs, so the components that now render through it
 * have the escaping and sanitizing helpers they call. Required before the
 * autoloader for the same reason ABSPATH is.
 */
require_once dirname( __DIR__ ) . '/vendor/arraypress/wp-field-kit/tests/stubs.php';

/*
 * Filters, which the kit's stubs record but never run — this library's
 * sanitizer applies its own per-type ones and expects them back.
 */
if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( $hook, $value, ...$args ) {
		foreach ( $GLOBALS['fk_filters'][ $hook ] ?? [] as $callback ) {
			$value = $callback( $value, ...$args );
		}

		return $value;
	}
}

if ( ! function_exists( 'has_filter' ) ) {
	function has_filter( $hook, $callback = false ) {
		return ! empty( $GLOBALS['fk_filters'][ $hook ] );
	}
}

/*
 * The handful core provides that the kit's stubs have no reason to: the kit
 * renders fields, and these are what the *components* around them call.
 */
if ( ! function_exists( 'wp_parse_args' ) ) {
	function wp_parse_args( $args, $defaults = [] ) {
		return array_merge( $defaults, (array) $args );
	}
}

if ( ! function_exists( 'wp_generate_uuid4' ) ) {
	function wp_generate_uuid4() {
		// Not random — a test that renders twice wants the same markup twice,
		// and nothing here depends on uniqueness across a process.
		return '00000000-0000-4000-8000-000000000000';
	}
}

if ( ! function_exists( 'esc_url' ) ) {
	function esc_url( $url ) {
		return esc_url_raw( $url );
	}
}

/*
 * wp-composer-assets turns an on-disk asset path into a URL relative to the
 * content directory, so it needs both of these to exist. PaymentMethod asks
 * it for a card brand image while rendering.
 */
if ( ! defined( 'WP_CONTENT_DIR' ) ) {
	define( 'WP_CONTENT_DIR', dirname( __DIR__, 3 ) );
}

if ( ! function_exists( 'content_url' ) ) {
	function content_url( $path = '' ) {
		return 'https://example.test/wp-content' . ( '' === $path ? '' : '/' . ltrim( (string) $path, '/' ) );
	}
}

/*
 * wp-composer-assets resolves an asset's URL through this. PaymentMethod
 * asks it for a card brand image while rendering, so without it that
 * component is fatal here -- and it was, unnoticed, until a test finally
 * rendered it.
 */
if ( ! function_exists( 'wp_normalize_path' ) ) {
	function wp_normalize_path( $path ) {
		$path = str_replace( '\\', '/', (string) $path );
		$path = preg_replace( '|(?<=.)/+|', '/', $path );

		return ':' === substr( $path, 1, 1 ) ? ucfirst( $path ) : $path;
	}
}

if ( ! function_exists( 'get_bloginfo' ) ) {
	function get_bloginfo( $show = '', $filter = 'raw' ) {
		return 'language' === $show ? 'en-US' : '';
	}
}

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

/*
 * And wp-money's functions again. It is a Composer `files` entry, so it
 * already ran when the autoloader was required -- above, but also by phpunit
 * before this file -- and returned without declaring anything because ABSPATH
 * did not exist yet. `require`, not `require_once`: the path is already in the
 * included list, so require_once would do nothing at all.
 */
require dirname( __DIR__ ) . '/vendor/arraypress/wp-money/src/Functions.php';
