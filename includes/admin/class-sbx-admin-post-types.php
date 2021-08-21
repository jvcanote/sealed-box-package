<?php
/**
 * Handles post types in admin
 *
 *
 *
 * @version  1.0.0
 * @package  SealedBox/Admin/PostTypes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Admin post types class.
 */
final class SBX_Admin_Post_Types {

	/**
	 * Updated post data.
	 *
	 * @var array|object post_data
	 */
	protected $post_data = array();

	/**
	 * Get class instance
	 */
	public static function get_instance() {
        static $admin_post_types = null;

		if ( null === $admin_post_types ) {
			$admin_post_types = new SBX_Admin_Post_Types();
        }

		return $admin_post_types;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {

        // filters
        add_filter( 'post_row_actions', array( $this, 'post_row_actions' ), 10, 2 );
        add_filter( 'display_post_states', array( $this, 'display_post_states' ), 10, 2 );
        add_filter( 'default_content', array( $this, 'default_content' ), 10, 2 );
        add_filter( 'wp_insert_post_data', array( $this, 'insert_post_data' ), 10, 1 );

        // actions
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ) );
        add_action( 'save_post_sbx_service_route', array( $this, 'save_post_meta' ), 10, 3 );
		add_action( 'add_meta_boxes', array( $this, 'remove_meta_box' ), 1, 1 );
    }

    /**
     * Remove meta box.
     *
     * @since 1.0.0
     * @return  void
     */
    public function remove_meta_box( $post_type ) {
        // var_dump($post_type);
        // var_dump( class_exists( 'SBX_Meta_Box_Service_Route_Data', true ) );
        if ( 'sbx_service_route' === $post_type && class_exists( 'SBX_Meta_Box_Service_Route_Data' ) ) {
            remove_meta_box( 'slugdiv', 'sbx_service_route', 'normal' );
        }
    }

    /**
     * Set values for new post
     *
     * @param string  $post_content
     * @param object  $post
     * @return string
     */
    public function default_content( $post_content, $post ) {

        // Check the post type.
        if ( 'sbx_service_route' !== sbx_get_var( $_POST['post_type'] ) ) {
            return $post_content;
        }

        if ( ! is_object( $post ) ) {
            return $post_content;
        }

        // Check the user's permissions.
        if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'edit_post', $post->ID ) ) {
            return $post_content;
        }

        // Set post author
        $post->post_author   = get_current_user_id();

        // Set post password
        $post->post_password = wp_generate_password( 32 );

        return $post_content;
    }
    /**
	 * Filters the default post display states used in the posts list table.
	 *
	 * @since 1.0.0
	 *
	 * @param string[] $post_states An array of post display states.
	 * @param WP_Post  $post        The current post object.
	 */
	public function display_post_states( $post_states, $post ) {

        if ( ! is_object( $post ) ) {
            return $post_states;
        }
        if ( 'sbx_service_route' === sbx_get_var( $post->post_type ) ) {
            unset( $post_states['protected'] );
        }

        return $post_states;
    }

    /**
     * Filters the array of row action links on the Posts list table.
     *
     * The filter is evaluated only for non-hierarchical post types.
     *
     * @since 1.0.0
     *
     * @param string[] $actions An array of row action links. Defaults are
     *                          'Edit', 'Quick Edit', 'Restore', 'Trash',
     *                          'Delete Permanently', 'Preview', and 'View'.
     * @param WP_Post  $post    The post object.
     */
    public function post_row_actions( $actions, $post ) {
        if ( 'sbx_service_route' === sbx_get_var( $post->post_type ) ) {
            unset( $actions['inline hide-if-no-js'] );
        }

        return $actions;
    }

	/**
	 * Update and store post data
	 *
	 * @param array $post_data
	 * @return array
	 */
	public function insert_post_data( $post_data ) {
        error_log( print_r($post_data, true));

        // Check the post type.
        if ( 'sbx_service_route' !== sbx_get_var( $post_data['post_type'] ) ) {
            return $post_data;
        }

        // Check the user's permissions.
        if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'edit_post', sbx_get_var( $_POST['post_ID'] ) ) ) {
            return $post_data;
        }

        $route_type_id              = absint( sbx_get_var( $_POST['radio_tax_input']['sbx_route_type'][0], get_option( 'default_sbx_route_type', 0 ) ) );
        $namespace_id               = absint( sbx_get_var( $_POST['radio_tax_input']['sbx_route_namespace'][0], get_option( 'default_sbx_route_namespace', 0 ) ) );
        $version_id                 = absint( sbx_get_var( $_POST['radio_tax_input']['sbx_route_version'][0], get_option( 'default_sbx_route_version', 0 ) ) );

        $post_name                  = sanitize_title_with_dashes( ! empty( $post_data['post_name'] ) ? $post_data['post_name'] : ( ! empty( $post_data['post_title'] ) ? $post_data['post_title'] : 'auto-draft' ) );

        // Set post title and name
        $post_data['post_title']    = sbx_unique_route_slug( $post_data['post_name'] = 'auto-draft' === $post_name ? 'route' : $post_name, $_POST['post_ID'], $post_data['post_status'], $route_type_id, $namespace_id, $version_id );

        // Set post author
        $post_data['post_author']   = empty( $post_data['post_author'] ) ? get_current_user_id() : $post_data['post_author'];

        // Set post password
        $post_data['post_password'] = empty( $post_data['post_password'] ) ? wp_generate_password( 32 ) : $post_data['post_password'];

        $this->post_data = $post_data;

		return $post_data;
    }

	/**
	 * Save post meta
	 *
	 * @param array  $post_id
	 * @param object $post
	 * @param bool   $update
	 * @return void
	 */
	public function save_post_meta( $post_id, $post, $update ) {

        // Check if our nonce is set.
        if ( ! isset( $_POST['sealed_box_meta_nonce'] ) ) {
            return;
        }

        // Verify that the nonce is valid.
        if ( ! wp_verify_nonce( $_POST['sealed_box_meta_nonce'], 'sealed_box_save_data' ) ) {
            return;
        }

        // If this is an autosave, our form has not been submitted, so we don't want to do anything.
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // Check the post type.
        if ( 'sbx_service_route' !== sbx_get_var( $_POST['post_type'] ) ) {
            return;
        }

        // Check the user's permissions.
        if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        if ( ! $update ) { // || empty( $this->post_data )
            return;
        }

        $meta_keys      = apply_filters( 'sealed_box_service_route_registered_meta_keys', array() );
        $type_meta_keys = apply_filters( 'sealed_box_' . sbx_get_post_data_by_key( 'route_type', 'basic' ) . '_service_route_registered_meta_keys', array() );

        error_log( print_r([$post_id, $meta_keys, $type_meta_keys], true));

        foreach ( $meta_keys as $meta_key ) {

            if ( in_array( $meta_key, $type_meta_keys ) ) {
                update_post_meta( $post_id, sbx_prefix( $meta_key ), sbx_get_post_data_by_key( $meta_key ) );
            } else {
                delete_post_meta( $post_id, sbx_prefix( $meta_key ) );
            }
        }
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

		// Meta boxes
		if ( in_array( $screen_id, array( 'sbx_service_route' ) ) ) {

            add_action( 'admin_print_styles', array( __CLASS__, 'admin_print_styles' ), 20 );
            add_action( 'admin_print_footer_scripts', array( __CLASS__, 'admin_print_footer_scripts' ), 20 );

			wp_enqueue_style( 'jquery-ui-style' );
			wp_enqueue_script( 'jquery-tiptip' );
            wp_enqueue_script( 'sbx-term-input' );
		}
    }

	/**
	 * Admin print styles
	 * @access public
	 * @return void
	 * @since  1.0
	 */
	public static function admin_print_styles() {
        ?>

        <style id="sbx_metadata_styles">
            #post-body #postbox-sortables {
                min-height: 50px
            }

            .misc-pub-section.misc-pub-visibility,
            .misc-pub-section.misc-pub-curtime {
                display: none;
            }

            #sealed-box-service-route-data .hndle {
                padding: 10px 12px 8px;
                flex-grow: 1;
                white-space: nowrap;
                display: flex;
                flex-basis: auto;
            }

            #sealed-box-service-route-data .hndle > span {
                line-height: 30px;
                display: inline-block
            }

            #sealed-box-service-route-data .hndle #route_box {
                padding-left: 14px !important;
                border-left: 1px solid #dfdfdf !important;
                margin-top: 4px;
                margin-bottom: 4px;
                margin-left: 14px;
                padding-top: 2px;
                padding-right: 1rem !important;
                /* display: inline-block; */
                position: relative;
                white-space: normal;
                font-size: 13px;
                line-height: 30px;
                width: calc( 80% - 40px );
            }

            #sealed-box-service-route-data .hndle:after {
                /* content: ''; */
                display: table;
                width: 100%;
                clear: right;
            }

            .col-1.form-field.col-inline {
                width: auto !important;
                min-width: inherit !important;
                float: left !important;
                padding: 1px 15px 1px 12px !important;
                margin: 8px 0 8px -1px !important;
                display: inline !important;
            }

            .sealed_box_options_panel .col-1.col-inline label,
            .sealed_box_options_panel .col-1.col-inline label {
                margin: 0 !important;
                padding: 0 !important;
                float: left !important;
                display: block !important;
                width: auto;
            }

            .sealed_box_options_panel p.form-field.col-inline {
                padding-bottom: 11px !important;
            }

            .sealed_box_options_panel p.form-field.col-2 {
                min-width: calc(200% - 182px);
            }

            #sealed-box-service-route-data .hndle:hover:not(:focus-within) #route_box {
                /* max-width: calc(100% - 12.4ex); */
            }
            #route_box > * {
                line-height: 30px;
                display: inline-flex;
                /* margin-right: -0.7em; */
            }
            #route_box > * {
                margin-right: -4px
            }
            #route_box > .route_box-accessibility-group-wrapper {
                left: -4px;
                position: relative;
            }
            #sealed-box-service-route-data .hndle:hover #route_box > .route_box-accessibility-group-wrapper {
                /* position: relative; */
                width: 100%;
                /* margin-right: -75%; */
            }

            @media (min-width:1300px) {
                #sealed-box-service-route-data .hndle #route_box {
                    /* width: 75%; */
                }
            }

            @media (max-width:1399px) {
                #sealed-box-service-route-data .hndle:hover:not(:focus-within) #route_box {
                    /* max-width: calc(80% - 12.4ex); */
                }
                #sealed-box-service-route-data .hndle:hover:not(:focus-within) #route_box .route_box-accessibility-group-wrapper,
                #poststuff #post-body.columns-2 #sealed-box-service-route-data .hndle #route_box .route_box-accessibility-group-wrapper {
                    /* display: block; */
                    /* white-space: nowrap; */
                }

            }

            @media (max-width:1299px) {

                .form-field.col-inline {
                    min-width: inherit !important;
                    padding: 0 15px 0 9px !important;
                    margin: 4px 0px 4px 0px !important;
                    display: inline !important;
                }

                .sealed_box_options_panel .col-1.col-inline label {
                    width: 117px !important;
                    min-width: inherit !important;
                    float: left !important;
                    padding: 0 15px 0 4px !important;
                    margin-left: -1px !important;
                    display: block !important;
                }

                .sealed_box_options_panel .form-field.col-inline input[type=checkbox] {
                    float: right;
                    margin-top: auto !important;
                }

                /* #sealed-box-service-route-data .hndle #route_box {
                    overflow: visible;
                    position: relative;
                }
                #sealed-box-service-route-data .hndle:hover:not(:focus-within) {
                    position: relative;
                    width: auto;
                    padding-right: 80px;
                } */
            }

            #sealed-box-service-route-data .hndle #route_box small {
                /* color: #7e8993; */
                /* color: #666; */
                font-size: 13px;
                font-weight: 400;
                /* margin-right: -.5em !important; */
            }

            #sealed-box-service-route-data .hndle #route_box small:not([style*="display: none"])+small {
                /* margin-left: .5em; */
            }

            #sealed-box-service-route-data .hndle #route_box input[type="text"]::-moz-selection {
                background: #f1f1f1;
            }

            #sealed-box-service-route-data .hndle #route_box input[type="text"]::selection {
                background: #f1f1f1;
            }

            #sealed-box-service-route-data .hndle select,
            #sealed-box-service-route-data .hndle input[type="text"] {
                margin-top: 0
            }

            #sbx_input_size_test_block.input {
                font-size: 14px;
                font-weight: 400;
            }

            @media (max-width:782px) {
                #sbx_input_size_test_block.input {
                    font-size: 16px;
                    /* padding-left: 1em; */
                    /* padding-right: 1em; */
                }
            }

            #sealed-box-service-route-data .hndle input,
            #sealed-box-service-route-data .hndle select {
                vertical-align: baseline
            }

            #sealed-box-service-route-data .hndle select {
                text-align: center !important;
                background-image: none !important
            }

            #sealed-box-service-route-data .hndle input[type="text"] {
                background-image: none !important;
                max-width: calc(10% + 80px);
                cursor: text !important;
            }

            #sealed-box-service-route-data .hndle select,
            #sealed-box-service-route-data .hndle input[type="text"] {
                /* margin-right: -.5em !important; */
                /* margin-bottom: -1px; */
                border-width: 0 0 1px 0;
                border-color: #ccd0d4;
                transition-duration: .15s, .15s;
                transition-timing-function: ease-in-out, ease-in-out;
                transition-property: width, text-indent;
                padding: 0 0 0 0;
                text-indent: 1px;
                font-weight: 400;
                text-align-last: center;
                line-height: 31px;
            }
            #sealed-box-service-route-data .hndle input:hover,
            #sealed-box-service-route-data .hndle select:hover,
            #sealed-box-service-route-data .hndle input:active,
            #sealed-box-service-route-data .hndle select:active,
            #sealed-box-service-route-data .hndle input:focus,
            #sealed-box-service-route-data .hndle select:focus {
                position: relative;
                z-index: 10;
            }
            /*
            #sealed-box-service-route-data .hndle input:focus,
            #sealed-box-service-route-data .hndle select:hover,
            #sealed-box-service-route-data .hndle input:focus,
            #sealed-box-service-route-data .hndle select:hover {

            } */

            #sealed-box-service-route-data .hndle #route_box:hover small {
                /* color: #016087 !important; */
                cursor: pointer;
            }

            #sealed-box-service-route-data>.handlediv {
                position: absolute;
                right: 0;
            }

            #sealed-box-service-route-data .wrap {
                margin: 0
            }

            #sealed-box-service-route-data .panel-wrap {
                background: #fff
            }

            #sealed-box-service-route-data .sbx-metaboxes-wrapper,
            #sealed-box-service-route-data .sealed_box_options_panel {
                float: left;
                width: 80%;
                position: relative;
                overflow: hidden;
                padding: 9px 0;
            }

            #sealed-box-service-route-data .sbx-metaboxes-wrapper .sbx-radios,
            #sealed-box-service-route-data .sealed_box_options_panel .sbx-radios {
                display: block;
                float: left;
                margin: 0
            }

            #sealed-box-service-route-data .sbx-metaboxes-wrapper .sbx-radios li,
            #sealed-box-service-route-data .sealed_box_options_panel .sbx-radios li {
                display: block;
                padding: 0 0 10px
            }

            #sealed-box-service-route-data .sbx-metaboxes-wrapper .sbx-radios li input,
            #sealed-box-service-route-data .sealed_box_options_panel .sbx-radios li input {
                width: auto
            }

            #sealed-box-service-route-data .panel-wrap,
            .sealed-box .panel-wrap {
                overflow: hidden
            }

            #sealed-box-service-route-data ul.sbx-tabs,
            .sealed-box ul.sbx-tabs {
                margin: 0;
                width: 20%;
                float: left;
                line-height: 1em;
                /* padding: 0 0 10px; */
                padding: 0;
                position: relative;
                background-color: #fafafa;
                border-right: 1px solid #eee;
                box-sizing: border-box
            }

            #sealed-box-service-route-data ul.sbx-tabs::after,
            .sealed-box ul.sbx-tabs::after {
                content: "";
                display: block;
                width: 100%;
                height: 9999em;
                position: absolute;
                bottom: -9999em;
                left: 0;
                background-color: #fafafa;
                border-right: 1px solid #eee
            }

            #sealed-box-service-route-data ul.sbx-tabs li,
            .sealed-box ul.sbx-tabs li {
                margin: 0;
                padding: 0;
                display: block;
                position: relative
            }

            #sealed-box-service-route-data ul.sbx-tabs li a,
            .sealed-box ul.sbx-tabs li a {
                margin: 0;
                padding: 10px;
                display: block;
                box-shadow: none;
                text-decoration: none;
                line-height: 20px !important;
                border-bottom: 1px solid #eee
            }

            #sealed-box-service-route-data ul.sbx-tabs li a span,
            .sealed-box ul.sbx-tabs li a span {
                margin-left: .618em;
                margin-right: .618em
            }

            #sealed-box-service-route-data ul.sbx-tabs li a::before,
            .sealed-box ul.sbx-tabs li a::before {
                font-family: Dashicons;
                speak: none;
                font-weight: 400;
                font-variant: normal;
                text-transform: none;
                line-height: 1;
                -webkit-font-smoothing: antialiased;
                content: "";
                text-decoration: none
            }

            #sealed-box-service-route-data ul.sbx-tabs li.route_options a::before,
            .sealed-box ul.sbx-tabs li.route_options a::before {
                content: "\f103"
            }

            #sealed-box-service-route-data ul.sbx-tabs li.request_options a::before,
            .sealed-box ul.sbx-tabs li.request_options a::before {
                content: "\f175"
            }

            #sealed-box-service-route-data ul.sbx-tabs li.redirection_options a::before,
            .sealed-box ul.sbx-tabs li.redirection_options a::before {
                /* font-family: SealedBox; */
                content: "\f180"
            }

            #sealed-box-service-route-data ul.sbx-tabs li.linked_service_route_options a::before,
            .sealed-box ul.sbx-tabs li.linked_service_route_options a::before {
                content: "\f107"
            }

            #sealed-box-service-route-data ul.sbx-tabs li.attribute_options a::before,
            .sealed-box ul.sbx-tabs li.attribute_options a::before {
                content: "\f481"
            }

            #sealed-box-service-route-data ul.sbx-tabs li.schema_options a::before,
            .sealed-box ul.sbx-tabs li.schema_options a::before {
                content: "\f509"
                    /* content: "\f111" */
            }

            #sealed-box-service-route-data ul.sbx-tabs li.marketplace-suggestions_options a::before,
            .sealed-box ul.sbx-tabs li.marketplace-suggestions_options a::before {
                content: none
            }

            #sealed-box-service-route-data ul.sbx-tabs li.variations_options a::before,
            .sealed-box ul.sbx-tabs li.variations_options a::before {
                content: "\f509"
            }

            #sealed-box-service-route-data ul.sbx-tabs li.usage_restriction_options a::before,
            .sealed-box ul.sbx-tabs li.usage_restriction_options a::before {
                font-family: SealedBox;
                content: "\e602"
            }

            #sealed-box-service-route-data ul.sbx-tabs li.usage_limit_options a::before,
            .sealed-box ul.sbx-tabs li.usage_limit_options a::before {
                font-family: SealedBox;
                content: "\e601"
            }

            #sealed-box-service-route-data ul.sbx-tabs li.general_coupon_data a::before,
            .sealed-box ul.sbx-tabs li.general_coupon_data a::before {
                font-family: SealedBox;
                content: "\e600"
            }

            #sealed-box-service-route-data ul.sbx-tabs li.active a,
            .sealed-box ul.sbx-tabs li.active a {
                color: #555;
                position: relative;
                background-color: #eee
            }

            #sealed-box-service-route-data .inside,
            #sealed-box-route-type-options .inside {
                margin: 0;
                padding: 0;
                clear:both;
            }

            .panel,
            .sealed_box_options_panel {
                padding: 9px;
                color: #555
            }

            .panel .form-field .sealed-box-help-tip,
            .sealed_box_options_panel .form-field .sealed-box-help-tip {
                font-size: 1.4em
            }

            .sealed-box-help-tip {
                color: #666;
                display: inline-block;
                font-size: 1.1em;
                font-style: normal;
                height: 16px;
                line-height: 16px;
                position: relative;
                vertical-align: middle;
                width: 16px
            }

            .sealed-box-help-tip::after {
                font-family: Dashicons;
                speak: none;
                font-weight: 400;
                font-variant: normal;
                text-transform: none;
                line-height: 1;
                -webkit-font-smoothing: antialiased;
                margin: 0;
                text-indent: 0;
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                text-align: center;
                content: "";
                cursor: help
            }

            .branch-5-3 .sealed-box-help-tip {
                font-size: 1.2em;
                cursor: help
            }

            h2 .sealed-box-help-tip {
                margin-top: -5px;
                margin-left: .25em
            }

            .panel {
                padding: 0
            }

            .sealed_box_options_panel .col-1+.col-1.description {
                font-size: 13px;
                font-style: normal;
            }

            #sealed-box-service-route-data .form-table th {
                margin: 0;
                padding: 0 0 14px 0;
            }

            #sealed-box-service-route-data .form-table td {
                margin: 0;
                padding: 14px 0 0px 0;
                min-height: 30px;
            }

            #sealed-box-service-route-data .form-table th.col-1,
            #sealed-box-service-route-data .form-table td.col-1 {
                display: table-cell !important;
            }

            #sealed-box-service-route-data .form-table th:last-child,
            #sealed-box-service-route-data .form-table td:last-child {
                padding-right: 0;
            }

            #sealed-box-service-route-data .form-table table {
                margin-bottom: 14px;
                width: calc(100% - 22px) !important;
                max-width: 95%;
            }

            .sealed_box_options_panel .col-1+.col-1.description {
                padding-left: 30px !important;
                padding-right: 30px !important;
                padding: 14px 30px !important;
                /* margin: auto !important; */
                width: calc(50% - 49px);
                width: auto;
                height: 100%;
                /* min-height: 100%; */
                border-left: 1px solid #eeeeee !important;
                /* position: absolute; */
            }

            .sealed_box_options_panel .col-1 .form-field .form-field .options_group,
            .sealed_box_options_panel .col-1 select,
            .sealed_box_options_panel .col-1 textarea,
            .sealed_box_options_panel .col-1 input[type=email],
            .sealed_box_options_panel .col-1 input[type=number],
            .sealed_box_options_panel .col-1 input[type=password],
            .sealed_box_options_panel .col-1 input[type=text] {
                width: calc(100% - 22px) !important
            }

            .sealed_box_options_panel .col-1 ul.sbx-radios,
            .sealed_box_options_panel .col-1 ul.sbx-checkboxes {
                width: calc(100% - 17px) !important
            }

            @media (min-width:1300px) {

                .col-1 {
                    /* width: calc(50% - 194px); */
                    display: inline-block;
                    vertical-align: top;
                }

                .sealed_box_options_panel .description.col-1 {
                    float: right;
                }

                .sealed_box_options_panel .col-1+.col-1.description {
                    padding-left: 30px !important;
                    padding-right: 30px !important;
                    padding: 14px 30px !important;
                    /* margin: auto !important; */
                    width: calc(50% - 49px);
                    width: auto;
                    height: auto;
                    /* min-height: 100%; */
                    border-left: 1px solid #eeeeee !important;
                    /* position: absolute; */
                }
                .sealed_box_options_panel .col-1 .form-field .form-field .options_group,
                .sealed_box_options_panel .col-1 select,
                .sealed_box_options_panel .col-1 textarea,
                .sealed_box_options_panel .col-1 input[type=email],
                .sealed_box_options_panel .col-1 input[type=number],
                .sealed_box_options_panel .col-1 input[type=password],
                .sealed_box_options_panel .col-1 input[type=text] {
                    width: calc(100% - 22px) !important
                }

                .sealed_box_options_panel .col-1 ul.sbx-radios,
                .sealed_box_options_panel .col-1 ul.sbx-checkboxes {
                    width: calc(100% - 17px) !important
                }

                .sealed_box_options_panel p.description.col-1+p.form-field.col-1 {
                    clear: both !important;
                    float: none !important;
                    display: block;
                }

            }

            @media (max-width:1299px) {
                .sealed_box_options_panel .col-1+.col-1.description {
                    /* padding-left: 12px !important; */
                    /* width: calc(60% - 30px); */
                    /* position: relative;
                    float: none; */
                    /* margin-top: -5px !important; */
                }

                .sealed_box_options_panel p.description {
                    /* padding: 0 0 30px 0 !important;
                    width: calc(100% - 182px) !important;
                    margin: 0 20px 0 0 !important;
                    clear: none;
                    display: block;
                    float: right; */

                }
            }

            p.description {
                grid-area: 1 / none / none / 2;
            }

            #sealed-box-route-type-options .panel {
                margin: 0;
                padding: 9px
            }

            #sealed-box-route-type-options .panel p,
            .sealed_box_options_panel fieldset.form-field,
            .sealed_box_options_panel p {
                margin: 0 0 9px;
                font-size: 12px;
                padding: 5px 9px;
                line-height: 24px
            }

            #sealed-box-route-type-options .panel p::after,
            .sealed_box_options_panel fieldset.form-field::after,
            .sealed_box_options_panel p::after {
                content: ".";
                display: block;
                height: 0;
                clear: both;
                visibility: hidden
            }

            .sealed_box_options_panel p label {
                line-height: 16px !important;
                padding-top: 4px !important;
            }

            .sealed_box_options_panel .options_group fieldset ul.sbx-checkboxes li label {
                margin-left: 0;
                display: inline-block;
                float: none;
            }

            .sealed_box_options_panel .checkbox {
                margin-top: 4px !important;
                vertical-align: middle;
                float: left
            }

            .sealed_box_options_panel .versioned_files table {
                width: 100%;
                padding: 0 !important
            }

            .sealed_box_options_panel .versioned_files table th {
                padding: 7px 0 7px 7px !important
            }

            .sealed_box_options_panel .versioned_files table th.sort {
                width: 17px;
                padding: 7px !important
            }

            .sealed_box_options_panel .versioned_files table th .sealed-box-help-tip {
                font-size: 1.1em;
                margin-left: 0
            }

            .sealed_box_options_panel .versioned_files table td {
                vertical-align: middle !important;
                padding: 4px 0 4px 7px !important;
                position: relative
            }

            .sealed_box_options_panel .versioned_files table td:last-child {
                padding-right: 7px !important
            }

            .sealed_box_options_panel .versioned_files table td input.input_text {
                width: 100%;
                float: none;
                min-width: 0;
                margin: 1px 0
            }

            .sealed_box_options_panel .versioned_files table td .upload_file_button {
                width: auto;
                float: right;
                cursor: pointer
            }

            .sealed_box_options_panel .versioned_files table td .delete {
                display: block;
                text-indent: -9999px;
                position: relative;
                height: 1em;
                width: 1em;
                font-size: 1.2em
            }

            .sealed_box_options_panel .versioned_files table td .delete::before {
                font-family: Dashicons;
                speak: none;
                font-weight: 400;
                font-variant: normal;
                text-transform: none;
                line-height: 1;
                -webkit-font-smoothing: antialiased;
                margin: 0;
                text-indent: 0;
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                text-align: center;
                content: "";
                color: #999
            }

            .sealed_box_options_panel .versioned_files table td .delete:hover::before {
                color: #a00
            }

            .sealed_box_options_panel .versioned_files table td.sort {
                width: 17px;
                cursor: move;
                font-size: 15px;
                text-align: center;
                background: #f9f9f9;
                padding-right: 7px !important
            }

            .sealed_box_options_panel .versioned_files table td.sort::before {
                content: "\f333";
                font-family: Dashicons;
                text-align: center;
                line-height: 1;
                color: #999;
                display: block;
                width: 17px;
                float: left;
                height: 100%
            }

            .sealed_box_options_panel .versioned_files table td.sort:hover::before {
                color: #333
            }


            .sealed_box_options_panel {
                /* min-height: 175px; */
                box-sizing: border-box
            }

            .sealed_box_options_panel .versioned_files {
                padding: 0 9px 0 162px;
                position: relative;
                margin: 9px 0
            }

            .sealed_box_options_panel .versioned_files label {
                position: absolute;
                left: 0;
                margin: 0 0 0 12px;
                line-height: 24px
            }

            .sealed_box_options_panel p {
                margin: 9px 0
            }

            .sealed_box_options_panel fieldset.form-field,
            .sealed_box_options_panel p.form-field {
                padding: 5px 20px 5px 162px !important;
                min-height: 30px;
            }

            .sealed_box_options_panel .sale_price_dates_fields .short:first-of-type {
                margin-bottom: 1em
            }

            .sealed_box_options_panel .sale_price_dates_fields .short:nth-of-type(2) {
                clear: left
            }

            .sealed_box_options_panel label,
            .sealed_box_options_panel legend {
                float: left;
                width: 150px;
                padding: 0;
                margin: 0 0 0 -150px
            }

            .sealed_box_options_panel label .req,
            .sealed_box_options_panel legend .req {
                font-weight: 700;
                font-style: normal;
                color: #a00
            }

            .sealed_box_options_panel .description {
                padding: 0;
                margin: 0 0 0 7px;
                clear: none;
                /* display: inline */
            }

            .sealed_box_options_panel input[type="text"]+.description {
                padding-top: 7px !important;
                margin-left: 0 !important;
                clear: left;
                display: block !important;
            }

            .sealed_box_options_panel .description-block {
                margin-left: 0;
                display: block
            }

            .sealed_box_options_panel input,
            .sealed_box_options_panel select,
            .sealed_box_options_panel textarea {
                margin: 0 5px 0 0
            }

            .sealed_box_options_panel textarea {
                float: left;
                /* height: 3.5em; */
                line-height: 1.5em;
                vertical-align: top
            }

            .sealed_box_options_panel textarea.code {
                white-space: pre;
            }

            .sealed_box_options_panel input[type=email],
            .sealed_box_options_panel input[type=number],
            .sealed_box_options_panel input[type=password],
            .sealed_box_options_panel input[type=text] {
                width: 50%;
                float: left
            }

            .sealed_box_options_panel input.button {
                width: auto;
                margin-left: 8px
            }

            .sealed_box_options_panel select {
                float: left
            }

            .sealed_box_options_panel.short,
            .sealed_box_options_panel input[type=email].short,
            .sealed_box_options_panel input[type=number].short,
            .sealed_box_options_panel input[type=password].short,
            .sealed_box_options_panel input[type=text].short {
                width: 50%
            }

            .sealed_box_options_panel .sized {
                width: auto !important;
                margin-right: 6px
            }

            .sealed_box_options_panel .options_group {
                border-top: 1px solid #fff;
                border-bottom: 1px solid #eee
            }

            .options_group {
                display: grid;
                grid-template-columns: 50% 1fr;
                grid-template-rows: auto auto auto auto auto;
                gap: 0px 0px;
            }

            .sealed_box_options_panel .options_group:first-child {
                border-top: 0
            }

            .sealed_box_options_panel .options_group:last-child {
                border-bottom: 0
            }

            .sealed_box_options_panel .options_group+.options_group {
                margin-top: -2px;
                border-top-color: transparent;
            }

            .options_group[style*="display: none"]+.options_group {
                margin-top: -1px;
            }


            .sealed_box_options_panel .options_group fieldset {
                margin: 9px 0;
                font-size: 12px;
                padding: 5px 9px;
                line-height: 24px
            }

            .sealed_box_options_panel .options_group fieldset label {
                width: auto;
                float: none
            }

            .sealed_box_options_panel .options_group fieldset ul {
                float: left;
                width: 50%;
                margin: 0;
                padding: 0
            }

            .sealed_box_options_panel .options_group fieldset ul li {
                margin: 0;
                width: auto
            }

            .sealed_box_options_panel .options_group fieldset ul li input {
                width: auto;
                float: none;
                margin-right: 4px
            }

            .sealed_box_options_panel .options_group fieldset ul.sbx-checkboxes li {
                display:inline-block;
                margin-right: 1rem;
            }

            .sealed_box_options_panel .options_group fieldset ul.sbx-checkboxes li label .checkbox {
                width: 1rem;
                float: left;
                margin-right: 12px;
            }

            .sealed_box_options_panel .options_group fieldset ul.sbx-radios label {
                margin-left: 0
            }

            .sealed_box_options_panel .dimensions_field .wrap {
                display: block;
                width: 50%
            }

            .sealed_box_options_panel .dimensions_field .wrap input {
                width: 30.75%;
                margin-right: 3.8%
            }

            .sealed_box_options_panel .dimensions_field .wrap .last {
                margin-right: 0
            }

            .sealed_box_options_panel.padded {
                padding: 1em
            }

            .sealed_box_options_panel .select2-container {
                float: left
            }

            #sealed-box-service-route-data input.dp-applied {
                float: left
            }

            #grouped_service_route_options,
            #simple_service_route_options,
            #publickey_service_route_options {
                padding: 12px;
                font-style: italic;
                color: #666
            }

            #sbx-route-namespacechecklist {
                column-count: auto;
                column-fill: auto;
                column-gap: 60px;
                column-rule: #eee;
                column-rule-width: 1px;
                column-rule-style: dotted;
                column-span: all;
                column-width: 200px
            }

            .tips {
                cursor: help;
                text-decoration: none
            }

            img.tips {
                padding: 5px 0 0
            }

            #tiptip_holder {
                display: none;
                z-index: 8675309;
                position: absolute;
                top: 0;
                left: 0
            }

            #tiptip_holder.tip_top {
                padding-bottom: 5px
            }

            #tiptip_holder.tip_top #tiptip_arrow_inner {
                margin-top: -7px;
                margin-left: -6px;
                border-top-color: #333
            }

            #tiptip_holder.tip_bottom {
                padding-top: 5px
            }

            #tiptip_holder.tip_bottom #tiptip_arrow_inner {
                margin-top: -5px;
                margin-left: -6px;
                border-bottom-color: #333
            }

            #tiptip_holder.tip_right {
                padding-left: 5px
            }

            #tiptip_holder.tip_right #tiptip_arrow_inner {
                margin-top: -6px;
                margin-left: -5px;
                border-right-color: #333
            }

            #tiptip_holder.tip_left {
                padding-right: 5px
            }

            #tiptip_holder.tip_left #tiptip_arrow_inner {
                margin-top: -6px;
                margin-left: -7px;
                border-left-color: #333
            }

            #tiptip_content,
            .chart-tooltip,
            .sbx_error_tip {
                color: #fff;
                font-size: .8em;
                max-width: 150px;
                background: #333;
                text-align: center;
                border-radius: 3px;
                padding: .618em 1em;
                box-shadow: 0 1px 3px rgba(0, 0, 0, .2)
            }

            #tiptip_content code,
            .chart-tooltip code,
            .sbx_error_tip code {
                padding: 1px;
                background: #888
            }

            #tiptip_arrow,
            #tiptip_arrow_inner {
                position: absolute;
                border-color: transparent;
                border-style: solid;
                border-width: 6px;
                height: 0;
                width: 0
            }
        </style>

        <?php
    }

	/**
	 * Admin print footer scripts
	 * @access public
	 * @return void
	 * @since  1.0
	 */
	public static function admin_print_footer_scripts() {
        ?>
        <script>
            <?php $service_type_json = wp_json_encode(sbx_get_the_terms('sbx_route_type', 'description', 'term_id')); ?>
            var sbx_service_type = <?php echo empty($service_type_json) ? 0 : $service_type_json; ?>;
        </script>

        <script>
            var sbxSheet = document.getElementById('sbx_metadata_styles').sheet,
            cssMap = new Map();
            cssMap.set( "a", "#sealed-box-service-route-data .hndle input, #sealed-box-service-route-data .hndle select" );
            cssMap.set( "a:active, a:hover", "#sealed-box-service-route-data .hndle input:hover, #sealed-box-service-route-data .hndle select:hover, #sealed-box-service-route-data .hndle input:active, #sealed-box-service-route-data .hndle select:active, #sealed-box-service-route-data .hndle input:focus, #sealed-box-service-route-data .hndle select:focus" );
            Array.from(document.styleSheets)
                .reduce(function (memo, sheet) {
                    return _.intersection(String(sheet.href).split(","), [
                            "common",
                            "forms",
                            "list-tables",
                        ]).length > 2 ?
                        Array.from(sheet.cssRules) :
                        memo;
                })
                .reduce(function (memo, rule) {
                    if (_.contains(Array.from(cssMap.keys()), rule.selectorText) && rule.styleMap.has("color")) {
                        memo.insertRule(
                            cssMap.get(rule.selectorText) + " { " + rule.style.cssText + " }"
                        );
                    }
                    return memo;
                }, sbxSheet);

            (function ($) {
                $.fn.resizeInput = function (settings) {
                    var arrowWidth = 2,
                        extraWidth = 3,
                        texttimeout = 0,
                        textisruning = 0,

                        resizerouteBoxInput = function (expand = 0) {
                            // create test element
                            var $this = this,
                                isSelect = $this.is('select') && 0 !== $this.find("option:selected").length,
                                $tiptip = $('#tiptip_holder'),
                                text = isSelect ? $this.find("option:selected").text() : $this.val(),
                                style = getComputedStyle( this.get(0) ).cssText,
                                $test = $('<span id="sbx_input_size_test_block" class="code input">').html(text),
                                width;
                            // add to body, get width, and get out
                            $test.appendTo('body');
                            width = $test.width() + (isSelect ? arrowWidth : extraWidth) + expand;
                            $test.remove();

                            $this.width(!isSelect && width < 40 ? 40 : width);
                            if ($tiptip.length) {
                                if ( !$this.is(':focus') ) {
                                    $tiptip.css('left', + ( expand / 2 ) );
                                } else {
                                    $tiptip.css('left', 0 );
                                }
                            }
                        },

                        resizeInput = function (event) {

                            var $this = $(this),
                                isSelect = $this.is('select') && 0 !== $this.find("option:selected").length,
                                inputEvt = 'input' === event.type;
                                callback = $.proxy(resizerouteBoxInput, $this);

                            if (inputEvt && $this.is('input')) {

                                clearTimeout(texttimeout);

                                texttimeout = setTimeout(function (callback) {

                                        textisruning = false;
                                        callback(30);

                                    }, 1000, callback);

                                textisruning = true;

                                callback(40);

                            } else if (isSelect) {

                                callback(0);

                            } else if (!inputEvt) {

                                if (!$(this).is(':focus')) {

                                    callback(0);

                                } else {

                                    callback(40);

                                }
                            }

                            isSelect && $this.trigger('blur');
                            // run on start
                        };

                    setTimeout(function ($elems) {

                        $elems.each(function () {
                            $(this).trigger('blur');
                        });

                    }, 1000, this);

                    return this.each(function () {

                        $(this).

                            on('change input', resizeInput).

                            on('click', function () {
                                var cb = $.proxy(resizerouteBoxInput, $(this));
                                $(this).is(':focus') ? cb(40) : cb(0);
                            }).

                            on('mouseover focus', function () {
                                var cb = $.proxy(resizerouteBoxInput, $(this));
                                cb(40);
                            }).

                            on('mouseout blur', function () {
                                if (!$(this).is(':focus')) {
                                    var cb = $.proxy(resizerouteBoxInput, $(this));
                                    cb(0);
                                }

                            }).trigger('change');
                    });
                };

            })(jQuery);

            // (function($) {

            // 	var testBlockSpan = document.createElement('span');
            // 	testBlockSpan.style.setProperty('display', 'none', 'important');

            // 	function getExpandedWidth(input, value = null) {
            // 		var isHidden = null === input.offsetParent,
            // 			expandedWidth = NaN,
            // 			inputsCssText = input.style.cssText,
            // 			parentCssText = input.parentElement.style.cssText;
            // 		console.log(input, value);
            // 		// add test element to body.
            // 		if (!testBlockSpan.isConnected)
            // 			document.body.append(testBlockSpan);

            // 		// expand parent and show input element.
            // 		input.parentElement.style.setProperty('display', 'block', 'important');
            // 		input.parentElement.style.setProperty('position', 'absolute', 'important');
            // 		if (isHidden) input.style.setProperty('display', 'inline-block', 'important');

            // 		// set expansion test element style.
            // 		testBlockSpan.removeAttribute('style');
            // 		testBlockSpan.style = getComputedStyle(input).cssText;
            // 		testBlockSpan.style.setProperty('width', 'initial', 'important');
            // 		testBlockSpan.style.setProperty('max-width', 'initial', 'important');
            // 		testBlockSpan.style.setProperty('min-width', 'initial', 'important');
            // 		testBlockSpan.style.setProperty('position', 'absolute', 'important');
            // 		testBlockSpan.style.setProperty('display', 'inline-block', 'important');
            // 		testBlockSpan.style.setProperty('-webkit-appearance', 'none', 'important');
            // 		console.log(testBlockSpan);
            // 		// resolve value and set test element content.;
            // 		testBlockSpan.innerHTML = null === value ? input.value : value;

            // 		// capture high accuracy expanded width or use fallback.
            // 		expandedWidth = parseFloat(getComputedStyle(testBlockSpan).width);

            // 		if (isNaN(expandedWidth))
            // 			expandedWidth = testBlockSpan.offsetWidth;

            // 		// revert elements styles.
            // 		testBlockSpan.innerHTML = '';
            // 		testBlockSpan.removeAttribute('style');
            // 		testBlockSpan.style.setProperty('display', 'none', 'important');

            // 		input.parentElement.style = parentCssText;
            // 		input.style = inputsCssText;

            // 		return expandedWidth;
            // 	}

            // 	function restArguments(func, startIndex) {
            // 		startIndex = startIndex == null ? func.length - 1 : +startIndex;
            // 		return function() {
            // 			var length = Math.max(arguments.length - startIndex, 0),
            // 				rest = Array(length),
            // 				index = 0;
            // 			for (; index < length; index++) {
            // 				rest[index] = arguments[index + startIndex];
            // 			}
            // 			switch (startIndex) {
            // 				case 0:
            // 					return func.call(this, rest);
            // 				case 1:
            // 					return func.call(this, arguments[0], rest);
            // 				case 2:
            // 					return func.call(this, arguments[0], arguments[1], rest);
            // 			}
            // 			var args = Array(startIndex + 1);
            // 			for (index = 0; index < startIndex; index++) {
            // 				args[index] = arguments[index];
            // 			}
            // 			args[startIndex] = rest;
            // 			return func.apply(this, args);
            // 		};
            // 	}
            // 	var now = Date.now || function() {
            // 	return new Date().getTime();
            // 	};

            // 	var delay = restArguments(function(func, wait, args) {
            // 		return setTimeout(function() {
            // 			return func.apply(null, args);
            // 		}, wait);
            // 	});

            // 	function throttle(func, wait, options) {
            // 		var timeout, context, args, result;
            // 		var previous = 0;
            // 		if (!options) options = {};

            // 		var later = function() {
            // 			previous = options.leading === false ? 0 : now();
            // 			timeout = null;
            // 			result = func.apply(context, args);
            // 			if (!timeout) context = args = null;
            // 		};

            // 		var throttled = function() {
            // 			var _now = now();
            // 			if (!previous && options.leading === false) previous = _now;
            // 			var remaining = wait - (_now - previous);
            // 			context = this;
            // 			args = arguments;
            // 			if (remaining <= 0 || remaining > wait) {
            // 				if (timeout) {
            // 					clearTimeout(timeout);
            // 					timeout = null;
            // 				}
            // 				previous = _now;
            // 				result = func.apply(context, args);
            // 				if (!timeout) context = args = null;
            // 			} else if (!timeout && options.trailing !== false) {
            // 				timeout = setTimeout(later, remaining);
            // 			}
            // 			return result;
            // 		};

            // 		throttled.cancel = function() {
            // 			clearTimeout(timeout);
            // 			previous = 0;
            // 			timeout = context = args = null;
            // 		};

            // 		return throttled;
            // 	}

            // 	function debounce(func, wait, immediate) {
            // 		var timeout, result;

            // 		var later = function(context, args) {
            // 			timeout = null;
            // 			if (args) result = func.apply(context, args);
            // 		};

            // 		var debounced = restArguments(function(args) {
            // 			if (timeout) clearTimeout(timeout);
            // 			if (immediate) {
            // 				var callNow = !timeout;
            // 				timeout = setTimeout(later, wait);
            // 				if (callNow) result = func.apply(this, args);
            // 			} else {
            // 				timeout = delay(later, wait, this, args);
            // 			}

            // 			return result;
            // 		});

            // 		debounced.cancel = function() {
            // 			clearTimeout(timeout);
            // 			timeout = null;
            // 		};

            // 		return debounced;
            // 	}

            // 	function getExpandedSelectWidth(select) {
            // 		var options = $(select).data('resizeInput'),
            // 			textValue = !select.selectedOptions.length || select.selectedOptions.item(0).textContent,
            // 			expandedWidth = getExpandedWidth(select, textValue) + options.caretwidth;

            // 		// if (!isNaN(options.maxwidth) && options.minwidth > expandedWidth) {
            // 		// 	expandedWidth = options.minwidth;
            // 		// }
            // 		// if (!isNaN(options.maxwidth) && options.maxwidth < expandedWidth) {
            // 		// 	expandedWidth = options.maxwidth;
            // 		// }

            // 		return expandedWidth;
            // 	}

            // 	function getExpandedInputWidth(input) {
            // 		var options = $(input).data('resizeInput'),
            // 			textValue = input.value,
            // 			expandedWidth = getExpandedWidth(input, textValue) + options.extrawidth;

            // 		if (!isNaN(options.maxwidth) && options.minwidth > expandedWidth) {
            // 			expandedWidth = options.minwidth;
            // 		}
            // 		if (options.maxwidth && options.maxwidth < expandedWidth) {
            // 			expandedWidth = options.maxwidth;
            // 		}

            // 		return expandedWidth;
            // 	}

            // 	function resizeInput(settings) {

            // 		var options = $.extend({}, resizeInput.defaults, settings);

            // 		options.expandhover = !!options.expandhover;
            // 		options.expandfocus = !!options.expandfocus;

            // 		if (!['center', 'left', 'right'].includes(options.aligntext)) {
            // 			options.aligntext = resizeInput.defaults.aligntext;
            // 		}

            // 		options.expandwidth = parseFloat(options.expandwidth) || 0;
            // 		if (!options.expandhover && !options.expandfocus) {
            // 			options.expandwidth = 0;
            // 		}

            // 		options.extrawidth = parseFloat(options.extrawidth) || 0;
            // 		options.caretwidth = parseFloat(options.caretwidth) || 0;

            // 		options.minwidth = parseFloat(options.minwidth) || false;
            // 		options.maxwidth = parseFloat(options.maxwidth) || false;

            // 		if ($.isPlainObject(options.animations)) {
            // 			if (!options.animations.hasOwnProperty('expand') || !$.isPlainObject(options.animations.expand)) {
            // 				options.animations.expand = false !== options.animations.expand ? resizeInput.defaults.animations.expand : {};
            // 			}
            // 			if (!options.animations.hasOwnProperty('contract') || !$.isPlainObject(options.animations.contract)) {
            // 				options.animations.contract = false !== options.animations.contract ? resizeInput.defaults.animations.contract : {};
            // 			}
            // 		} else {
            // 			options.animations = false;
            // 		}

            // 		setTimeout(function($inputs) {
            // 			$inputs.each(function() {
            // 				$(this).trigger('blur');
            // 			});
            // 		}, 1000, this);

            // 		return this.each(function() {

            // 			var getInputWidth;
            // 			$(this).css('text-align-last', options.aligntext).data('resizeInput', options);

            // 			if ($(this).is('select')) {

            // 				getInputWidth = getExpandedSelectWidth;

            // 			} else {

            // 				getInputWidth = getExpandedInputWidth;

            // 				$(this).on('input', debounce(function(event) {
            // 					event.stopPropagation();
            // 					var inputWidth = getInputWidth(this),
            // 						animateOpts = options.animations && options.animations.expand;

            // 					if (!animateOpts) {
            // 						console.log(inputWidth, this)
            // 						$(this).width(inputWidth);
            // 					} else {
            // 						$(this).stop().animate({
            // 							width: inputWidth + 'px'
            // 						}, ...animateOpts);
            // 					}
            // 				}, 1000, true))
            // 			}

            // 			$(this).on('change', debounce(function(event) {
            // 				event.stopPropagation();
            // 				var inputWidth = getInputWidth(this),
            // 					inputFocus = $(this).is(':focus'),
            // 					animateOpts = options.animations && options.animations.expand;

            // 				if (options.expandhover || options.expandfocus) {
            // 					inputWidth += options.expandwidth;
            // 				}

            // 				if (!animateOpts) {
            // 					console.log(inputWidth, this)
            // 					$(this).width(inputWidth);
            // 				} else {
            // 					$(this).stop().animate({
            // 						width: inputWidth + 'px'
            // 					}, ...animateOpts);
            // 				}

            // 				if ($(this).is('select')) {
            // 					$(this).trigger('blur');
            // 				}
            // 			}, 1000, true)).

            // 			on('click', function(event) {
            // 				event.stopPropagation();
            // 				var inputWidth = getInputWidth(this),
            // 					inputFocus = $(this).is(':focus'),
            // 					animateOpts = options.animations && options.animations.expand;

            // 				if (options.expandhover || options.expandfocus) {
            // 					inputWidth += options.expandwidth;
            // 				}

            // 				if (!animateOpts) {
            // 					debounce($(this).width(inputWidth), 1000);
            // 				} else {
            // 					$(this).stop().animate({
            // 						width: inputWidth + 'px'
            // 					}, ...animateOpts);
            // 				}
            // 			}).

            // 			on('focus mouseover', debounce(function(event) {
            // 				event.stopPropagation();
            // 				var inputWidth = getInputWidth(this),
            // 					animateOpts = options.animations && options.animations.expand;

            // 				if (options.expandfocus) {
            // 					inputWidth += options.expandwidth;
            // 				}

            // 				if (!animateOpts) {
            // 					$(this).width(inputWidth);
            // 				} else {
            // 					$(this).stop().animate({
            // 						width: inputWidth + 'px'
            // 					}, ...animateOpts);
            // 				}
            // 			}, 1000, true)).

            // 			on('mouseout blur', debounce(function(event) {
            // 				event.stopPropagation();
            // 				var inputWidth = getInputWidth(this),
            // 					inputFocus = $(this).is(':focus'),
            // 					animateOpts = options.animations && options.animations.contract;

            // 				if (!inputFocus) {
            // 					if (!animateOpts) {
            // 						$(this).width(inputWidth);
            // 					} else {
            // 						$(this).stop().animate({
            // 							width: inputWidth + 'px'
            // 						}, ...animateOpts);
            // 					}
            // 				}

            // 			}, 1000, true)).trigger('change');
            // 		})
            // 	}


            // 	resizeInput.defaults = {

            // 		// Expand the width of the input on mouse over.
            // 		expandhover: true,

            // 		// Expand the width of the input on focus.
            // 		expandfocus: true,

            // 		// Alignment of the text in the expanded field.
            // 		aligntext: 'center',

            // 		// Extra amount to expand for expandhover and expandfocus.
            // 		expandwidth: 40,

            // 		// This amount is added to the width.
            // 		extrawidth: 3,

            // 		// Width of arrow on dropdown menu.
            // 		caretwidth: 2,

            // 		// Min/max for the field width.
            // 		minwidth: 40,
            // 		maxwidth: NaN,

            // 		// Animation timing and easing options. Set this value to false and disable JS animations.
            // 		// This gives you the opportunty to supply the animation using CSS.
            // 		animations: false // {
            // 		// 	// expand: {
            // 		// 	// 	duration: 300,
            // 		// 	// 	easing: 'easein'
            // 		// 	// },
            // 		// 	// contract: {
            // 		// 	// 	duration: 200,
            // 		// 	// 	easing: 'easein'
            // 		// 	// }
            // 		// 	expand: [
            // 		// 		300,
            // 		// 		'easein'
            // 		// 	],
            // 		// 	contract: [
            // 		// 		200,
            // 		// 		'easein'
            // 		// 	]
            // 		// }
            // 	}

            // 	$.fn.extend({
            // 		resizeInput: resizeInput
            // 	})
            // })(jQuery);


            jQuery(function($) {

                if (!String.prototype.startsWith) {
                    Object.defineProperty(String.prototype, 'startsWith', {
                        value: function(search, rawPos) {
                            var pos = rawPos > 0 ? rawPos | 0 : 0;
                            return this.substring(pos, pos + search.length) === search;
                        }
                    });
                }

                String.prototype.trimLeft = function(charlist) {
                    if (charlist === undefined) charlist = "\s";
                    return this.replace(new RegExp("^[" + charlist + "]+"), "");
                };

                String.prototype.trimRight = function(charlist) {
                    if (charlist === undefined) charlist = "\s";
                    return this.replace(new RegExp("[" + charlist + "]+$"), "");
                };

                // Prevent enter submitting post form.
                $('#upsell_product_data').bind('keypress', function(e) {
                    if (e.keyCode === 13) {
                        return false;
                    }
                });

                // Route box.
                $('#sealed-box-service-route-data .hndle').append($('#route_box'));

                // Resizing inline route fields.
                $('#route_box :input').resizeInput();

                $(function() {
                    // Prevent inputs in meta box headings opening/closing contents.
                    $('#sealed-box-service-route-data').find('.hndle').unbind('click.postboxes');

                    $('#sealed-box-service-route-data').on('click', '.hndle', function(event) {

                        // If the user clicks on some form input inside the h3 the box should not be toggled.
                        if ($(event.target).filter('small, span, :input, option, label, select').length) {
                            return;
                        }

                        $('#sealed-box-service-route-data').toggleClass('closed');
                    });
                });

                // Route box input slug value inline.
                $(function() {

                    $('#route_box select option').each(function() {
                        $(this).data('title', $(this).text()).text($(this).data('value'));
                    });

                    var $route_varsion_group = $('#sbx_route_versionchecklist');

                    $("#route_version").
                    on('mousedown focus', function() {
                        var value = $(this).val();
                        $('[selected]', this).removeAttr('selected');
                        $(this).append($('option', this)).val(value);
                        $route_varsion_group.remove();
                    }).
                    on('mouseout', function() {
                        var value = $(this).val();
                        $('[selected]', this).removeAttr('selected');
                        $(this).append($route_varsion_group.append($('option', this))).val(value);
                    });

                    var $route_namespace_group = $('#sbx_route_namespacechecklist');

                    $("#route_namespace").
                    on('mousedown focus', function() {
                        var value = $(this).val();
                        $('[selected]', this).removeAttr('selected');
                        $(this).append($('option', this)).val(value);
                        $route_namespace_group.remove();
                    }).
                    on('mouseout', function() {
                        var value = $(this).val();
                        $('[selected]', this).removeAttr('selected');
                        $(this).append($route_namespace_group.append($('option', this))).val(value);
                    });

                    $('#route_type option').each(function() {
                        $(this).data('title', $(this).text());
                    });

                    $("#route_box select").
                    on('mousedown focus', function() {
                        $('option', this).each(function() {
                            $(this).text($(this).data('title'));
                        });
                    }).
                    on('mouseout change blur', function() {
                        $('option', this).each(function() {
                            $(this).text($(this).data('value'));
                        });
                    });

                });

                $(function() {

                    // Show selected service type descripttion
                    function update_service_type_description() {
                        return $('#service_description').length && $('#route-type option:selected').length && $('#service_description').text($('#route-type option:selected').attr('title'));
                        // return $('#service_description').length && $('#route-type option:selected').length && sbx_service_type && sbx_service_type.hasOwnProperty('description') && sbx_service_type['description'].hasOwnProperty($('#route-type').val()) && $('#service_description').text(sbx_service_type['description'][$('#route-type').val()]);
                    }

                    $(document.body).

                    // Synchronize post name input
                    on('change input', '#post-name', function(ev) {
                        if ($(ev.target).val() !== $('#post_name').val()) {
                            $('#post_name').val($(ev.target).val()).trigger('change');
                        }
                    }).

                    on('change input', '#post_name', function(ev) {
                        if ($(ev.target).val() !== $('#post-name').val()) {
                            $('#post-name').val($(ev.target).val()).trigger('change');
                        }
                    }).

                    // Synchronize routebox service type input
                    on('change', '#route-type', function(ev) {
                        if ($(':selected', $(ev.target)).data('title') !== $('#route_type :selected').data('title')) {
                            $('#route_type').val($(':selected', $(ev.target)).data('value')).trigger('change');
                            update_service_type_description();
                        }
                    }).

                    on('change', '#route_type', function(ev) {
                        if ($(ev.target).val() !== $('#route-type :selected').data('value')) {
                            $('#route-type').val($('#route-type option[data-value="' + $(ev.target).val() + '"]').val()).trigger('change');
                            update_service_type_description();
                        }
                    }).

                    // Synchronize routebox namespace input
                    on('change', '#route-namespace', function(ev) {
                        if ($(':selected', $(ev.target)).data('title') !== $('#route_namespace :selected').data('title')) {
                            $('#route_namespace').val($(':selected', $(ev.target)).data('value')).trigger('change');
                            update_service_type_description();
                        }
                    }).

                    on('change', '#route_namespace', function(ev) {
                        if ($(ev.target).val() !== $('#route-namespace :selected').data('value')) {
                            $('#route-namespace').val($('#route-namespace option[data-value="' + $(ev.target).val() + '"]').val()).trigger('change');
                            update_service_type_description();
                        }
                    }).

                    // Synchronize routebox version input
                    on('change', '#route-version', function(ev) {
                        if ($(':selected', $(ev.target)).data('title') !== $('#route_version :selected').data('title')) {
                            $('#route_version').val($(':selected', $(ev.target)).data('value')).trigger('change');
                            update_service_type_description();
                        }
                    }).

                    on('change', '#route_version', function(ev) {
                        if ($(ev.target).val() !== $('#route-version :selected').data('value')) {
                            $('#route-version').val($('#route-version option[data-value="' + $(ev.target).val() + '"]').val()).trigger('change');
                            update_service_type_description();
                        }
                    }).

                    // Synchronize freshly added namespace
                    on('wpListAddEnd', '#sbx_route_namespacechecklist', function(ev) {
                        var $lsItem = $(ev.target).parent(),
                            $addTag = $lsItem.find(':selected'),
                            $newOpt = $('<option/>', {
                                text: $addTag.text(),
                                attr: {
                                    value: $addTag.data('value'),
                                    'data-value': $lsItem.val(),
                                    'data-title': $addTag.text()
                                }
                            });
                        $('#route-namespace [selected]').removeAttr('selected');
                        $('#route-namespace optgroup').prepend($newOpt);
                        $('#route-namespace').val($addTag.data('value')).trigger('change').trigger('blur');
                    }).

                    // Synchronize freshly added version
                    on('wpListAddEnd', '#sbx_route_versionchecklist', function(ev) {
                        var $lsItem = $(ev.target).parent(),
                            $addTag = $lsItem.find(':selected'),
                            $newOpt = $('<option/>', {
                                text: $addTag.text(),
                                attr: {
                                    value: $addTag.data('value'),
                                    'data-value': $lsItem.val(),
                                    'data-title': $addTag.text()
                                }
                            });
                        $('#route-version [selected]').removeAttr('selected');
                        $('#route-version optgroup').prepend($newOpt);
                        $('#route-version').val($addTag.data('value')).trigger('change').trigger('blur');
                    }).

                    // Synchronize route method
                    // on('change input', '#request-method', function(ev) {
                    //     if ($(ev.target).val() !== $('#_request_method').val()) {
                    //         $('#_request_method').val($(ev.target).val()).trigger('change');
                    //     }
                    // }).

                    on('change input', '#_request_method_field', function(ev) {
                        var newValue = $('[name^="_request_method["]').serializeArray().map(function(item){return item.value}).join(',');
                        if (newValue !== $('#request-method').val()) {
                            $('#request-method').append($('#request-method > option').first().remove().attr('value',newValue).text(newValue)).trigger('change');
                        }
                    }).

                    // Synchronize encrypted parameter
                    on('change input', '#message-param', function(ev) {
                        if ($(ev.target).val() !== $('#_message_param').val()) {
                            $('#_message_param').val($(ev.target).val()).trigger('change');
                        }
                    }).

                    on('change input', '#_message_param', function(ev) {
                        if ($(ev.target).val() !== $('#message-param').val()) {
                            $('#message-param').val($(ev.target).val()).trigger('change');
                        }
                    });

                    // Run sync
                    $('#route-type').trigger('change');
                    $('#route-namespace').trigger('change');
                    $('#route-version').trigger('change');

                    // Set service type description
                    update_service_type_description()
                });

                // Service Type specific options.
                // $('#route-type').change(function () {

                // 	// Get value.
                // 	var select_val = $(this).find("option:selected").data('value')

                // 	if ('basic' === select_val) {
                // 		// $( 'input#_manage_stock' ).change();
                // 		// $( 'input#_versioned_route' ).prop( 'checked', false );
                // 		// $( 'input#_protected_route' ).removeAttr( 'checked' );
                // 	} else if ('redirection' === select_val) {
                // 		// $( 'input#_versioned_route' ).prop( 'checked', false );
                // 		// $( 'input#_protected_route' ).removeAttr( 'checked' );
                // 	}

                // 	show_and_hide_panels();

                // 	$('ul.sbx-tabs li:visible').eq(0).find('a').click();

                // 	$(document.body).trigger('sealed-box-route-type-change', select_val, $(this));

                // });

                // $('input#_versioned_route, input#_protected_route, input#_restricted_route').change(function () {
                // 	show_and_hide_panels();
                // });

                function show_and_hide_panels() {
                    var is_show = [],
                        is_hide = [];
                    $('.options_group p :input').map(function() {
                        var $this = $(this),
                            type = $this.is('input') ? $this.attr('type') : this.tagName.toLowerCase(),
                            value = 'checkbox' === type ? $this.is(':checked') : (type !== 'select' ? false : $this.val()),
                            name = value ? $this.attr('name').trimLeft('_') : null;
                        return !value ? null : (true === value ? name : name + '_is_' + value.split(' ').join('_').toLowerCase());
                    }).toArray().forEach(function(val) {
                        is_show.push('.show_if_' + val);
                        is_hide.push('.hide_if_' + val);
                    });

                    $('#sealed-box-service-route-data [class*="hide_if_"]').show();
                    $('#sealed-box-service-route-data [class*="show_if_"]').hide();

                    $(is_show.join(', ')).show();
                    $(is_hide.join(', ')).hide();

                    // Hide empty panels/tabs after display.
                    $('.sealed_box_options_panel').each(function() {
                        var $children = $(this).children('.options_group');

                        if (0 === $children.length) {
                            return;
                        }

                        var $invisble = $children.filter(function() {
                            return 'none' === $(this).css('display');
                        });

                        // Hide panel.
                        if ($invisble.length === $children.length) {
                            var $id = $(this).prop('id');
                            $('.service_route_data_tabs').find('li a[href="#' + $id + '"]').parent().hide();
                        }
                    });

                    if (!$('.service_route_data_tabs li.active').is(':visible')) {
                        $('ul.sbx-tabs li:visible').eq(0).find('a').click();
                    }

                    return;

                    // var rest_service_type = $('#route-type').find(':selected').data('value');
                    // var is_publiclykeyed = $('input#_protected_route:checked').length;
                    // var is_restricted = $('input#_restricted_route:checked').length;
                    // var is_versioned = $('input#_versioned_route:checked').length;

                    // // Hide/Show all with rules.
                    // var hide_classes = '.hide_if_versioned_route, .hide_if_restricted_route, .hide_if_protected_route';
                    // var show_classes = '.show_if_versioned_route, .show_if_restricted_route, .show_if_protected_route';

                    // $.each(['basic', 'redirection'], function (index, value) {
                    // 	hide_classes += ', .hide_if_' + value;
                    // 	show_classes += ', .show_if_' + value;
                    // });

                    // $(hide_classes).show();
                    // $(show_classes).hide();

                    // // Shows rules.
                    // if (is_versioned) {
                    // 	$('.show_if_versioned_route').show();
                    // }
                    // if (is_restricted) {
                    // 	$('.show_if_restricted_route').show();
                    // }
                    // if (is_publiclykeyed) {
                    // 	$('.show_if_protected_route').show();
                    // }

                    // $('.show_if_' + rest_service_type).show();

                    // // Hide rules.
                    // if (is_versioned) {
                    // 	$('.hide_if_versioned_route').hide();
                    // }
                    // if (is_restricted) {
                    // 	$('.hide_if_restricted_route').hide();
                    // }
                    // if (is_publiclykeyed) {
                    // 	$('.hide_if_protected_route').hide();
                    // }

                    // $('.hide_if_' + rest_service_type).hide();

                    // $('input#_manage_stock').change();

                    // Hide empty panels/tabs after display.
                    // $('.sealed_box_options_panel').each(function () {
                    // 	var $children = $(this).children('.options_group');

                    // 	if (0 === $children.length) {
                    // 		return;
                    // 	}

                    // 	var $invisble = $children.filter(function () {
                    // 		return 'none' === $(this).css('display');
                    // 	});

                    // 	// Hide panel.
                    // 	if ($invisble.length === $children.length) {
                    // 		var $id = $(this).prop('id');
                    // 		$('.service_route_data_tabs').find('li a[href="#' + $id + '"]').parent().hide();
                    // 	}
                    // });
                }

                $('.options_group').find('select, input[type="checkbox"]').change(show_and_hide_panels);

                // Stock options.
                /* $('input#_manage_stock').change(function () {
                    if ($(this).is(':checked')) {
                        $('div.stock_fields').show();
                        $('p.stock_status_field').hide();
                    } else {
                        var rest_service_type = $('option[value="' + $('select#route-type').val() + '"]', $('select#route-type')).data('value');

                        $('div.stock_fields').hide();
                        $('p.stock_status_field:not( .hide_if_' + rest_service_type + ' )').show();
                    }

                    $('input.variable_manage_stock').change();
                }).change(); */

                show_and_hide_panels();

            });

            jQuery(function($) {

                // Run tipTip
                function runTipTip() {
                    // Remove any lingering tooltips
                    $('#tiptip_holder').removeAttr('style');
                    $('#tiptip_arrow').removeAttr('style');
                    $('.tips').tipTip({
                        'attribute': 'data-tip',
                        'fadeIn': 50,
                        'fadeOut': 50,
                        'delay': 200
                    });
                }

                runTipTip();

                // Allow Tabbing
                $('#titlediv').find('#title').keyup(function(event) {
                    var code = event.keyCode || event.which;

                    // Tab key
                    /* if (code === '9' && $('#sealed-box-coupon-description').length > 0) {
                        event.stopPropagation();
                        $('#sealed-box-coupon-description').focus();
                        return false;
                    } */
                });

                $('.sbx-metaboxes-wrapper').on('click', '.sbx-metabox > h3', function() {
                    $(this).parent('.sbx-metabox').toggleClass('closed').toggleClass('open');
                });

                // Tabbed Panels
                $(document.body).on('sbx-init-tabbed-panels', function() {
                    $('ul.sbx-tabs').show();
                    $('ul.sbx-tabs a').click(function(e) {
                        e.preventDefault();
                        var panel_wrap = $(this).closest('div.panel-wrap');
                        $('ul.sbx-tabs li', panel_wrap).removeClass('active');
                        $(this).parent().addClass('active');
                        $('div.panel', panel_wrap).hide();
                        $($(this).attr('href')).show();
                    });
                    $('div.panel-wrap').each(function() {
                        $(this).find('ul.sbx-tabs li').eq(0).find('a').click();
                    });
                }).trigger('sbx-init-tabbed-panels');

                // Meta-Boxes - Open/close
                $('.sbx-metaboxes-wrapper').on('click', '.sbx-metabox h3', function(event) {
                    // If the user clicks on some form input inside the h3, like a select list (for variations), the box should not be toggled
                    if ($(event.target).filter(':input, option, .sort').length) {
                        return;
                    }

                    $(this).next('.sbx-metabox-content').stop().slideToggle();
                }).

                on('click', '.expand_all', function() {
                    $(this).closest('.sbx-metaboxes-wrapper').find('.sbx-metabox > .sbx-metabox-content').show();
                    return false;
                }).

                on('click', '.close_all', function() {
                    $(this).closest('.sbx-metaboxes-wrapper').find('.sbx-metabox > .sbx-metabox-content').hide();
                    return false;
                });

                $('.sbx-metabox.closed').each(function() {
                    $(this).find('.sbx-metabox-content').hide();
                });
            });
        </script>

        <?php
    }
}

SBX_Admin_Post_Types::get_instance();
