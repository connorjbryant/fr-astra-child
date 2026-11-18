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

add_action('wp', 'wooConnor');
function wooConnor(){
    WC()->session->set('woo_message', 'Hello');

    $val = WC()->session->get('woo_message');

    echo $val;
}

add_action('the_content', 'customContent');
function customContent($content){
    if(is_singular()){
        $new = '<p id="ajax">New thing</p><button class="js-btn">Click</button>';
    }

    return $content . $new;
}