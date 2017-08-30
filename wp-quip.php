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

defined('ABSPATH') || exit;

$wpq_loader = __DIR__ . '/src/wp-quip.php';

include $wpq_loader;

unset($wpq_loader);
