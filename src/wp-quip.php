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

if (!class_exists('wpQuip')):

    class WPQuip {

        private $version;
        
        private $shortcodes;

        /*
         * Primary wpQuip instance
         *
         * Only one instance of wpQuip is allowed at any time.
         *
         * @since 1.0.0
         */
        public static function instance() {

            // store instance locally
            static $instance = null;

            if (null === $instance) {
                // set up the instance
                $instance = new wpQuip;
                $instance->setup_environment();
                $instance->includes();
                $instance->setup_actions();
            }

            return $instance;

        }

        private function setup_environment() {

            // versions
            $this->version      = '1.0.0';

        }

        /*
         * Include required files
         *
         * @since 1.0.0
         *
         * @access private
         */
        private function includes() {
            // todo: require all core files
            
        }


        /*
         * Setup hooks and actions
         *
         * @since 1.0.0
         *
         * @access private
         */
        private function setup_actions() {

        }

    }

endif;