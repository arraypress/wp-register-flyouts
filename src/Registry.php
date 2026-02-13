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

	/**
	 * Parse a compound flyout ID into prefix and flyout_id.
	 *
	 * Splits 'prefix_flyout_name' into ['prefix' => 'prefix', 'flyout_id' => 'flyout_name'].
	 *
	 * @param string $id Full flyout identifier.
	 *
	 * @return array{prefix: string, flyout_id: string}|null Parsed components or null if invalid.
	 */
	public static function parse_id( string $id ): ?array {
		$pos = strpos( $id, '_' );

		if ( $pos === false || $pos === 0 ) {
			return null;
		}

		return [
			'prefix'    => substr( $id, 0, $pos ),
			'flyout_id' => substr( $id, $pos + 1 ),
		];
	}

	/**
	 * Resolve a compound flyout ID to a Manager instance and flyout_id.
	 *
	 * Creates the Manager if it doesn't exist yet.
	 *
	 * @param string $id     Full flyout identifier (prefix_flyout_name).
	 * @param bool   $create Whether to create the Manager if not found.
	 *
	 * @return array{manager: Manager, flyout_id: string}|null Resolved components or null.
	 */
	public static function resolve( string $id, bool $create = false ): ?array {
		$parts = self::parse_id( $id );

		if ( ! $parts ) {
			return null;
		}

		$manager = self::instance()->get( $parts['prefix'] );

		if ( ! $manager && $create ) {
			$manager = new Manager( $parts['prefix'] );
		}

		if ( ! $manager ) {
			return null;
		}

		return [
			'manager'   => $manager,
			'flyout_id' => $parts['flyout_id'],
		];
	}

}