<?php
/**
 * Renderable Interface
 *
 * Contract for components that can be rendered to HTML output.
 * All components must implement this interface to ensure consistent
 * rendering behavior across the flyout system.
 *
 * @package     ArrayPress\RegisterFlyouts\Interfaces
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 * @since       1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterFlyouts\Interfaces;

/**
 * Interface Renderable
 *
 * Defines the contract for renderable components.
 *
 * @since 1.0.0
 */
interface Renderable {

	/**
	 * Render the component to HTML
	 *
	 * Generates the complete HTML output for the component based on its
	 * current configuration and state. Components should return escaped
	 * and properly formatted HTML ready for output.
	 *
	 * @since 1.0.0
	 *
	 * @return string Generated HTML output
	 */
	public function render(): string;

}