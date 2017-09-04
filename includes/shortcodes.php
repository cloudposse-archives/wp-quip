<?php

// todo: set auth token from options page

if ( !function_exists( 'display_document' ) ) {
    function display_document($attrs, $content = '') {

        if (!$attrs['url']) return;

        $args = array(
            'headers'       => array(
                'Authorization' => 'Bearer UE5hQU1BOTcwRDE=|1535650617|95LXzfmOeSH0XeOsP+5yQTpxMqdSxkMwPF4RnBO0Jek=',
            ),
        );

        $html = wpq_get_html_document($attrs['url'], $args);

        foreach ($html->find('p') as $p) {
            $p->innertext = trim_unicode_space($p->innertext);
        }

        foreach ($html->find('img') as $img) {
            $src = str_replace('/blob/', '', $img->attr['src']);
            $image = wpq_create_encoded_image($src, $args);
            $img->outertext = "<img src='data:image/jpeg;base64," . $image . "'>";
        }

        return $html;
    }
}

add_shortcode('quip', 'display_document');