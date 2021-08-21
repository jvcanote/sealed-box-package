<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="schema_service_route_data" class="panel sealed_box_options_panel hidden">

	<div class="options_group">
        <?php
        sealed_box_wp_text_input(
            array(
                'id'                => '_message_param',
                'label'             => __( 'Encrypted parameter', 'sealedbox' ),
				'value'             => $route_object->message_param ?? 'sealed_box',
                'default'           => 'sealed_box',
                'class'             => 'short',
                'wrapper_class'     => 'col-1',
                'required'          => true,
                'desc_tip'          => true,
                'description'       => __( 'The parameter containing the encrypted message. This value will be converted to lowercase alphanumeric characters. Spaces will be replaced with dashes.', 'sealed-box'),
                'placeholder'       => 'sealed_box',
            )
        );

        sealed_box_wp_paragraph_input(
            array(
                'id'                 => 'service-description',
                'class'              => 'col-1 description',
                'label'              => __( 'About this service type', 'sealedbox' ),
                'field_type'         => 'paragraph',
            )
        );

		sealed_box_wp_select_input(
			array(
				'id'            => '_message_format',
				'label'         => __( 'Decrypted format', 'sealedbox' ),
				'value'         => $route_object->message_format,
                'default'       => 'raw',
                'class'         => 'select short',
                'wrapper_class' => 'col-1',
                'required'      => true,
                'desc_tip'      => true,
                'description'   => __( 'The format expected of the decrypted message.', 'sealedbox' ),
                'options'       => array(
                    'raw'     => 'Raw',
                    'array'   => 'Array',
                    'boolean' => 'Boolean',
                    'integer' => 'Integer',
                    'number'  => 'Number',
                    'object'  => 'Object',
                    'string'  => 'String',
                    'csv'     => 'CSV',
                    'json'    => 'JSON',
                    'url'     => 'URL',
                    'xml'     => 'XML',
                )
			)
        );

        sealed_box_wp_repeater_input(
            array(
                'id'                => '_argument_schema',
                'add'               => __( 'Add', 'sealedbox' ),
                'label'             => __( 'Argument schema', 'sealedbox' ),
				'value'             => $route_object->argument_schema,
                'class'             => '',
                'wrapper_class'     => 'widefat col-2',
                'desc_tip'          => false,
                'description'       => __( 'Argument schema to provide data about which parameters should be accepted.', 'sealed-box'),
                'fields'            => array(
                    array(
                        'id'            => 'name',
                        'label'         => __( 'Name', 'sealedbox' ),
                        'type'          => 'text',
                        'head_class'    => 'th-full',
                        'wrapper_class' => 'col-1 td-full',
                    ),
                    array(
                        'id'            => 'type',
                        'label'         => __( 'Type', 'sealedbox' ),
                        'type'          => 'select',
                        'head_class'    => 'th-full',
                        'options'       => array(
                            'string'  => 'String',
                            'boolean' => 'Boolean',
                            'integer' => 'Integer',
                            'number'  => 'Number',
                        ),
                    ),
                    array(
                        'id'            => 'required',
                        'label'         => __( 'Required', 'sealedbox' ),
                        'type'          => 'checkbox',
                        'default'       => 1,
                        'head_class'    => 'th-full num',
                        'wrapper_class' => 'num',
                    )
                )
			)
        );
        ?>
        <div class="clear"></div>

	</div>

    <?php do_action( 'sealed_box_schema_service_route_data' ); ?>

</div>
