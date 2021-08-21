<?php
/**
 * Sealed Box Redirection
 *
 *
 *
 * @package  SealedBox/Service/Redirection
 * @version  3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SBX_Service_Redirection Class.
 */
class SBX_Service_Redirection extends SBX_Service_Basic {

	/** @var string $name description */
	protected $name = 'redirection';

	/** @var string $version service version. */
	protected $version = '1.0.0';

	/** @var self $instance description */
	protected static $instance = null;

    /**
     * Define service settings.
     *
     * @since   1.0.0
	 * @param   mixed[]            $settings
     * @return  void
     */
    protected function define_settings() {

		parent::define_settings();

		$this->settings = array(


			// Service information
			'info' => array(
				'name'        => __( 'Redirection', 'sealedbox' ),
				'description' => _x( 'Create a Sealed Box Redirection Service to forward requests to the location encrypted within the ciphertext.', 'REST service type description ', 'sealedbox' ),
			),


			// Meta box tab settings
			'data_meta_box_tab_settings' => parent::get_data_meta_box_tab_settings( array(
					'redirection' => array(
						'label'    => __( 'Redirection', 'sealedbox' ),
						'target'   => 'redirection_service_route_data',
						'class'    => array( 'show_if_route_type_is_redirection' ),
						'view'     => SBX_ABSPATH . 'includes/services/views/html-route-data-redirection.php',
						'priority' => 70,
					),
				)
			),


			// Post meta settings
			'post_meta_settings' => parent::get_post_meta_settings(
				sbx_parse_args_list( array(
						'_redirect_status_code'  => array(
							'description'   => __( 'Redirection header status codes.', 'sealedbox' ),
						),
						'_redirect_request_params'  => array(
							'description'   => __( 'Pass through the original request paramaters.', 'sealedbox' ),
						)
					),

					SEALED_BOX_DEFAULT_POST_META_SETTINGS
				)
			)
		);
	}

	/**
	 * Return array of post meta settings.
	 *
	 * @return mixed[]
	 */
	public function get_post_meta_settings( $settings = array() ) {
		$settings = parent::get_post_meta_settings( $settings );
		return wp_parse_args( $this->settings['post_meta_settings'], $settings );
	}

	/**
     * Process the payload thourgh this service type.
     *
     * @since   1.0.0
	 * @param   mixed             $payload
	 * @param   string            $cipher_text
	 * @param   integer           $route_id
     * @return  void
     */
    public function perform_service( $payload, $cipher_text, $route_id, $rest_route ) {

		if ( ! is_string( $payload ) || ! sbx_is_valid_url( $payload ) ) {
			$rest_route->response = new WP_Error( 'rest_invalid_redirect_url', esc_html__( 'The redirect URL is not valid.', 'sealedbox' ), array( 'status' => 500 ) );

			return;
		}

		$query = $_GET;
		$route = sbx_get_route( $route_id );

		unset( $query[ $route->message_param ] );

		$redirect_url = sbx_string_to_bool( $route->sbx_redirect_request_params ) ? add_query_arg( $query, $payload ) : $payload;

		// Create the response object
		$response = new WP_REST_Response();

		// Add a custom status code
		$response->set_status( $route->redirect_status_code );

		// Add a custom header
		$response->header( 'Location', $redirect_url );

		$rest_route->response = new WP_Error( 'rest_redirection', esc_html__( 'This URL has been redirected.', 'sealedbox' ), array( 'status' => 307 ) );
	}
}
