<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    |
    | People always get notifications inside the application. Emails are
    | optional: turn them on once the mail server is configured and a queue
    | worker is running, because they are sent through the queue.
    |
    */

    'notifications' => [
        'mail' => (bool) env('HELPDESK_MAIL_NOTIFICATIONS', false),
    ],

];
