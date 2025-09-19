<?php
/**
 * Check file sizes and provide optimization recommendations
 */

echo "Checking file sizes for optimization...\n\n";

// Check CSS files
$cssFiles = [
    'themes/apkcms/Frontend/Assets/css/styles.css',
    'themes/apkcms/Frontend/Assets/css/styles.min.css'
];

echo "CSS Files:\n";
foreach ($cssFiles as $file) {
    if (file_exists($file)) {
        $size = filesize($file);
        $sizeKB = round($size / 1024, 2);
        echo "- $file: {$sizeKB}KB\n";
    }
}

// Check JS files
$jsFiles = [
    'themes/apkcms/Frontend/Assets/js/script.js',
    'themes/apkcms/Frontend/Assets/js/script.min.js',
    'themes/apkcms/Frontend/Assets/js/script-optimized.js',
    'themes/apkcms/Frontend/Assets/js/script-optimized.min.js',
    'themes/apkcms/Frontend/Assets/js/lazy-load.js',
    'themes/apkcms/Frontend/Assets/js/lazy-load.min.js',
    'themes/apkcms/Frontend/Assets/js/single.js',
    'themes/apkcms/Frontend/Assets/js/single.min.js',
    'themes/apkcms/Frontend/Assets/js/single-news.js',
    'themes/apkcms/Frontend/Assets/js/single-news.min.js'
];

echo "\nJavaScript Files:\n";
$totalJSSize = 0;
foreach ($jsFiles as $file) {
    if (file_exists($file)) {
        $size = filesize($file);
        $sizeKB = round($size / 1024, 2);
        $totalJSSize += $size;
        echo "- $file: {$sizeKB}KB\n";
    }
}

echo "\nTotal JavaScript size: " . round($totalJSSize / 1024, 2) . "KB\n";

// Check image files
echo "\nImage Files (largest 10):\n";
$imageFiles = [];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('writeable/uploads/'));
foreach ($iterator as $file) {
    if ($file->isFile() && preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $file->getFilename())) {
        $imageFiles[] = [
            'path' => $file->getPathname(),
            'size' => $file->getSize()
        ];
    }
}

// Sort by size (largest first)
usort($imageFiles, function($a, $b) {
    return $b['size'] - $a['size'];
});

$totalImageSize = 0;
for ($i = 0; $i < min(10, count($imageFiles)); $i++) {
    $file = $imageFiles[$i];
    $sizeKB = round($file['size'] / 1024, 2);
    $totalImageSize += $file['size'];
    echo "- " . basename($file['path']) . ": {$sizeKB}KB\n";
}

echo "\nTotal image size (top 10): " . round($totalImageSize / 1024, 2) . "KB\n";

// Recommendations
echo "\n=== OPTIMIZATION RECOMMENDATIONS ===\n";

if (file_exists('themes/apkcms/Frontend/Assets/css/styles.min.css')) {
    $originalSize = file_exists('themes/apkcms/Frontend/Assets/css/styles.css') ? filesize('themes/apkcms/Frontend/Assets/css/styles.css') : 0;
    $minifiedSize = filesize('themes/apkcms/Frontend/Assets/css/styles.min.css');
    $savings = $originalSize - $minifiedSize;
    $savingsPercent = round(($savings / $originalSize) * 100, 1);
    echo "✅ CSS minified: Saved {$savingsPercent}% (" . round($savings / 1024, 2) . "KB)\n";
} else {
    echo "❌ CSS not minified\n";
}

if (file_exists('themes/apkcms/Frontend/Assets/js/script-optimized.min.js')) {
    echo "✅ JavaScript optimized and minified\n";
} else {
    echo "❌ JavaScript not optimized\n";
}

if (file_exists('themes/apkcms/Frontend/Assets/js/lazy-load.min.js')) {
    echo "✅ Lazy loading implemented\n";
} else {
    echo "❌ Lazy loading not implemented\n";
}

echo "\n=== NEXT STEPS ===\n";
echo "1. Test the website with Google PageSpeed Insights\n";
echo "2. Monitor Core Web Vitals\n";
echo "3. Consider implementing a CDN for static assets\n";
echo "4. Optimize images with WebP format\n";
echo "5. Implement service worker for caching\n";
?>
