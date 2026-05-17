<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Uptime Monitor Configuration
    |--------------------------------------------------------------------------
    |
    | This file is for storing the configuration for the uptime monitoring system.
    | You can adjust settings such as check intervals, notification preferences,
    | and other related options here.
    |
    */

    'alert_email' => env('MONITOR_ALERT_EMAIL', env('MAIL_FROM_ADDRESS')),
];
