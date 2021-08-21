<?php
/**
 * Sealed Box Core Functions
 *
 * General core functions available on both the front-end and admin.
 *
 * @package SealedBox\Functions
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

// Include core functions (available in both admin and frontend).
require SBX_ABSPATH . 'includes/sbx-conditional-functions.php';
require SBX_ABSPATH . 'includes/sbx-formatting-functions.php';
require SBX_ABSPATH . 'includes/sbx-service-functions.php';
require SBX_ABSPATH . 'includes/sbx-route-functions.php';
require SBX_ABSPATH . 'includes/sbx-term-functions.php';
require SBX_ABSPATH . 'includes/sbx-rest-functions.php';

/**
 * Get prefix for use with wp_cache_set. Allows all cache in a group to be invalidated at once.
 *
 * @param  string $group Group of cache to get.
 * @return string
 */
function sbx_get_cache_prefix( $group ) {
	// Get cache key - uses cache key sbx_orders_cache_prefix to invalidate when needed.
	$prefix = wp_cache_get( 'sbx_' . $group . '_cache_prefix', $group );

	if ( false === $prefix ) {
		$prefix = microtime();
		wp_cache_set( 'sbx_' . $group . '_cache_prefix', $prefix, $group );
	}

	return 'sbx_cache_' . $prefix . '_';
}

/**
 * Invalidate cache group.
 *
 * @param string $group Group of cache to clear.
 * @since 1.0.0
 */
function sbx_invalidate_cache_group( $group ) {
	wp_cache_set( 'sbx_' . $group . '_cache_prefix', microtime(), $group );
}

/**
 * Queue some JavaScript code to be output in the footer.
 *
 * @param string $code Code.
 */
function sbx_enqueue_js( $code ) {
	global $sbx_queued_js;

	if ( empty( $sbx_queued_js ) ) {
		$sbx_queued_js = '';
	}

	$sbx_queued_js .= "\n" . $code . "\n";
}

/**
 * Output any queued javascript code in the footer.
 */
function sbx_print_js() {
	global $sbx_queued_js;

	if ( ! empty( $sbx_queued_js ) ) {
		// Sanitize.
		$sbx_queued_js = wp_check_invalid_utf8( $sbx_queued_js );
		$sbx_queued_js = preg_replace( '/&#(x)?0*(?(1)27|39);?/i', "'", $sbx_queued_js );
		$sbx_queued_js = str_replace( "\r", '', $sbx_queued_js );

		$js = "<!-- Sealed Box JavaScript -->\n<script type=\"text/javascript\">\njQuery(function($) { $sbx_queued_js });\n</script>\n";

		/**
		 * Queued jsfilter.
		 *
		 * @since 1.0.0
		 * @param string $js JavaScript code.
		 */
		echo apply_filters( 'sealed_box_queued_js', $js ); // WPCS: XSS ok.

		unset( $sbx_queued_js );
	}
}

/**
 * Get user agent string.
 *
 * @since  1.0.0
 * @return string
 */
function sbx_get_user_agent() {
	return isset( $_SERVER['HTTP_USER_AGENT'] ) ? sbx_clean( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : ''; // @codingStandardsIgnoreLine
}

/**
 * Generate a rand hash.
 *
 * @since  1.0.0
 * @return string
 */
function sbx_rand_hash() {
	if ( ! function_exists( 'openssl_random_pseudo_bytes' ) ) {
		return sha1( wp_rand() );
	}

	return bin2hex( openssl_random_pseudo_bytes( 20 ) ); // @codingStandardsIgnoreLine
}

/**
 * WC API - Hash.
 *
 * @since  1.0.0
 * @param  string $data Message to be hashed.
 * @return string
 */
function sbx_api_hash( $data ) {
	return hash_hmac( 'sha256', $data, 'sbx-api' );
}

/**
 * Find all possible combinations of values from the input array and return in a logical order.
 *
 * @since 1.0.0
 * @param array $input Input.
 * @return array
 */
function sbx_array_cartesian( $input ) {
	$input   = array_filter( $input );
	$results = array();
	$indexes = array();
	$index   = 0;

	// Generate indexes from keys and values so we have a logical sort order.
	foreach ( $input as $key => $values ) {
		foreach ( $values as $value ) {
			$indexes[ $key ][ $value ] = $index++;
		}
	}

	// Loop over the 2D array of indexes and generate all combinations.
	foreach ( $indexes as $key => $values ) {
		// When result is empty, fill with the values of the first looped array.
		if ( empty( $results ) ) {
			foreach ( $values as $value ) {
				$results[] = array( $key => $value );
			}
		} else {
			// Second and subsequent input sub-array merging.
			foreach ( $results as $result_key => $result ) {
				foreach ( $values as $value ) {
					// If the key is not set, we can set it.
					if ( ! isset( $results[ $result_key ][ $key ] ) ) {
						$results[ $result_key ][ $key ] = $value;
					} else {
						// If the key is set, we can add a new combination to the results array.
						$new_combination         = $results[ $result_key ];
						$new_combination[ $key ] = $value;
						$results[]               = $new_combination;
					}
				}
			}
		}
	}

	// Sort the indexes.
	arsort( $results );

	// Convert indexes back to values.
	foreach ( $results as $result_key => $result ) {
		$converted_values = array();

		// Sort the values.
		arsort( $results[ $result_key ] );

		// Convert the values.
		foreach ( $results[ $result_key ] as $key => $value ) {
			$converted_values[ $key ] = array_search( $value, $indexes[ $key ], true );
		}

		$results[ $result_key ] = $converted_values;
	}

	return $results;
}

/**
 * Display a SealedBox help tip.
 *
 * @since  1.0.0
 * @param  string $tip        Help tip text.
 * @param  bool   $allow_html Allow sanitized HTML if true or escape.
 * @return string
 */
function sbx_help_tip( $tip, $allow_html = false ) {
	if ( $allow_html ) {
		$tip = sbx_sanitize_tooltip( $tip );
	} else {
		$tip = esc_attr( $tip );
	}

	return '<span class="sealed-box-help-tip tips" data-tip="' . $tip . '"></span>';
}

/**
 * User to sort two values with ausort.
 *
 * @since 1.0.0
 * @param int $a First value to compare.
 * @param int $b Second value to compare.
 * @return int
 */
function sbx_uasort_comparison( $a, $b ) {
	if ( $a === $b ) {
		return 0;
	}
	return ( $a < $b ) ? -1 : 1;
}

/**
 * Sort values based on ascii, usefull for special chars in strings.
 *
 * @param string $a First value.
 * @param string $b Second value.
 * @return int
 */
function sbx_ascii_uasort_comparison( $a, $b ) {
	if ( function_exists( 'iconv' ) && defined( 'ICONV_IMPL' ) && @strcasecmp( ICONV_IMPL, 'unknown' ) !== 0 ) {
		$a = @iconv( 'UTF-8', 'ASCII//TRANSLIT//IGNORE', $a );
		$b = @iconv( 'UTF-8', 'ASCII//TRANSLIT//IGNORE', $b );
	}
	return strcmp( $a, $b );
}

/**
 * Based on wp_list_pluck, this calls a method instead of returning a property.
 *
 * @since 1.0.0
 * @param array      $list              List of objects or arrays.
 * @param int|string $callback_or_field Callback method from the object to place instead of the entire object.
 * @param int|string $index_key         Optional. Field from the object to use as keys for the new array.
 *                                      Default null.
 * @return array Array of values.
 */
function sbx_list_pluck( $list, $callback_or_field, $index_key = null ) {
	// Use wp_list_pluck if this isn't a callback.
	$first_el = current( $list );
	if ( ! is_object( $first_el ) || ! is_callable( array( $first_el, $callback_or_field ) ) ) {
		return wp_list_pluck( $list, $callback_or_field, $index_key );
	}
	if ( ! $index_key ) {
		/*
		 * This is simple. Could at some point wrap array_column()
		 * if we knew we had an array of arrays.
		 */
		foreach ( $list as $key => $value ) {
			$list[ $key ] = $value->{$callback_or_field}();
		}
		return $list;
	}

	/*
	 * When index_key is not set for a particular item, push the value
	 * to the end of the stack. This is how array_column() behaves.
	 */
	$newlist = array();
	foreach ( $list as $value ) {
		// Get index. @since 3.2.0 this supports a call1.0.0
		if ( is_callable( array( $value, $index_key ) ) ) {
			$newlist[ $value->{$index_key}() ] = $value->{$callback_or_field}();
		} elseif ( isset( $value->$index_key ) ) {
			$newlist[ $value->$index_key ] = $value->{$callback_or_field}();
		} else {
			$newlist[] = $value->{$callback_or_field}();
		}
	}
	return $newlist;
}

/**
 * Get an item of post data if set, otherwise return a default value.
 *
 * @since  1.0.0
 * @param  string $key     Meta key.
 * @param  string $default Default value.
 * @return mixed Value sanitized by sbx_clean.
 */
function sbx_get_post_data_by_key( $key, $default = '' ) {
	return sbx_clean( wp_unslash( sbx_get_var( $_POST[ $key ], $default ) ) ); // @codingStandardsIgnoreLine
}

/**
 * Get data if set, otherwise return a default value or null. Prevents notices when data is not set.
 *
 * @since  1.0.0
 * @param  mixed  $var     Variable.
 * @param  string $default Default value.
 * @return mixed
 */
function sbx_get_var( &$var, $default = null ) {
	return isset( $var ) ? $var : $default;
}

/**
 * Make a URL relative, if possible.
 *
 * @since 1.0.0
 * @param string $url URL to make relative.
 * @return string
 */
function sbx_get_relative_url( $url ) {
	return sbx_is_external_resource( $url ) ? $url : str_replace( array( 'http://', 'https://' ), '//', $url );
}

/**
 * See if a resource is remote.
 *
 * @since 1.0.0
 * @param string $url URL to check.
 * @return bool
 */
function sbx_is_external_resource( $url ) {
	$wp_base = str_replace( array( 'http://', 'https://' ), '//', get_home_url( null, '/', 'http' ) );

	return strstr( $url, '://' ) && ! strstr( $url, $wp_base );
}

/**
 * Convert a decimal (e.g. 3.5) to a fraction (e.g. 7/2).
 * From: https://www.designedbyaturtle.co.uk/2015/converting-a-decimal-to-a-fraction-in-php/
 *
 * @param float $decimal the decimal number.
 * @return array|bool a 1/2 would be [1, 2] array (this can be imploded with '/' to form a string).
 */
function sbx_decimal_to_fraction( $decimal ) {
	if ( 0 > $decimal || ! is_numeric( $decimal ) ) {
		// Negative digits need to be passed in as positive numbers and prefixed as negative once the response is imploded.
		return false;
	}

	if ( 0 === $decimal ) {
		return array( 0, 1 );
	}

	$tolerance   = 1.e-4;
	$numerator   = 1;
	$h2          = 0;
	$denominator = 0;
	$k2          = 1;
	$b           = 1 / $decimal;

	do {
		$b           = 1 / $b;
		$a           = floor( $b );
		$aux         = $numerator;
		$numerator   = $a * $numerator + $h2;
		$h2          = $aux;
		$aux         = $denominator;
		$denominator = $a * $denominator + $k2;
		$k2          = $aux;
		$b           = $b - $a;
	} while ( abs( $decimal - $numerator / $denominator ) > $decimal * $tolerance );

	return array( $numerator, $denominator );
}

/**
 * Return the html selected attribute if stringified $value is found in array of stringified $options
 * or if stringified $value is the same as scalar stringified $options.
 *
 * @param string|int       $value   Value to find within options.
 * @param string|int|array $options Options to go through when looking for value.
 * @return string
 */
function sbx_selected( $value, $options ) {
	if ( is_array( $options ) ) {
		$options = array_map( 'strval', $options );
		return selected( in_array( (string) $value, $options, true ), true, false );
	}

	return selected( $value, $options, false );
}

function sealed_box_class( ...$parts ) {
	array_unshift( $parts, SEALED_BOX['prefix'] );
	return strtolower( sanitize_title_with_dashes( implode( '-', $parts ) ) );
}

function sealed_box_unslug( $parts ) {
	return sealed_box_unprefix( $parts, SEALED_BOX['prefix'] );
}

function sealed_box_unprefix( $parts, $prefix = SEALED_BOX['prefix'] ) {
	return trim( 0 === strpos( $parts, $prefix ) ? substr( $parts, strlen( $prefix ) ) : $parts, '-' );
}

function sealed_box_meta( ...$parts ) {
	$private = is_bool( current( $parts ) ) && true === array_shift( $parts ) ? '_' : '';
	array_unshift( $parts, SEALED_BOX['prefix'] );
	return $private . implode( '_', $parts );
}

/**
 * Parse arguments for each.
 *
 * Default args are applied to each item in the list.
 *
 * @param  array[]|object[]  $list      List of args.
 * @param  array|object      $dafaults  Default args applied to each item in the list.
 * @return array
 */
function sbx_parse_args_list( $list, $defaults ) {
	$list = (array) $list;
	$each = array();
	foreach ( $list as $key => $values ) {
		$each[ $key ] = $defaults;
		foreach ( $defaults as $name => $default ) {
			if ( array_key_exists( $name, $values ) ) {
				$each[ $key ][ $name ] = $values[ $name ];
			}
		}
	}
	return $each;
}