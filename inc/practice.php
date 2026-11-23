<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// function check_time(){     
//     $file_path = get_stylesheet_directory() . '/test.txt';
//     $two_weeks_ago = strtotime('-14 days');

//     if (file_exists($file_path)){
//         if(filemtime($file_path) < $two_weeks_ago){
//             echo "Old file";
//         } else {
//             echo "New file";
//         }
//     } else {
//         echo "File does not exist";
//     }
// }

add_action('wp', 'customCookie');
function customCookie(){
    WC()->session->set('customCookie1', 'message');

    $val = WC()->session->get('customCookie1');

    echo $val;
}

add_action('the_content', 'newMarkup');
function newMarkup($content){
    if(is_singular()){
        $new = '<p id="ajax">Hello</p><button class="js-btn">Click</button>';
    }

    return $content . $new;
}