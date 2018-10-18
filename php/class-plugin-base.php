<?php
/**
 * Class Plugin_Base
 *
 * @package WPMiddleware
 */

namespace WPMiddleware;

/**
 * Class Plugin_Base
 *
 * @package WPMiddleware
 */
abstract class Plugin_Base {

	/**
	 * Plugin config.
	 *
	 * @var array
	 */
	public $config = array();

	/**
	 * Plugin slug.
	 *
	 * @var string
	 */
	public $slug;

	/**
	 * Plugin directory path.
	 *
	 * @var string
	 */
	public $dir_path;

	/**
	 * Plugin directory URL.
	 *
	 * @var string
	 */
	public $dir_url;

	/**
	 * Directory in plugin containing autoloaded classes.
	 *
	 * @var string
	 */
	protected $autoload_class_dir = 'php';

	/**
	 * Autoload matches cache.
	 *
	 * @var array
	 */
	protected $autoload_matches_cache = array();

	/**
	 * Required instead of a static variable inside the add_doc_hooks method
	 * for the sake of unit testing.
	 *
	 * @var array
	 */
	protected $_called_doc_hooks = array();

	/**
	 * Plugin_Base constructor.
	 */
	public function __construct() {
		$location       = $this->locate_plugin();
		$this->slug     = $location['dir_basename'];
		$this->dir_path = $location['dir_path'];
		$this->dir_url  = $location['dir_url'];
		spl_autoload_register( array( $this, 'autoload' ) );
	}

	/**
	 * Get reflection object for this class.
	 *
	 * @return \ReflectionObject
	 */
	public function get_object_reflection() {
		static $reflection;
		if ( empty( $reflection ) ) {
			$reflection = new \ReflectionObject( $this );
		}
		return $reflection;
	}

	/**
	 * Autoload for classes that are in the same namespace as $this.
	 *
	 * @param string $class Class name.
	 * @return void
	 */
	public function autoload( $class ) {
		if ( ! isset( $this->autoload_matches_cache[ $class ] ) ) {
			if ( ! preg_match( '/^(?P<namespace>.+)\\\\(?P<class>[^\\\\]+)$/', $class, $matches ) ) {
				$matches = false;
			}
			$this->autoload_matches_cache[ $class ] = $matches;
		} else {
			$matches = $this->autoload_matches_cache[ $class ];
		}
		if ( empty( $matches ) ) {
			return;
		}
		if ( $this->get_object_reflection()->getNamespaceName() !== $matches['namespace'] ) {
			return;
		}
		$class_name = $matches['class'];

		$class_path = \trailingslashit( $this->dir_path );
		if ( $this->autoload_class_dir ) {
			$class_path .= \trailingslashit( $this->autoload_class_dir );
		}
		$class_path .= sprintf( 'class-%s.php', strtolower( str_replace( '_', '-', $class_name ) ) );
		if ( is_readable( $class_path ) ) {
			require_once $class_path;
		}
	}

	/**
	 * Version of plugin_dir_url() which works for plugins installed in the plugins directory,
	 * and for plugins bundled with themes.
	 *
	 * @throws Exception If the plugin is not located in the expected location.
	 * @return array
	 */
	public function locate_plugin() {
		$file_name = $this->get_object_reflection()->getFileName();
		if ( '/' !== \DIRECTORY_SEPARATOR ) {
			$file_name = str_replace( \DIRECTORY_SEPARATOR, '/', $file_name ); // Windows compat.
		}

		$plugin_dir  = dirname( dirname( $file_name ) );
		$plugin_path = $this->relative_path( $plugin_dir, basename( content_url() ), \DIRECTORY_SEPARATOR );

		$dir_url      = content_url( trailingslashit( $plugin_path ) );
		$dir_path     = $plugin_dir;
		$dir_basename = basename( $plugin_dir );

		return compact( 'dir_url', 'dir_path', 'dir_basename' );
	}

	/**
	 * Relative Path
	 *
	 * Returns a relative path from a specified starting position of a full path
	 *
	 * @param string $path The full path to start with.
	 * @param string $start The directory after which to start creating the relative path.
	 * @param string $sep The directory separator.
	 *
	 * @return string
	 */
	public function relative_path( $path, $start, $sep ) {
		$path = explode( $sep, untrailingslashit( $path ) );
		if ( count( $path ) > 0 ) {
			foreach ( $path as $p ) {
				array_shift( $path );
				if ( $p === $start ) {
					break;
				}
			}
		}
		return implode( $sep, $path );
	}

	/**
	 * Return whether we're on WordPress.com VIP production.
	 *
	 * @return bool
	 */
	public function is_wpcom_vip_prod() {
		return ( defined( '\WPCOM_IS_VIP_ENV' ) && \WPCOM_IS_VIP_ENV );
	}

	/**
	 * Call trigger_error() if not on VIP production.
	 *
	 * @param string $message Warning message.
	 * @param int    $code    Warning code.
	 */
	public function trigger_warning( $message, $code = \E_USER_WARNING ) {
		if ( ! $this->is_wpcom_vip_prod() ) {
			trigger_error( esc_html( get_class( $this ) . ': ' . $message ), $code );
		}
	}
}
