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
        $response = wp_remote_retrieve_body(wp_remote_get($url, $args));
        $html = str_get_html(json_decode($response)->html);
        return $html;
    }
}

/*
 * Makes request for image, converts binary format to base64
 */
if ( !function_exists('wpq_create_encoded_image') ) {
    function wpq_create_encoded_image($src, $args) {
        $url = "https://platform.quip.com/1/blob/" . $src;
        $response = wp_remote_retrieve_body(wp_remote_get($url, $args));
        $image = base64_encode($response);
        return $image;
    }
}