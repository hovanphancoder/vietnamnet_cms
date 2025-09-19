<?php
/**
 * Fix Images - Remove lazy loading and show images normally
 * This script fixes the image display issue by removing data-src attributes
 */

echo "🔧 Fixing image display issues...\n";

// Function to fix images in content
function fixImages($content) {
    // Pattern to match img tags with data-src
    $pattern = '/<img([^>]*?)src=["\']([^"\']*?)["\']([^>]*?)data-src=["\']([^"\']*?)["\']([^>]*?)>/i';
    
    $replacement = function($matches) {
        $beforeSrc = $matches[1];
        $placeholderSrc = $matches[2];
        $betweenSrc = $matches[3];
        $realSrc = $matches[4];
        $afterDataSrc = $matches[5];
        
        // Remove data-src and loading="lazy" attributes
        $afterDataSrc = preg_replace('/\s+data-src=["\'][^"\']*["\']/', '', $afterDataSrc);
        $afterDataSrc = preg_replace('/\s+loading=["\']lazy["\']/', '', $afterDataSrc);
        
        // Use the real src instead of placeholder
        return '<img' . $beforeSrc . 'src="' . $realSrc . '"' . $betweenSrc . $afterDataSrc . '>';
    };
    
    return preg_replace_callback($pattern, $replacement, $content);
}

// Process all PHP files in theme
$themeDir = __DIR__;
$processedFiles = 0;

if (is_dir($themeDir)) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($themeDir));
    
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $filePath = $file->getPathname();
            $content = file_get_contents($filePath);
            
            if ($content !== false) {
                $newContent = fixImages($content);
                
                if ($newContent !== $content) {
                    file_put_contents($filePath, $newContent);
                    echo "✅ Fixed: " . basename($filePath) . "\n";
                    $processedFiles++;
                }
            }
        }
    }
}

echo "\n🎯 Image fix complete!\n";
echo "Processed: $processedFiles files\n";
echo "Images should now display normally.\n";
?>
