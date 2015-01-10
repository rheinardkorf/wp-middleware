<?php
/**
 * Test_WP_Middleware
 *
 * @package WPMiddleware
 */

namespace WPMiddleware;

/**
 * Class Test_WP_Middleware
 *
 * @package WPMiddleware
 */
class Test_WP_Middleware extends \WP_UnitTestCase {

	/**
	 * Test _wp_middleware_php_version_error().
	 *
	 * @see _wp_middleware_php_version_error()
	 */
	public function test_wp_middleware_php_version_error() {
		ob_start();
		_wp_middleware_php_version_error();
		$buffer = ob_get_clean();
		$this->assertContains( '<div class="error">', $buffer );
	}

	/**
	 * Test _wp_middleware_php_version_text().
	 *
	 * @see _wp_middleware_php_version_text()
	 */
	public function test_wp_middleware_php_version_text() {
		$this->assertContains( 'WP Middleware plugin error:', _wp_middleware_php_version_text() );
	}
}
