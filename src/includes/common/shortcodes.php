<?php


if (!class_exists('WPQ_Shortcodes')):

    class WPQ_Shortcodes {

        public $codes = array();

        public function __construct() {
            $this->setup_globals();
            $this->add_shortcodes();
        }

        private function setup_globals() {
            $this->codes = apply_filters('wpq_shortcodes', array(
                
            ));
        }

        private function add_shortcodes() {

        }

    }

endif;