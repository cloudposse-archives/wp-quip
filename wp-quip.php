<?php
/*
Plugin Name: WP Quip
Plugin URI: http://cloudposse.com
Description: Quip integration for Wordpress
Version: 1.0.0
Author: Cloud Posse
Author URI: https://cloudposse.com
License: Apache 2.0
License URI: https://www.apache.org/licenses/LICENSE-2.0
Text Domain: wp-quip
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

require_once 'simple_html_dom.php';
require_once 'functions.php';

define( 'CLOUDPOSSE_WP_QUIP_PLUGIN_VERSION', '1.0.0' );
define( 'CLOUDPOSSE_WP_QUIP_PLUGIN_VERSION_KEY', 'cloudposse_wp_quip_plugin_version' );
define( 'CLOUDPOSSE_WP_QUIP_PLUGIN_ACCESS_TOKEN_KEY', 'cloudposse_wp_quip_plugin_access_token' );
define( 'CLOUDPOSSE_WP_QUIP_PLUGIN_TRANSLATE_DOMAIN', 'cloudposse_wp_quip_plugin' );
define( 'CLOUDPOSSE_WP_QUIP_PLUGIN_TRANSIENT_KEY', 'cloudposse_wp_quip_plugin_transient' );
define( 'CLOUDPOSSE_WP_QUIP_PLUGIN_QUIP_THREAD_BASE_URL', 'https://platform.quip.com/1/threads/' );
define( 'CLOUDPOSSE_WP_QUIP_PLUGIN_QUIP_IMAGE_BASE_URL', 'https://platform.quip.com/1/blob/' );
define( 'CLOUDPOSSE_WP_QUIP_PLUGIN_QUIP_DEFULT_TTL_SECONDS', 3600 );


register_activation_hook( __FILE__, 'cloudposse_wp_quip_plugin_activate' );
function cloudposse_wp_quip_plugin_activate() {
	$current_version = get_option( CLOUDPOSSE_WP_QUIP_PLUGIN_VERSION_KEY );

	if ( $current_version === false ) {
		add_option( CLOUDPOSSE_WP_QUIP_PLUGIN_VERSION_KEY, CLOUDPOSSE_WP_QUIP_PLUGIN_VERSION );
	} else if ( $current_version != CLOUDPOSSE_WP_QUIP_PLUGIN_VERSION ) {
		// Execute upgrade logic here

		// Then update the version value
		update_option( CLOUDPOSSE_WP_QUIP_PLUGIN_VERSION_KEY, CLOUDPOSSE_WP_QUIP_PLUGIN_VERSION );
	}
}

register_deactivation_hook( __FILE__, 'cloudposse_wp_quip_plugin_deactivate' );
function cloudposse_wp_quip_plugin_deactivate() {
	delete_option( CLOUDPOSSE_WP_QUIP_PLUGIN_VERSION_KEY );
}


add_shortcode( 'quip', 'cloudposse_wp_quip_plugin_display_document_func' );
function cloudposse_wp_quip_plugin_display_document_func( $attrs ) {
	try {
		$quip_access_token = get_option( CLOUDPOSSE_WP_QUIP_PLUGIN_ACCESS_TOKEN_KEY );
		if ( ! $quip_access_token || ! is_string( $quip_access_token ) ) {
			throw new Exception( "Invalid or missing Quip Access Token. Set Quip Access Token in 'Settings/WP Quip'" );
		}

		$id = $attrs['id'];
		if ( ! $id ) {
			throw new Exception( "'id' attribute is required for [quip] shortcode" );
		}

		$ttl = $attrs['ttl'];
		if ( ! $ttl ) {
			$ttl = CLOUDPOSSE_WP_QUIP_PLUGIN_QUIP_DEFULT_TTL_SECONDS;
		}

		$args = array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $quip_access_token
			)
		);

		return cloudposse_wp_quip_plugin_get_html_document( $id, $ttl, $args );

	} catch ( Exception $e ) {
		cloudposse_wp_quip_plugin_log( "Exception: " . $e->getMessage() );

		return "<div style='font-size: 16px; color: red; background: white; margin: 30px'>" . "WP QUIP ERROR:<br><br>" . $e->getMessage() . "</div>";
	}
}

// Register "WP Quip" submenu in the WP "Settings" menu
add_action( "admin_menu", "cloudposse_wp_quip_plugin_menu_func" );
function cloudposse_wp_quip_plugin_menu_func() {
	add_submenu_page( "options-general.php",                // Menu parent
		"WP Quip Settings",                                  // Page title
		"WP Quip",                                           // Menu title
		"manage_options",                                   // Permissions (manage_options is an easy way to target Admins)
		"cloudposse_wp_quip",                              // Menu slug
		"cloudposse_wp_quip_plugin_display_settings_page"   // Callback that prints the markup
	);
}

// Markup for the "WP Quip" submenu Settings page
function cloudposse_wp_quip_plugin_display_settings_page() {

	if ( ! current_user_can( "manage_options" ) ) {
		wp_die( __( "You do not have permissions to access this page." ) );
	}

	if ( isset( $_GET['status'] ) && $_GET['status'] == 'success' ) {
		?>
        <div id="message" class="updated notice is-dismissible">
            <p><?php _e( "Settings updated!", CLOUDPOSSE_WP_QUIP_PLUGIN_TRANSLATE_DOMAIN ); ?></p>
            <button type="button" class="notice-dismiss">
                <span class="screen-reader-text"><?php _e( "Dismiss this notice.", CLOUDPOSSE_WP_QUIP_PLUGIN_TRANSLATE_DOMAIN ); ?></span>
            </button>
        </div>
		<?php
	}

	?>
    <form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">

        <input type="hidden" name="action" value="update_cloudposse_wp_quip_plugin_settings"/>

        <h3><?php _e( "Quip Settings", CLOUDPOSSE_WP_QUIP_PLUGIN_TRANSLATE_DOMAIN ); ?></h3>

        <p>
            <label><?php _e( "Quip Access Token:", CLOUDPOSSE_WP_QUIP_PLUGIN_TRANSLATE_DOMAIN ); ?></label>
            <input style="width: 700px" type="text" name="<?php echo CLOUDPOSSE_WP_QUIP_PLUGIN_ACCESS_TOKEN_KEY; ?>"
                   value="<?php echo get_option( CLOUDPOSSE_WP_QUIP_PLUGIN_ACCESS_TOKEN_KEY ); ?>"
                   title="<?php echo CLOUDPOSSE_WP_QUIP_PLUGIN_ACCESS_TOKEN_KEY; ?>"/>
        </p>

        <input class="button button-primary" type="submit" value="<?php _e( "Save", CLOUDPOSSE_WP_QUIP_PLUGIN_TRANSLATE_DOMAIN ); ?>"/>

    </form>

	<?php
}

// Add "Settings" link to the plugin action links. The link will be visible after plugin activation
add_filter( "plugin_action_links", "cloudposse_wp_quip_plugin_add_action_link_func", 10, 2 );
function cloudposse_wp_quip_plugin_add_action_link_func( $links, $file ) {
	static $this_plugin;

	if ( ! $this_plugin ) {
		$this_plugin = plugin_basename( __FILE__ );
	}

	if ( $file == $this_plugin ) {
		$settings_link = '<a href="' . get_bloginfo( 'wpurl' ) . '/wp-admin/admin.php?page=cloudposse_wp_quip">Settings</a>';
		array_unshift( $links, $settings_link );
	}

	return $links;
}

add_action( 'admin_post_update_cloudposse_wp_quip_plugin_settings', 'cloudposse_wp_quip_plugin_handle_save' );
function cloudposse_wp_quip_plugin_handle_save() {
	$quip_access_token = ( ! empty( $_POST[ CLOUDPOSSE_WP_QUIP_PLUGIN_ACCESS_TOKEN_KEY ] ) ) ? $_POST[ CLOUDPOSSE_WP_QUIP_PLUGIN_ACCESS_TOKEN_KEY ] : null;
	update_option( CLOUDPOSSE_WP_QUIP_PLUGIN_ACCESS_TOKEN_KEY, $quip_access_token, true );

	// Redirect back to settings page
	$redirect_url = get_bloginfo( "url" ) . "/wp-admin/options-general.php?page=cloudposse_wp_quip&status=success";
	header( "Location: " . $redirect_url );
	exit;
}
