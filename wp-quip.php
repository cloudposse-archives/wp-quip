<?php
/*
Plugin Name: WP Quip
Plugin URI: http://cloudposse.com
Description: Quip integration for WordPress
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
define( 'CLOUDPOSSE_WP_QUIP_PLUGIN_DEFAULT_TTL_KEY', 'cloudposse_wp_quip_plugin_default_ttl' );
define( 'CLOUDPOSSE_WP_QUIP_PLUGIN_DEFULT_TTL_SECONDS', 7200 );


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
		// Prevent slowness of post edit with 'quip' shortcode when Yoast plugin installed
		// https://github.com/Yoast/wordpress-seo/issues/6564
		$include_images = true;
		$doing_ajax     = function_exists( 'wp_doing_ajax' ) ? wp_doing_ajax() : ( defined( 'DOING_AJAX' ) && DOING_AJAX );
		if ( $doing_ajax && ( $_REQUEST['action'] === 'wpseo_filter_shortcodes' ) ) {
			$include_images = false;
		}

		$quip_access_token = get_option( CLOUDPOSSE_WP_QUIP_PLUGIN_ACCESS_TOKEN_KEY );
		if ( ! $quip_access_token || ! is_string( $quip_access_token ) ) {
			throw new Exception( "Invalid or missing Quip API Access Token.<br> Set Quip API Access Token in 'Settings/WP Quip'" );
		}

		$id = $attrs['id'];
		if ( ! $id ) {
			throw new Exception( "'id' attribute is required for [quip] shortcode" );
		}

		$ttl = $attrs['ttl'];
		if ( ! $ttl ) {
			$ttl = get_option( CLOUDPOSSE_WP_QUIP_PLUGIN_DEFAULT_TTL_KEY );
			if ( ! $ttl ) {
				$ttl = CLOUDPOSSE_WP_QUIP_PLUGIN_DEFULT_TTL_SECONDS;
				update_option( CLOUDPOSSE_WP_QUIP_PLUGIN_DEFAULT_TTL_KEY, $ttl );
			}
		}

		$args = array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $quip_access_token
			)
		);

		return cloudposse_wp_quip_plugin_get_html_document( $id, $ttl, $args, $include_images );

	} catch ( Exception $e ) {
		cloudposse_wp_quip_plugin_log( "Exception: " . $e->getMessage() );

		return "<div style='font-size: 16px; color: red; background: white; margin: 30px'>" . "WP QUIP ERROR:<br><br>" . $e->getMessage() . "</div>";
	}
}

// Register "WP Quip" submenu in the WP "Settings" menu
add_action( "admin_menu", "cloudposse_wp_quip_plugin_menu_func" );
function cloudposse_wp_quip_plugin_menu_func() {
	add_submenu_page( "options-general.php",                // Menu parent
		"Quip Settings",                                     // Page title
		"WP Quip",                                           // Menu title
		"manage_options",                                    // Permissions (manage_options is an easy way to target Admins)
		"cloudposse_wp_quip_settings",                      // Menu slug
		"cloudposse_wp_quip_plugin_display_settings_page"    // Callback that prints the markup
	);
}

// Markup for "Quip Settings" page
function cloudposse_wp_quip_plugin_display_settings_page() {

	if ( ! current_user_can( "manage_options" ) ) {
		wp_die( __( "You do not have permissions to access this page", CLOUDPOSSE_WP_QUIP_PLUGIN_TRANSLATE_DOMAIN ) );
	}

	$ttl = get_option( CLOUDPOSSE_WP_QUIP_PLUGIN_DEFAULT_TTL_KEY );
	if ( ! $ttl ) {
		$ttl = CLOUDPOSSE_WP_QUIP_PLUGIN_DEFULT_TTL_SECONDS;
	}

	$quip_access_token = get_option( CLOUDPOSSE_WP_QUIP_PLUGIN_ACCESS_TOKEN_KEY );

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

        <div>
            <label style="display: inline-block; width: 230px;" for="<?php echo CLOUDPOSSE_WP_QUIP_PLUGIN_DEFAULT_TTL_KEY; ?>">
				<?php _e( "Default Time-to-Live (seconds):", CLOUDPOSSE_WP_QUIP_PLUGIN_TRANSLATE_DOMAIN ); ?>
            </label>
            <input style="width: 150px" type="number"
                   name="<?php echo CLOUDPOSSE_WP_QUIP_PLUGIN_DEFAULT_TTL_KEY; ?>"
                   value="<?php echo $ttl; ?>"
                   title="<?php echo CLOUDPOSSE_WP_QUIP_PLUGIN_DEFAULT_TTL_KEY; ?>"/>
        </div>

        <div>
            <label style="display: inline-block; width: 230px;" for="<?php echo CLOUDPOSSE_WP_QUIP_PLUGIN_ACCESS_TOKEN_KEY; ?>">
				<?php _e( "Quip API Access Token:", CLOUDPOSSE_WP_QUIP_PLUGIN_TRANSLATE_DOMAIN ); ?>
            </label>
            <input style="width: 700px" type="text"
                   name="<?php echo CLOUDPOSSE_WP_QUIP_PLUGIN_ACCESS_TOKEN_KEY; ?>"
                   value="<?php echo $quip_access_token; ?>"
                   title="<?php echo CLOUDPOSSE_WP_QUIP_PLUGIN_ACCESS_TOKEN_KEY; ?>"/>
        </div>

        <input class="button button-primary" type="submit" style="width: 100px; margin: 30px 0 0 0" value="<?php _e( "Save", CLOUDPOSSE_WP_QUIP_PLUGIN_TRANSLATE_DOMAIN ); ?>"/>

    </form>

    <div style="margin: 50px 0 0 0">
        To generate a Quip API Access Token, visit this page: <a href="https://quip.com/dev/token" target="_blank" title="https://quip.com/dev/token">https://quip.com/dev/token</a>
    </div>

    <div style="margin: 50px 0 0 0">
        <br>
        <hr>
        <br>
        <p>
            "WP Quip" plugin is developed and maintained by Cloud Posse LLC - Expert Cloud Architects
        </p>
        <p>
            For help or questions, contact us:
        </p>
        <p>
            <a href="https://cloudposse.com" target="_blank" title="Cloud Posse">https://cloudposse.com</a>
        </p>
        <p>
            Email: <a href="mailto:hello@cloudposse.com">hello@cloudposse.com</a>
        </p>
    </div>

    <div style="margin: 50px 0 0 0">
        <img style="width: 300px" src="<?php echo( plugin_dir_url( __FILE__ ) . "screenshot-1.png" ); ?>"">
    </div>

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
		$settings_link = '<a href="' . get_bloginfo( 'wpurl' ) . '/wp-admin/admin.php?page=cloudposse_wp_quip_settings">Settings</a>';
		array_unshift( $links, $settings_link );
	}

	return $links;
}

// Callback to save data from "Quip Settings" page
add_action( 'admin_post_update_cloudposse_wp_quip_plugin_settings', 'cloudposse_wp_quip_plugin_handle_settings_save' );
function cloudposse_wp_quip_plugin_handle_settings_save() {
	$ttl = ( ! empty( $_POST[ CLOUDPOSSE_WP_QUIP_PLUGIN_DEFAULT_TTL_KEY ] ) ) ? $_POST[ CLOUDPOSSE_WP_QUIP_PLUGIN_DEFAULT_TTL_KEY ] : CLOUDPOSSE_WP_QUIP_PLUGIN_DEFULT_TTL_SECONDS;
	update_option( CLOUDPOSSE_WP_QUIP_PLUGIN_DEFAULT_TTL_KEY, $ttl, true );

	$quip_access_token = ( ! empty( $_POST[ CLOUDPOSSE_WP_QUIP_PLUGIN_ACCESS_TOKEN_KEY ] ) ) ? $_POST[ CLOUDPOSSE_WP_QUIP_PLUGIN_ACCESS_TOKEN_KEY ] : null;
	update_option( CLOUDPOSSE_WP_QUIP_PLUGIN_ACCESS_TOKEN_KEY, $quip_access_token, true );

	// Redirect back to settings page
	$redirect_url = get_bloginfo( "url" ) . "/wp-admin/options-general.php?page=cloudposse_wp_quip_settings&status=success";
	header( "Location: " . $redirect_url );
	exit;
}
