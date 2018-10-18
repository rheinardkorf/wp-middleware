<?php
/**
 * Bootstraps the WP Middleware plugin.
 *
 * @package WPMiddleware
 */

namespace WPMiddleware;

/**
 * Main plugin bootstrap file.
 */
class Plugin extends Plugin_Base {

	/**
	 * Registered proxies.
	 *
	 * @var array
	 */
	public $proxies = array();

	/**
	 * Initiate the plugin resources.
	 *
	 * Priority is 9 because WP_Customize_Widgets::register_settings() happens at
	 * after_setup_theme priority 10. This is especially important for plugins
	 * that extend the Customizer to ensure resources are available in time.
	 *
	 * @action after_setup_theme, 9
	 */
	public function init() {
		$this->config  = apply_filters( 'wp_middleware_plugin_config', $this->config, $this );
		$this->proxies = apply_filters( 'wp_middleware_proxy', $this->proxies );
		add_action( 'rest_api_init', array( $this, 'register_proxy_endpoints' ) );
	}

	/**
	 * Register all the proxies.
	 *
	 * @action rest_api_init
	 *
	 * @return void
	 */
	public function register_proxy_endpoints() {
		if ( ! empty( $this->proxies ) ) {
			foreach ( $this->proxies as $proxy_args ) {

				$proxy = new Proxy( $proxy_args );

				register_rest_route(
					$proxy->namespace,
					'/.*',
					array(
						'methods'             => $proxy->valid_methods,
						'callback'            => array( $proxy, 'handle_request' ),
						'permission_callback' => '__return_true',
					)
				);

			}
		}
	}
}
