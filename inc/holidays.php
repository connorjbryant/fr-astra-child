<?php

if ( ! defined( 'ABSPATH' )){
    exit;
}

/* WP-CRON holiday check */
add_action( 'init', function(){
    if ( ! wp_next_scheduled( 'fr_daily_holiday_check' )){
        wp_schedule_event(
            time(),
            'daily',
            'fr_daily_holiday_check'
        );
    }
});

/* Holiday Reminder */
add_action( 'fr_daily_holiday_check', 'fr_check_upcoming_holidays' );
function fr_check_upcoming_holidays(){
    $to = 'connor@flexrockperformance.com';
    $subject = 'Upcoming Holiday';

    $newYears = date('Y-m-d', strtotime('January 1 next year'));
    $memorialDay = date('Y-m-d', strtotime('last monday of may'));
    $laborDay = date('Y-m-d', strtotime('first monday of september'));
    $thanksgiving = date('Y-m-d', strtotime('fourth thursday of november'));
    $christmas = date('Y-m-d', strtotime('12/25'));
    $newYearsEve = date('Y-m-d', strtotime('12/31'));

    $currentDate = wp_date('Y-m-d');

    $holidays = [
        'New Year\'s Day'   => $newYears,
        'Memorial Day'      => $memorialDay,
        'Labor Day'         => $laborDay,
        'Thanksgiving'      => $thanksgiving,
        'Christmas'         => $christmas,
        'New Year\'s Eve'   => $newYearsEve
    ];

    foreach ($holidays as $holidayName => $holidayDate){
        $weekBefore = date('Y-m-d', strtotime($holidayDate . ' -1 week'));

        if ($currentDate === $weekBefore){
            $message = $holidayName . " is one week away! Remember you have it off.";

            wp_mail($to, $subject, $message);
        }
    }
}

?>