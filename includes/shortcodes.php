<?php

if ( !function_exists( 'display_document' ) ) {
    function display_document($attrs, $content = '') {

        // Check that ID and URL parameters exist
        if (!$attrs['id'] || $attrs['url']) return;
        
    }
}

add_shortcode('wpq_display_document', 'display_document');