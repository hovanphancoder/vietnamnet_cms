<?php

return [
    // Application configuration
    'app' => [
        'debug' => true, //Set to true for development mode, show error and debug information
        'app_url' => 'https://vietnamnet2.vn',
        'app_name' => 'apkthemes',
        'app_timezone' => 'Asia/Ho_Chi_Minh'
    ],
    'files' => [
        'path' => 'writeable/uploads',
        'files_url' => '/uploads/',
        'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'docx', 'doc', 'xls', 'xlsx', 'csv', 'ppt', 'pptx', 'txt', 'rar', 'zip', 'iso' , 'mp3', 'wav', 'mkv', 'mp4', 'srt'], // Allowed file types
        'max_file_size' => 10485760, // Maximum file size limit: 10MB (in bytes)
        'images_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp'], // File types supported for thumbnail display
        'max_file_count' => 10, // Limit number of files uploaded at once
        'limit' => 48, // Limit number of files per pagination page
    ],
    'security' => [
        'app_id' => '458395437545',
        'app_secret' => 'ApkTemplatesSecret@2025'
    ],
    'db' => [
        // Database configuration
        'db_driver' => 'mysql',
        'db_host' => 'localhost',
        'db_port' => 3306,
        'db_prefix' => 'fast_',
        'db_username' => 'root',
        'db_password' => '',
        'db_database' => 'vietnamnet2.vn',
        'db_charset'  => 'utf8mb4',
        'db_collate'  => 'utf8mb4_unicode_ci',
    ],
    'theme' => [
        'theme_path' => 'themes',
        'theme_name' => 'apkcms'
    ]
    
];