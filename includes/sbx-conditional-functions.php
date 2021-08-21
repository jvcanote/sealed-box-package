<?php
/**
 * Sealed Box Conditional Functions
 *
 * Functions for determining the current context.
 *
 * @package     SealedBox/Functions
 * @version     1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Is_sealedbox - Returns true if on a page which uses Sealed Box.
 *
 * @return bool|void
 */
function is_sealedbox() {
	return;
}

if ( ! function_exists( 'is_ajax' ) ) {

	/**
	 * Is_ajax - Returns true when the page is loaded via ajax.
	 *
	 * @return bool
	 */
	function is_ajax() {
		return function_exists( 'wp_doing_ajax' ) ? wp_doing_ajax() : defined( 'DOING_AJAX' );
	}
}

/**
 * Simple check for validating a URL, it must start with http:// or https://.
 * and pass FILTER_VALIDATE_URL validation.
 *
 * @param  string $url to check.
 * @return bool
 */
function sbx_is_valid_url( $url ) {

	// Must start with http:// or https://.
	if ( 0 !== strpos( $url, 'http://' ) && 0 !== strpos( $url, 'https://' ) ) {
		return false;
	}

	// Must pass validation.
	if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
		return false;
	}

	return true;
}

/**
 * Check if the home URL is https. If it is, we don't need to do things such as 'force ssl'.
 *
 * @since  1.0.0
 * @return bool
 */
function sbx_site_is_https() {
	return false !== strstr( get_option( 'home' ), 'https:' );
}

/**
 * Checks whether the content passed contains a specific short code.
 *
 * @param  string $tag Shortcode tag to check.
 * @return bool
 */
function sbx_post_content_has_shortcode( $tag = '' ) {
	global $post;

	return is_singular() && is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, $tag );
}

/**
 * Check if a CSV file is valid.
 *
 * @since 1.0.0
 * @param string $file       File name.
 * @param bool   $check_path If should check for the path.
 * @return bool
 */
function sbx_is_file_valid_csv( $file, $check_path = true ) {
	/**
	 * Filter check for CSV file path.
	 *
	 * @since 1.0.0
	 * @param bool $check_import_file_path If requires file path check. Defaults to true.
	 */
	$check_import_file_path = apply_filters( 'sealed_box_csv_importer_check_import_file_path', true );

	if ( $check_path && $check_import_file_path && false !== stripos( $file, '://' ) ) {
		return false;
	}

	/**
	 * Filter CSV valid file types.
	 *
	 * @since 1.0.0
	 * @param array $valid_filetypes List of valid file types.
	 */
	$valid_filetypes = apply_filters(
		'sealed_box_csv_import_valid_filetypes',
		array(
			'csv' => 'text/csv',
			'txt' => 'text/plain',
		)
	);

	$filetype = wp_check_filetype( $file, $valid_filetypes );

	if ( in_array( $filetype['type'], $valid_filetypes, true ) ) {
		return true;
	}

	return false;
}
