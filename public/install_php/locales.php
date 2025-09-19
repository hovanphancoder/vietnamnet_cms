<?php
/**
 * Get available locales for the installer
 * Returns the 4 most popular languages worldwide with full information
 */

// Most popular languages worldwide with complete information
$availableLanguages = [
    [
        'code' => 'en',
        'name' => 'English',
        'flag' => 'https://flagcdn.com/w20/us.png'
    ],
    [
        'code' => 'zh',
        'name' => '中文',
        'flag' => 'https://flagcdn.com/w20/cn.png'
    ],
    [
        'code' => 'hi',
        'name' => 'हिन्दी',
        'flag' => 'https://flagcdn.com/w20/in.png'
    ],
    [
        'code' => 'es',
        'name' => 'Español',
        'flag' => 'https://flagcdn.com/w20/es.png'
    ]
];

// Return as JSON
echo json_encode($availableLanguages);
?>
