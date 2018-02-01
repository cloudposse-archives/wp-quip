<?php
/*
Plugin Name: WP Quip
Plugin URI: http://cloudposse.com
Description: Quip integration for WordPress
Version: 1.0.0
Author: Cloud Posse
Author URI: https://cloudposse.com
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: wp-quip
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

define( 'CLOUDPOSSE_WP_QUIP_PLUGIN_VERSION', '1.0.0' );
define( 'CLOUDPOSSE_WP_QUIP_PLUGIN_VERSION_KEY', 'cloudposse_wp_quip_plugin_version' );
define( 'CLOUDPOSSE_WP_QUIP_PLUGIN_ACCESS_TOKEN_KEY', 'cloudposse_wp_quip_plugin_access_token' );
define( 'CLOUDPOSSE_WP_QUIP_PLUGIN_TRANSLATE_DOMAIN', 'cloudposse_wp_quip_plugin' );
define( 'CLOUDPOSSE_WP_QUIP_PLUGIN_TRANSIENT_KEY', 'cloudposse_wp_quip_plugin_transient' );
define( 'CLOUDPOSSE_WP_QUIP_PLUGIN_QUIP_THREAD_BASE_URL', 'https://platform.quip.com/1/threads/' );
define( 'CLOUDPOSSE_WP_QUIP_PLUGIN_QUIP_IMAGE_BASE_URL', 'https://platform.quip.com/1' );
define( 'CLOUDPOSSE_WP_QUIP_PLUGIN_DEFAULT_TTL_KEY', 'cloudposse_wp_quip_plugin_default_ttl' );
define( 'CLOUDPOSSE_WP_QUIP_PLUGIN_DEFULT_TTL_SECONDS', 7200 );


register_activation_hook( __FILE__, 'cloudposse_wp_quip_plugin_activate' );
/**
 * Plugin activation hook
 */
function cloudposse_wp_quip_plugin_activate() {
	$current_version = get_option( CLOUDPOSSE_WP_QUIP_PLUGIN_VERSION_KEY );

	if ( $current_version === false ) {
		add_option( CLOUDPOSSE_WP_QUIP_PLUGIN_VERSION_KEY, CLOUDPOSSE_WP_QUIP_PLUGIN_VERSION );
	} else if ( $current_version != CLOUDPOSSE_WP_QUIP_PLUGIN_VERSION ) {
		// Execute upgrade logic here

		// Update version
		update_option( CLOUDPOSSE_WP_QUIP_PLUGIN_VERSION_KEY, CLOUDPOSSE_WP_QUIP_PLUGIN_VERSION );
	}
}


register_deactivation_hook( __FILE__, 'cloudposse_wp_quip_plugin_deactivate' );
/**
 * Plugin deactivation hook
 */
function cloudposse_wp_quip_plugin_deactivate() {
	delete_option( CLOUDPOSSE_WP_QUIP_PLUGIN_VERSION_KEY );
}


add_shortcode( 'quip', 'cloudposse_wp_quip_plugin_display_document_func' );
/**
 * Fetch a Quip document from cache or Quip API. Return the document for the 'quip' shortcode, or an error if any occurs
 *
 * @param $attrs
 *
 * @return string
 */
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
		if ( ! $id || ! is_string( $id ) ) {
			throw new Exception( "'id' attribute is required for [quip] shortcode and it must be a string" );
		}

		if ( ! isset( $attrs['ttl'] ) ) {
			$ttl = get_option( CLOUDPOSSE_WP_QUIP_PLUGIN_DEFAULT_TTL_KEY );
			if ( $ttl === false ) {
				$ttl = CLOUDPOSSE_WP_QUIP_PLUGIN_DEFULT_TTL_SECONDS;
				update_option( CLOUDPOSSE_WP_QUIP_PLUGIN_DEFAULT_TTL_KEY, $ttl );
			}
		} else {
			$ttl = $attrs['ttl'];
		}

		if ( ! is_numeric( $ttl ) ) {
			throw new Exception( "'ttl' attribute must be an integer" );
		}

		$args = array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $quip_access_token
			)
		);

		return cloudposse_wp_quip_plugin_get_html_document( $id, intval( $ttl ), $args, $include_images );

	} catch ( Exception $e ) {
		cloudposse_wp_quip_plugin_log( "Exception: " . $e->getMessage() );

		return "<div style='font-size: 16px; color: red; background: white; margin: 30px'>" . "WP QUIP ERROR:<br><br>" . $e->getMessage() . "</div>";
	}
}


add_action( "admin_menu", "cloudposse_wp_quip_plugin_menu_func" );
/**
 * Register "WP Quip" submenu in the "Settings" menu
 */
function cloudposse_wp_quip_plugin_menu_func() {
	add_submenu_page( "options-general.php",                // Menu parent
		"Quip Settings",                                     // Page title
		"WP Quip",                                           // Menu title
		"manage_options",                                    // Permissions (manage_options is an easy way to target Admins)
		"cloudposse_wp_quip_settings",                      // Menu slug
		"cloudposse_wp_quip_plugin_display_settings_page"    // Callback that prints the markup
	);
}


/**
 * Markup for "WP Quip Settings" page
 */
function cloudposse_wp_quip_plugin_display_settings_page() {

	if ( ! current_user_can( "manage_options" ) ) {
		wp_die( __( "You do not have permissions to access this page", CLOUDPOSSE_WP_QUIP_PLUGIN_TRANSLATE_DOMAIN ) );
	}

	if ( isset( $_GET['status'] ) ) {
		$status = $_GET['status'];

		if ( $status === 'success' ) {
			?>
            <div class="updated notice is-dismissible">
                <p><?php _e( "Settings updated", CLOUDPOSSE_WP_QUIP_PLUGIN_TRANSLATE_DOMAIN ); ?></p>
                <button type="button" class="notice-dismiss">
                    <span class="screen-reader-text"><?php _e( "Dismiss this notice.", CLOUDPOSSE_WP_QUIP_PLUGIN_TRANSLATE_DOMAIN ); ?></span>
                </button>
            </div>
			<?php
		} elseif ( $status === 'error' ) {
			$error = isset( $_GET['error'] ) ? $_GET['error'] : 'Invalid settings';

			?>
            <div class="error notice is-dismissible">
                <p><?php _e( $error, CLOUDPOSSE_WP_QUIP_PLUGIN_TRANSLATE_DOMAIN ); ?></p>
                <button type="button" class="notice-dismiss">
                    <span class="screen-reader-text"><?php _e( "Dismiss this notice.", CLOUDPOSSE_WP_QUIP_PLUGIN_TRANSLATE_DOMAIN ); ?></span>
                </button>
            </div>
			<?php
		}
	}

	$ttl = get_option( CLOUDPOSSE_WP_QUIP_PLUGIN_DEFAULT_TTL_KEY );
	if ( $ttl === false || ! is_numeric( $ttl ) ) {
		$ttl = CLOUDPOSSE_WP_QUIP_PLUGIN_DEFULT_TTL_SECONDS;
	}

	$quip_access_token = get_option( CLOUDPOSSE_WP_QUIP_PLUGIN_ACCESS_TOKEN_KEY );
	if ( ! $quip_access_token || ! is_string( $quip_access_token ) ) {
		$quip_access_token = '';
	}

	?>
    <form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">

        <input type="hidden" name="action" value="update_cloudposse_wp_quip_plugin_settings"/>
        <h3><?php _e( "Quip Settings", CLOUDPOSSE_WP_QUIP_PLUGIN_TRANSLATE_DOMAIN ); ?></h3>

        <div>
            <label style="display: inline-block; width: 230px;" for="<?php echo CLOUDPOSSE_WP_QUIP_PLUGIN_DEFAULT_TTL_KEY; ?>">
				<?php _e( "Default Time-to-Live (seconds):", CLOUDPOSSE_WP_QUIP_PLUGIN_TRANSLATE_DOMAIN ); ?>
            </label>
            <input style="width: 150px" type="number" min="0"
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
        <img alt="CloudPosse Logo" style="width: 300px" src="<?php echo( plugin_dir_url( __FILE__ ) . "images/CloudPosse_Logo.png" ); ?>"">
    </div>

	<?php
}


add_filter( "plugin_action_links", "cloudposse_wp_quip_plugin_add_action_link_func", 10, 2 );
/**
 * Add "Settings" link to the plugin action links. The link will be visible after plugin activation
 *
 * @param $links
 * @param $file
 *
 * @return mixed
 */
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


add_action( 'admin_post_update_cloudposse_wp_quip_plugin_settings', 'cloudposse_wp_quip_plugin_handle_settings_save' );
/**
 * Callback to save the data from "WP Quip Settings" page
 */
function cloudposse_wp_quip_plugin_handle_settings_save() {
	$status = 'success';
	$error  = '';

	$ttl = ( isset( $_POST[ CLOUDPOSSE_WP_QUIP_PLUGIN_DEFAULT_TTL_KEY ] ) ) ? $_POST[ CLOUDPOSSE_WP_QUIP_PLUGIN_DEFAULT_TTL_KEY ] : CLOUDPOSSE_WP_QUIP_PLUGIN_DEFULT_TTL_SECONDS;

	if ( ! is_numeric( $ttl ) || intval( $ttl ) < 0 ) {
		$status = 'error';
		$error  = 'Invalid Time-to-Live';
	}

	$quip_access_token = ( isset( $_POST[ CLOUDPOSSE_WP_QUIP_PLUGIN_ACCESS_TOKEN_KEY ] ) ) ? $_POST[ CLOUDPOSSE_WP_QUIP_PLUGIN_ACCESS_TOKEN_KEY ] : null;

	if ( ! is_string( $quip_access_token ) || strlen( $quip_access_token ) === 0 ) {
		$status = 'error';
		$error  = 'Invalid Quip API Access Token';
	}

	if ( $status === 'success' ) {
		update_option( CLOUDPOSSE_WP_QUIP_PLUGIN_DEFAULT_TTL_KEY, intval( $ttl ), true );
		update_option( CLOUDPOSSE_WP_QUIP_PLUGIN_ACCESS_TOKEN_KEY, $quip_access_token, true );
	}

	// Redirect back to settings page
	$redirect_url = get_bloginfo( 'url' ) . '/wp-admin/options-general.php?page=cloudposse_wp_quip_settings&status=' . $status . '&error=' . urlencode( $error );
	header( "Location: " . $redirect_url );
	exit;
}


add_action( 'wp_enqueue_scripts', 'cloudposse_wp_quip_plugin_enqueue_scripts_and_styles' );
/**
 * Enqueue scripts and styles
 */
function cloudposse_wp_quip_plugin_enqueue_scripts_and_styles() {
	wp_enqueue_style( "cloudposse_wp_quip_plugin", plugin_dir_url( __FILE__ ) . "css/plugin.css", array(), CLOUDPOSSE_WP_QUIP_PLUGIN_VERSION, "all" );
}


/**
 * Trim zero space Unicode characters
 *
 * @param string $text
 *
 * @return string
 */
function cloudposse_wp_quip_plugin_trim_unicode_space( $text ) {
	return preg_replace( '/[\x{200B}-\x{200D}]/u', '', $text );
}


/**
 * Return a Quip document from cache or request a document from Quip API by the document ID
 *
 * @param string $id
 * @param integer $ttl
 * @param array $args
 * @param bool $include_images
 *
 * @return string
 * @throws Exception
 */
function cloudposse_wp_quip_plugin_get_html_document( $id, $ttl, $args, $include_images = true ) {
	$url           = CLOUDPOSSE_WP_QUIP_PLUGIN_QUIP_THREAD_BASE_URL . $id;
	$transient_key = CLOUDPOSSE_WP_QUIP_PLUGIN_TRANSIENT_KEY . '-' . $url . "-" . $include_images;

	if ( $ttl == 0 ) {
		delete_transient( $transient_key );
		$html = cloudposse_wp_quip_plugin_get_html_document_from_quip_api( $id, $ttl, $args, $include_images );

		return $html;
	}

	$html = get_transient( $transient_key );
	if ( $html !== false && ! empty( $html ) ) {
		return $html;
	}

	$html = cloudposse_wp_quip_plugin_get_html_document_from_quip_api( $id, $ttl, $args, $include_images );
	set_transient( $transient_key, $html, $ttl );

	return $html;
}


/**
 * Request a document from Quip API by the document ID
 *
 * @param string $id
 * @param integer $ttl
 * @param array $args
 * @param bool $include_images
 *
 * @return string
 * @throws Exception
 */
function cloudposse_wp_quip_plugin_get_html_document_from_quip_api( $id, $ttl, $args, $include_images = true ) {
	$url      = CLOUDPOSSE_WP_QUIP_PLUGIN_QUIP_THREAD_BASE_URL . $id;
	$response = wp_remote_get( $url, $args );

	if ( is_wp_error( $response ) ) {
		throw new Exception( $response->get_error_message() );
	}

	if ( $response['response'] && $response['response']['code'] && $response['response']['code'] !== 200 ) {
		$err = $response['body'];
		throw new Exception( $err ? $err : 'Quip API Error' );
	}

	$body     = wp_remote_retrieve_body( $response );
	$body_obj = json_decode( $body );
	$html     = cloudposse_wp_quip_plugin_trim_unicode_space( $body_obj->html );
	$document = new DOMDocument();
	$document->loadHTML( mb_convert_encoding( $html, 'HTML-ENTITIES', 'UTF-8' ) );
	$images = $document->getElementsByTagName( 'img' );

	foreach ( $images as $img ) {
		if ( $include_images ) {
			$img_encoded = cloudposse_wp_quip_plugin_get_encoded_image( $img->getAttribute( 'src' ), $ttl, $args );
			$img->setAttribute( 'src', "data:image/jpeg;base64," . $img_encoded );
		} else {
			$document->removeChild( $img );
		}
	}

	$html = '<div class="wp-quip">' . $document->saveHTML() . '</div>';

	return $html;
}


/**
 * Fetch an image from cache or download from Quip. Convert binary format to base64
 *
 * @param string $src
 * @param integer $ttl
 * @param array $args
 *
 * @return string
 * @throws Exception
 */
function cloudposse_wp_quip_plugin_get_encoded_image( $src, $ttl, $args ) {
	$transient_key = CLOUDPOSSE_WP_QUIP_PLUGIN_TRANSIENT_KEY . '-' . $src;

	if ( $ttl == 0 ) {
		delete_transient( $transient_key );
		$image = cloudposse_wp_quip_plugin_download_image( $src, $args );

		return $image;
	}

	$image = get_transient( $transient_key );
	if ( $image !== false && ! empty( $image ) ) {
		return $image;
	}

	$image = cloudposse_wp_quip_plugin_download_image( $src, $args );
	set_transient( $transient_key, $image, $ttl );

	return $image;
}


/**
 * Download image
 *
 * @param string $src
 * @param array $args
 *
 * @return string
 * @throws Exception
 */
function cloudposse_wp_quip_plugin_download_image( $src, $args ) {
	$url      = CLOUDPOSSE_WP_QUIP_PLUGIN_QUIP_IMAGE_BASE_URL . $src;
	$response = wp_remote_get( $url, $args );

	if ( is_wp_error( $response ) ) {
		throw new Exception( $response->get_error_message() );
	}

	if ( $response['response'] && $response['response']['code'] && $response['response']['code'] !== 200 ) {
		$err = $response['body'];
		throw new Exception( $err ? $err : 'Quip API Error' );
	}

	$body  = wp_remote_retrieve_body( $response );
	$image = base64_encode( $body );

	return $image;
}


/**
 * Log message if debugging is enabled
 *
 * @param string $message
 *
 */
function cloudposse_wp_quip_plugin_log( $message ) {
	if ( WP_DEBUG === true ) {
		if ( is_array( $message ) || is_object( $message ) ) {
			error_log( print_r( $message, true ) );
		} else {
			error_log( $message );
		}
	}
}
