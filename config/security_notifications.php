<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Security Notification Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for security notifications when rent time_end_use is reached
    |
    */

    'enabled' => env('SECURITY_NOTIFICATIONS_ENABLED', true),
    
    'role_id' => env('SECURITY_ROLE_ID', 2),
    
    'timing' => [
        'end_time_tolerance_minutes' => 1, // Minutes tolerance for end time detection
        'overdue_check_minutes' => 5, // Minutes after end time to check for overdue
    ],
    
    'whatsapp' => [
        'enabled' => env('SECURITY_WHATSAPP_ENABLED', true),
        'priority' => 'high', // high, normal, low
    ],
    
    'database' => [
        'enabled' => env('SECURITY_DATABASE_NOTIFICATIONS', true),
    ],
    
    'email' => [
        'enabled' => env('SECURITY_EMAIL_NOTIFICATIONS', false),
        'fallback' => true, // Send email if WhatsApp fails
    ],
    
    'testing' => [
        'enabled' => env('SECURITY_TESTING_MODE', false),
        'test_phone' => env('SECURITY_TEST_PHONE', null),
    ],
];

