<?php
// Test upload with new image
require_once 'public/index.php';

// Clear logs
file_put_contents(PATH_WRITE . 'logs/logger.log', '');

// Create test image
$testImagePath = PATH_ROOT . '/test_new_image.jpg';
$testImageContent = file_get_contents($testImagePath);

// Simulate $_FILES array
$_FILES = [
    'files' => [
        'name' => ['test_new_image.jpg'],
        'type' => ['image/jpeg'],
        'tmp_name' => [$testImagePath],
        'error' => [0],
        'size' => [filesize($testImagePath)]
    ]
];

// Simulate $_POST array
$_POST = [
    'path' => '2025/09/23',
    'config' => json_encode([
        'resizes' => [],
        'watermark' => false,
        'watermark_img' => null,
        'output' => [
            'jpg' => ['name' => 'jpg', 'q' => 80],
            'webp' => ['name' => 'jpg.webp', 'q' => 80]
        ],
        'original' => true
    ])
];

// Simulate $_SERVER
$_SERVER['REQUEST_METHOD'] = 'POST';

try {
    // Create controller instance
    $controller = new \App\Controllers\Api\V1\FilesController();

    // Call the upload method
    $result = $controller->upload();

    echo "Upload result:\n";
    print_r($result);

    // Show logs
    echo "\n=== LOGS ===\n";
    $logContent = file_get_contents(PATH_WRITE . 'logs/logger.log');
    echo $logContent;

    // Check if file was created in correct location
    $uploadDir = PATH_WRITE . 'uploads/2025/09/23/';
    echo "\n=== CHECKING UPLOAD DIRECTORY ===\n";
    echo "Upload directory: " . $uploadDir . "\n";
    echo "Directory exists: " . (is_dir($uploadDir) ? 'YES' : 'NO') . "\n";

    if (is_dir($uploadDir)) {
        $files = scandir($uploadDir);
        echo "Files in directory:\n";
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                echo "- " . $file . "\n";
            }
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Cleanup
if (file_exists($testImagePath)) {
    unlink($testImagePath);
}
echo "\nTest completed.\n";
