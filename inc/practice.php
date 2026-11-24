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

// add_action('wp', 'meCookie');
// function meCookie(){
//     WC()->session->set('message', 'hi');

//     $message = WC()->session->get('message');

//     echo $message;
// }

// add_action('the_content', 'newthing');
// function newthing($content){
//     if(is_singular()){
//         $new = '<button class="js-start">Click</button><p id="ajax">hello</p>';
//     }

//     return $content . $new;
// }