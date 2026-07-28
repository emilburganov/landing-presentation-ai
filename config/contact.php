<?php

return [
    'rate_limit_max' => env('CONTACT_RATE_LIMIT_MAX', 5),
    'rate_limit_window' => env('CONTACT_RATE_LIMIT_WINDOW', 600),
    'owner_email' => env('CONTACT_OWNER_EMAIL'),
];
