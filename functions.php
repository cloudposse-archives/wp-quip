<?php

/*
 * Trims zero space unicode character
 */
function cloudposse_wp_quip_plugin_trim_unicode_space( $text ) {
	return preg_replace( '/[\x{200B}-\x{200D}]/u', '', $text );
}


/**
 * Makes request for Quip document
 *
 * @param $id
 * @param $ttl
 * @param $args
 * @param bool $include_images
 *
 * @return bool|mixed|simple_html_dom
 * @throws Exception
 */
function cloudposse_wp_quip_plugin_get_html_document( $id, $ttl, $args, $include_images = true ) {
	$url           = CLOUDPOSSE_WP_QUIP_PLUGIN_QUIP_THREAD_BASE_URL . $id;
	$transient_key = CLOUDPOSSE_WP_QUIP_PLUGIN_TRANSIENT_KEY . '-' . $url . "-" . $include_images;
	$html          = get_transient( $transient_key );
	if ( $html == false && ! empty( $html ) ) {
		return $html;
	} else {
		$response = wp_remote_get( $url, $args );

		if ( is_wp_error( $response ) ) {
			throw new Exception( $response->get_error_message() );
		} elseif ( $response['response'] && $response['response']['code'] && $response['response']['code'] !== 200 ) {
			$err = $response['body'];
			throw new Exception( $err ? $err : 'Quip API Error' );
		} else {
			$body     = wp_remote_retrieve_body( $response );
			$body_obj = json_decode( $body );
			$html     = str_get_html( $body_obj->html );

			if ( $html && is_object( $html ) ) {

				foreach ( $html->childNodes() as $node ) {
					$node->attr['style'] = 'color: black';
				}

				foreach ( $html->find( 'pre' ) as $pre ) {
					$pre->attr['style'] = 'color: black; background: #eee; font-weight: 400; font-family: Courier, monospace; font-size: 0.9375rem; line-height: 1.6; max-width: 100%; overflow: auto; padding: 1.6em; display: block; white-space: pre; margin: 1em 0 1.6em 0;';
				}

				foreach ( $html->find( 'p' ) as $p ) {
					$p->innertext = cloudposse_wp_quip_plugin_trim_unicode_space( $p->innertext );
				}

				if ( $include_images ) {
					foreach ( $html->find( 'img' ) as $img ) {
						$src            = str_replace( '/blob/', '', $img->attr['src'] );
						$image          = cloudposse_wp_quip_plugin_create_encoded_image( $src, $ttl, $args );
						$img->outertext = "<img src='data:image/jpeg;base64," . $image . "'>";
					}
				}
			}

			$html = '<div class="wp-quip">' . $html . '</div>';
			set_transient( $transient_key, $html, $ttl );

			return $html;
		}
	}
}


/*
 * Makes request for image, converts binary format to base64
 */
function cloudposse_wp_quip_plugin_create_encoded_image( $src, $ttl, $args ) {
	$transient_key = CLOUDPOSSE_WP_QUIP_PLUGIN_TRANSIENT_KEY . '-' . $src;
	$image         = get_transient( $transient_key );
	if ( $image !== false && ! empty( $image ) ) {
		return $image;
	} else {
		$url      = CLOUDPOSSE_WP_QUIP_PLUGIN_QUIP_IMAGE_BASE_URL . $src;
		$response = wp_remote_retrieve_body( wp_remote_get( $url, $args ) );
		$image    = base64_encode( $response );
		set_transient( $transient_key, $image, $ttl );

		return $image;
	}
}


/*
 * Logs messages
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
