<?php
/**
 * Sealed Box Formatting
 *
 * Functions for formatting data.
 *
 * @package SealedBox/Functions
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Prefix a string.
 *
 * Add a prefix for general use in the plugin.
 *
 * @since  1.0.0
 * @param  string $string
 * @param  string $prefix Default: 'sbx'
 * @param  string $glue   Default: '_'
 * @return string
 */
function sbx_prefix( $string, $prefix = SEALED_BOX['prefix'], $glue = '_' ) {
	return 0 === strpos( $string, $prefix . $glue ) ? $string : $prefix . $glue . ltrim( $string, "{$glue}-_ \t\n\r\0\x0B" );
}

/**
 * Get the nicename.
 *
 * Remove prefix, dashes and underscores.
 *
 * @since  1.0.0
 * @param  string $string
 * @param  string $prefix Default: SEALED_BOX['prefix']
 * @return string
 */
function sbx_unprefix( $string, $prefix = SEALED_BOX['prefix'] ) {
	return ltrim( 0 === strpos( $string, $prefix ) ? substr( $string, strlen( $prefix ) ) : $string, "-_ \t\n\r\0\x0B" );
}

/**
 * Get term nicename.
 *
 * Sealed Box term slugs are prefixed with the taxonomy name.
 * Returns the term slug with the prefixed taxonomy name removed.
 *
 * @since  1.0.0
 * @param array|object|WP_Term $term
 * @return string
 */
function sbx_get_term_nicename( $term ) {
	// array accessible
	$term = wp_parse_args( $term, array( 'slug' => '', 'taxonomy' => '' ) );
	return sbx_get_term_slug( $term['slug'], $term['taxonomy'] );
}

/**
 * Get an unprefixed term slug.
 *
 * @since 1.0.0
 *
 * @param  string $term_name Term name.
 * @param  string $tax_name  Taxonomy name.
 * @return string
 */
function sbx_get_term_slug( $term_name, $tax_name ) {
	$cache_group = 'sealedbox_term-' . $tax_name;
	$cache_key   = sbx_get_cache_prefix( $cache_group ) . 'slug-' . $term_name;
	$cache_value = wp_cache_get( $cache_key, $cache_group );

	if ( $cache_value ) {
		return $cache_value;
	}

	$term_name = sbx_sanitize_term_name( $term_name );
	$term_slug = sbx_unprefix( $term_name, $tax_name );
	wp_cache_set( $cache_key, $term_slug, $cache_group );

	return $term_slug;
}

/**
 * Get taxonomy term slug.
 *
 * @param string $slug
 * @param string $tax_name
 * @return string
 */
function sbx_get_taxonomy_term_slug( $slug, string $tax_name = 'sbx_route_namespace' ) {
	if ( in_array( $tax_name, array( 'sbx_route_type', 'sbx_route_namespace' ) ) ) {
		$slug = "{$tax_name}-" . sbx_get_term_slug( $slug, $tax_name );
	}
	return $slug;
}

/**
 * Converts a string (e.g. 'yes' or 'no') to a bool.
 *
 * @since 1.0.0
 * @param string $string String to convert.
 * @return bool
 */
function sbx_string_to_bool( $string ) {
	return is_bool( $string ) ? $string : ( 'yes' === strtolower( $string ) || 1 === $string || 'true' === strtolower( $string ) || '1' === $string );
}

/**
 * Converts a bool to a 'yes' or 'no'.
 *
 * @since 1.0.0
 * @param bool $bool String to convert.
 * @return string
 */
function sbx_bool_to_string( $bool ) {
	if ( ! is_bool( $bool ) ) {
		$bool = sbx_string_to_bool( $bool );
	}
	return true === $bool ? 'yes' : 'no';
}

/**
 * Explode a string into an array by $delimiter and remove empty values.
 *
 * @since 1.0.0
 * @param string $string    String to convert.
 * @param string $delimiter Delimiter, defaults to ','.
 * @return array
 */
function sbx_string_to_array( $string, $delimiter = ',' ) {
	return is_array( $string ) ? $string : array_filter( explode( $delimiter, $string ) );
}

/**
 * Sanitize term names. Slug format (no spaces, lowercase).
 * Urldecode is used to reverse munging of UTF8 characters.
 *
 * @param string $term term name.
 * @return string
 */
function sbx_sanitize_term_name( $term ) {
	return apply_filters( 'sanitize_term_name', urldecode( sanitize_title( urldecode( $term ) ) ), $term );
}

/**
 * Sanitize permalink values before insertion into DB.
 *
 * Cannot use sbx_clean because it sometimes strips % chars and breaks the user's setting.
 *
 * @since  1.0.0
 * @param  string $value Permalink.
 * @return string
 */
function sbx_sanitize_permalink( $value ) {
	global $wpdb;

	$value = $wpdb->strip_invalid_text_for_column( $wpdb->options, 'option_value', $value );

	if ( is_wp_error( $value ) ) {
		$value = '';
	}

	$value = esc_url_raw( trim( $value ) );
	$value = str_replace( 'http://', '', $value );
	return untrailingslashit( $value );
}

/**
 * Gets the filename part of a download URL.
 *
 * @param string $file_url File URL.
 * @return string
 */
function sbx_get_filename_from_url( $file_url ) {
	$parts = wp_parse_url( $file_url );
	if ( isset( $parts['path'] ) ) {
		return basename( $parts['path'] );
	}
}

/**
 * Convert a float to a string without locale formatting which PHP adds when changing floats to strings.
 *
 * @param  float $float Float value to format.
 * @return string
 */
function sbx_float_to_string( $float ) {
	if ( ! is_float( $float ) ) {
		return $float;
	}

	$locale = localeconv();
	$string = strval( $float );
	$string = str_replace( $locale['decimal_point'], '.', $string );

	return $string;
}

/**
 * Clean variables using sanitize_text_field. Arrays are cleaned recursively.
 * Non-scalar values are ignored.
 *
 * @param string|array $var Data to sanitize.
 * @return string|array
 */
function sbx_clean( $var ) {
	if ( is_array( $var ) ) {
		return array_map( 'sbx_clean', $var );
	} else {
		return is_scalar( $var ) ? sanitize_text_field( $var ) : $var;
	}
}

/**
 * Function wp_check_invalid_utf8 with recursive array support.
 *
 * @param string|array $var Data to sanitize.
 * @return string|array
 */
function sbx_check_invalid_utf8( $var ) {
	if ( is_array( $var ) ) {
		return array_map( 'sbx_check_invalid_utf8', $var );
	} else {
		return wp_check_invalid_utf8( $var );
	}
}

/**
 * Run sbx_clean over posted textarea but maintain line breaks.
 *
 * @since  1.0.0
 * @param  string $var Data to sanitize.
 * @return string
 */
function sbx_sanitize_textarea( $var ) {
	return implode( "\n", array_map( 'sbx_clean', explode( "\n", $var ) ) );
}

/**
 * Sanitize a string destined to be a tooltip.
 *
 * Tooltips are encoded with htmlspecialchars to prevent XSS.
 * Should not be used in conjunction with esc_attr()
 *
 * @since  1.0.0
 * @param  string $var Data to sanitize.
 * @return string
 */
function sbx_sanitize_tooltip( $var ) {
	return htmlspecialchars(
		wp_kses(
			html_entity_decode( $var ),
			array(
				'br'     => array(),
				'em'     => array(),
				'strong' => array(),
				'small'  => array(),
				'span'   => array(),
				'ul'     => array(),
				'li'     => array(),
				'ol'     => array(),
				'p'      => array(),
			)
		)
	);
}

/**
 * Merge two arrays.
 *
 * @param array $a1 First array to merge.
 * @param array $a2 Second array to merge.
 * @return array
 */
function sbx_array_overlay( $a1, $a2 ) {
	foreach ( $a1 as $k => $v ) {
		if ( ! array_key_exists( $k, $a2 ) ) {
			continue;
		}
		if ( is_array( $v ) && is_array( $a2[ $k ] ) ) {
			$a1[ $k ] = sbx_array_overlay( $v, $a2[ $k ] );
		} else {
			$a1[ $k ] = $a2[ $k ];
		}
	}
	return $a1;
}

/**
 * Notation to numbers.
 *
 * This function transforms the php.ini notation for numbers (like '2M') to an integer.
 *
 * @param  string $size Size value.
 * @return int
 */
function sbx_let_to_num( $size ) {
	$l   = substr( $size, -1 );
	$ret = (int) substr( $size, 0, -1 );
	switch ( strtoupper( $l ) ) {
		case 'P':
			$ret *= 1024;
			// No break.
		case 'T':
			$ret *= 1024;
			// No break.
		case 'G':
			$ret *= 1024;
			// No break.
		case 'M':
			$ret *= 1024;
			// No break.
		case 'K':
			$ret *= 1024;
			// No break.
	}
	return $ret;
}

/**
 * SealedBox Date Format - Allows to change date format for everything SealedBox.
 *
 * @return string
 */
function sbx_date_format() {
	return apply_filters( 'sealedbox_date_format', get_option( 'date_format' ) );
}

/**
 * SealedBox Time Format - Allows to change time format for everything SealedBox.
 *
 * @return string
 */
function sbx_time_format() {
	return apply_filters( 'sealedbox_time_format', get_option( 'time_format' ) );
}

/**
 * Convert mysql datetime to PHP timestamp, forcing UTC. Wrapper for strtotime.
 *
 * Based on wcs_strtotime_dark_knight() from WC Subscriptions by Prospress.
 *
 * @since  1.0.0
 * @param  string   $time_string    Time string.
 * @param  int|null $from_timestamp Timestamp to convert from.
 * @return int
 */
function sbx_string_to_timestamp( $time_string, $from_timestamp = null ) {
	$original_timezone = date_default_timezone_get();

	// @codingStandardsIgnoreStart
	date_default_timezone_set( 'UTC' );

	if ( null === $from_timestamp ) {
		$next_timestamp = strtotime( $time_string );
	} else {
		$next_timestamp = strtotime( $time_string, $from_timestamp );
	}

	date_default_timezone_set( $original_timezone );
	// @codingStandardsIgnoreEnd

	return $next_timestamp;
}

/**
 * Convert a date string to a SBX_DateTime.
 *
 * @since  1.0.0
 * @param  string $time_string Time string.
 * @return SBX_DateTime
 */
function sbx_string_to_datetime( $time_string ) {
	// Strings are defined in local WP timezone. Convert to UTC.
	if ( 1 === preg_match( '/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})(Z|((-|\+)\d{2}:\d{2}))$/', $time_string, $date_bits ) ) {
		$offset    = ! empty( $date_bits[7] ) ? iso8601_timezone_to_offset( $date_bits[7] ) : sbx_timezone_offset();
		$timestamp = gmmktime( $date_bits[4], $date_bits[5], $date_bits[6], $date_bits[2], $date_bits[3], $date_bits[1] ) - $offset;
	} else {
		$timestamp = sbx_string_to_timestamp( get_gmt_from_date( gmdate( 'Y-m-d H:i:s', sbx_string_to_timestamp( $time_string ) ) ) );
	}
	$datetime = new SBX_DateTime( "@{$timestamp}", new DateTimeZone( 'UTC' ) );

	// Set local timezone or offset.
	if ( get_option( 'timezone_string' ) ) {
		$datetime->setTimezone( new DateTimeZone( sbx_timezone_string() ) );
	} else {
		$datetime->set_utc_offset( sbx_timezone_offset() );
	}

	return $datetime;
}

/**
 * SealedBox Timezone - helper to retrieve the timezone string for a site until.
 * a WP core method exists (see https://core.trac.wordpress.org/ticket/24730).
 *
 * Adapted from https://secure.php.net/manual/en/function.timezone-name-from-abbr.php#89155.
 *
 * @since  1.0.0
 * @return string PHP timezone string for the site
 */
function sbx_timezone_string() {
	// If site timezone string exists, return it.
	$timezone = get_option( 'timezone_string' );
	if ( $timezone ) {
		return $timezone;
	}

	// Get UTC offset, if it isn't set then return UTC.
	$utc_offset = intval( get_option( 'gmt_offset', 0 ) );
	if ( 0 === $utc_offset ) {
		return 'UTC';
	}

	// Adjust UTC offset from hours to seconds.
	$utc_offset *= 3600;

	// Attempt to guess the timezone string from the UTC offset.
	$timezone = timezone_name_from_abbr( '', $utc_offset );
	if ( $timezone ) {
		return $timezone;
	}

	// Last try, guess timezone string manually.
	foreach ( timezone_abbreviations_list() as $abbr ) {
		foreach ( $abbr as $city ) {
			// WordPress restrict the use of date(), since it's affected by timezone settings, but in this case is just what we need to guess the correct timezone.
			if ( (bool) date( 'I' ) === (bool) $city['dst'] && $city['timezone_id'] && intval( $city['offset'] ) === $utc_offset ) { // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
				return $city['timezone_id'];
			}
		}
	}

	// Fallback to UTC.
	return 'UTC';
}

/**
 * Get timezone offset in seconds.
 *
 * @since  1.0.0
 * @return float
 */
function sbx_timezone_offset() {
	$timezone = get_option( 'timezone_string' );

	if ( $timezone ) {
		$timezone_object = new DateTimeZone( $timezone );
		return $timezone_object->getOffset( new DateTime( 'now' ) );
	} else {
		return floatval( get_option( 'gmt_offset', 0 ) ) * HOUR_IN_SECONDS;
	}
}

/**
 * Callback which can flatten post meta (gets the first value if it's an array).
 *
 * @since  1.0.0
 * @param  array $value Value to flatten.
 * @return mixed
 */
function sbx_flatten_meta_callback( $value ) {
	return ! is_array( $value ) ? $value : ( empty( $value ) ? null : current( $value ) );
}

if ( ! function_exists( 'sbx_rgb_from_hex' ) ) {

	/**
	 * Convert RGB to HEX.
	 *
	 * @param mixed $color Color.
	 *
	 * @return array
	 */
	function sbx_rgb_from_hex( $color ) {
		$color = str_replace( '#', '', $color );
		// Convert shorthand colors to full format, e.g. "FFF" -> "FFFFFF".
		$color = preg_replace( '~^(.)(.)(.)$~', '$1$1$2$2$3$3', $color );

		$rgb      = array();
		$rgb['R'] = hexdec( $color[0] . $color[1] );
		$rgb['G'] = hexdec( $color[2] . $color[3] );
		$rgb['B'] = hexdec( $color[4] . $color[5] );

		return $rgb;
	}
}

if ( ! function_exists( 'sbx_hex_darker' ) ) {

	/**
	 * Make HEX color darker.
	 *
	 * @param mixed $color  Color.
	 * @param int   $factor Darker factor.
	 *                      Defaults to 30.
	 * @return string
	 */
	function sbx_hex_darker( $color, $factor = 30 ) {
		$base  = sbx_rgb_from_hex( $color );
		$color = '#';

		foreach ( $base as $k => $v ) {
			$amount      = $v / 100;
			$amount      = round( $amount * $factor );
			$new_decimal = $v - $amount;

			$new_hex_component = dechex( $new_decimal );
			if ( strlen( $new_hex_component ) < 2 ) {
				$new_hex_component = '0' . $new_hex_component;
			}
			$color .= $new_hex_component;
		}

		return $color;
	}
}

if ( ! function_exists( 'sbx_hex_lighter' ) ) {

	/**
	 * Make HEX color lighter.
	 *
	 * @param mixed $color  Color.
	 * @param int   $factor Lighter factor.
	 *                      Defaults to 30.
	 * @return string
	 */
	function sbx_hex_lighter( $color, $factor = 30 ) {
		$base  = sbx_rgb_from_hex( $color );
		$color = '#';

		foreach ( $base as $k => $v ) {
			$amount      = 255 - $v;
			$amount      = $amount / 100;
			$amount      = round( $amount * $factor );
			$new_decimal = $v + $amount;

			$new_hex_component = dechex( $new_decimal );
			if ( strlen( $new_hex_component ) < 2 ) {
				$new_hex_component = '0' . $new_hex_component;
			}
			$color .= $new_hex_component;
		}

		return $color;
	}
}

if ( ! function_exists( 'sbx_hex_is_light' ) ) {

	/**
	 * Determine whether a hex color is light.
	 *
	 * @param mixed $color Color.
	 * @return bool  True if a light color.
	 */
	function sbx_hex_is_light( $color ) {
		$hex = str_replace( '#', '', $color );

		$c_r = hexdec( substr( $hex, 0, 2 ) );
		$c_g = hexdec( substr( $hex, 2, 2 ) );
		$c_b = hexdec( substr( $hex, 4, 2 ) );

		$brightness = ( ( $c_r * 299 ) + ( $c_g * 587 ) + ( $c_b * 114 ) ) / 1000;

		return $brightness > 155;
	}
}

if ( ! function_exists( 'sbx_light_or_dark' ) ) {

	/**
	 * Detect if we should use a light or dark color on a background color.
	 *
	 * @param mixed  $color Color.
	 * @param string $dark  Darkest reference.
	 *                      Defaults to '#000000'.
	 * @param string $light Lightest reference.
	 *                      Defaults to '#FFFFFF'.
	 * @return string
	 */
	function sbx_light_or_dark( $color, $dark = '#000000', $light = '#FFFFFF' ) {
		return sbx_hex_is_light( $color ) ? $dark : $light;
	}
}

if ( ! function_exists( 'sbx_format_hex' ) ) {

	/**
	 * Format string as hex.
	 *
	 * @param string $hex HEX color.
	 * @return string|null
	 */
	function sbx_format_hex( $hex ) {
		$hex = trim( str_replace( '#', '', $hex ) );

		if ( strlen( $hex ) === 3 ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}

		return $hex ? '#' . $hex : null;
	}
}

/**
 * Wrapper for mb_strtoupper which see's if supported first.
 *
 * @since  1.0.0
 * @param  string $string String to format.
 * @return string
 */
function sbx_strtoupper( $string ) {
	return function_exists( 'mb_strtoupper' ) ? mb_strtoupper( $string ) : strtoupper( $string );
}

/**
 * Make a string lowercase.
 * Try to use mb_strtolower() when available.
 *
 * @since  1.0.0
 * @param  string $string String to format.
 * @return string
 */
function sbx_strtolower( $string ) {
	return function_exists( 'mb_strtolower' ) ? mb_strtolower( $string ) : strtolower( $string );
}

/**
 * Trim a string and append a suffix.
 *
 * @param  string  $string String to trim.
 * @param  integer $chars  Amount of characters.
 *                         Defaults to 200.
 * @param  string  $suffix Suffix.
 *                         Defaults to '...'.
 * @return string
 */
function sbx_trim_string( $string, $chars = 200, $suffix = '...' ) {
	if ( strlen( $string ) > $chars ) {
		if ( function_exists( 'mb_substr' ) ) {
			$string = mb_substr( $string, 0, ( $chars - mb_strlen( $suffix ) ) ) . $suffix;
		} else {
			$string = substr( $string, 0, ( $chars - strlen( $suffix ) ) ) . $suffix;
		}
	}
	return $string;
}

/**
 * Sanitize terms from an attribute text based.
 *
 * @since  1.0.0
 * @param  string $term Term value.
 * @return string
 */
function sbx_sanitize_term_text_based( $term ) {
	return trim( wp_strip_all_tags( wp_unslash( $term ) ) );
}

/**
 * Format a date for output.
 *
 * @since  1.0.0
 * @param  SBX_DateTime $date   Instance of SBX_DateTime.
 * @param  string      $format Data format.
 *                             Defaults to the sbx_date_format function if not set.
 * @return string
 */
function sbx_format_datetime( $date, $format = '' ) {
	if ( ! $format ) {
		$format = sbx_date_format();
	}
	if ( ! is_a( $date, 'SBX_DateTime' ) ) {
		return '';
	}
	return $date->date_i18n( $format );
}

/**
 * Get part of a string before :.
 *
 * Used for example in shipping methods ids where they take the format
 * method_id:instance_id
 *
 * @since  1.0.0
 * @param  string $string String to extract.
 * @return string
 */
function sbx_get_string_before_colon( $string ) {
	return trim( current( explode( ':', (string) $string ) ) );
}

/**
 * Array merge and sum function.
 *
 * Source:  https://gist.github.com/Nickology/f700e319cbafab5eaedc
 *
 * @since 1.0.0
 * @return array
 */
function sbx_array_merge_recursive_numeric() {
	$arrays = func_get_args();

	// If there's only one array, it's already merged.
	if ( 1 === count( $arrays ) ) {
		return $arrays[0];
	}

	// Remove any items in $arrays that are NOT arrays.
	foreach ( $arrays as $key => $array ) {
		if ( ! is_array( $array ) ) {
			unset( $arrays[ $key ] );
		}
	}

	// We start by setting the first array as our final array.
	// We will merge all other arrays with this one.
	$final = array_shift( $arrays );

	foreach ( $arrays as $b ) {
		foreach ( $final as $key => $value ) {
			// If $key does not exist in $b, then it is unique and can be safely merged.
			if ( ! isset( $b[ $key ] ) ) {
				$final[ $key ] = $value;
			} else {
				// If $key is present in $b, then we need to merge and sum numeric values in both.
				if ( is_numeric( $value ) && is_numeric( $b[ $key ] ) ) {
					// If both values for these keys are numeric, we sum them.
					$final[ $key ] = $value + $b[ $key ];
				} elseif ( is_array( $value ) && is_array( $b[ $key ] ) ) {
					// If both values are arrays, we recursively call ourself.
					$final[ $key ] = sbx_array_merge_recursive_numeric( $value, $b[ $key ] );
				} else {
					// If both keys exist but differ in type, then we cannot merge them.
					// In this scenario, we will $b's value for $key is used.
					$final[ $key ] = $b[ $key ];
				}
			}
		}

		// Finally, we need to merge any keys that exist only in $b.
		foreach ( $b as $key => $value ) {
			if ( ! isset( $final[ $key ] ) ) {
				$final[ $key ] = $value;
			}
		}
	}

	return $final;
}

/**
 * Sanitize host list.
 *
 * Returns a string of unique host name matching patterns
 * listed one per line.
 *
 * @param string|array $host_list
 * @return string
 */
if ( ! function_exists( 'sbx_sanitize_host_list' ) ) :

    function sbx_sanitize_host_list( $host_list ) : string {
        return implode( "\n", array_unique( array_filter( array_map( 'trim', preg_split( "/\n|\r|\||\,/", is_array( $host_list ) ? implode( "\n", $host_list ) : $host_list ) ) ) ) );
    }

endif;

/**
 * Parse host matches.
 *
 * Returns an array of valid host names from a textarea
 * containing one per line. The match criteria may be expanded
 * using the [*] wildcard character and refined by using the [-]
 * exception character.
 *
 * ^-?((\*)|\*?((25[0-5*]|2[0-4*][0-9*]|[01*]?[0-9*][0-9*]?)\.){3}\*?(25[0-5*]|2[0-4*][0-9*]|[01*]?[0-9*][0-9*]?|\*?)|((\*\.)?([a-zA-Z0-9-*]+\.){0,5}\*?([a-zA-Z0-9-*][a-zA-Z0-9-*]+|\*?)\.([a-zA-Z*]{2,63}?|\*)))$
 *
 * @param string|array $host_list
 * @return array
 */
if ( ! function_exists( 'sbx_parse_host_list' ) ) :

    function sbx_parse_host_list( $host_list ) : array {
        $host_list = sbx_sanitize_host_list( $host_list );
        preg_match_all( "/^-?((\*)|\*?((25[0-5*]|2[0-4*][0-9*]|[01*]?[0-9*][0-9*]?)\.){3}\*?(25[0-5*]|2[0-4*][0-9*]|[01*]?[0-9*][0-9*]?|\*?)|((\*\.)?([a-zA-Z0-9-*]+\.){0,5}\*?([a-zA-Z0-9-*][a-zA-Z0-9-*]+|\*?)([a-zA-Z*]{2,63}?|\*)))(?<!\.$)$/m", $host_list, $valid_hosts );
        return current( $valid_hosts );
    }

endif;

/**
 * Host list.
 *
 * Retrieve a host list determined by the arguments
 * passed into the function.
 *
 * @param array $host_list
 * @param int   $flag
 * @return array
 */
if ( ! function_exists( 'sbx_host_list' ) ) :

    function sbx_host_list( $host_list, $flag = 0 ) : array {
        return preg_grep( "/^-/", $host_list, $flag );
    }

endif;

/**
 * Host whitelist.
 *
 * Retrieve the whitelisted hosts. Hosts matching the
 * chriteria within this list will be included.
 *
 * @param array $host_list
 * @return array
 */
if ( ! function_exists ( 'sbx_allowed_host_list' ) ) :

    function sbx_allowed_host_list( $host_list ) : array {
        return sbx_host_list( $host_list, PREG_GREP_INVERT );
    }

endif;

/**
 * Host blacklist.
 *
 * Items prefixed with the [-] exception character will be
 * designated to the blacklist. Hosts matching the
 * chriteria within this list will be excluded.
 *
 * @param array $host_list
 * @return array
 */
if ( ! function_exists ( 'sbx_blocked_host_list' ) ) :

    function sbx_blocked_host_list( $host_list ) : array {
        return array_map( function( $item ) {
			return substr( $item, 1 );
        }, sbx_host_list( $host_list ) );
    }

endif;

/**
 * Host match.
 *
 * @param string $host
 * @param string|array $host_list
 * @return boolean
 */
if ( ! function_exists ( 'sbx_validate_host' ) ) :

    function sbx_validate_host( $host, $host_list ) : bool {

        $host_list = sbx_parse_host_list( $host_list );
        $blacklist = sbx_blocked_host_list( $host_list );
        $whitelist = sbx_allowed_host_list( $host_list );

        if ( empty( $blacklist ) && empty( $whitelist ) ) {
            return true;
        }

        $transforms = array(
            '\*' => '[a-z0-9-]+',
            '\.' => '\.',
            '\|' => '|',
        );

        $modifiers = 'i';

        $pattern = empty( $blacklist ) ? '' : '(?:(?!' . strtr( preg_quote( implode( '|', $blacklist ), '# '), $transforms ) . '))';
        $pattern = '#^' . $pattern . '(' . strtr( preg_quote( implode( '|', $whitelist ), '# '), $transforms ) . ')' . '$#' . $modifiers;

        return (boolean) preg_match( $pattern, $host );
    }

endif;

/**
 * Implode and escape HTML attributes for output.
 *
 * @since 3.3.0
 * @param array $raw_attributes Attribute name value pairs.
 * @return string
 */
function sbx_implode_html_attributes( $raw_attributes ) {
	$attributes = array();
	foreach ( $raw_attributes as $name => $value ) {
		$attributes[] = esc_attr( $name ) . '="' . esc_attr( $value ) . '"';
	}
	return implode( ' ', $attributes );
}

/**
 * Escape JSON for use on HTML or attribute text nodes.
 *
 * @since 1.0.0
 * @param string $json JSON to escape.
 * @param bool   $html True if escaping for HTML text node, false for attributes. Determines how quotes are handled.
 * @return string Escaped JSON.
 */
function sbx_esc_json( $json, $html = false ) {
	return _wp_specialchars(
		$json,
		$html ? ENT_NOQUOTES : ENT_QUOTES, // Escape quotes in attribute nodes only.
		'UTF-8',                           // json_encode() outputs UTF-8 (really just ASCII), not the blog's charset.
		true                               // Double escape entities: `&amp;` -> `&amp;amp;`.
	);
}

/**
 * Parse a relative date option from the settings API into a standard format.
 *
 * @since 1.0.0
 * @param mixed $raw_value Value stored in DB.
 * @return array Nicely formatted array with number and unit values.
 */
function sbx_parse_relative_date_option( $raw_value ) {
	$periods = array(
		'days'   => __( 'Day(s)', 'sealedbox' ),
		'weeks'  => __( 'Week(s)', 'sealedbox' ),
		'months' => __( 'Month(s)', 'sealedbox' ),
		'years'  => __( 'Year(s)', 'sealedbox' ),
	);

	$value = wp_parse_args(
		(array) $raw_value,
		array(
			'number' => '',
			'unit'   => 'days',
		)
	);

	$value['number'] = ! empty( $value['number'] ) ? absint( $value['number'] ) : '';

	if ( ! in_array( $value['unit'], array_keys( $periods ), true ) ) {
		$value['unit'] = 'days';
	}

	return $value;
}

/**
 * Format the endpoint slug, strip out anything not allowed in a url.
 *
 * @since 1.0.0
 * @param string $raw_value The raw value.
 * @return string
 */
function sbx_sanitize_endpoint_slug( $raw_value ) {
	return sanitize_title( $raw_value );
}
// add_filter( 'sealedbox_admin_settings_sanitize_option_sealedbox_checkout_pay_endpoint', 'sbx_sanitize_endpoint_slug', 10, 1 );
// add_filter( 'sealedbox_admin_settings_sanitize_option_sealedbox_checkout_order_received_endpoint', 'sbx_sanitize_endpoint_slug', 10, 1 );
// add_filter( 'sealedbox_admin_settings_sanitize_option_sealedbox_myaccount_add_payment_method_endpoint', 'sbx_sanitize_endpoint_slug', 10, 1 );
// add_filter( 'sealedbox_admin_settings_sanitize_option_sealedbox_myaccount_delete_payment_method_endpoint', 'sbx_sanitize_endpoint_slug', 10, 1 );
// add_filter( 'sealedbox_admin_settings_sanitize_option_sealedbox_myaccount_set_default_payment_method_endpoint', 'sbx_sanitize_endpoint_slug', 10, 1 );
// add_filter( 'sealedbox_admin_settings_sanitize_option_sealedbox_myaccount_orders_endpoint', 'sbx_sanitize_endpoint_slug', 10, 1 );
// add_filter( 'sealedbox_admin_settings_sanitize_option_sealedbox_myaccount_view_order_endpoint', 'sbx_sanitize_endpoint_slug', 10, 1 );
// add_filter( 'sealedbox_admin_settings_sanitize_option_sealedbox_myaccount_downloads_endpoint', 'sbx_sanitize_endpoint_slug', 10, 1 );
// add_filter( 'sealedbox_admin_settings_sanitize_option_sealedbox_myaccount_edit_account_endpoint', 'sbx_sanitize_endpoint_slug', 10, 1 );
// add_filter( 'sealedbox_admin_settings_sanitize_option_sealedbox_myaccount_edit_address_endpoint', 'sbx_sanitize_endpoint_slug', 10, 1 );
// add_filter( 'sealedbox_admin_settings_sanitize_option_sealedbox_myaccount_payment_methods_endpoint', 'sbx_sanitize_endpoint_slug', 10, 1 );
// add_filter( 'sealedbox_admin_settings_sanitize_option_sealedbox_myaccount_lost_password_endpoint', 'sbx_sanitize_endpoint_slug', 10, 1 );
// add_filter( 'sealedbox_admin_settings_sanitize_option_sealedbox_logout_endpoint', 'sbx_sanitize_endpoint_slug', 10, 1 );
