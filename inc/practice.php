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

// add_action('wp', 'customCookie');
// function customCookie(){
//     WC()->session->set('customCookie', 'ConnorCookie');

//     $cookie = WC()->session->get('customCookie');

//     echo $cookie;
// }

// add_action('the_content', 'customAddition');
// function customAddition($content){
//     if(is_singular()){
//         $custom_markup = '<button class="js-btn">Click me</button><p id="ajax">Test</p>';
//     }

//     return $content . $custom_markup;
// }