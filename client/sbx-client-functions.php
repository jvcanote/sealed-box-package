<?php

/**
 * Abstract classes.
 */
include_once dirname( __FILE__ ) . '/abstracts/abstract-sbx-client-procedural-value.php';

/**
 * Core classes and functions.
 */
include_once dirname( __FILE__ ) . '/class-sbx-client-procedural-value.php';
include_once dirname( __FILE__ ) . '/class-sbx-client-procedural-encrypted-value.php';

if ( ! defined( 'SBX_5414fe09be5c8ec9bfcd7ef99e82f7c5' ) ) {
    define(
        'SBX_5414fe09be5c8ec9bfcd7ef99e82f7c5',
        array(
            'ID'  => '5414fe09be5c8ec9bfcd7ef99e82f7c5',
            'URL' => 'http://plugin.sandbox.local/wp-json/headspace/v1/service/basic.route-3',
        )
    );
}

if ( ! function_exists( 'sbx_value_5414fe09be5c8ec9bfcd7ef99e82f7c5' ) ) {
    /**
     * sbx_value_5414fe09be5c8ec9bfcd7ef99e82f7c5.
     *
     * @param  string $message Value to process.
     * @return string
     */
    function sbx_value_5414fe09be5c8ec9bfcd7ef99e82f7c5( $message ) {
        if ( is_scalar( $message ) ) {
            /**
             * Performs an HTTP request and returns its response.
             *
             * @see WP_Http::request() For information on default arguments.
             *
             * @param string $url  URL to retrieve.
             * @param array  $args Optional. Request arguments. Default empty array.
             * @return array|WP_Error $response {
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
            $response = wp_remote_request( SBX_5414fe09be5c8ec9bfcd7ef99e82f7c5['URL'], array( 'method' => 'OPTIONS', ) );
            if (
                is_array( $response ) &&
                isset( $response['http_response'] ) &&
                $response['http_response'] instanceof WP_HTTP_Response
            ) {
                $client = $response['http_response']->get_data();
                if ( $client instanceof SBX_Client_Procedural_Encrypted_Value ) {
                    $message = $client->get_value( $message );
                }
            }
        }
        return $message;
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
             * @param string $transient Transient name. Expected to not be SQL-escaped.
             * @return mixed $response Value of transient.
             */
            $response = get_transient( 'sbx_http_response_5414fe09be5c8ec9bfcd7ef99e82f7c5' );
            if ( $response ) {
                $preempt = $response;
            }
        }
        return $preempt;
    }
}


if ( ! function_exists( 'sbx_http_response_5414fe09be5c8ec9bfcd7ef99e82f7c5 ' ) ) {
    /**
     * Filters the HTTP API response immediately before the response is returned.
     *
     * @param array  $response    HTTP response.
     * @param array  $parsed_args HTTP request arguments.
     * @param string $url         The request URL.
     */
     function sbx_http_response_5414fe09be5c8ec9bfcd7ef99e82f7c5( array $response, array $parsed_args, string $url ) {
        if (
            SBX_5414fe09be5c8ec9bfcd7ef99e82f7c5['URL'] === $url &&
            isset( $response['http_response'], $parsed_args['method'] ) &&
            $response['http_response'] instanceof WP_HTTP_Response &&
            'OPTIONS' === $parsed_args['method']
        ) {
            $data = json_decode( $response['http_response']->get_data(), true );
            if ( is_array( $data ) && isset( $data['for'], $data['expires'] ) && 'encryption' === $data['for'] ) {
                $client     = new SBX_Client_Procedural_Encrypted_Value( $data );
                $expiration = current_time( 'timestamp' ) - absint( $data['expires'] );
                // Set the client data on the http response object.
                $response['http_response']->set_data( $client );
                /**
                 * Sets/updates the value of a transient.
                 *
                 * You do not need to serialize values. If the value needs to be serialized,
                 * then it will be serialized before it is set.
                 *
                 * @param string $transient  Transient name. Expected to not be SQL-escaped.
                 *                           Must be 172 characters or fewer in length.
                 * @param mixed  $response   Transient value. Must be serializable if non-scalar.
                 *                           Expected to not be SQL-escaped.
                 * @param int    $expiration Optional. Time until expiration in seconds. Default 0 (no expiration).
                 * @return bool True if the value was set, false otherwise.
                 */
                set_transient( 'sbx_http_response_5414fe09be5c8ec9bfcd7ef99e82f7c5', $response, $expiration );
            }
        }
        return $response;
    }
}

if ( ! function_exists( 'sbx_do_shortcod_5414fe09be5c8ec9bfcd7ef99e82f7c5 ' ) ) {
    /**
     * Do shortcode.
     *
     * Every shortcode callback is passed three parameters by default,
     * including an array of attributes (`$atts`), the shortcode content
     * or null if not set (`$content`), and finally the shortcode tag
     * itself (`$shortcode_tag`), in that order.
     *
     * @param array       $atts          An array of shortcode attributes.
     * @param null|string $content       The content within the shortcode tag.
     * @param string      $shortcode_tag The shortcode tag used.
     * @return string The encoded message.
     */
    function sbx_do_shortcod_5414fe09be5c8ec9bfcd7ef99e82f7c5( $atts = array(), ?string $content = null, string $shortcode_tag = '' ) {
        $atts = shortcode_atts(
            array(
                'message' => ! empty( $content ) ? $content : '',
            ),
            $atts,
            $shortcode_tag
        );
        $output = apply_filters( 'sbx_value_5414fe09be5c8ec9bfcd7ef99e82f7c5', $atts['message'] );
        return is_scalar( $output ) ? $output : wp_json_encode( $output );
    }
}

add_filter( 'sbx_value_5414fe09be5c8ec9bfcd7ef99e82f7c5', 'sbx_value_5414fe09be5c8ec9bfcd7ef99e82f7c5', 12, 1 );
add_filter( 'pre_http_request', 'sbx_pre_http_request_5414fe09be5c8ec9bfcd7ef99e82f7c5', 12, 3 );
add_filter( 'http_response', 'sbx_http_response_5414fe09be5c8ec9bfcd7ef99e82f7c5', 12, 3 );

add_shortcode( 'sbx_value_5414fe09be5c8ec9bfcd7ef99e82f7c5', 'sbx_do_shortcod_5414fe09be5c8ec9bfcd7ef99e82f7c5' );
