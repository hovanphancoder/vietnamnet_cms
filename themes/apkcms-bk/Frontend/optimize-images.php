<?php
/**
 * Image Optimization Script
 * Optimizes images for better PageSpeed performance
 */

// Check if ImageMagick is available
if (!extension_loaded('imagick')) {
    die("ImageMagick extension is required for image optimization.\n");
}

// Configuration
$uploadDir = 'writeable/uploads/';
$maxWidth = 800;
$maxHeight = 600;
$quality = 85;

// Supported image formats
$supportedFormats = ['jpg', 'jpeg', 'png', 'webp'];

echo "Starting image optimization...\n";

// Function to optimize image
function optimizeImage($filePath, $maxWidth, $maxHeight, $quality) {
    try {
        $image = new Imagick($filePath);
        
        // Get original dimensions
        $originalWidth = $image->getImageWidth();
        $originalHeight = $image->getImageHeight();
        
        // Calculate new dimensions maintaining aspect ratio
        $ratio = min($maxWidth / $originalWidth, $maxHeight / $originalHeight);
        
        if ($ratio < 1) {
            $newWidth = (int)($originalWidth * $ratio);
            $newHeight = (int)($originalHeight * $ratio);
            
            // Resize image
            $image->resizeImage($newWidth, $newHeight, Imagick::FILTER_LANCZOS, 1);
            
            // Set quality
            $image->setImageCompressionQuality($quality);
            
            // Strip metadata
            $image->stripImage();
            
            // Save optimized image
            $image->writeImage($filePath);
            
            echo "Optimized: $filePath ({$originalWidth}x{$originalHeight} -> {$newWidth}x{$newHeight})\n";
            
            return true;
        } else {
            echo "Skipped: $filePath (already optimal size)\n";
            return false;
        }
        
    } catch (Exception $e) {
        echo "Error optimizing $filePath: " . $e->getMessage() . "\n";
        return false;
    }
}

// Function to convert to WebP
function convertToWebP($filePath) {
    try {
        $image = new Imagick($filePath);
        $image->setImageFormat('webp');
        $image->setImageCompressionQuality(85);
        $image->stripImage();
        
        $webpPath = pathinfo($filePath, PATHINFO_DIRNAME) . '/' . pathinfo($filePath, PATHINFO_FILENAME) . '.webp';
        $image->writeImage($webpPath);
        
        echo "Created WebP: $webpPath\n";
        return $webpPath;
        
    } catch (Exception $e) {
        echo "Error creating WebP for $filePath: " . $e->getMessage() . "\n";
        return false;
    }
}

// Process images
$processed = 0;
$optimized = 0;
$webpCreated = 0;

if (is_dir($uploadDir)) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($uploadDir));
    
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $extension = strtolower($file->getExtension());
            
            if (in_array($extension, $supportedFormats)) {
                $filePath = $file->getPathname();
                $processed++;
                
                // Optimize original image
                if (optimizeImage($filePath, $maxWidth, $maxHeight, $quality)) {
                    $optimized++;
                }
                
                // Create WebP version for JPG/PNG
                if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
                    if (convertToWebP($filePath)) {
                        $webpCreated++;
                    }
                }
            }
        }
    }
}

echo "\nOptimization complete!\n";
echo "Processed: $processed images\n";
echo "Optimized: $optimized images\n";
echo "WebP created: $webpCreated images\n";
echo "Estimated savings: " . ($optimized * 20) . "KB\n";
?>
