<?php

/*
 * Trims zero space unicode character
 */
if ( !function_exists('trim_unicode_space') ) {
    function trim_unicode_space($text) {
        $text = preg_replace('/[\x{200B}-\x{200D}]/u', '', $text);
        return $text;
    }
}

/*
 * Makes request for HTML document
 */
if ( !function_exists('wpq_get_html_document') ) {
    function wpq_get_html_document($url, $args) {
        $html = get_transient('wpquip_html');
        if ( !empty($html) ) {
            return $html;
        } else {
            $response = wp_remote_retrieve_body(wp_remote_get($url, $args));
            $html = str_get_html(json_decode($response)->html);
            set_transient('wpquip_html', $html, HOUR_IN_SECONDS); // todo: take arg for time
            return $html;
        }
    }
}

/*
 * Makes request for image, converts binary format to base64
 */
if ( !function_exists('wpq_create_encoded_image') ) {
    function wpq_create_encoded_image($src, $args) {
        $image = get_transient('wpquip_image');
        if ( !empty($image) ) {
            return $image;
        } else {
            $url = "https://platform.quip.com/1/blob/" . $src;
            $response = wp_remote_retrieve_body(wp_remote_get($url, $args));
            $image = base64_encode($response);
            set_transient('wpquip_image', $image, HOUR_IN_SECONDS); // todo: take arg for time
            return $image;
        }
    }
}