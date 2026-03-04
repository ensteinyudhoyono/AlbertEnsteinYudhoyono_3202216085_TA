<?php

return [
    /*
    |--------------------------------------------------------------------------
    | WhatsApp Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for GOWA WhatsApp API integration
    |
    */

    'base_url' => env('WHATSAPP_BASE_URL', 'http://localhost:3000'),
    'client_id' => env('WHATSAPP_CLIENT_ID'),
    'api_key' => env('WHATSAPP_API_KEY'),
    'basic_auth' => [
        'user' => env('WHATSAPP_BASIC_USER', 'admin'),
        'password' => env('WHATSAPP_BASIC_PASSWORD', 'admin'),
    ],
    'use_api_credentials' => env('WHATSAPP_USE_API_CREDENTIALS', false),
    
    /*
    |--------------------------------------------------------------------------
    | Default Settings
    |--------------------------------------------------------------------------
    */
    
    'default_country_code' => '62', // Indonesia
    'enable_logging' => env('WHATSAPP_ENABLE_LOGGING', true),
    
    /*
    |--------------------------------------------------------------------------
    | Notification Templates
    |--------------------------------------------------------------------------
    */
    
    'templates' => [
        'rent_created' => [
            'title' => ' PEMINJAMAN BARU',
            'template' => "Ruangan: {room_name}\nPeminjam: {user_name}\nWaktu: {start_time} - {end_time}\nStatus: Pending"
        ],
        'rent_approved' => [
            'title' => '✅ PEMINJAMAN DISETUJUI',
            'template' => "Ruangan: {room_name}\nPeminjam: {user_name}\nWaktu: {start_time} - {end_time}\nStatus: Disetujui"
        ],
        'rent_rejected' => [
            'title' => '❌ PEMINJAMAN DITOLAK',
            'template' => "Ruangan: {room_name}\nPeminjam: {user_name}\nWaktu: {start_time} - {end_time}\nStatus: Ditolak"
        ],
    ]
]; 