<?php
/**
 * Displays the inventory tab in the product data meta box.
 *
 * @package SealedBox\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="request_service_route_data" class="panel sealed_box_options_panel hidden">

	<div class="options_group">
		<?php
		sealed_box_wp_checkbox_input(
            array(
                'id'            => '_restricted_route',
                'value'         => $route_object->restricted_route,
                'wrapper_class' => 'col-2',
                'label'         => __( 'Restrict access', 'sealedbox' ),
                'description'   => __( 'Only allow requests covered by the security policies access to this service type.', 'sealedbox' ),
                'desc_tip'      => false,
                'default'       => 'yes',
            )
        );?>
    </div>

	<div class="options_group show_if_restricted_route">
		<?php
		sealed_box_wp_textarea_input(
			array(
                'id'                => '_request_hosts',
                'value'             => $route_object->request_hosts,
                'wrapper_class'     => 'col-1',
                'class'             => 'textarea code',
                'label'             => __( 'Request hosts', 'sealedbox' ),
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_host_list',
                'show_in_rest'      => false,
                'single'            => true,
                'rows'              => 5,
                'cols'              => 10,
                // 'desc_tip'          => __( 'List one per line with <code>*</code> as wildcard. Use the prefix <code>-</code> to deny access from specific referers.', 'sealedbox' ),
                // 'description'       => __( 'Referer host names and IP addresses. Restrict endpoint access to requests comming from referers listed here.', 'sealedbox' ) . __( 'List one per line with <code>*</code> as wildcard. Use the prefix <code>-</code> to deny access from specific referers.', 'sealedbox' ),
                'placeholder'       => "*.allowed.com\n192.168.*.*\n-not.allowed.net",
            )
        );

		sealed_box_wp_paragraph_input(
            array(
                'id'                 => 'request_hosts_description',
                'class'              => 'col-1',
                'label'              => __( 'Specify host names and IP addresses', 'sealedbox' ),
                'field_type'         => 'paragraph',
                'description'        => __( 'Restrict access to requests comming from domains and IPs listed here. List one per line. Match a range of hosts with the wildcard <code>*</code> expression. Add the prefix <code>-</code> to disallow access for particular domains.', 'sealedbox' ),
            )
        );
		?>
        <div class="clear"></div>

	</div>

    <?php do_action( 'sealed_box_request_service_route_data' ); ?>

</div>
