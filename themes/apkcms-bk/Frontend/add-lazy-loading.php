<?php
/**
 * Add lazy loading attributes to images
 * This script adds data-src attributes for lazy loading
 */

echo "Adding lazy loading attributes to images...\n";

// Function to process HTML content
function addLazyLoading($content) {
    // Pattern to match img tags
    $pattern = '/<img([^>]*?)src=["\']([^"\']*?)["\']([^>]*?)>/i';
    
    $replacement = function($matches) {
        $beforeSrc = $matches[1];
        $src = $matches[2];
        $afterSrc = $matches[3];
        
        // Skip if already has data-src or is a placeholder
        if (strpos($beforeSrc, 'data-src') !== false || 
            strpos($afterSrc, 'data-src') !== false |
            strpos($src, 'placeholder') !== false ||
            strpos($src, 'data:') !== false) {
            return $matches[0];
        }
        
        // Add lazy loading attributes
        return '<img' . $beforeSrc . 'src=""  . $src . '"' . $afterSrc . '>';
    };
    
    return preg_replace_callback($pattern, $replacement, $content);
}

// Process theme files
$themeDir = 'themes/apkcms/Frontend/';
$processedFiles = 0;

if (is_dir($themeDir)) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($themeDir));
    
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $filePath = $file->getPathname();
            $content = file_get_contents($filePath);
            
            if ($content !== false) {
                $newContent = addLazyLoading($content);
                
                if ($newContent !== $content) {
                    file_put_contents($filePath, $newContent);
                    echo "Processed: $filePath\n";
                    $processedFiles++;
                }
            }
        }
    }
}

echo "\nLazy loading attributes added to $processedFiles files\n";
echo "Images will now load lazily for better performance!\n";
?>
