<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( CLOUDPOSSE_WP_QUIP_PLUGIN_VERSION_KEY );
delete_option( CLOUDPOSSE_WP_QUIP_PLUGIN_ACCESS_TOKEN_KEY );
delete_option( CLOUDPOSSE_WP_QUIP_PLUGIN_DEFAULT_TTL_KEY );
