<?php
/*
Plugin Name: WP Quip
Plugin URI: http://cloudposse.com
Description: A simplistic Quip integration for Wordpress
Version: 1.0.0
Author: Ryan Cassidy
Author URI: http://cloudposse.com
License: Apache 2.0
License URI: https://www.apache.org/licenses/LICENSE-2.0
Text Domain: WPQuip
*/

defined( 'ABSPATH' ) || exit;

/*
 * Constants
 */
define( 'WPQ_FILE', __FILE__ );
define( 'WPQ_PATH', plugin_dir_path( WPQ_FILE ) );
define( 'WPQ_URL', plugin_dir_url( WPQ_URL ) );

register_activation_hook( WPQ_FILE, array( 'WPQuip', 'activate' ) );
register_deactivation_hook( WPQ_FILE, array( 'WPQuip', 'deactivate' ) );

if ( !class_exists( 'WPQuip' ) ):

    final class WPQuip {

        private static $instance = null;

        /**
         * Primary WPQuip instance
         *
         * Only one instance of WPQuip is allowed at any time.
         *
         * @since 1.0.0
         */
        public static function instance() {

            if ( !isset(self::$instance) ) {
                // set up the instance
                self::$instance = new WPQuip;
            }

            return self::$instance;

        }

        /**
         * Constructor.
         *
         * @since 1.0.0
         */
        public function __construct() {

            $this->includes();

            add_action( 'plugins_loaded', array($this, 'init') );

        }

        /**
         * Loads all relevant plugin files.
         *
         * @since 1.0.0
         */
        public function includes() {

            require_once plugin_dir_path( WPQ_FILE ) . '/includes/shortcodes.php';

        }

        /**
         * Runs upon activation of plugin.
         *
         * @since 1.0.0
         */
        public static function activate() {

        }

        /**
         * Runs upon deactivation of plugin
         *
         * @since 1.0.0
         */
        public static function deactivate() {

        }

    }

/**
 * Primary function responsible for returning the only WPQuip instance
 * to functions everywhere.
 *
 * @since 1.0.0
 */
function wpquip() {
    return WPQuip::instance();
}

wpquip();

endif;