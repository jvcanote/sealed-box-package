<?php
/**
 * Sealed Box Route Functions
 *
 * Functions for route specific things.
 *
 * @package SealedBox/Functions
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Main function for returning rest route, uses the SBX_Service_Route class.
 *
 * This function should only be called after 'init' action is finished, as there might be taxonomies that are getting
 * registered during the init action.
 *
 * @since 1.0.0
 *
 * @param mixed $the_route Post object or post ID of the route.
 * @param array $deprecated Previously used to pass arguments to the factory, e.g. to force a type.
 * @return SBX_Service_Route|null|false
 */
function sbx_get_route( $the_route = false ) {
	if ( ! did_action( 'sealed_box_init' ) || ! did_action( 'sealed_box_after_register_taxonomy' ) || ! did_action( 'sealed_box_after_register_post_type' ) ) {
		/* translators: 1: sbx_get_route 2: sealed_box_init 3: sealed_box_after_register_taxonomy 4: sealed_box_after_register_post_type */
		_doing_it_wrong( __FUNCTION__, sprintf( __( '%1$s should not be called before the %2$s, %3$s and %4$s actions have finished.', 'sealedbox' ), 'sbx_get_route', 'sealed_box_init', 'sealed_box_after_register_taxonomy', 'sealed_box_after_register_post_type' ), SEALED_BOX_VERSION );
		return false;
	}
	return SBX_Service_Routes::get_route( $the_route );
}

/**
 * Query the REST route.
 *
 * This may effect the main loop.
 *
 * @since	1.0.0
 * @global
 * @param   string  $namespace	Default: null
 * @param   string  $version    Default: null
 * @param   string  $type       Default: null
 * @param   string  $name       Default: null
 * @return	mixed
 */
function sbx_query_the_route( string $namespace = null, string $version = null, string $type = null, string $name = null ) {

	$query = array(
		'post_type' => 'sbx_service_route'
	);

	if ( ! isset( $name ) && ! isset( $type ) && ! isset( $namespace ) ) {
		return false;
	}

	if ( isset( $name ) ) {
		$query['post_name'] = $name;
	}

	if ( isset( $type ) || isset( $namespace ) || isset( $version ) ) {

		$query['tax_query'] = array();

		if ( isset( $type, $namespace ) ) {
			$query['tax_query']['relation'] = 'AND';
		}
	}

	if ( isset( $type ) ) {
		$query['tax_query'][] = array(
			'taxonomy' => 'sbx_route_type',
			'terms'    => array( sbx_get_taxonomy_term_slug( $type, 'sbx_route_type' ) ),
			'field'    => 'slug',
		);
	}

	if ( isset( $namespace ) ) {
		$query['tax_query'][] = array(
			'taxonomy' => 'sbx_route_namespace',
			'terms'    => array( sbx_get_taxonomy_term_slug( $namespace, 'sbx_route_namespace' ) ),
			'field'    => 'slug',
		);
	}

	if ( isset( $version ) ) {
		$query['tax_query'][] = array(
			'taxonomy' => 'sbx_route_version',
			'terms'    => array( sbx_get_taxonomy_term_slug( $version, 'sbx_route_version' ) ),
			'field'    => 'slug',
		);
	}

	$query = new WP_Query( $query );

	return $query->have_posts() ? sbx_get_route( $query->post ) : false;
}

/**
 * Get route types.
 *
 * @since 1.0.0
 * @return array
 */
function sbx_get_route_types() {

	return (array) apply_filters(
		'sealed_box_service_route_type_selector',
		array(
			'basic'    => __( 'Basic route', 'sealedbox' ),
			'redirection' => __( 'Redirection route', 'sealedbox' )
		)
	);

}

/**
 * Are there routes with the parameters
 *
 * @since 1.0.0
 * @param array $params
 * @return boolean
 */
function sbx_has_term_routes( ...$term_ids ) {

	return ! empty( sbx_get_term_service_route_ids( $term_ids, array( 'sbx_route_type', 'sbx_route_namespace', 'sbx_route_version' ) ) );
}

/**
 * Retrieves route term for a taxonomy.
 *
 * @since  1.0.0
 * @param  int    $route_id Route ID.
 * @param  string $tax_name Taxonomy slug.
 * @return WP_Term
 */
function sbx_get_route_term( $route_id, $tax_name ) {

	return sbx_flatten_meta_callback( sbx_get_the_route_terms( $route_id, $tax_name ) ) ?? get_term( absint( get_option( 'default_' . $tax_name, 0 ) ), $tax_name );
}

/**
 * Retrieves route term ID for a taxonomy.
 *
 * @since  1.0.0
 * @param  int    $route_id Route ID.
 * @param  string $tax_name Taxonomy slug.
 * @return int
 */
function sbx_get_route_term_id( $route_id, $tax_name ) {

	return sbx_flatten_meta_callback( sbx_get_the_route_terms( $route_id, $tax_name, 'term_id' ) ) ?? absint( get_option( 'default_' . $tax_name, 0 ) );
}

/**
 * Retrieves route term ID for a taxonomy.
 *
 * @since  1.0.0
 * @param  int    $route_id Route ID.
 * @param  string $tax_name Taxonomy slug.
 * @return int
 */
function sbx_get_route_term_slug( $route_id, $tax_name ) {
	$default = get_term( absint( get_option( 'default_' . $tax_name, 0 ) ), $tax_name );

	return sbx_flatten_meta_callback( sbx_get_the_route_terms( $route_id, $tax_name, 'slug' ) ) ?? $default->slug ?? null;
}

/**
 * Get the route service type term for a route by ID.
 *
 * @since  1.0.0
 * @param  int $route_id Route ID.
 * @return WP_Term
 */
function sbx_get_route_type( $route_id ) {

	return sbx_get_route_term( $route_id, 'sbx_route_type' );
};

/**
 * Get the route namespace term for a route by ID.
 *
 * @since  1.0.0
 * @param  int $route_id Route ID.
 * @return WP_Term
 */
function sbx_get_route_namespace( $route_id ) {

	return sbx_get_route_term( $route_id, 'sbx_route_namespace' );
}

/**
 * Get the route version term for a route by ID.
 *
 * @since  1.0.0
 * @param  int $route_id Route ID.
 * @return WP_Term
 */
function sbx_get_route_version( $route_id ) {

	return sbx_get_route_term( $route_id, 'sbx_route_version' );
}

/**
 * Get the route service type ID for a route by ID.
 *
 * @since  1.0.0
 * @param  int $route_id Route ID.
 * @return int
 */
function sbx_get_route_type_id( $route_id ) {

	return sbx_get_route_term_id( $route_id, 'sbx_route_type' );
};

/**
 * Get the route namespace ID for a route by ID.
 *
 * @since  1.0.0
 * @param  int $route_id Route ID.
 * @return int
 */
function sbx_get_route_namespace_id( $route_id ) {

	return sbx_get_route_term_id( $route_id, 'sbx_route_namespace' );
}

/**
 * Get the route version ID for a route by ID.
 *
 * @since  1.0.0
 * @param  int $route_id Route ID.
 * @return int
 */
function sbx_get_route_version_id( $route_id ) {

	return sbx_get_route_term_id( $route_id, 'sbx_route_version' );
}

/**
 * Get the route service type ID for a route by ID.
 *
 * @since  1.0.0
 * @param  int $route_id Route ID.
 * @return int
 */
function sbx_get_route_type_slug( $route_id ) {

	return sbx_get_route_term_slug( $route_id, 'sbx_route_type' );
};

/**
 * Get the route namespace ID for a route by ID.
 *
 * @since  1.0.0
 * @param  int $route_id Route ID.
 * @return int
 */
function sbx_get_route_namespace_slug( $route_id ) {

	return sbx_get_route_term_slug( $route_id, 'sbx_route_namespace' );
}

/**
 * Get the route version ID for a route by ID.
 *
 * @since  1.0.0
 * @param  int $route_id Route ID.
 * @return int
 */
function sbx_get_route_version_slug( $route_id ) {

	return sbx_get_route_term_slug( $route_id, 'sbx_route_version' );
}

/**
 * Returns the route service type in a list.
 *
 * @param int    $route_id Route ID.
 * @param string $sep (default: ', ').
 * @param string $before (default: '').
 * @param string $after (default: '').
 * @return string
 */
function sbx_get_route_type_list( $route_id, $sep = ', ', $before = '', $after = '' ) {

	return get_the_term_list( $route_id, 'sbx_route_type', $before, $sep, $after );
}

/**
 * Returns the route namespaces in a list.
 *
 * @param int    $route_id Route ID.
 * @param string $sep (default: ', ').
 * @param string $before (default: '').
 * @param string $after (default: '').
 * @return string
 */
function sbx_get_route_namespace_list( $route_id, $sep = ', ', $before = '', $after = '' ) {

	return get_the_term_list( $route_id, 'sbx_route_namespace', $before, $sep, $after );
}

/**
 * Returns the route versions in a list.
 *
 * @param int    $route_id Route ID.
 * @param string $sep (default: ', ').
 * @param string $before (default: '').
 * @param string $after (default: '').
 * @return string
 */
function sbx_get_route_version_list( $route_id, $sep = ', ', $before = '', $after = '' ) {

	return get_the_term_list( $route_id, 'sbx_route_version', $before, $sep, $after );
}

/**
 * Helper to get cached object terms and filter by field using wp_list_pluck().
 * Works as a cached alternative for wp_get_post_terms() and wp_get_object_terms().
 *
 * @since  1.0.0
 * @param  int|object|null $route_id  Object ID.
 * @param  string          $tax_name  Taxonomy slug.
 * @param  bool|string     $nicename  Unprefix term slugs.
 * @param  string          $field     Field name.
 * @param  string          $index_key Index key name.
 * @return WP_Term[]
 */
function sbx_get_the_route_terms( $route_id, $tax_name, $field = null, $index_key = null, $nicename = 'use_nicename' ) {
	global $post;

	if ( is_object( $route_id ) ) {
		$route_id = $route_id->ID;

	} elseif ( empty( $route_id ) ) {
		$route_id = $post->ID;
	}

	$nicename    = $nicename ? 'use_nicename' : null;
	$cache_group = 'sbx_service_route_' . $route_id;
	$cache_key   = sbx_get_cache_prefix( $cache_group ) . $tax_name . $nicename . $field . $index_key;
	$terms       = wp_cache_get( $cache_key, $cache_group );

	if ( false !== $terms ) {
		return $terms;
	}

	$terms = sbx_get_service_route_terms( $route_id, $tax_name );

	if ( ! $terms || is_wp_error( $terms ) ) {
		return array();
	}

	if ( $nicename ) {

		foreach( $terms as &$term ) {
			$term->slug = sbx_get_term_nicename( $term );
		}
	}

	$terms = is_null( $field ) ? $terms : wp_list_pluck( $terms, $field, $index_key );

	wp_cache_add( $cache_key, $terms, $cache_group );

	return $terms;
}

/**
 * Wrapper used to get terms for a rest route.
 *
 * @param  int    $route_id Rest Route ID.
 * @param  string $tax_name Taxonomy slug.
 * @param  array  $args     Query arguments.
 * @return WP_Term[]
 */
function sbx_get_service_route_terms( $route_id, $tax_name, $args = array() ) {

	if ( ! taxonomy_exists( $tax_name ) ) {

		return array();
	}

	return apply_filters( 'sealed_box_get_service_route_terms', _sbx_get_cached_service_route_terms( $route_id, $tax_name, $args ), $route_id, $tax_name, $args );
}

/**
 * Cached version of wp_get_post_terms().
 * This is a private function (internal use ONLY).
 *
 * @since  1.0.0
 * @param  int    $route_id Rest Route ID.
 * @param  string $tax_name Taxonomy slug.
 * @param  array  $args     Query arguments.
 * @return WP_Term[]
 */
function _sbx_get_cached_service_route_terms( $route_id, $tax_name, $args = array() ) {
	$cache_group = 'sbx_service_route_' . $route_id;
	$cache_key   = $tax_name . md5( wp_json_encode( $args ) );
	$terms       = wp_cache_get( $cache_key, $cache_group );

	if ( false !== $terms ) {

		return $terms;
	}

	$terms = wp_get_post_terms( $route_id, $tax_name, $args );

	wp_cache_add( $cache_key, $terms, $cache_group );

	return $terms;
}

/**
 * When a post is updated and terms recounted (called by _update_post_term_count), clear the ids.
 *
 * @param int    $object_id  Object ID.
 * @param array  $terms      An array of object terms.
 * @param int[]  $tt_ids     An array of term taxonomy IDs.
 * @param string $tax_name   Taxonomy slug.
 * @param bool   $append     Whether to append new terms to the old terms.
 * @param int[]  $old_tt_ids Old array of term taxonomy IDs.
 */
function sbx_clear_term_service_route_ids( $object_id, $terms, $tt_ids, $tax_name, $append, $old_tt_ids ) {

	foreach ( $old_tt_ids as $term_id ) {
		delete_term_meta( $term_id, 'rest_route_ids' );
	}

	foreach ( $tt_ids as $term_id ) {
		delete_term_meta( $term_id, 'rest_route_ids' );
	}
}
add_action( 'set_object_terms', 'sbx_clear_term_service_route_ids', 10, 6 );

/**
 * Computes a unique slug for the post, when given the desired slug and some post details.
 *
 * @todo Make this work
 *
 * @since 1.0.0
 *
 * @global wpdb       $wpdb       WordPress database abstraction object.
 * @global WP_Rewrite $wp_rewrite WordPress rewrite component.
 *
 * @param string $slug        The desired slug (post_name).
 * @param int    $post_ID     Post ID.
 * @param string $post_status No uniqueness checks are made if the post is still draft or pending.
 * @param int    $route_type_id    Service Type term ID.
 * @param int    $namespace_id  Namespace term ID
 * @param int    $version_id  Version term ID
 * @return string Unique slug for the post, based on $post_name (with a -1, -2, etc. suffix)
 */
function sbx_unique_route_slug( $slug, $post_id, $post_status, $route_type_id, $namespace_id, $version_id = 0 ) {

	if ( in_array( $post_status, array( 'draft', 'pending', 'auto-draft' ) ) ) {

		return $slug;
	}

	global $wpdb, $wp_rewrite;

	$original_slug = $slug;

	$feeds = $wp_rewrite->feeds;
	if ( ! is_array( $feeds ) ) {
		$feeds = array();
	}

	// Post slugs must be unique across all posts.
	$check_sql = "SELECT post_name FROM $wpdb->posts AS p
	LEFT JOIN $wpdb->term_relationships AS sr ON (p.ID = sr.object_id)
	LEFT JOIN $wpdb->term_taxonomy AS st ON (sr.term_taxonomy_id = st.term_taxonomy_id)
	LEFT JOIN $wpdb->terms AS s ON (st.term_id = s.term_id)
	LEFT JOIN $wpdb->term_relationships AS nr ON (p.ID = nr.object_id)
	LEFT JOIN $wpdb->term_taxonomy AS nt ON (nr.term_taxonomy_id = nt.term_taxonomy_id)
	LEFT JOIN $wpdb->terms AS n ON (nt.term_id = n.term_id)
	LEFT JOIN $wpdb->term_relationships AS vr ON (p.ID = vr.object_id)
	LEFT JOIN $wpdb->term_taxonomy AS vt ON (vr.term_taxonomy_id = vt.term_taxonomy_id)
	LEFT JOIN $wpdb->terms AS v ON (vt.term_id = v.term_id)
	WHERE p.post_type = 'sbx_service_route' AND p.post_name = %s AND p.ID != %d
	AND st.taxonomy = 'sbx_route_type' AND s.term_id = %d
	AND nt.taxonomy = 'sbx_route_namespace' AND n.term_id = %d
	AND vt.taxonomy = 'sbx_route_version' AND v.term_id = %d
	LIMIT 1";

	$post_name_check = $wpdb->get_var( $wpdb->prepare( $check_sql, $slug, $post_id, $route_type_id, $namespace_id, $version_id ) );

	//update_post_meta( $post_id, 'post_name_check', [ $post_name_check, $slug, $post_id, $route_type_id, $namespace_id ] );

	if ( $post_name_check ) {
		$suffix = 2;
		do {
			$alt_post_name   = _truncate_post_slug( $slug, 200 - ( strlen( $suffix ) + 1 ) ) . "-$suffix";
			$post_name_check = $wpdb->get_var( $wpdb->prepare( $check_sql, $alt_post_name, $post_id, $route_type_id, $namespace_id, $version_id ) );
			$suffix++;
		} while ( $post_name_check );
		$slug = $alt_post_name;
	}

	/**
	 * Filters the unique post slug.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug          The post slug.
	 * @param int    $post_id       Post ID.
	 * @param string $post_status   The post status.
	 * @param int    $route_type_id    Service Type term ID.
	 * @param int    $namespace_id  Namespace term ID
	 * @param string $original_slug The original post slug.
	 */
	return apply_filters( 'sbx_unique_service_route_slug', $slug, $post_id, $post_status, $route_type_id, $namespace_id, $version_id, $original_slug );
}