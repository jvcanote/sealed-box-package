<?php
/**
 * Basic Sealed Box Service
 *
 *
 *
 * @package  SealedBox/Service/Basic
 * @version  3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SBX_Service_Basic Class.
 */
class SBX_Service_Basic extends SBX_Abstract_Service {

	/** @var string $name description */
	protected $name = 'basic';

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

		$this->settings = array(


			// Service information
			'info' => array(
				'name'        => __( 'Basic', 'sealedbox' ),
				'description' => _x( 'REST endpoints providing the Basic Sealed Box Service, will return a response based on the decrypted ciphertext values encapsulated within the message parameter.', 'REST service description ', 'sealedbox' ),
			),


			// Meta box tab settings
			'data_meta_box_tab_settings' => array(
				'route' => [
					'label'    => __( 'Route', 'sealedbox' ),
					'target'   => 'route_service_route_data',
					'view'     => SBX_ABSPATH . 'includes/services/views/html-route-data-route.php',
					'class'    => array(),
					'priority' => 10,
				],
				'request'  => [
					'label'    => __( 'Request', 'sealedbox' ),
					'target'   => 'request_service_route_data',
					'view'     => SBX_ABSPATH . 'includes/services/views/html-route-data-request.php',
					'class'    => array(),
					'priority' => 20,
				],
				'schema'   => [
					'label'    => __( 'Schema', 'sealedbox' ),
					'target'   => 'schema_service_route_data',
					'view'     => SBX_ABSPATH . 'includes/services/views/html-route-data-schema.php',
					'class'    => array(),
					'priority' => 30,
				]
			),


			// Post meta settings
			'post_meta_settings' => sbx_parse_args_list( array(
					'_versioned_route'  => array(
						'description'   => __( 'Versioned routes will branch to support multiple WP-JSON REST API versions.', 'sealedbox' ),
					),
					'_restricted_route' => array(
						'description'   => __( 'Only allow requests covered by the security policies access to this service type.', 'sealedbox' ),
					),
					'_request_method'   => array(
						'description'   => __( 'The specific methods used to make service type requests through this route.', 'sealedbox' ),
						'show_in_rest'  => array(
							'schema' => array(
								'type' => 'array',
								'enum' => array(
									WP_REST_Server::READABLE  => WP_REST_Server::READABLE,
									WP_REST_Server::CREATABLE => WP_REST_Server::CREATABLE,
									// 'OPTIONS' => 'OPTIONS'
									// WP_REST_Server::READABLE . 'or' .  WP_REST_Server::CREATABLE => WP_REST_Server::READABLE . 'or' .  WP_REST_Server::CREATABLE,
								),
								'items' => 'string',
							)
						)
					),
					'_request_hosts'    =>  array(
						'description'       => __( 'Restrict access to requests comming from domains and IPs listed here.', 'sealedbox' ),
						'sanitize_callback' => 'sbx_sanitize_host_list',
					),
					'_message_param'    =>  array(
						'description'   => __( 'The parameter containing the encrypted message.', 'sealedbox' ),
						'default'       => 'sealed_box'
					),
					'_message_format'   =>  array(
						'description'   => __( 'The format expected of the decrypted message.', 'sealedbox' )
					),
					'_argument_schema'  => array(
						'type'          => 'array',
						'description'   => __( 'Argument schema to provide data about which parameters should be accepted.', 'sealedbox' ),
						'single'        => true,
						'show_in_rest'  => array(
							'schema' => array(
								'items' => array(
									'type'       => 'object',
									'properties' => array(
										'name' => array(
											'type' => 'string',
										),
										'type' => array(
											'type' => 'string',
											'enum' => array(
												'string',
												'boolean',
												'integer',
												'number',
											),
										),
										'required' => array(
											'type' => 'boolean',
										)
									)
								)
							)
						)
					)
				),

				SEALED_BOX_DEFAULT_POST_META_SETTINGS
			)
		);
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
    public function perform_service( $payload, $cipher_text, $route_id, $rest_route ) {}

    /**
     * Add meta box.
     *
     * @since 1.0.0
     * @return  void
     */
    public function add_meta_box( $post ) {
		add_meta_box( 'sealed-box-rest-route-info', __( 'Route Info', 'sealedbox' ), array( $this, 'route_info_metabox' ), 'sbx_service_route' );
	}

	/**
	 * Undocumented function
	 *
	 * @param [type] $post
	 */
	public function route_info_metabox( $post ) {
		global $route_post_id, $route_object, $route_type_object, $route_namespace_object, $wp_meta_keys;

		$route_object = sbx_get_route( $route_post_id );
		$hook_base    = $route_object->get_namespace();
		// $cypher = '';
		// $cypher = $route_object->encrypt_payload( array( 'message' => "Hello Dolly — it's so nice to have you back where you belong." ) );
		// echo '
		// 	<p>Access your custom route through the following link: <br/>
		// 		<a target="_blank" href="' . esc_url( $route_object->get_rest_api_service_route() ) . '?' . $route_object->message_param . '=' . $cypher  . '">' . esc_html( $route_object->get_rest_api_service_route() ) . '</a>
		// 	</p>
		// 	<p>Public key: <br/>
		// 	' . esc_html( $route_object->get_public_key() ) . '
		// 	</p>';
		// var_dump( $route_object->to_array() );
		// var_dump( $route_object->to_detail() );
		echo "<pre>";

		// print_r( $wp_meta_keys[ 'post' ][ 'sbx_service_route' ] );
		// print_r( get_registered_metadata( 'post', absint( $post->ID ) ) );



		$token  = strtotime( $route_object->modified );
		$hook   = sbx_prefix( 'client_procedural_encrypted_value_' . $token );
		$method = $route_object->request_method;
		$type   = 'string';
		$args   = array();
		$filter = array();

		switch ( $route_object->message_format ) {
			case 'array':
			case 'boolean':
			case 'integer':
			case 'number':
			case 'object':
				$type = $route_object->message_format;
				break;
			default:
				$type = 'string';
				break;
		}

		$args[ $route_object->message_param ] = array(
			'type'     => $type,
			'format'   => $route_object->message_format,
			'required' => true,
		);

		foreach ( $route_object->argument_schema as $argument ) {
			if ( isset( $argument['name'] ) && ! empty( $argument['name'] ) ) {
				$args[ $argument['name'] ] = array_intersect_key( $argument, array_flip( array( 'type' ) ) );
				$required = false;
				if ( array_key_exists( 'required', $argument ) ) {
					$required = sbx_string_to_bool( $argument['required'] );
				}
				$args[ $argument['name'] ]['required'] = $required;
			}
		}

		if ( in_array( $route_object->message_format, array( 'array', 'object' ) ) ) {
			$filter[] = $hook . '[input:sodium_crypto_box_seal.0],serialize,10,1';
		}
		if ( 'json' === $route_object->message_format ) {
			$filter[] = $hook . '[input:sodium_crypto_box_seal.0],json_encode,10,1';
		}

		$filter[] = $hook . '[input:sodium_crypto_box_seal.1],rawurldecode,10,1';
		$filter[] = $hook . '[input:sodium_crypto_box_seal.1],base64_decode,11,1';
		$filter[] = $hook . '[output:' . $route_object->message_param . '],sodium_crypto_box_seal,-1,2';
		$filter[] = $hook . '[output:' . $route_object->message_param . '],base64_encode,10,1';
		$filter[] = $hook . '[output:' . $route_object->message_param . '],rawurlencode,11,1';

		$response = array(
			'data' => array(
				'route'     => $route_object->get_rest_api_service_route(),
				'publickey' => $route_object->get_public_key(),
				'for'       => 'encryption',
				'type'      => SEALED_BOX_CRYPT['type'],
				'format'    => 'base64',
				'issued'    => $token,
				'expires'   => $token + MONTH_IN_SECONDS,
				'method'    => array_shift( $method ),
				'args'      => $args,
				'data'      => array(
					'for'    => 'procedural-value',
					'input'  => 'sodium_crypto_box_seal.0,sodium_crypto_box_seal.1',
					'output' => $route_object->message_param,
					'value'  => array(
						'input:sodium_crypto_box_seal.0' => 'message',
						'input:sodium_crypto_box_seal.1' => 'publickey',
						'output:' . $route_object->message_param => 'sodium_crypto_box_seal.0,sodium_crypto_box_seal.1',
					),
					'filter' => $filter
				),
			)
		);

		$response = $this->evaluate_http_response( $response, array( 'message' => "Hello Dolly — it's so nice to have you back where you belong." ) );

		foreach ( $response['data'] as $key => $value ) {
			echo "<h3>" . $key . "</h3>";
			var_export( $value );
		}

		// print_r( $response['results'] );

		echo "<h3>dycrypt</h3>";

		$result = $route_object->decrypt_payload( $response['result'][ $route_object->message_param ] );

		if ( 'json' === $route_object->message_format ) {
			$result = json_decode( $result );
		}
		echo $result;

		echo "</pre>";

		// print_r( SealedBox()->services->list_pluck_services('get_post_meta_keys', 'get_name') );

		//'publish' === $route_object->post_status ? $route_object->encrypt_payload( array( 'message' => "Hello Dolly — it's so nice to have you back where you belong." ) ) : ''; // $decypher = $route_object->decrypt_payload( $cypher ); // echo $cypher; // print_r( $decypher );
		// <pre>' . print_r( get_post_meta( $route_object->ID, 'post_name_check' ), true ) . /* print_r( get_registered_metadata( 'post', $route_object->ID ), true ) . */ '</pre>';
		/* $request = wp_remote_request( $route_object->get_rest_api_service_route(), [ 'method'      => 'OPTIONS', 'timeout'     => 10, 'sslverify'   => false, 'httpversion' => '1.1', 'headers'     => array( 'Accept-Encoding' => '*', 'Accept'          => 'application/vnd.dmm-v1+json', ], ]); if (is_wp_error($request)) { $error_message = $request->get_error_message(); } else { // check response code, should be 200 $response_code = wp_remote_retrieve_response_code($request); if (false === strstr($response_code, '200')) { $response_message    = wp_remote_retrieve_response_message($request); $error_message = "{$response_code} {$response_message}"; } } $response = wp_remote_retrieve_body($request); $response = json_decode( $response, true ); */ // check for json error // if ('No error' !== ($error = json_last_error_msg())) { // 	$error_message = $error; // // response should be an array // } elseif (!is_array($response)) { // 	$error_message = 'No JSON object was returned.'; // echo '<pre>' . print_r( $response, true ) . '</pre>'; // global $wpdb; /* $check_sql = "SELECT post_name FROM $wpdb->posts AS p JOIN $wpdb->term_relationships AS sr ON (p.ID = sr.object_id) JOIN $wpdb->term_taxonomy AS st ON (sr.term_taxonomy_id = st.term_taxonomy_id) JOIN $wpdb->terms AS s ON (st.term_id = s.term_id) JOIN $wpdb->term_relationships AS nr ON (p.ID = nr.object_id) JOIN $wpdb->term_taxonomy AS nt ON (nr.term_taxonomy_id = nt.term_taxonomy_id) JOIN $wpdb->terms AS n ON (nt.term_id = n.term_id) WHERE p.post_type = 'sbx_service_route' AND p.post_name = %s AND p.ID != %d AND st.taxonomy = 'sbx_route_type' AND s.term_id = %d AND nt.taxonomy = 'sbx_route_namespace' AND n.term_id = %d LIMIT 1"; echo '<pre>' . print_r( [$check_sql, 'rout', $route_object->ID, $route_object->route_type_id, $route_object->namespace_id], true ) . '</pre>'; $post_name_check = $wpdb->get_var( $wpdb->prepare( $check_sql, $route_object->slug, $route_object->ID, $route_object->route_type_id, $route_object->namespace_id ) ); echo '<pre>' . print_r( $post_name_check, true ) . '</pre>'; */
	}

    /**
     * Evaluate HTTP Response
     *
     * Create and update response values. The values
     * are produced by filters defined in the response.
     *
     * @param array  $response    HTTP response.
     * @param array  $parsed_args HTTP request arguments.
     * @param string $url         The request URL.
     * @return array
     */
    public function evaluate_http_response( $response, $parsed_args = null, $url = null ) {
		if ( ! isset( $response['data'] ) || ! isset( $response['data'], $response['data']['data'], $response['data']['for'], $response['data']['data']['for'] ) && 'encryption' === $response['data']['for'] && 'procedural-value' === $response['data']['data']['for'] ) {
            return $response;
        }

		// Setup data.
		$data = $response['data'];

		$results = new SBX_Client_Procedural_Encrypted_Value( $data );
		// $response['results'] = $results;
		$response['result'] = $results->get_value( $parsed_args['message'] );


		return $response;
    }

    /**
     * Assign values for previously evaluated key names
     *
     * Evaluate each value and assign values for
     * items that match key names previously defined
     * in input values.
     *
     * @param array $values Array possibly containing Key names to evaluate.
     * @param array $input  Array containing previously evaluated key names.
     * @return array
     * @throws missing_value
     */
    protected function assign_key_name_values( &$values, $results ) {
        foreach( $values as &$value ) {
            if ( is_array( $value ) ) {
                $this->assign_key_name_values( $value, $results );
            } elseif ( is_string( $value ) ) {
                foreach ( $results as $index => $set ) {
                    if ( is_array( $set ) && isset( $set[ $value ] ) ) {
                        $value = $set[ $value ];
						break;
                    } elseif ( $value === $index ) {
						$value = $set;
						break;
					}
                }
            }
        }
    }

        /**
         * Get the value of remove_filter
         */
        public function getRemove_filter()
        {
                return $this->remove_filter;
        }
}
