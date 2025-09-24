<?php
// Test upload script with debug logging
require_once 'index.php';

// Clear previous logs
file_put_contents(PATH_WRITE . 'logs/logger.log', '');

echo "=== Testing file upload with debug logging ===\n";

// Simulate $_FILES array
$_FILES = [
    'files' => [
        'name' => ['The-Gioi-24H.jpg'],
        'type' => ['image/jpeg'],
        'tmp_name' => [sys_get_temp_dir() . '/test_upload.jpg'],
        'error' => [0],
        'size' => [1024]
    ]
];

// Simulate $_POST array
$_POST = [
    'path' => '2025:09:23',
    'config' => '{"resizes":[],"watermark":false,"watermark_img":null,"output":{"jpg":{"name":"jpg","q":80},"webp":{"name":"jpg.webp","q":80}},"original":true}'
];

// Create a test image file
$testImagePath = sys_get_temp_dir() . '/test_upload.jpg';
$imageData = base64_decode('/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/2wBDAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAv/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwA/8A8A');
file_put_contents($testImagePath, $imageData);

echo "Created test image at: $testImagePath\n";
echo "Test image size: " . filesize($testImagePath) . " bytes\n";

// Set up the environment
$_SERVER['REQUEST_METHOD'] = 'POST';

try {
    // Create controller instance
    $controller = new \App\Controllers\Api\V1\FilesController();

    // Call the upload method
    $result = $controller->upload();

    echo "Upload result:\n";
    print_r($result);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== DEBUG LOGS ===\n";
$logContent = file_get_contents(PATH_WRITE . 'logs/logger.log');
echo $logContent;

echo "\n=== Checking upload directory ===\n";
$uploadDir = PATH_WRITE . 'uploads/2025/09/23/';
echo "Upload directory: $uploadDir\n";
echo "Directory exists: " . (is_dir($uploadDir) ? 'YES' : 'NO') . "\n";

if (is_dir($uploadDir)) {
    echo "Directory contents:\n";
    $files = scandir($uploadDir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            echo "  - $file\n";
        }
    }
}

// Clean up
unlink($testImagePath);
echo "\nTest completed.\n";
