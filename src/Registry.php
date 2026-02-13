<?php
/**
 * Flyout Registry
 *
 * Singleton registry for managing multiple flyout managers.
 *
 * @package     ArrayPress\RegisterFlyouts
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @since       2.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterFlyouts;

/**
 * Class Registry
 *
 * Stores all Manager instances by their prefix, allowing the REST API
 * to resolve flyout configurations on incoming requests.
 */
class Registry {

	/**
	 * Singleton instance.
	 *
	 * @var Registry|null
	 */
	private static ?Registry $instance = null;

	/**
	 * Registered manager instances.
	 *
	 * @var array<string, Manager>
	 */
	private array $managers = [];

	/**
	 * Get singleton instance.
	 *
	 * @return Registry
	 */
	public static function instance(): Registry {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Private constructor for singleton.
	 */
	private function __construct() {
	}

	/**
	 * Register a manager instance.
	 *
	 * @param string  $prefix  Unique prefix (e.g. 'sugarcart').
	 * @param Manager $manager Manager instance.
	 *
	 * @return void
	 */
	public static function register( string $prefix, Manager $manager ): void {
		self::instance()->managers[ $prefix ] = $manager;
	}

	/**
	 * Get a registered manager.
	 *
	 * @param string $prefix Manager prefix.
	 *
	 * @return Manager|null
	 */
	public function get( string $prefix ): ?Manager {
		return $this->managers[ $prefix ] ?? null;
	}

	/**
	 * Check if a manager is registered.
	 *
	 * @param string $prefix Manager prefix.
	 *
	 * @return bool
	 */
	public function has( string $prefix ): bool {
		return isset( $this->managers[ $prefix ] );
	}

	/**
	 * Get all registered managers.
	 *
	 * @return array<string, Manager>
	 */
	public function all(): array {
		return $this->managers;
	}

	/**
	 * Unregister a manager.
	 *
	 * @param string $prefix Manager prefix.
	 *
	 * @return bool
	 */
	public function unregister( string $prefix ): bool {
		if ( isset( $this->managers[ $prefix ] ) ) {
			unset( $this->managers[ $prefix ] );

			return true;
		}

		return false;
	}

}