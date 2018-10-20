<?php
/**
 * Prepares the Middleware proxy.
 *
 * @package WPMiddleware
 */

namespace WPMiddleware;

/**
 * Middleware Proxy.
 */
class Proxy extends Plugin_Base {

	/**
	 * The Proxy's namespace.
	 *
	 * @var string
	 */
	public $namespace = 'unknown';

	/**
	 * Valid methods.
	 *
	 * @var array
	 */
	public $valid_methods = array( 'GET', 'POST', 'PUT', 'DELETE' );

	/**
	 * The real host address.
	 *
	 * @var string
	 */
	private $api_host = '';

	/**
	 * Constructor
	 *
	 * @param Array(type) $args Proxy arguments.
	 */
	public function __construct( $args ) {
		$this->namespace     = ! empty( $args['namespace'] ) ? sanitize_text_field( $args['namespace'] ) : $this->namespace;
		$this->valid_methods = ! empty( $args['valid_methods'] ) ? (array) $args['valid_methods'] : $this->valid_methods;
		$this->api_host      = ! empty( $args['api_host'] ) ? esc_url_raw( $args['api_host'] ) : '/' . $this->namespace . '/';
	}

	/**
	 * Proxy requests.
	 *
	 * @param \WP_REST_Request $request Proxied request.
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function handle_request( \WP_REST_Request $request ) {

		$request_method = $request->get_method();

		/**
		 * Use for whitelisting/blacklisting.
		 */
		$response = apply_filters( $this->namespace . '_proxy_response_override', false, $request );
		if ( false !== $response ) {
			return $response;
		}

		/**
		 * Filters a proxy REST request before it is run.
		 *
		 * @param \WP_REST_Request $request Original request.
		 */
		$request = apply_filters( $this->namespace . '_proxy_request', $request );

		/**
		 * Fires before a proxy REST request is run.
		 *
		 * @param \WP_REST_Request $request API request.
		 */
		do_action( $this->namespace . '_proxy_request_received', $request );

		// Get the route.
		$route = $this->route( $request );

		/**
		 * Pre-fetch results. Can be from database or from cache.
		 *
		 * @param array            $        Empty array.
		 * @param string           $route   The API route to request.
		 * @param \WP_REST_Request $request API request.
		 */
		$result = apply_filters( $this->namespace . '_proxy_result_pre', array(), $route, $request );

		// We have no results, use the API.
		if ( empty( $result ) ) {
			// Request arguments.
			$args = array(
				'method'  => $request_method,
				/**
				 * Filter the request headers.
				 *
				 * @param array            $        Header KV pairs.
				 * @param string           $route   Route requested.
				 * @param \WP_REST_Request $request API request.
				 */
				'headers' => apply_filters(
					$this->namespace . '_proxy_request_headers',
					array(
						'Accept'       => 'application/json',
						'Content-Type' => 'application/json',
					),
					$route,
					$request
				),
			);

			// Add body if PUT/POST.
			if ( 'PUT' === $request_method || 'POST' === $request_method ) {
				$body         = $request->get_body();
				$body         = ! empty( $body ) ? $body : wp_json_encode( $request->get_params() );
				$args['body'] = $body;
			}

			// Proxy the request.
			$response = wp_safe_remote_request( $route, $args );

			/**
			 * Raw API response received.
			 *
			 * @param object|array     $response Response from API.
			 * @param string           $route    Route requested.
			 * @param \WP_REST_Request $request  API request.
			 */
			do_action( $this->namespace . '_proxy_raw_response_received', $response, $route, $request );
			if ( ! is_wp_error( $response ) && ! empty( $response ) ) {
				$result = json_decode( $response['body'], true );
			} else {
				$result = $response;
			}

			/**
			 * Do something with the response before it is returned. E.g. Import items as posts and/or cache it.
			 *
			 * @param array|\WP_Error  $result  Result from API call.
			 * @param string           $route   Route requested.
			 * @param \WP_REST_Request $request API request.
			 */
			do_action( $this->namespace . '_proxy_response_received', $result, $route, $request );
		}

		/**
		 * Filter the response results before returning it.
		 *
		 * @param array|\WP_Error  $result  Result from API call.
		 * @param string           $route   Route requested.
		 * @param \WP_REST_Request $request API request.
		 */
		$result        = apply_filters( $this->namespace . '_proxy_result', $result, $route, $request );
		$rest_response = new \WP_REST_Response( $result );

		/**
		 * Filter the WordPress REST response before it gets dispatched.
		 *
		 * @param \WP_REST_Response $rest_response Response to send back to request..
		 * @param string            $route         Route requested.
		 * @param \WP_REST_Request  $request       API request.
		 */
		$rest_response = apply_filters( $this->namespace . '_proxy_rest_response', $rest_response, $route, $request );
		return rest_ensure_response( $rest_response );
	}

	/**
	 * Given a request, return the real URL.
	 *
	 * @param \WP_REST_Request $request The request.
	 *
	 * @return string
	 */
	private function route( \WP_REST_Request $request ) {
		$route = str_replace( '/' . $this->namespace . '/', '', $request->get_route() );
		$route = sprintf( '%s%s', trailingslashit( $this->api_host ), $route );
		$route = add_query_arg( $request->get_query_params(), $route );
		return $route;
	}
}
