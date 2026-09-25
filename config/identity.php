<?php

return [
    'otp' => [
        'length' => 6,
        'expires_after_minutes' => 10,
        'max_attempts' => 3,
        'resend_cooldown_seconds' => 300,
        'max_requests' => 5,
        'request_decay_seconds' => 3600,
    ],
];
