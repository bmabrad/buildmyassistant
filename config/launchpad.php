<?php

return [
    // Maximum chat email submissions per IP per hour before rate-limiting kicks in.
    'chat_rate_limit' => (int) env('LAUNCHPAD_CHAT_RATE_LIMIT', 5),

    // Where admin alerts for generation fallbacks and terminal delivery failures go.
    'admin_alert_email' => env('LAUNCHPAD_ADMIN_ALERT_EMAIL', 'help@buildmyassistant.co'),
];
