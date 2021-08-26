<?php

/**
 * Include the encryption service classes (require only if needed).
 *
 * @todo You may need to update the paths to these files to their locations within your project.
 */
require_once dirname( __FILE__ ) . '/sbx-client/abstracts/abstract-sbx-client-procedural-value.php';
require_once dirname( __FILE__ ) . '/sbx-client/class-sbx-client-procedural-value.php';
require_once dirname( __FILE__ ) . '/sbx-client/class-sbx-client-procedural-encrypted-value.php';

defined( 'SBX_5414fe09be5c8ec9bfcd7ef99e82f7c5' ) || define( 'SBX_5414fe09be5c8ec9bfcd7ef99e82f7c5',
    array(
        'ID'  => '5414fe09be5c8ec9bfcd7ef99e82f7c5',
        'URL' => 'http://plugin.sandbox.local/wp-json/headspace/v1/service/basic.route-3',
    )
);

if ( ! function_exists( 'sbx_value_5414fe09be5c8ec9bfcd7ef99e82f7c5' ) ) {
    /**
     * Function to process messages via the encrypted service route: 5414fe09be5c8ec9bfcd7ef99e82f7c5
     *
     * @param  mixed|int|float|string|bool $message Scalar typed value to encrypt proceduraly.
     * @return mixed|WP_Error Procedural value containing encrypted message.
     */
    function sbx_value_5414fe09be5c8ec9bfcd7ef99e82f7c5( $message ) {
        if ( is_scalar( $message ) ) {
            /**
             * Performs an HTTP request and returns its response.
             *
             * @var array|WP_Error $response {
             *     The response array or a WP_Error on failure.
             *
             *     @type string[]                       $headers       Array of response headers keyed by their name.
             *     @type string                         $body          Response body.
             *     @type array                          $response      {
             *         Data about the HTTP response.
             *
             *         @type int|false    $code    HTTP response code.
             *         @type string|false $message HTTP response message.
             *     }
             *     @type WP_HTTP_Cookie[]               $cookies       Array of response cookies.
             *     @type WP_HTTP_Requests_Response|null $http_response Raw HTTP response object.
             * }
             */
            $response = wp_remote_request(
                SBX_5414fe09be5c8ec9bfcd7ef99e82f7c5['URL'],    // The service route endpoint.
                array( 'method' => 'OPTIONS', )                 // Using the options method will return the value procedure.
            );

            if ( is_wp_error( $response ) ) {
                /**
                 * @var WP_Error $message WP Error object.
                 */
                $message = $response;

                /** @todo Throw or log error */

            } elseif (
                is_array( $response ) && isset( $response['http_response'] ) &&
                $response['http_response'] instanceof WP_HTTP_Response &&
                $response['http_response']->get_data() instanceof SBX_Client_Procedural_Encrypted_Value
            ) {
                /**
                 * @var mixed $message The processed message value.
                 */
                $message = $response['http_response']->get_data()->get_value( $message );
            }
        }

        return $message; // Return the message value.
    }
}

if ( ! function_exists( 'sbx_pre_http_request_5414fe09be5c8ec9bfcd7ef99e82f7c5 ' ) ) {
    /**
     * Filters the preemptive return value of an HTTP request.
     *
     * Returning a non-false value from the filter will short-circuit the HTTP request and return
     * early with that value. A filter should return one of:
     *
     *  - An array containing 'headers', 'body', 'response', 'cookies', and 'filename' elements
     *  - A WP_Error instance
     *  - boolean false to avoid short-circuiting the response
     *
     * Returning any other value may result in unexpected behaviour.
     *
     * @param false|array|WP_Error $preempt     A preemptive return value of an HTTP request. Default false.
     * @param array                $parsed_args HTTP request arguments.
     * @param string               $url         The request URL.
     */
    function sbx_pre_http_request_5414fe09be5c8ec9bfcd7ef99e82f7c5( $preempt, array $parsed_args, string $url ) {
        if (
            SBX_5414fe09be5c8ec9bfcd7ef99e82f7c5['URL'] === $url &&
            isset( $parsed_args['method'], $preempt ) &&
            'OPTIONS' === $parsed_args['method'] &&
            false === $preempt
        ) {
            /**
             * Retrieves the value of a transient.
             *
             * If the transient does not exist, does not have a value, or has expired,
             * then the return value will be false.
             *
             * @var mixed $response Value of transient.
             */
            $response = get_transient( 'sbx_http_response_5414fe09be5c8ec9bfcd7ef99e82f7c5' );

            if ( false !== $response ) {
                /**
                 * @var mixed $preempt The preemptive return value from the transient. */
                $preempt = maybe_unserialize( $response );
            }
        }

        return $preempt; // Maybe return preemptive response.
    }
}

if ( ! function_exists( 'sbx_http_response_5414fe09be5c8ec9bfcd7ef99e82f7c5' ) ) {
    /**
     * Filter the HTTP API response immediately before the response is returned.
     *
     * This will set a client value object to the data of the response object.
     *
     * @param array  $response    HTTP response.
     * @param array  $parsed_args HTTP request arguments.
     * @param string $url         The request URL.
     */
     function sbx_http_response_5414fe09be5c8ec9bfcd7ef99e82f7c5(
        array  $response,
        array  $parsed_args,
        string $url
    ) {
        if (
            SBX_5414fe09be5c8ec9bfcd7ef99e82f7c5['URL'] === $url &&
            isset( $response['http_response'], $parsed_args['method'] ) &&
            $response['http_response'] instanceof WP_HTTP_Response &&
            'OPTIONS' === $parsed_args['method']
        ) {
            /**
             * @var mixed $data Decoded JSON value from response body.
             */
            $data = json_decode( $response['http_response']->get_data(), true );

            if ( is_array( $data ) && isset( $data['for'], $data['expires'] ) && 'encryption' === $data['for'] ) {
                /**
                 * @var SBX_Client_Procedural_Value $client The procedural value object.
                 */
                $client = new SBX_Client_Procedural_Encrypted_Value( $data );
                $response['http_response']->set_data( $client ); // Set the response data with the $client.

                /**
                 * @var int $expiration Transient expiration.
                 */
                $expiration = current_time( 'timestamp' ) - absint( $data['expires'] );
                set_transient( 'sbx_http_response_5414fe09be5c8ec9bfcd7ef99e82f7c5', $response, $expiration ); // Sets/updates the value of a transient.
            }
        }

        return $response; // Return the response value.
    }
}

if ( ! function_exists( 'sbx_do_shortcod_5414fe09be5c8ec9bfcd7ef99e82f7c5' ) ) {
    /**
     * Shortcode demonstration.
     *
     * Demonstrate the procedural encryption service running through your WordPress JSON API.
     *
     * Example: [sbx_value_5414fe09be5c8ec9bfcd7ef99e82f7c5 message="raw value"] or
     *          [sbx_value_5414fe09be5c8ec9bfcd7ef99e82f7c5]raw value[/sbx_value_5414fe09be5c8ec9bfcd7ef99e82f7c5]
     *
     * @param array         $atts   {
     *     An array of shortcode attributes.
     *
     *     @type string     $message        Value to be encrypted. Uses the $content param for fallback.
     * }
     * @param null|string   $content        The content within the shortcode tag.
     * @param string        $shortcode_tag  The shortcode tag used.
     * @return string The encoded message.
     */
    function sbx_do_shortcod_5414fe09be5c8ec9bfcd7ef99e82f7c5(
                $atts          = array(),
        ?string $content       = null,
        string  $shortcode_tag = ''
    ) {
        /**
         * @var array $atts  Applied shortcode attributes with fallback.
         */
        $atts = shortcode_atts(
            array( 'message' => (string) $content, ),   // Fallback message attribute.
            $atts,                                      // Parsed attributes.
            $shortcode_tag                              // Shortcode tag for filters.
        );

        /**
         * @var mixed $input  Value to be encrypted.
         */
        $input = $atts['message'];

        /**
         * Example of client side encryption using filter.
         *
         * Filter the $input to retrieve proceduraly encrypted result.
         *
         * @var mixed|WP_Error    $output The value after processing.
         */
        $output = apply_filters( 'sbx_value_5414fe09be5c8ec9bfcd7ef99e82f7c5', $input );

        return is_scalar( $output ) ? $output : wp_json_encode( $output ); // Return scalar typed value.
    }
}

// Add sealed box value filter.
add_filter( 'sbx_value_5414fe09be5c8ec9bfcd7ef99e82f7c5', 'sbx_value_5414fe09be5c8ec9bfcd7ef99e82f7c5', 12, 1 );

// Add pre HTTP request filter.
add_filter( 'pre_http_request', 'sbx_pre_http_request_5414fe09be5c8ec9bfcd7ef99e82f7c5', 12, 3 );

// Add HTTP response filter.
add_filter( 'http_response', 'sbx_http_response_5414fe09be5c8ec9bfcd7ef99e82f7c5', 12, 3 );

// Add demonstration shortcode.
add_shortcode( 'sbx_value_5414fe09be5c8ec9bfcd7ef99e82f7c5', 'sbx_do_shortcod_5414fe09be5c8ec9bfcd7ef99e82f7c5' );
