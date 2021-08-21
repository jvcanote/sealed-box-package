<?php
/**
 * Sealed Box REST Functions
 *
 * Functions for REST specific things.
 *
 * @package SealedBox/Functions
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Parses and formats a date for ISO8601/RFC3339.
 *
 * Required WP 4.4 or later.
 * See https://developer.wordpress.org/reference/functions/mysql_to_rfc3339/
 *
 * @since 1.0.0
 * @param  string|null|SBX_DateTime $date Date.
 * @param  bool                     $utc  Send false to get local/offset time.
 * @return string|null ISO8601/RFC3339 formatted datetime.
 */
function sbx_rest_prepare_date_response( $date, $utc = true ) {
	if ( is_numeric( $date ) ) {
		$date = new SBX_DateTime( "@$date", new DateTimeZone( 'UTC' ) );
		$date->setTimezone( new DateTimeZone( sbx_timezone_string() ) );
	} elseif ( is_string( $date ) ) {
		$date = new SBX_DateTime( $date, new DateTimeZone( 'UTC' ) );
		$date->setTimezone( new DateTimeZone( sbx_timezone_string() ) );
	}

	if ( ! is_a( $date, 'SBX_DateTime' ) ) {
		return null;
	}

	// Get timestamp before changing timezone to UTC.
	return gmdate( 'Y-m-d\TH:i:s', $utc ? $date->getTimestamp() : $date->getOffsetTimestamp() );
}

/**
 * Encodes a value according to RFC 3986.
 * Supports multidimensional arrays.
 *
 * @since 1.0.0
 * @param string|array $value The value to encode.
 * @return string|array       Encoded values.
 */
function sbx_rest_urlencode_rfc3986( $value ) {
	if ( is_array( $value ) ) {
		return array_map( 'sbx_rest_urlencode_rfc3986', $value );
	}

	return str_replace( array( '+', '%7E' ), array( ' ', '~' ), rawurlencode( $value ) );
}

/**
 * Check permissions of posts on REST API.
 *
 * @since 1.0.0
 * @param string $post_type Post type.
 * @param string $context   Request context.
 * @param int    $object_id Post ID.
 * @return bool
 */
function sbx_rest_check_post_permissions( $post_type, $context = 'read', $object_id = 0 ) {
	$contexts = array(
		'read'   => 'read_private_posts',
		'create' => 'publish_posts',
		'edit'   => 'edit_post',
		'delete' => 'delete_post',
		'batch'  => 'edit_others_posts',
	);

	if ( 'revision' === $post_type ) {
		$permission = false;
	} else {
		$cap              = $contexts[ $context ];
		$post_type_object = get_post_type_object( $post_type );
		$permission       = current_user_can( $post_type_object->cap->$cap, $object_id );
	}

	return apply_filters( 'sealed_box_rest_check_permissions', $permission, $context, $object_id, $post_type );
}

/**
 * Check permissions of route terms on REST API.
 *
 * @since 1.0.0
 * @param string $taxonomy  Taxonomy.
 * @param string $context   Request context.
 * @param int    $object_id Post ID.
 * @return bool
 */
function sbx_rest_check_route_term_permissions( $taxonomy, $context = 'read', $object_id = 0 ) {
	$contexts = array(
		'read'   => 'manage_terms',
		'create' => 'edit_terms',
		'edit'   => 'edit_terms',
		'delete' => 'delete_terms',
		'batch'  => 'edit_terms',
	);

	$cap             = $contexts[ $context ];
	$taxonomy_object = get_taxonomy( $taxonomy );
	$permission      = current_user_can( $taxonomy_object->cap->$cap, $object_id );

	return apply_filters( 'sealed_box_rest_check_permissions', $permission, $context, $object_id, $taxonomy );
}
