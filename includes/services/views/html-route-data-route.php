<?php
/**
 * Route general data panel.
 *
 * @package SealedBox/Admin
 */

defined( 'ABSPATH' ) || exit;

?>
<div id="route_service_route_data" class="panel sealed_box_options_panel">

	<div class="options_group">
        <?php
        sealed_box_wp_checkbox_input(
            array(
                'id'            => '_versioned_route',
                'value'         => $route_object->versioned_route,
                'wrapper_class' => 'col-2',
                'label'         => __( 'Use version', 'sealedbox' ),
                'default'       => 'yes',
                'desc_tip'      => false,
                'description'   => __( 'Versioned routes will branch to support multiple WP-JSON REST API versions.', 'sealedbox' ),
            )
        );  ?>
    </div>

    <div class="options_group">
        <?php
        wp_enqueue_script( 'sbx-term-input' );

        wp_nonce_field( 'radio_nonce-sbx_route_version', '_radio_nonce-sbx_route_version' );
        sealed_box_wp_select_term_input(
            array(
                'id'                => 'route_version',
                'name'              => 'route_version',
                'label'             => $route_version_object->labels->singular_name,
                'value'             => $route_object->version,
                'class'             => 'select short',
                'wrapper_class'     => 'col-1 show_if_versioned_route',
                'options'           => sbx_get_the_terms( 'sbx_route_version' ),
                'desc_tip'          => false,
                'taxonomy'          => 'sbx_route_version',
            )
        );

        sealed_box_wp_text_input(
            array(
                'id'                => 'post_name',
                'name'              => 'post_name',
                'label'             => 'Route name',
                'value'             => $route_object->name,
                'class'             => 'short',
                'wrapper_class'     => 'col-1',
                'required'          => true,
                'desc_tip'          => true,
                'description'       => __('The unique name for this route. This value will be converted to lowercase alphanumeric characters. Spaces will be replaced with dashes.', 'sealed-box'),
            )
        );

        wp_nonce_field( 'radio_nonce-sbx_route_type', '_radio_nonce-sbx_route_type' );
        sealed_box_wp_select_input(
            array(
                'id'                => 'route_type',
                'label'             => $route_type_object->labels->singular_name,
                'value'             => $route_object->route_type_id,
                'class'             => 'select short',
                'wrapper_class'     => 'col-1',
                'options'           => sbx_get_the_terms( 'sbx_route_type', 'name', 'slug' ),
                'desc_tip'          => false,
            )
        );

        wp_nonce_field( 'radio_nonce-sbx_route_namespace', '_radio_nonce-sbx_route_namespace' );
        sealed_box_wp_select_term_input(
            array(
                'id'                => 'route_namespace',
                'name'              => 'route_namespace',
                'label'             => $route_namespace_object->labels->singular_name,
                'value'             => $route_object->namespace,
                'class'             => 'select short',
                'wrapper_class'     => 'col-1',
                'options'           => sbx_get_the_terms( 'sbx_route_namespace' ),
                'desc_tip'          => false,
                'taxonomy'          => 'sbx_route_namespace',
            )
        );

        sealed_box_wp_checkboxes_input(
            array(
                'id'                 => '_request_method',
                'value'              => $route_object->request_method,
                'wrapper_class'      => 'col-1',
                'class'              => 'checkbox',
                'label'              => __( 'Request method', 'sealedbox' ),
                'required'           => true,
                'desc_tip'           => true,
                'description'        => __( 'The specific methods used to make service type requests through this route.', 'sealedbox' ),
                'custom_attributes'  => array(
                    'OPTIONS' => array(
                        'disabled' => 'disabled',
                        'readonly' => 'readonly',
                        'checked'  => 'checked'
                    )
                ),
                'options'            => array(
                    WP_REST_Server::READABLE  => WP_REST_Server::READABLE,
                    WP_REST_Server::CREATABLE => WP_REST_Server::CREATABLE,
                    'OPTIONS' => 'OPTIONS',
                ),
            )
        );

        sealed_box_wp_paragraph_input(
            array(
                'id'                => 'service_description',
                'class'             => 'col-1',
                'label'             => __('About this service type', 'sealed-box'),
            )
        );
        ?>
        <div class="clear"></div>

    </div>

    <?php do_action( 'sealed_box_route_service_route_data' ); ?>

</div>
