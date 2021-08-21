<?php

defined( 'ABSPATH' )|| exit;

?>
<div id="redirection_service_route_data" class="panel sealed_box_options_panel hidden">


	<div class="options_group ">
		<?php
        sealed_box_wp_select_input(
            array(
                'id'                 => '_redirect_status_code',
                'value'              => $route_object->_redirect_status_code,
                'wrapper_class'      => 'col-2',
                'class'              => 'select short',
                'label'              => __( 'Redirect status code', 'sealedbox' ),
                'required'           => true,
				'desc_tip'           => true,
				// 'description'        => __( 'Redirection header status codes.', 'sealedbox' ),
                'options'            => array(
                    307  => '307 ' . get_status_header_desc( 307 ) . ' (preserves method)',
                    303  => '303 ' . get_status_header_desc( 303 ) . ' (force GET method)',
                    302  => '302 ' . get_status_header_desc( 302 ) . ' (legacy default)',
                ),
            )
        );
        /* sealed_box_wp_paragraph_input(
            array(
                'id'                 => 'service-description',
                'class'              => 'col-1 description',
                'label'              => __( 'About this service type', 'sealedbox' ),
                'field_type'         => 'paragraph',
            )
        ); */
		/* sealed_box_wp_checkbox_input(
			array(
                'id'                 => '_redirect_encrypted_param_is',
                'value'              => $route_object->redirect_encrypted_param_is,
                'wrapper_class'      => 'col-2',
                'label'              => __( 'Encrypted parameter is', 'sealedbox' ),
                'required'           => true,
                'desc_tip'           => true,
                'description'        => __( 'The redirect URL is encrypted parameter.', 'sealedbox' ),
                'options'            => array(
					'redirect_url'   =>  __( 'The redirect URL', 'sealedbox' ),
					'list_pointer'   =>  __( 'URL list pointer', 'sealedbox' ),
					'extra_params'   =>  __( 'Request parameters', 'sealedbox' ),
                ),
			)
		); */
		?>
	</div>

	<div class="options_group">
		<?php
		sealed_box_wp_checkbox_input(
            array(
                'id'            => '_redirect_request_params',
                'value'         => $route_object->redirect_request_params,
                'wrapper_class' => 'col-2',
                'label'         => __( 'Preserve request data', 'sealedbox' ),
                'description'   => __( 'Pass through the original request paramaters.', 'sealedbox' ),
                'desc_tip'      => false,
                'default'       => 'yes',
            )
		);
		?>
		<div class="clear"></div>

	</div>

    <?php do_action( 'sealed_box_redirection_service_route_data' ); ?>

</div>