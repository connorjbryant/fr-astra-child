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

// add_action('wp', 'session_ex');
// function session_ex(){

//     if(isset($_GET['secret'])){
//         WC()->session->set('custom_val', 'Hello');

//         $value = WC()->session->get('custom_val');
//         echo "<p style='position: fixed; bottom: 0; z-index: 1000;'>" . $value . "</p>";

//         if(WC()->session->get('custom_val')){
//             echo "Ayo";
//         }
//         if(WC()->session->get('custom_val')){
//             echo "ayo";
//         }
//     }
//     check_time();
// }

// add_filter('the_content', 'my_content');
// function my_content($content){
//     if (is_singular()){
//         $new = '<form method="GET">
//         <input type="text" name="secret">
//         <button type="submit">Submit</button>';
//         return $content . $new;
//     }
// }