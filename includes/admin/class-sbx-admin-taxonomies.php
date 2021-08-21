<?php
/**
 * Handles taxonomies in admin
 *
 *
 *
 * @version  1.0.0
 * @package  SealedBox/Admin
 */

defined( 'ABSPATH' ) || exit;

/**
 * Admin taxonomies class.
 */
class SBX_Admin_Taxonomies {

	/**
	 * Default service type ID.
	 *
	 * @var int
	 */
	private $default_type_id = 0;

	/**
	 * Default namespace ID.
	 *
	 * @var int
	 */
	private $default_namespace_id = 0;

	/**
	 * Default version ID.
	 *
	 * @var int
	 */
	private $default_version_id = 0;

    /**
     * @var boolean - whether to filter get_terms() or not
     */
    private $filter_terms = true;

    /**
     * @var boolean - whether to print Nonce or not
     */
    private $use_nonce = true;

	/**
	 * Get class instance
	 */
	public static function get_instance() {
		static $admin_taxonomies = null;

        if ( null === $admin_taxonomies ) {
            $admin_taxonomies = new SBX_Admin_Taxonomies();
		}

        return $admin_taxonomies;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {

		// Default service type ID.
		$this->default_type_id = absint( get_option( 'default_sbx_route_type', 0 ) );

		// Default namespace ID.
		$this->default_namespace_id = absint( get_option( 'default_sbx_route_namespace', 0 ) );

		// Default version ID.
		$this->default_version_id = absint( get_option( 'default_sbx_route_version', 0 ) );
		// update_option( 'default_sbx_route_namespace', 0 );

        // Generate initial terms.
        add_action( 'init', array( $this, 'init_default_terms' ), 10 );

		// Default service type terms.
		add_filter( 'sbx_route_type_default_terms', array( $this, 'route_type_default_terms' ), 10, 1 );

		// Default namespace terms.
		add_filter( 'sbx_route_namespace_default_terms', array( $this, 'route_namespace_default_terms' ), 10, 1 );

		// Default version terms.
		add_filter( 'sbx_route_version_default_terms', array( $this, 'route_version_default_terms' ), 10, 1 );

        // Remove taxonomy metaboxes.
        add_action( 'admin_menu', array( $this, 'remove_meta_box' ) );

		// Never save more than 1 term
		add_action( 'save_post', array( $this, 'save_single_term' ) );

		// Namespace/term ordering.
		add_action( 'create_term', array( $this, 'create_term' ), 5, 3 );

		// Fallback default namespace term.
		add_action( "delete_sbx_route_namespace", array( $this, 'assign_default_route_namespace_term' ), 10, 4 );

		// Add columns.
		add_filter( 'manage_edit-sbx_route_namespace_columns', array( $this, 'route_namespace_columns' ) );
		add_filter( 'manage_sbx_route_namespace_custom_column', array( $this, 'route_namespace_column' ), 10, 3 );

		// Add row actions.
		add_filter( 'sbx_route_namespace_row_actions', array( $this, 'route_namespace_row_actions' ), 10, 2 );
		add_filter( 'admin_init', array( $this, 'handle_route_namespace_row_actions' ) );

		// Taxonomy page descriptions.
		add_action( 'sbx_route_namespace_pre_add_form', array( $this, 'route_namespace_description' ) );
		add_action( 'after-sbx_route_namespace-table', array( $this, 'route_namespace_notes' ) );

		// Maintain hierarchy of terms.
		add_filter( 'wp_terms_checklist_args', array( $this, 'disable_checked_ontop' ) );

		// Admin footer scripts for this route namespaces admin screen.
        add_action( 'admin_footer', array( $this, 'scripts_at_route_namespace_screen_footer' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ) );

		// Hack global taxonomy to switch all radio taxonomies to hierarchical on edit screen
		add_action( 'load-edit.php', array( $this, 'make_hierarchical' ) );
		add_action( 'wp_ajax_inline-save', array( $this, 'make_hierarchical' ), 0 );

		// Add nonce to post edit/quick edit/bulk edit
		add_action( 'quick_edit_custom_box', array( $this, 'rest_route_edit_nonce' ) );
		// add_action( 'sealed_box_endpoint_service_route_data', array( $this, 'rest_route_edit_nonce' ) );

		// Add ajax callback for adding a non-hierarchical term
		// add_action( 'wp_ajax_add-sbx_route_type', array( __CLASS__, 'add_non_hierarchical_term' ), 5 );
		add_action( 'wp_ajax_add-sbx_route_namespace', array( __CLASS__, 'add_route_namespace_term' ), 5 );
		add_action( 'wp_ajax_add-sbx_route_version', array( __CLASS__, 'add_route_version_term' ), 5 );

		// change checkboxes to radios & trigger get_terms() filter
		add_filter( 'wp_terms_checklist_args', array( $this, 'filter_terms_checklist_args' ) );

		// Add default term
		add_filter( 'default_content', array( $this, 'default_content' ), 12, 2 );

		// Filter slugs in edit tag form - remove taxonomy prefix
		add_filter( 'editable_slug', array( __CLASS__, 'get_editable_slug' ), 10, 2 );

		// Filter slug before update db - add taxonomy prefix
		add_filter( 'pre_sbx_route_namespace_slug', array( __CLASS__, 'pre_taxonomy_slug' ), 10, 1 );
    }

    /**
     * Add the default terms for Sealed Box route taxonomies.
	 *
	 * @since 1.0.0
	 * @return array
     */
    public function init_default_terms() {

		foreach ( array( 'type', 'namespace', 'version' ) as $tax_key ) {
			$tax_name        = "sbx_route_{$tax_key}";
			$default_term_id = "default_{$tax_key}_id";

			$terms           = array();
			$default_terms   = apply_filters( "{$tax_name}_default_terms", array() );

			foreach ( (array) $default_terms as $term_args ) {
				$term_slug         = sanitize_title_with_dashes( trim( $term_args['name'] ) );
				$term_args['slug'] = self::pre_taxonomy_slug( $term_slug, $tax_name );
				$terms[]           = self::add_term( $term_args['name'], $tax_name, $term_args );
			}

			if ( ! term_exists( $this->$default_term_id, $tax_name ) ) {
				$current_terms = get_terms( array(
					'taxonomy'   => $tax_name,
					'meta_key'   => 'order',
					'orderby'    => 'meta_value_num',
					'hide_empty' => 0,
					'number'     => 1
				) );

				if ( empty( $current_terms ) || is_wp_error( $current_terms ) ) {
					$current_terms = (array) $terms;
				}

				$default_term           = current( $current_terms );
				$this->$default_term_id = is_object( $default_term ) ? $default_term->term_id : 0;

				update_option( "default_{$tax_name}", $this->$default_term_id );
			}
		}
    }

    /**
     * Get the default terms for route namespace taxonomy.
	 *
	 * @since 1.0.0
	 * @return array
     */
    public function route_namespace_default_terms( $default_terms = array() ) {
		$default_terms[] = array(
			'name'        => 'SealedBox',
			'description' => _x( 'Namespace for your Sealed Box REST API routes.', 'REST namespace description ', 'sealedbox' ),
		);

		return $default_terms;
	}

    /**
     * Get the default terms for route version taxonomy.
	 *
	 * @since 1.0.0
	 * @return array
     */
    public function route_version_default_terms( $default_terms = array() ) {
		$default_terms[] = array(
			'name'        => 'v1',
			'description' => _x( 'Version for your Sealed Box REST API routes.', 'REST version description ', 'sealedbox' ),
		);

		return $default_terms;
	}

    /**
     * Get the default terms for route namespace taxonomy.
	 *
	 * @since 1.0.0
	 * @return array
     */
    public function route_type_default_terms( $default_terms = array() ) {
		foreach( (array) SealedBox()->services as $service ) {
			$default_terms[] = $service->get_info();
		}

		return $default_terms;
	}

    /**
     * Remove the default metabox
     *
     * @access public
     * @return  void
     * @since 1.0.0
     */
    public function remove_meta_box() {
		remove_meta_box( 'tagsdiv-sbx_route_type', 'sbx_service_route', 'side' );
        remove_meta_box( 'tagsdiv-sbx_route_namespace', 'sbx_service_route', 'side' );
        remove_meta_box( 'tagsdiv-sbx_route_version', 'sbx_route_version', 'side' );
    }


	/**
	 * Order term when created (put in position 0).
	 *
	 * @param mixed  $term_id Term ID.
	 * @param mixed  $tt_id Term taxonomy ID.
	 * @param string $taxonomy Taxonomy slug.
	 */
	public function create_term( $term_id, $tt_id = '', $taxonomy = '' ) {
		if ( 'sbx_route_namespace' !== $taxonomy ) {

			return;
		}

		update_term_meta( $term_id, 'order', 0 );
	}

	/**
	 * Description for sbx_route_namespace page to aid users.
	 */
	public function route_namespace_description() {
		echo wp_kses(
			wpautop( __( 'Route namespaces for your REST API can be managed here. To see more namespaces listed click the "screen options" link at the top-right of this page.', 'sealedbox' ) ),
			array( 'p' => array() )
		);
	}

	/**
	 * Add some notes to describe the behavior of the default namespace.
	 */
	public function route_namespace_notes() {
		$namespace      = get_term( $this->default_namespace_id, 'sbx_route_namespace' );
		$namespace_name = ( ! $namespace || is_wp_error( $namespace ) ) ? _x( 'Uncategorized', 'Default namespace slug', 'sealedbox' ) : $namespace->name;
		?>
		<div class="form-wrap edit-term-notes">
			<p>
				<strong><?php esc_html_e( 'Note:', 'sealedbox' ); ?></strong><br>
				<?php
					printf(
						/* translators: %s: default namespace */
						esc_html__( 'Deleting a namespace does not delete the routes in that namespace. Instead, routes that were only assigned to the deleted namespace are set to the namespace %s.', 'sealedbox' ),
						'<strong>' . esc_html( $namespace_name ) . '</strong>'
					);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Thumbnail column added to namespace admin.
	 *
	 * @param mixed $columns Columns array.
	 * @return array
	 */
	public function route_namespace_columns( $columns ) {
		$columns['versions'] = 'Versions';

		return $columns;
	}

	/**
	 * Adjust row actions.
	 *
	 * @param array  $actions Array of actions.
	 * @param object $term Term object.
	 * @return array
	 */
	public function route_namespace_row_actions( $actions = array(), $term = null ) {
		if ( $this->default_namespace_id !== $term->term_id && current_user_can( 'edit_term', $term->term_id ) ) {
			$actions['make_default'] = sprintf(
				'<a href="%s" aria-label="%s">%s</a>',
				wp_nonce_url( 'edit-tags.php?action=make_default&amp;taxonomy=sbx_route_namespace&amp;post_type=sbx_service_route&amp;tag_ID=' . absint( $term->term_id ), 'make_default_' . absint( $term->term_id ) ),
				/* translators: %s: taxonomy term name */
				esc_attr( sprintf( __( 'Make &#8220;%s&#8221; the default namespace', 'sealedbox' ), $term->name ) ),
				__( 'Make default', 'sealedbox' )
			);
		}

		return $actions;
	}

	/**
	 * Handle custom row actions.
	 */
	public function handle_route_namespace_row_actions() {
		if ( isset( $_GET['action'], $_GET['tag_ID'], $_GET['_wpnonce'] ) && 'make_default' === $_GET['action'] ) { // WPCS: CSRF ok, input var ok.
			$make_default_id = absint( $_GET['tag_ID'] ); // WPCS: Input var ok.

			if ( wp_verify_nonce( $_GET['_wpnonce'], 'make_default_' . $make_default_id ) && current_user_can( 'edit_term', $make_default_id ) ) { // WPCS: Sanitization ok, input var ok, CSRF ok.
				$this->default_namespace_id = $make_default_id;
				update_option( 'default_sbx_route_namespace', $make_default_id );
			}
		}
	}

	/**
	 * Thumbnail column value added to namespace admin.
	 *
	 * @param string $columns Column HTML output.
	 * @param string $column Column name.
	 * @param int    $id Route ID.
	 *
	 * @return string
	 */
	public function route_namespace_column( $columns, $column, $id ) {
		if ( 'cb' === $column ) {
            // Prepend tooltip for default namespace.
			// if ( $this->default_namespace_id === $id ) {
			// 	$columns = '<span title="' . __( 'This is the default namespace and it cannot be deleted. It will be automatically assigned to routes with no namespace.', 'sealedbox' ) . '"></span>';
			// }
		}

		if ( 'handle' === $column ) {
			// $columns .= '<input type="hidden" name="term_id" value="' . esc_attr( $id ) . '" />';
		}

		if ( 'versions' === $column ) {
			// $columns .= '<input type="hidden" name="term_id" value="' . esc_attr( $id ) . '" />';
		}

		return $columns;
	}

	/**
	 * Maintain term hierarchy when editing a route.
	 *
	 * @param  array $args Term checklist args.
	 * @return array
	 */
	public function disable_checked_ontop( $args ) {
		if ( ! empty( $args['taxonomy'] ) && 'sbx_route_namespace' === $args['taxonomy'] ) {
			$args['checked_ontop'] = false;
		}

		return $args;
	}

	/**
	 * Admin footer scripts for the route namespaces admin screen
	 *
	 * @return void
	 */
	public function scripts_at_route_namespace_screen_footer() {
		if ( ! isset( $_GET['taxonomy'] ) || 'sbx_route_namespace' !== $_GET['taxonomy'] ) { // WPCS: CSRF ok, input var ok.

			return;
		}
		// Ensure the tooltip is displayed when the image column is disabled on route namespaces.
		sbx_enqueue_js(
			"(function( $ ) {
				'use strict';
				$( 'tr#tag-" . absint( $this->default_namespace_id ) . " > th' ).html( '<center><span class=\"dashicons dashicons-nametag tips\" data-tip=\"" . esc_attr__( 'This is the default namespace and it cannot be deleted. It will be automatically assigned to routes with no namespace.', 'sealedbox' ) . "\"></span></center>' );
			})( jQuery );"
		);
    }

	/**
	 * Get rest route term.
	 *
	 * @param object|WP_Post $post
	 * @param string         $field
	 * @return string
	 */
	public function get_the_term( $post, $field = null ) {
		$term  = get_term( $this->default_namespace_id, 'sbx_route_namespace' );
		$terms = get_the_terms( $post->ID, 'sbx_route_namespace' );

		if ( is_array( $terms ) && ! empty( $terms ) ) {
			$term = reset( $terms );
		}

		return is_null( $field ) ? $term : $term->$field;
	}



	/**
	 * Get the editable slug.
	 *
	 * @param string $slug
	 * @param object $term
	 * @return string
	 */
	public static function get_editable_slug( $slug, $term ) {
		if ( isset( $term->taxonomy ) && in_array( $term->taxonomy, array( 'sbx_route_type', 'sbx_route_namespace', 'sbx_route_version' ) ) ) {
			$slug = sbx_get_term_slug( $slug, $term->taxonomy );
		}

		return $slug;
	}


	/**
	 * Edit taxonomy slug.
	 *
	 * @param string $slug
	 * @param string $tax_name
	 * @return string
	 */
	public static function pre_taxonomy_slug( $slug, string $tax_name = 'sbx_route_namespace' ) {
		if ( isset( $tax_name ) && in_array( $tax_name, array( 'sbx_route_type', 'sbx_route_namespace', 'sbx_route_version' ) ) ) {
			$slug = "{$tax_name}-" . sbx_get_term_slug( $slug, $tax_name );
		}

		return $slug;
	}


	/**
	 * Output term dropdown for the post.
	 *
	 * @param string|null $object_type post type.
	 * @return boolean|array
	 */
	public function get_field( string $object_type = null ) {
		global $post_id;

		$post = get_post( $post_id );
		$object_type = sealed_box_unslug( $object_type ?? $post->post_type ?? '' );

		if ( empty( $object_type ) || ! array_key_exists( $object_type, $this->fields ) ) {

			return false;
		}

		$field = (array) $this->fields[ $object_type ];

		foreach ( array( 'id', 'name', 'label', 'class', 'wrapper_class' ) as $key ) {

			if ( is_array( $field[ $key ] ) && 1 < count( $field[ $key ] ) ) {
				$argument = $field[ $key ];
				$format = array_shift( $argument );

				foreach ( $argument as &$property ) {
					$property = (array) $this->$property ?? '';
				}

				$field[ $key ] = vsprintf( $format, $argument );
			}
		}

		$terms = sbx_get_terms('sbx_route_namespace', array( 'use_nicename' => true ) );

		$default = array(
			'class'         => 'select short',
			'wrapper_class' => 'col-1',
			'id'            => $this->names['meta'],
			'label'         => $this->taxonomy->label,
			'value'         => $this->get_the_term( $post, 'term_id' ),
			'desc_tip'      => false,
			'options'       => wp_list_pluck( $terms, 'name', 'slug' ),
			'data'          => array(
				'description' => wp_list_pluck( $terms, 'description', 'term_id' ),
			),
			'taxonomy'      => $this->taxonomy,
			'field_type'    => 'select',
		);

		$field = wp_parse_args( (array) $field, $default );

		return $field;
	}

	/**
	 * Define our custom Walker.
	 * Tell checklist function to use our new Walker.
	 *
	 * @access public
	 * @param  array $args
	 * @return array
	 * @since 1.1.0
	 */
	public function filter_terms_checklist_args( $args ) {
		if ( isset( $args['taxonomy'] ) && in_array( $args['taxonomy'], array( 'sbx_route_type', 'sbx_route_namespace', 'sbx_route_version' ) ) ) {

			$this->set_terms_filter( true );

			// Add a filter to get_terms() but only for radio lists
			add_filter( 'get_terms', array( $this, 'filter_get_terms' ), 10, 3 );

			if ( ! class_exists( 'SBX_Term_Radio_Walker' ) ) {
				include_once SBX_ABSPATH . '/includes/walkers/class-sbx-term-radio-walker.php';
			}

			$args['walker'] = new SBX_Term_Radio_Walker;
		}

		return $args;
	}


	/**
	 * Add new 0 or null term in metabox and quickedit
	 * this will allow users to "undo" a term if the taxonomy is not required
	 *
	 * @param  array $terms
	 * @param  array $taxonomies
	 * @param  array $args
	 * @return array
	 * @since 1.4
	 */
	public function filter_get_terms( $terms, $taxonomies, $args ){

		// only filter terms for radio taxes (except category) and only in the checkbox - need to check $args b/c get_terms() is called multiple times in wp_terms_checklist()
		if ( ! in_array( 'category', (array) $taxonomies ) && isset( $args['fields'] ) && $args['fields'] == 'all' && $this->get_terms_filter()
			&& in_array( 'sbx_route_type', (array) $taxonomies ) || in_array( 'sbx_route_namespace', (array) $taxonomies ) || in_array( 'sbx_route_version', (array) $taxonomies ) ) {

			// remove filter after 1st run
			remove_filter( current_filter(), __FUNCTION__, 10, 3 );

			// turn the switch OFF
			$this->set_terms_filter( false );

			// $no_term = get_term_by( 'name', reset($this->entries), 'sbx_route_namespace' );

			// array_push( $terms, $no_term );

		}

		return $terms;
	}


	/**
	 * Only ever save a single term
	 *
	 * @param  int $post_id
	 * @return int
	 * @since 1.1.0
	 */
	public function save_single_term( $post_id ) {

		// verify if this is an auto save routine. If it is our form has not been submitted, so we dont want to do anything
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {

			return $post_id;
		}

		// prevent weirdness with multisite
		if ( function_exists( 'ms_is_switched' ) && ms_is_switched() ) {

			return $post_id;
		}

		// make sure we're on a supported post type
		if ( isset( $_REQUEST['post_type'] ) && 'sbx_service_route' !== $_REQUEST['post_type'] ) {

			return $post_id;
		}

		// verify nonce
		if ( isset( $_POST['_radio_nonce-sbx_route_type'] ) && wp_verify_nonce( $_REQUEST['_radio_nonce-sbx_route_type'], 'radio_nonce-sbx_route_type' ) ) {

            // OK, we must be authenticated by now: we need to find and save the data
			$term_id = absint( $_REQUEST['radio_tax_input']['sbx_route_type'][0] ?? $this->default_type_id );

			// set the single terms
			if ( current_user_can( 'edit_posts' ) ) {
				wp_set_object_terms( $post_id, $term_id, 'sbx_route_type');
			}
        }

		// verify nonce
		if ( isset( $_POST['_radio_nonce-sbx_route_namespace'] ) && wp_verify_nonce( $_REQUEST['_radio_nonce-sbx_route_namespace'], 'radio_nonce-sbx_route_namespace' ) ) {

            // OK, we must be authenticated by now: we need to find and save the data
			$term_id = absint( $_REQUEST['radio_tax_input']['sbx_route_namespace'][0] ?? $this->default_namespace_id );

			// set the single terms
			if ( current_user_can( 'assign_categories' ) ) {
				wp_set_object_terms( $post_id, $term_id, 'sbx_route_namespace');
			}
        }

		// verify nonce
		if ( isset( $_POST['_radio_nonce-sbx_route_version'] ) && wp_verify_nonce( $_REQUEST['_radio_nonce-sbx_route_version'], 'radio_nonce-sbx_route_version' ) ) {

            // OK, we must be authenticated by now: we need to find and save the data
			$term_id = absint( $_REQUEST['radio_tax_input']['sbx_route_version'][0] ?? $this->default_version_id );

			// set the single terms
			if ( current_user_can( 'assign_categories' ) ) {
				wp_set_object_terms( $post_id, $term_id, 'sbx_route_version');
			}
        }

		return $post_id;
	}


	/**
	 * Use this action to switch all radio taxonomies to hierarchical on edit.php
	 * at the moment, there is no filter, so we have to hack the global variable
	 *
	 * @param  array $columns
	 * @return array
	 * @since 1.7.0
	 */
	public function make_hierarchical() {
		global $wp_taxonomies;
		$wp_taxonomies['sbx_route_type']->hierarchical = true;
		$wp_taxonomies['sbx_route_namespace']->hierarchical = true;
		$wp_taxonomies['sbx_route_version']->hierarchical = true;
	}


	/**
	 * Add nonces to quick edit and bulk edit
	 *
	 * @return HTML
	 * @since 1.7.0
	 */
	public function rest_route_edit_nonce() {
		if ( $this->use_nonce ) {
			$this->use_nonce = false;
			// wp_enqueue_script( 'sbx-term-input' );
			SBX_Admin_Taxonomies::admin_enqueue_scripts();
			wp_nonce_field( 'radio_nonce-sbx_route_type', '_radio_nonce-sbx_route_type' );
			wp_nonce_field( 'radio_nonce-sbx_route_namespace', '_radio_nonce-sbx_route_namespace' );
			wp_nonce_field( 'radio_nonce-sbx_route_version', '_radio_nonce-sbx_route_version' );
		}
	}

	/**
	 * Fallback to default when term is deleted.
	 *
	 * @param int     $term_id      Term ID.
	 * @param int     $tt_id        Term taxonomy ID.
	 * @param mixed   $deleted_term Copy of the deleted term, WP_Error otherwise.
	 * @param array   $post_ids     List of term route IDs.
	 * @return void
	 */
	public function assign_default_route_namespace_term( $term_id, $tt_id, $deleted_term, $post_ids ) {
		if ( empty( $term_id ) || empty( $post_ids ) || is_wp_error( $deleted_term ) ) {

			return;
		}
		foreach( $post_ids as $post_id ) {
			wp_set_object_terms( $post_id, $this->default_namespace_id, 'sbx_route_namespace' );
		}
	}

	/**
	 * Set values for new post
	 *
	 * @param string $post_content
	 * @param object $post
	 * @return string
	 */
	public function default_content( $post_content, $post ) {
		if ( isset( $post->post_type ) && 'sbx_service_route' === $post->post_type ) {
			wp_set_object_terms( $post->ID, $this->default_type_id, 'sbx_route_type' );
			wp_set_object_terms( $post->ID, $this->default_namespace_id, 'sbx_route_namespace' );
			wp_set_object_terms( $post->ID, $this->default_version_id, 'sbx_route_version' );
		}

		return $post_content;
	}


	/**
	 * Turn on/off the terms filter.
	 *
	 * Only filter get_terms() in the wp_terms_checklist() function
	 *
	 * @access public
	 * @param  bool $filter_terms
	 * @return bool
	 * @since 1.7.0
	 */
	private function set_terms_filter( $filter_terms = true ) {
		$this->filter_terms = (bool) $filter_terms;
	}


	/**
	 * Only filter get_terms() in the wp_terms_checklist() function
	 *
	 * @access public
	 * @return bool
	 * @since 1.7.0
	 */
	private function get_terms_filter() {
		// give users a chance to disable the no term feature
		return apply_filters( 'radio_buttons_for_taxonomies_no_term_sbx_route_namespace', $this->filter_terms );
	}


	/**
	 * Add a new term to the database if it does not already exist.
	 *
	 * @param string $term_name
	 * @param string $taxonomy The taxonomy for which to add the term.
	 * @param array  $term_args
	 * @return array|WP_Error
	 * @since 1.0
	 */
	public static function add_term( $term_name, string $taxonomy, $term_args = array() ) {
		$term = term_exists( $term_name, $taxonomy );

		if ( ! $term && ! empty( $term_name ) && ! empty( $taxonomy ) ) {
			$term_slug     = sanitize_title_with_dashes( trim( $term_name ) );
			$term_nicename = sanitize_title_with_dashes( $term_slug );

			if ( '' === $term_nicename ) {
				return $term;
			}

			$default_args = array(
				'name'     => $term_name,
				'slug'     => self::pre_taxonomy_slug( $term_slug, $taxonomy ),
				'taxonomy' => $taxonomy
			);
			$term_args = apply_filters( "add_{$taxonomy}_term_args", wp_parse_args( $term_args, $default_args ) );
			$term      = wp_insert_term( $term_args['name'], $term_args['taxonomy'], $term_args );
		}

		if ( is_array( $term ) && ! empty( $taxonomy ) ) {
			$term = get_term( $term['term_id'], $taxonomy );
		}

		return $term;
	}


	/**
	 * Callback to set up the metabox
	 * Mimicks the traditional hierarchical term metabox, but modified with our nonces
	 *
	 * @access public
	 * @param  object $post
	 * @param  array  $box
	 * @return  print HTML
	 * @since 1.0.0
	 */
	public static function radio_input_metabox( $post, $box ) {

		wp_enqueue_script( 'sbx-term-input' );

		$defaults = array( 'taxonomy' => 'category' );
		if ( ! isset( $box['args'] ) || ! is_array( $box['args'] ) ) {
			$args = array();
		} else {
			$args = $box['args'];
		}

		$args     = wp_parse_args( $args, $defaults );
		$tax_name = esc_attr( $args['taxonomy'] );
		$taxonomy = get_taxonomy( $args['taxonomy'] );;

		wp_nonce_field( 'radio_nonce-' . $tax_name, '_radio_nonce-' . $tax_name );

		?>
		<div id="taxonomy-<?php echo esc_attr( $tax_name ); ?>" class="radio-buttons-for-taxonomies categorydiv">
			<div id="<?php echo esc_attr( $tax_name ); ?>-all" class="tabs-panel">
				<ul id="<?php echo esc_attr( $tax_name ); ?>checklist" data-wp-lists="list:<?php echo esc_attr( $tax_name ); ?>" class="categorychecklist form-no-clear">
					<?php wp_terms_checklist( $post->ID, array( 'taxonomy' => $tax_name, 'selected_cats' => array( Box_Pair_Taxonomies::get_instance( $taxonomy->name )->get_the_term( $post, 'term_id' ) ) ) ); ?>
				</ul>
			</div>

		<?php if ( current_user_can( $taxonomy->cap->edit_terms ) ) : ?>

			<div id="<?php echo esc_attr( $tax_name ); ?>-adder" class="wp-hidden-children">
				<a id="<?php echo esc_attr( $tax_name ); ?>-add-toggle" href="#<?php echo esc_attr( $tax_name ); ?>-add" class="hide-if-no-js taxonomy-add-new">
					<?php
						/* translators: %s: add new taxonomy label */
						printf( __( '+ %s' ), $taxonomy->labels->add_new_item );
					?>
				</a>
				<p id="<?php echo esc_attr( $tax_name ); ?>-add" class="category-add wp-hidden-child">
					<label class="screen-reader-text" for="new<?php echo esc_attr( $tax_name ); ?>"><?php echo $taxonomy->labels->add_new_item; ?></label>
					<input type="text" name="new<?php echo esc_attr( $tax_name ); ?>" id="new<?php echo esc_attr( $tax_name ); ?>" class="form-required form-input-tip" value="<?php echo esc_attr( $taxonomy->labels->new_item_name ); ?>" aria-required="true"/>
					<label class="screen-reader-text" for="new<?php echo esc_attr( $tax_name ); ?>_parent">
						<?php echo esc_html( $taxonomy->labels->parent_item_colon ); ?>
					</label>
					<?php

					// Only add parent option for hierarchical taxonomies.
					if ( is_taxonomy_hierarchical( $tax_name ) ) :

						$parent_dropdown_args = array(
							'taxonomy'         => $tax_name,
							'hide_empty'       => 0,
							'name'             => 'new' . $tax_name . '_parent',
							'orderby'          => 'name',
							'hierarchical'     => 1,
							'show_option_none' => '&mdash; ' . $taxonomy->labels->parent_item . ' &mdash;',
						);

						/**
						 * Filters the arguments for the taxonomy parent dropdown on the Post Edit page.
						 *
						 * @since 4.4.0
						 *
						 * @param array $parent_dropdown_args {
						 *     Optional. Array of arguments to generate parent dropdown.
						 *
						 *     @type string   $taxonomy         Name of the taxonomy to retrieve.
						 *     @type bool     $hide_if_empty    True to skip generating markup if no
						 *                                      categories are found. Default 0.
						 *     @type string   $name             Value for the 'name' attribute
						 *                                      of the select element.
						 *                                      Default "new{$tax_name}_parent".
						 *     @type string   $orderby          Which column to use for ordering
						 *                                      terms. Default 'name'.
						 *     @type bool|int $hierarchical     Whether to traverse the taxonomy
						 *                                      hierarchy. Default 1.
						 *     @type string   $show_option_none Text to display for the "none" option.
						 *                                      Default "&mdash; {$parent} &mdash;",
						 *                                      where `$parent` is 'parent_item'
						 *                                      taxonomy label.
						 * }
						 */
						$parent_dropdown_args = apply_filters( 'post_edit_category_parent_dropdown_args', $parent_dropdown_args );

						wp_dropdown_categories( $parent_dropdown_args );

					endif;

					?>
					<input type="button" id="<?php echo esc_attr( $tax_name ); ?>-add-submit" data-wp-lists="add:<?php echo esc_attr( $tax_name ); ?>checklist:<?php echo esc_attr( $tax_name ); ?>-add" class="button category-add-submit" value="<?php echo esc_attr( $taxonomy->labels->add_new_item ); ?>" />
					<?php wp_nonce_field( 'add-' . $tax_name, '_ajax_nonce-add-' . $tax_name, false ); ?>
					<span id="<?php echo esc_attr( $tax_name ); ?>-ajax-response"></span>
				</p>
			</div>
		<?php endif; ?>
		</div>
	<?php
	}


	/**
	 * Callback to set up the metabox
	 * Mimicks the traditional hierarchical term metabox, but modified with our nonces
	 *
	 * @access public
	 * @param  object $post
	 * @param  array  $box
	 * @return  print HTML
	 * @since 1.0.0
	 */
	public static function option_input_metabox( $post, $box ) {

		wp_enqueue_script( 'sbx-term-input' );

		$defaults = array( 'taxonomy' => 'category' );
		if ( ! isset( $box['args'] ) || ! is_array( $box['args'] ) ) {
			$args = array();
		} else {
			$args = $box['args'];
		}

		$args     = wp_parse_args( $args, $defaults );
		$tax_name = esc_attr( $args['taxonomy'] );
		$taxonomy = get_taxonomy( $tax_name );

		wp_nonce_field( 'radio_nonce-' . $tax_name, '_radio_nonce-' . $tax_name );

		?>
		<div id="taxonomy-<?php echo esc_attr( $tax_name ); ?>" class="option-buttons-for-taxonomies categorydiv">
			<div id="<?php echo esc_attr( $tax_name ); ?>-all">
				<?php
				Box_Pair_Taxonomies::get_instance( $tax_name )->the_term_dropdown(
					$post,
					array(
						'id'       => "{$tax_name}checklist",
						'name'     => "option_{$tax_name}_select",
						'class'    => 'categorychecklist form-no-clear',
						'selected' => Box_Pair_Taxonomies::get_instance( $tax_name )->get_the_term( $post, 'term_id' ),
						'required' => true,
					)
				);
				?>
			</div>

		<?php if ( current_user_can( $taxonomy->cap->edit_terms ) ) : ?>

			<div id="<?php echo esc_attr( $tax_name ); ?>-adder" class="wp-hidden-children">
				<a id="<?php echo esc_attr( $tax_name ); ?>-add-toggle" href="#<?php echo esc_attr( $tax_name ); ?>-add" class="hide-if-no-js taxonomy-add-new">
					<?php
						/* translators: %s: add new taxonomy label */
						printf( __( '+ %s' ), $taxonomy->labels->add_new_item );
					?>
				</a>
				<p id="<?php echo esc_attr( $tax_name ); ?>-add" class="category-add wp-hidden-child">
					<label class="screen-reader-text" for="new<?php echo esc_attr( $tax_name ); ?>"><?php echo $taxonomy->labels->add_new_item; ?></label>
					<input type="text" name="new<?php echo esc_attr( $tax_name ); ?>" id="new<?php echo esc_attr( $tax_name ); ?>" class="form-required form-input-tip" value="<?php echo esc_attr( $taxonomy->labels->new_item_name ); ?>" aria-required="true"/>
					<label class="screen-reader-text" for="new<?php echo esc_attr( $tax_name ); ?>_parent">
						<?php echo esc_html( $taxonomy->labels->parent_item_colon ); ?>
					</label>
					<?php

					// Only add parent option for hierarchical taxonomies.
					if ( is_taxonomy_hierarchical( $tax_name ) ) :

						$parent_dropdown_args = array(
							'taxonomy'         => $tax_name,
							'hide_empty'       => 0,
							'name'             => 'new' . $tax_name . '_parent',
							'orderby'          => 'name',
							'hierarchical'     => 1,
							'show_option_none' => '&mdash; ' . $taxonomy->labels->parent_item . ' &mdash;',
						);

						/**
						 * Filters the arguments for the taxonomy parent dropdown on the Post Edit page.
						 *
						 * @since 4.4.0
						 *
						 * @param array $parent_dropdown_args {
						 *     Optional. Array of arguments to generate parent dropdown.
						 *
						 *     @type string   $taxonomy         Name of the taxonomy to retrieve.
						 *     @type bool     $hide_if_empty    True to skip generating markup if no
						 *                                      categories are found. Default 0.
						 *     @type string   $name             Value for the 'name' attribute
						 *                                      of the select element.
						 *                                      Default "new{$tax_name}_parent".
						 *     @type string   $orderby          Which column to use for ordering
						 *                                      terms. Default 'name'.
						 *     @type bool|int $hierarchical     Whether to traverse the taxonomy
						 *                                      hierarchy. Default 1.
						 *     @type string   $show_option_none Text to display for the "none" option.
						 *                                      Default "&mdash; {$parent} &mdash;",
						 *                                      where `$parent` is 'parent_item'
						 *                                      taxonomy label.
						 * }
						 */
						$parent_dropdown_args = apply_filters( 'post_edit_category_parent_dropdown_args', $parent_dropdown_args );

						wp_dropdown_categories( $parent_dropdown_args );

					endif;

					?>
					<input type="button" id="<?php echo esc_attr( $tax_name ); ?>-add-submit" data-wp-lists="add:<?php echo esc_attr( $tax_name ); ?>checklist:<?php echo esc_attr( $tax_name ); ?>-add" class="button category-add-submit" value="<?php echo esc_attr( $taxonomy->labels->add_new_item ); ?>" />
					<?php wp_nonce_field( 'add-' . $tax_name, '_ajax_nonce-add-' . $tax_name, false ); ?>
					<span id="<?php echo esc_attr( $tax_name ); ?>-ajax-response"></span>
				</p>
			</div>
		<?php endif; ?>
		</div>
		<?php
	}


	/**
	 * Add new term from metabox
	 * Mimics _wp_ajax_add_hierarchical_term() but modified for non-hierarchical terms
	 *
	 * @return data for WP_Lists script
	 */
	public static function add_route_namespace_term() {
		$namespace = 'sbx_route_namespace';
		$action    = sbx_get_post_data_by_key( 'action', '' );

		check_ajax_referer( $action, "_ajax_nonce-add-{$namespace}" );

		if ( ! current_user_can( 'manage_categories' ) ) {
			wp_die( -1 );
		}

		$names = explode( ',', sbx_get_post_data_by_key( "new{$namespace}", '' ) );

		foreach ( $names as $term_name ) {

			$term_slug     = sanitize_title_with_dashes( trim( $term_name ) );
			$term_nicename = sanitize_title_with_dashes( $term_slug );

			if ( '' === $term_nicename ) {
				continue;
			}

			$term_args = array(
				'name' => $term_name,
				'slug' => self::pre_taxonomy_slug( $term_slug, $namespace ),
			);

			$term    = self::add_term( $term_name, $namespace, $term_args );
			$term_id = is_object( $term ) ? (int) $term->term_id : null;

			if ( empty( $term_id ) ) {
				continue;
			}

			$data = sprintf(
                '<option value="%2$s" data-value="%3$s" selected="selected"> %4$s</option>',
				esc_attr( $namespace ),
				esc_html( $term_slug ),
				absint( $term_id ),
				esc_html( $term->name )
			);

			$add = array(
				'what'     => $namespace,
				'id'       => $term_id,
				'data'     => str_replace( array( "\n", "\t" ), '', $data ),
				'position' => -1,
			);
		}

		$response = new WP_Ajax_Response( $add );
		$response->send();
	}

	/**
	 * Add new term from metabox
	 * Mimics _wp_ajax_add_hierarchical_term() but modified for non-hierarchical terms
	 *
	 * @return data for WP_Lists script
	 */
	public static function add_route_version_term() {
		$version = 'sbx_route_version';
		$action    = sbx_get_post_data_by_key( 'action', '' );

		check_ajax_referer( $action, "_ajax_nonce-add-{$version}" );

		if ( ! current_user_can( 'manage_categories' ) ) {
			wp_die( -1 );
		}

		$names = explode( ',', sbx_get_post_data_by_key( "new{$version}", '' ) );

		foreach ( $names as $term_name ) {

			$term_slug     = sanitize_title_with_dashes( trim( $term_name ) );
			$term_nicename = sanitize_title_with_dashes( $term_slug );

			if ( '' === $term_nicename ) {
				continue;
			}

			$term_args = array(
				'name' => $term_name,
				'slug' => self::pre_taxonomy_slug( $term_slug, $version ),
			);

			$term    = self::add_term( $term_name, $version, $term_args );
			$term_id = is_object( $term ) ? (int) $term->term_id : null;

			if ( empty( $term_id ) ) {
				continue;
			}

			$data = sprintf(
                '<option value="%2$s" data-value="%3$s" selected="selected"> %4$s</option>',
				esc_attr( $version ),
				esc_html( $term_slug ),
				absint( $term_id ),
				esc_html( $term->name )
			);

			$add = array(
				'what'     => $version,
				'id'       => $term_id,
				'data'     => str_replace( array( "\n", "\t" ), '', $data ),
				'position' => -1,
			);
		}

		$response = new WP_Ajax_Response( $add );
		$response->send();
	}

	/**
	 * Enqueue Scripts
	 * @access public
	 * @return void
	 * @since  1.0
	 */
	public static function admin_enqueue_scripts() {
		$screen       = get_current_screen();
		$screen_id    = $screen ? $screen->id : '';
		$suffix       = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		// List table
		if ( in_array( $screen_id, array( 'edit-sbx_route_type', 'edit-sbx_route_namespace' ) ) ) {
			wp_enqueue_style( 'jquery-ui-style' );
			wp_enqueue_script( 'jquery-tiptip' );
		}
	}
}

SBX_Admin_Taxonomies::get_instance();
