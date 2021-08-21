<?php
/**
 * Sealed Box Terms
 *
 * Functions for handling terms/term meta.
 *
 * @package SealedBox/Functions
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Change get terms defaults for attributes to order by the sorting setting, or default to menu_order for sortable taxonomies.
 *
 * Sorting options are now set as the default automatically.
 *
 * @since 1.0.0
 *
 * @param array $defaults   An array of default get_terms() arguments.
 * @param array $taxonomies An array of taxonomies.
 * @return array
 */
function sbx_change_get_terms_defaults( $defaults, $taxonomies ) {
	if ( is_array( $taxonomies ) && 1 < count( $taxonomies ) ) {
		return $defaults;
	}
	$taxonomy = is_array( $taxonomies ) ? (string) current( $taxonomies ) : $taxonomies;
	$orderby  = 'name';

	if ( in_array( $taxonomy, apply_filters( 'sealed_box_sortable_taxonomies', array( 'sbx_route_type', 'sbx_route_namespace', 'sbx_route_version' ) ), true ) ) {
		$orderby = 'menu_order';
	}

	// Change defaults. Invalid values will be changed later @see sbx_change_pre_get_terms.
	// These are in place so we know if a specific order was requested.
	switch ( $orderby ) {
		case 'menu_order':
		case 'name_num':
			$defaults['orderby'] = $orderby;
			break;
	}

	return $defaults;
}
add_filter( 'get_terms_defaults', 'sbx_change_get_terms_defaults', 10, 2 );

/**
 * Adds support to get_terms for menu_order argument.
 *
 * @since 1.0.0
 * @param WP_Term_Query $terms_query Instance of WP_Term_Query.
 */
function sbx_change_pre_get_terms( $terms_query ) {
	$args = &$terms_query->query_vars;

	// Put back valid orderby values.
	if ( 'menu_order' === $args['orderby'] ) {
		$args['orderby']               = 'name';
		$args['force_menu_order_sort'] = true;
	}

	if ( 'name_num' === $args['orderby'] ) {
		$args['orderby']            = 'name';
		$args['force_numeric_name'] = true;
	}

	// When COUNTING, disable custom sorting.
	if ( 'count' === $args['fields'] ) {
		return;
	}

	// Support menu_order arg used in previous versions.
	if ( ! empty( $args['menu_order'] ) ) {
		$args['order']                 = 'DESC' === strtoupper( $args['menu_order'] ) ? 'DESC' : 'ASC';
		$args['force_menu_order_sort'] = true;
	}

	if ( ! empty( $args['force_menu_order_sort'] ) ) {
		$args['orderby']  = 'meta_value_num';
		$args['meta_key'] = 'order'; // phpcs:ignore
		$terms_query->meta_query->parse_query_vars( $args );
	}
}
add_action( 'pre_get_terms', 'sbx_change_pre_get_terms', 10, 1 );

/**
 * Adjust term query to handle custom sorting parameters.
 *
 * @param array $clauses    Clauses.
 * @param array $taxonomies Taxonomies.
 * @param array $args       Arguments.
 * @return array
 */
function sbx_terms_clauses( $clauses, $taxonomies, $args ) {
	global $wpdb;

	// No need to filter when counting.
	if ( strpos( $clauses['fields'], 'COUNT(*)' ) !== false ) {
		return $clauses;
	}

	// Force numeric sort if using name_num custom sorting param.
	if ( ! empty( $args['force_numeric_name'] ) ) {
		$clauses['orderby'] = str_replace( 'ORDER BY t.name', 'ORDER BY t.name+0', $clauses['orderby'] );
	}

	// For sorting, force left join in case order meta is missing.
	if ( ! empty( $args['force_menu_order_sort'] ) ) {
		$clauses['join']    = str_replace( "INNER JOIN {$wpdb->termmeta} ON ( t.term_id = {$wpdb->termmeta}.term_id )", "LEFT JOIN {$wpdb->termmeta} ON ( t.term_id = {$wpdb->termmeta}.term_id AND {$wpdb->termmeta}.meta_key='order')", $clauses['join'] );
		$clauses['where']   = str_replace( "{$wpdb->termmeta}.meta_key = 'order'", "( {$wpdb->termmeta}.meta_key = 'order' OR {$wpdb->termmeta}.meta_key IS NULL )", $clauses['where'] );
		$clauses['orderby'] = 'DESC' === $args['order'] ? str_replace( 'meta_value+0', 'meta_value+0 DESC, t.name', $clauses['orderby'] ) : str_replace( 'meta_value+0', 'meta_value+0 ASC, t.name', $clauses['orderby'] );
	}

	return $clauses;
}
add_filter( 'terms_clauses', 'sbx_terms_clauses', 99, 3 );

/**
 * Get the route service type term for a route by ID.
 *
 * @since  1.0.0
 * @param  int $route_id Route ID.
 * @return WP_Term[]
 */
function sbx_get_route_type_terms( ...$params ) {
	return sbx_get_the_terms( 'sbx_route_type', ...$params );
};

/**
 * Get the route namespace term for a route by ID.
 *
 * @since  1.0.0
 * @param  int $route_id Route ID.
 * @return WP_Term[]
 */
function sbx_get_namespace_terms( ...$params ) {
	return sbx_get_the_terms( 'sbx_route_namespace', ...$params );
}

/**
 * Get the route version term for a route by ID.
 *
 * @since  1.0.0
 * @param  int $route_id Route ID.
 * @return WP_Term[]
 */
function sbx_get_route_version_terms( ...$params ) {
	return sbx_get_the_terms( 'sbx_route_version', ...$params );
}

/**
 * Get the route service type ID for a route by ID.
 *
 * @since  1.0.0
 * @param  int $route_id Route ID.
 * @return int[]
 */
function sbx_get_route_type_term_ids( ...$params ) {
	return sbx_get_the_terms( 'sbx_route_type', 'term_id', ...$params );
};

/**
 * Get the route namespace ID for a route by ID.
 *
 * @since  1.0.0
 * @param  int $route_id Route ID.
 * @return int[]
 */
function sbx_get_namespace_term_ids( ...$params ) {
	return sbx_get_the_terms( 'sbx_route_namespace', 'term_id', ...$params );
}

/**
 * Get the route version ID for a route by ID.
 *
 * @since  1.0.0
 * @param  int $route_id Route ID.
 * @return int[]
 */
function sbx_get_version_term_ids( ...$params ) {
	return sbx_get_the_terms( 'sbx_route_version', 'term_id', ...$params );
}

/**
 * Get the route namespace ID for a route by ID.
 *
 * @since  1.0.0
 * @param  int $route_id Route ID.
 * @return int[]
 */
function sbx_get_namespace_term_slugs( ...$params ) {
	return sbx_get_the_terms( 'sbx_route_namespace', 'slug', ...$params );
}

/**
 * Get the route version ID for a route by ID.
 *
 * @since  1.0.0
 * @param  int $route_id Route ID.
 * @return int[]
 */
function sbx_get_version_term_slugs( ...$params ) {
	return sbx_get_the_terms( 'sbx_route_version', 'slug', ...$params );
}

/**
 * Get all rest route terms for a specific taxonomy.
 *
 * @since  1.0.0
 * @param  string       $taxonomy  Taxonomy slug.
 * @param  string       $field     Field name.
 * @param  string       $index_key Index key name.
 * @param  array|string $args      Term query args.
 * @param  bool|string  $nicename  Unprefix term slugs.
 * @return WP_Term[]
 */
function sbx_get_the_terms( $tax_name = 'sbx_route_namespace', $field = null, $index_key = null, $args = 'hide_empty=0', $nicename = 'use_nicename' ) {
	$nicename    = $nicename ? 'use_nicename' : null;
	$cache_group = 'sealed_box_terms-' . $tax_name;
	$cache_key   = sbx_get_cache_prefix( $cache_group ) . $nicename . $field . $index_key . '-' . md5( wp_json_encode( $args ) );
	$terms       = wp_cache_get( $cache_key, $cache_group );

	if ( false !== $terms ) {
		return $terms;
	}

	$terms = sbx_get_terms( $tax_name, $args );

	if ( ! $terms || is_wp_error( $terms ) ) {
		$terms = array();
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
 * Wrapper used to get terms.
 *
 * @param  string        $tax_name Taxonomy slug.
 * @param  array|string  $args     Query arguments.
 * @return WP_Term[]
 */
function sbx_get_terms( $tax_name, $args = array() ) {
	if ( ! taxonomy_exists( $tax_name ) ) {
		return array();
	}

	return apply_filters( 'sealed_box_get_service_route_terms', _sbx_get_cached_terms( $tax_name, $args ), $tax_name, $args );
}

/**
 * Get all rest route terms for a specific taxonomy.
 *
 * @since  1.0.0
 * @param  string       $taxonomy  Taxonomy slug.
 * @param  array|string $args      Query arguments.
 * @param  string       $field     Field name.
 * @param  string       $index_key Index key name.
 * @return WP_Term[]
 */
function _sbx_get_cached_terms( $tax_name, $args = array() ) {
	$cache_group = 'sealed_box_terms-' . $tax_name;
	$cache_key   = sbx_get_cache_prefix( $cache_group ) . 'get_terms-' . md5( wp_json_encode( $args ) );
	$terms       = wp_cache_get( $cache_key, $cache_group );

	if ( false !== $terms ) {
		return $terms;
	}

	$terms = get_terms( $tax_name, $args );

	if ( ! $terms || is_wp_error( $terms ) ) {
		$terms = array();
	}

	wp_cache_add( $cache_key, $terms, $cache_group );

	return $terms;
}

/**
 * Sort by name (numeric).
 *
 * @param  WP_Post $a First item to compare.
 * @param  WP_Post $b Second item to compare.
 * @return int
 */
function _sbx_get_service_route_terms_name_num_usort_callback( $a, $b ) {
	$a_name = (float) $a->name;
	$b_name = (float) $b->name;

	if ( abs( $a_name - $b_name ) < 0.001 ) {
		return 0;
	}

	return ( $a_name < $b_name ) ? -1 : 1;
}

/**
 * Sort by parent.
 *
 * @param  WP_Post $a First item to compare.
 * @param  WP_Post $b Second item to compare.
 * @return int
 */
function _sbx_get_service_route_terms_parent_usort_callback( $a, $b ) {
	if ( $a->parent === $b->parent ) {
		return 0;
	}
	return ( $a->parent < $b->parent ) ? 1 : -1;
}

/**
 * Sealed Box Dropdown taxonomies.
 *
 * @param array $args Args to control display of dropdown.
 */
function sbx_service_route_dropdown_taxonomies( $args = array() ) {
	global $wp_query;

	$args = wp_parse_args(
		$args,
		array(
			'pad_counts'         => 1,
			'show_count'         => 1,
			'hierarchical'       => 1,
			'hide_empty'         => 1,
			'show_uncategorized' => 1,
			'orderby'            => 'name',
			'selected'           => isset( $wp_query->query_vars['sbx_route_namespace'] ) ? $wp_query->query_vars['sbx_route_namespace'] : '',
			'show_option_none'   => __( 'Select a namespace', 'sealedbox' ),
			'option_none_value'  => '',
			'value_field'        => 'slug',
			'taxonomy'           => 'sbx_route_namespace',
			'name'               => 'sbx_route_namespace',
			'class'              => 'dropdown_sbx_route_namespace',
		)
	);

	if ( 'order' === $args['orderby'] ) {
		$args['orderby']  = 'meta_value_num';
		$args['meta_key'] = 'order'; // phpcs:ignore
	}

	wp_dropdown_categories( $args );
}

/**
 * Move a term before the a given element of its hierarchy level.
 *
 * @param int    $the_term Term ID.
 * @param int    $next_id  The id of the next sibling element in save hierarchy level.
 * @param string $taxonomy Taxnomy.
 * @param int    $index    Term index (default: 0).
 * @param mixed  $terms    List of terms. (default: null).
 * @return int
 */
function sbx_reorder_terms( $the_term, $next_id, $taxonomy, $index = 0, $terms = null ) {
	if ( ! $terms ) {
		$terms = get_terms( $taxonomy, 'hide_empty=0&parent=0&menu_order=ASC' );
	}
	if ( empty( $terms ) ) {
		return $index;
	}

	$id = intval( $the_term->term_id );

	$term_in_level = false; // Flag: is our term to order in this level of terms.

	foreach ( $terms as $term ) {
		$term_id = intval( $term->term_id );

		if ( $term_id === $id ) { // Our term to order, we skip.
			$term_in_level = true;
			continue; // Our term to order, we skip.
		}
		// the nextid of our term to order, lets move our term here.
		if ( null !== $next_id && $term_id === $next_id ) {
			$index++;
			$index = sbx_set_term_order( $id, $index, $taxonomy, true );
		}

		// Set order.
		$index++;
		$index = sbx_set_term_order( $term_id, $index, $taxonomy );

		/**
		 * After a term has had it's order set.
		*/
		do_action( 'sealed_box_after_set_term_order', $term, $index, $taxonomy );

		// If that term has children we walk through them.
		$children = get_terms( $taxonomy, "parent={$term_id}&hide_empty=0&menu_order=ASC" );
		if ( ! empty( $children ) ) {
			$index = sbx_reorder_terms( $the_term, $next_id, $taxonomy, $index, $children );
		}
	}

	// No nextid meaning our term is in last position.
	if ( $term_in_level && null === $next_id ) {
		$index = sbx_set_term_order( $id, $index + 1, $taxonomy, true );
	}

	return $index;
}

/**
 * Set the sort order of a term.
 *
 * @param int    $term_id   Term ID.
 * @param int    $index     Index.
 * @param string $taxonomy  Taxonomy.
 * @param bool   $recursive Recursive (default: false).
 * @return int
 */
function sbx_set_term_order( $term_id, $index, $taxonomy, $recursive = false ) {

	$term_id = (int) $term_id;
	$index   = (int) $index;

	update_term_meta( $term_id, 'order', $index );

	if ( ! $recursive ) {
		return $index;
	}

	$children = get_terms( $taxonomy, "parent=$term_id&hide_empty=0&menu_order=ASC" );

	foreach ( $children as $term ) {
		$index++;
		$index = sbx_set_term_order( $term->term_id, $index, $taxonomy, true );
	}

	clean_term_cache( $term_id, $taxonomy );

	return $index;
}

/**
 * Return rest routes in a given term, and cache value.
 *
 * @param int[]|int       $term_ids  Term ID(s).
 * @param string[]|string $taxonomies Taxonom(y|ies).
 * @return int[]
 */
function sbx_get_term_service_route_ids( $term_ids, $taxonomies ) {
	if ( ! is_array( $term_ids ) ) {
		$term_ids = array( $term_ids );
	}
	if ( ! is_array( $taxonomies ) ) {
		$taxonomies = array( $taxonomies );
	}

	$route_ids = array();
    foreach ( $term_ids as $term_id ) {
        $route_ids[ $term_id ] = get_term_meta( $term_id, 'rest_route_ids', true );

        if ( false === $route_ids[ $term_id ] || ! is_array( $route_ids[ $term_id ] ) ) {
            $route_ids[ $term_id ] = get_objects_in_term( $term_id, $taxonomies );
            update_term_meta( $term_id, 'rest_route_ids', $route_ids[ $term_id ] );
        }
    }
	// array_intersect( (array) array_shift( $route_ids ), (array) array_shift( $route_ids ) );
	return array_intersect( ...$route_ids );
	// return array_reduce( $route_ids, 'array_intersect', array() );

}
