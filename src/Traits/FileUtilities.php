<?php
/**
 * File Utilities Trait
 *
 * Provides common file handling utilities for components that work with files.
 * Includes extension detection, icon mapping, and file type categorization.
 *
 * @package     ArrayPress\RegisterFlyouts\Traits
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 * @since       1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterFlyouts\Traits;

/**
 * Trait FileUtilities
 *
 * Common file handling methods for components.
 *
 * @since 1.0.0
 */
trait FileUtilities {

	/**
	 * File extension to dashicon mappings
	 *
	 * Maps file extensions to appropriate WordPress dashicon names
	 * for visual representation in the UI.
	 *
	 * @since 1.0.0
	 * @var array<string, string>
	 */
	private static array $FILE_ICONS = [
		// Documents
		'pdf'  => 'pdf',
		'doc'  => 'media-document',
		'docx' => 'media-document',
		'txt'  => 'media-text',

		// Images
		'jpg'  => 'format-image',
		'jpeg' => 'format-image',
		'png'  => 'format-image',
		'gif'  => 'format-image',
		'svg'  => 'format-image',
		'webp' => 'format-image',

		// Media
		'mp3'  => 'format-audio',
		'wav'  => 'format-audio',
		'ogg'  => 'format-audio',
		'mp4'  => 'format-video',
		'mov'  => 'format-video',
		'avi'  => 'format-video',
		'webm' => 'format-video',

		// Archives
		'zip'  => 'media-archive',
		'rar'  => 'media-archive',
		'7z'   => 'media-archive',
		'tar'  => 'media-archive',
		'gz'   => 'media-archive',

		// Code
		'js'   => 'media-code',
		'css'  => 'media-code',
		'php'  => 'media-code',
		'html' => 'media-code',
		'json' => 'media-code',
		'xml'  => 'media-code',

		// Spreadsheets
		'xls'  => 'media-spreadsheet',
		'xlsx' => 'media-spreadsheet',
		'csv'  => 'media-spreadsheet',
	];

	/**
	 * Get file extension from URL
	 *
	 * Extracts the file extension from a URL string, handling query strings
	 * and URL fragments appropriately.
	 *
	 * @param string $url File URL to parse
	 *
	 * @return string Lowercase file extension without dot, or empty string if none
	 * @since 1.0.0
	 *
	 */
	protected function get_file_extension( string $url ): string {
		if ( empty( $url ) ) {
			return '';
		}

		$path = parse_url( $url, PHP_URL_PATH );
		if ( ! $path ) {
			return '';
		}

		$extension = pathinfo( $path, PATHINFO_EXTENSION );

		return strtolower( $extension );
	}

	/**
	 * Get appropriate dashicon for file type
	 *
	 * Returns the WordPress dashicon name (without 'dashicons-' prefix)
	 * appropriate for the given file extension.
	 *
	 * @param string $extension File extension to get icon for
	 *
	 * @return string Dashicon name, defaults to 'media-default' if unknown
	 * @since 1.0.0
	 *
	 */
	protected function get_file_icon( string $extension ): string {
		return self::$FILE_ICONS[ $extension ] ?? 'media-default';
	}

	/**
	 * Get file icon from URL
	 *
	 * Convenience method that extracts extension from URL and returns
	 * the appropriate dashicon name.
	 *
	 * @param string $url File URL
	 *
	 * @return string Dashicon name
	 * @since 1.0.0
	 *
	 */
	protected function get_file_icon_from_url( string $url ): string {
		$extension = $this->get_file_extension( $url );

		return $this->get_file_icon( $extension );
	}

	/**
	 * Check if file type is an image
	 *
	 * @param string $extension File extension to check
	 *
	 * @return bool True if image file type
	 * @since 1.0.0
	 *
	 */
	protected function is_image_file( string $extension ): bool {
		$image_extensions = [ 'jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'bmp', 'ico' ];

		return in_array( strtolower( $extension ), $image_extensions, true );
	}

	/**
	 * Get all supported file icons
	 *
	 * Returns the complete array of file extension to icon mappings.
	 *
	 * @return array<string, string> Extension to icon mappings
	 * @since 1.0.0
	 *
	 */
	protected function get_all_file_icons(): array {
		return self::$FILE_ICONS;
	}

}