<?php

namespace System\Libraries\Upload;

use App\Libraries\Fastlang;
use System\Drivers\Image\ImageManager;
use System\Libraries\Upload\SecurityManager;
use System\Libraries\Logger;
use System\Libraries\Files;

/**
 * ImageOptimizer - Handle image optimization with resize, watermark, and format conversion
 * 
 * Main features:
 * - Process image optimization for uploaded files
 * - Resize images to multiple dimensions
 * - Add watermarks with positioning and opacity
 * - Convert images to WebP format (preserves original extension in filename)
 * - Strip EXIF metadata for security
 * - Support for JPEG, PNG, GIF, WebP formats
 * 
 * @package System\Libraries\Upload
 * @since 1.0.0
 */
class ImageOptimizer
{

    /**
     * Process image optimization if needed.
     * 
     * Determines if file is an image and has optimization options.
     * Calls optimize() method if optimization is required.
     * Handles resize string generation for database storage.
     * 
     * @param array $fileInfo File information array
     *                        Structure: ['finalPath' => string, 'path' => string, 'type' => string, ...]
     * @param array $options Upload options containing optimization settings
     *                        - 'resizes' => array: Image resize configurations
     *                        - 'watermark' => array: Watermark settings
     *                        - 'webp' => bool: Convert to WebP format
     * 
     * @return array Optimization result with structure:
     *               - success: bool - Optimization success status
     *               - error: string|null - Error message if any
     *               - data: array|null - Optimization data if successful
     *                 Structure: ['resizeStr' => string, 'optimized' => array|null]
     */
    public static function processImageOptimization($fileInfo, $options)
    {
        // Load language for static method
        Fastlang::load('files', APP_LANG);

        $config = config('files') ?? [];
        $allowed_types = $config['allowed_types'] ?? ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        $isImage = in_array($fileInfo['type'], $config['images_types'] ?? $allowed_types, true);

        // Check if webp is enabled (either boolean true or object with config)
        $webpEnabled = !empty($options['webp']) && (
            $options['webp'] === true ||
            (is_array($options['webp']) && !empty($options['webp']))
        );

        if (!$isImage || (empty($options['resizes']) && empty($options['watermark']) && !$webpEnabled)) {
            return [
                'success' => true,
                'error' => null,
                'data' => [
                    'resizeStr' => '',
                    'optimized' => null
                ]
            ];
        }

        // Get the file path - check both 'finalPath' and 'path' keys
        $filePath = $fileInfo['finalPath'] ?? $fileInfo['path'] ?? null;

        if (empty($filePath)) {
            return [
                'success' => false,
                'error' => Fastlang::_e('file path not found for optimization'),
                'data' => null
            ];
        }

        $optResult = self::optimize($filePath, $options);

        if (!empty($optResult['error'])) {
            Logger::error('ImageOptimizer::processImageOptimization - Optimization failed: ' . json_encode([
                'error' => $optResult['error'],
                'filePath' => $filePath
            ]));
            return [
                'success' => false,
                'error' => $optResult['error'],
                'data' => null
            ];
        }

        $resizeStr = '';
        if (!empty($optResult['resizes'])) {
            $sizes = [];
            foreach ($optResult['resizes'] as $resizePath) {
                if (preg_match('/_(\d+x\d+)\./', $resizePath, $m)) {
                    $sizes[] = $m[1];
                }
            }
            $resizeStr = implode(';', array_unique($sizes));
        }

        return [
            'success' => true,
            'error' => null,
            'data' => [
                'resizeStr' => $resizeStr,
                'optimized' => $optResult
            ]
        ];
    }

    /**
     * Optimize an image: resize, watermark, convert to webp, etc.
     * 
     * Performs comprehensive image optimization including:
     * - Resize images to specified dimensions
     * - Add watermarks with custom positioning
     * - Convert images to WebP format (preserves original extension in filename) (preserves original extension in filename)
     * - Strip EXIF metadata for security
     * - Handle multiple resize configurations
     * 
     * @param string $filePath Path to the original image file
     * @param array $options Optimization options
     *                        - 'resizes' => array: Array of resize configurations
     *                          Structure: [['width' => int, 'height' => int, 'cover' => bool], ...]
     *                        - 'watermark' => array: Watermark configuration
     *                          Structure: ['file' => string, 'position' => string, 'padding' => int, 'opacity' => int]
     *                        - 'webp' => bool: Convert to WebP format
     * 
     * @return array Optimization result with structure:
     *               - success: bool - Optimization success status
     *               - error: string|null - Error message if any
     *               - data: array|null - Optimization data if successful
     *                 Structure: [
     *                   'resizes' => array - Array of resized image paths,
     *                   'watermark' => string|null - Watermarked image path,
     *                   'webp' => array - Array of WebP converted image paths
     *                 ]
     */
    public static function optimize($filePath, array $options = [])
    {
        // Load language for static method
        Fastlang::load('files', APP_LANG);

        // Validate file path
        if (empty($filePath) || !is_string($filePath)) {
            Logger::error('ImageOptimizer::optimize - Invalid file path: ' . json_encode([
                'filePath' => $filePath
            ]));
            return [
                'success' => false,
                'error' => Fastlang::_e('invalid file path provided'),
                'data' => null
            ];
        }

        // Check if file exists
        if (!file_exists($filePath)) {
            Logger::error('ImageOptimizer::optimize - File does not exist: ' . json_encode([
                'filePath' => $filePath
            ]));
            return [
                'success' => false,
                'error' => Fastlang::_e('file does not exist', $filePath),
                'data' => null
            ];
        }

        $result = [
            'resizes'   => [],  // array of paths
            'watermark' => null, // single path or null
            'webp'      => [],  // array of paths
        ];

        try {
            $dir  = dirname($filePath);
            $base = pathinfo($filePath, PATHINFO_FILENAME); // abc
            $ext  = pathinfo($filePath, PATHINFO_EXTENSION);

            // Security: Strip EXIF metadata before processing
            SecurityManager::stripExifMetadata($filePath);

            $img  = ImageManager::load($filePath);

            // Resolve watermark file path first (if watermark is enabled)
            $watermarkPath = null;
            $wm = null;
            if (!empty($options['watermark']['file'])) {
                $wm = $options['watermark'];

                // Resolve watermark file path
                $watermarkPath = $wm['file'];
                if (!Files::isAbsolutePath($watermarkPath)) {
                    $baseUpload = PATH_WRITE . 'uploads';
                    $watermarkPath = rtrim(PATH_ROOT, '/\\') . DIRECTORY_SEPARATOR . trim($baseUpload, '/\\') . DIRECTORY_SEPARATOR . ltrim($watermarkPath, '/\\');
                }
                if (!file_exists($watermarkPath)) {
                    Logger::error('ImageOptimizer::optimize - Watermark file not found: ' . json_encode([
                        'watermarkPath' => $watermarkPath,
                        'baseUpload' => PATH_WRITE . 'uploads',
                        'rootPath' => PATH_ROOT
                    ]));
                    $watermarkPath = null; // Disable watermark if file not found
                }
            }

            if (!empty($options['resizes'])) {
                foreach ($options['resizes'] as $index => $config) {
                    $w     = $config['width']  ?? 0;
                    $h     = $config['height'] ?? 0;
                    $cover = $config['cover']  ?? true;

                    if ($w > 0 && $h > 0) {
                        // Create a fresh image instance from original file to avoid watermark duplication
                        $img2 = ImageManager::load($filePath);
                        try {
                            $img2->resize($w, $h, true, $cover);

                            // Apply watermark to resized image if enabled
                            if ($watermarkPath) {
                                $img2->addWatermark(
                                    $watermarkPath,
                                    $wm['position'] ?? 'bottom-right',
                                    $wm['padding']  ?? 10,
                                    $wm['opacity']  ?? 100
                                );
                            }
                        } catch (\Exception $e) {
                            Logger::error('ImageOptimizer::optimize - Resize/Watermark failed: ' . json_encode([
                                'error' => $e->getMessage(),
                                'width' => $w,
                                'height' => $h
                            ]));
                            $img2->destroy();
                            continue;
                        }
                        $name = "{$base}_{$w}x{$h}.{$ext}";
                        $path = "$dir/{$name}";

                        try {
                            // Use quality from config if available
                            $quality = $options['jpg_quality'] ?? 90;
                            $img2->save($path, $quality);
                        } catch (\Exception $e) {
                            Logger::error('ImageOptimizer::optimize - Save failed: ' . json_encode([
                                'error' => $e->getMessage(),
                                'path' => $path
                            ]));
                            $img2->destroy();
                            continue;
                        }
                        $result['resizes'][] = $path;

                        if (!empty($options['webp'])) {
                            // Handle webp as object with name and quality
                            $webpConfig = $options['webp'];
                            $webpQuality = 90; // Default quality

                            if (is_array($webpConfig) && isset($webpConfig['q'])) {
                                $webpQuality = (int)$webpConfig['q'];
                            }

                            $webp = "$dir/{$base}_{$w}x{$h}.{$ext}.webp";
                            try {
                                // Create a copy of the resized image for WebP conversion to preserve watermark
                                $img2Webp = clone $img2;
                                $img2Webp->convert('webp')->save($webp, $webpQuality);
                                $result['webp'][] = $webp;
                                $img2Webp->destroy();
                            } catch (\Exception $e) {
                                Logger::error('ImageOptimizer::optimize - WebP conversion failed: ' . json_encode([
                                    'error' => $e->getMessage(),
                                    'path' => $webp
                                ]));
                            }
                        }
                        $img2->destroy();
                    } else {
                        Logger::warning('ImageOptimizer::optimize - Invalid resize dimensions: ' . json_encode([
                            'width' => $w,
                            'height' => $h
                        ]));
                    }
                }
            }

            // Apply watermark to original image if enabled (after processing resizes)
            if ($watermarkPath && !empty($options['original'])) {
                try {
                    $img->addWatermark(
                        $watermarkPath,
                        $wm['position'] ?? 'bottom-right',
                        $wm['padding']  ?? 10,
                        $wm['opacity']  ?? 100
                    ); // Save the original image with watermark
                    $img->save($filePath, 90);
                } catch (\Exception $e) {
                    Logger::error('ImageOptimizer::optimize - Watermark application to original failed: ' . json_encode([
                        'error' => $e->getMessage(),
                        'watermark_path' => $watermarkPath
                    ]));
                }
            }

            if (!empty($options['webp'])) {
                // Handle webp as object with name and quality
                $webpConfig = $options['webp'];
                $webpQuality = 90; // Default quality

                if (is_array($webpConfig) && isset($webpConfig['q'])) {
                    $webpQuality = (int)$webpConfig['q'];
                }

                $webpPath = "$filePath.webp";

                try {
                    // Create a fresh image instance from original file for WebP conversion to avoid watermark duplication
                    $imgWebp = ImageManager::load($filePath);

                    // Apply watermark to WebP version if enabled
                    if ($watermarkPath) {
                        $imgWebp->addWatermark(
                            $watermarkPath,
                            $wm['position'] ?? 'bottom-right',
                            $wm['padding']  ?? 10,
                            $wm['opacity']  ?? 100
                        );
                    }

                    $imgWebp->convert('webp')->save($webpPath, $webpQuality);
                    $result['webp'][] = $webpPath;
                    $imgWebp->destroy();
                } catch (\Exception $e) {
                    Logger::error('ImageOptimizer::optimize - Original WebP conversion failed: ' . json_encode([
                        'error' => $e->getMessage(),
                        'path' => $webpPath
                    ]));
                }
            }

            $img->destroy();
        } catch (\Exception $e) {
            Logger::error('ImageOptimizer::optimize - Exception during optimization: ' . json_encode([
                'error' => $e->getMessage()
            ]));
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => null
            ];
        }

        return [
            'success' => true,
            'error' => null,
            'data' => $result
        ];
    }

    /**
     * Generate output path for processed image.
     * 
     * Creates the output path for processed images based on source file,
     * size specification, and optional format conversion.
     * 
     * @param string $sourceFile Source image file path
     * @param string $size Size specification (e.g. '300x200', 'original')
     * @param string|null $format Output format (optional, uses source format if null)
     * 
     * @return string Generated output path
     */
    public static function getOutputPath($sourceFile, $size, $format = null)
    {
        $info = pathinfo($sourceFile);
        $dir = $info['dirname'];
        $filename = $info['filename'];
        if ($size === 'original') {
            $newFilename = $filename;
        } else {
            $newFilename = $filename . '_' . $size;
        }
        if ($format) {
            $newFilename .= '.' . $format;
        } else {
            $newFilename .= '.' . $info['extension'];
        }
        return $dir . DIRECTORY_SEPARATOR . $newFilename;
    }

    /**
     * Build the final file name for a variant (original or resized).
     * 
     * Constructs the final filename for image variants based on base name,
     * size key, and file extension.
     * 
     * @param string $base Base file name (without extension)
     * @param string $sizeKey Size key (e.g. 'original', '300x200')
     * @param string $ext File extension
     * 
     * @return string Final filename for the variant
     */
    public static function buildFileName($base, $sizeKey, $ext)
    {
        return $sizeKey !== 'original'
            ? "{$base}_{$sizeKey}.{$ext}"
            : "{$base}.{$ext}";
    }

    /**
     * Crop an image to specific dimensions.
     * 
     * Crops an image from specified coordinates to specified dimensions.
     * 
     * @param string $filePath Path to the original image file
     * @param array $options Crop options
     *                        - 'x' => int: X coordinate for crop start
     *                        - 'y' => int: Y coordinate for crop start
     *                        - 'width' => int: Crop width
     *                        - 'height' => int: Crop height
     *                        - 'save_as' => string: Output filename (optional)
     *                        - 'quality' => int: Image quality (1-100, default: 90)
     * 
     * @return array Crop result with structure:
     *               - success: bool - Crop success status
     *               - error: string|null - Error message if any
     *               - data: array|null - Crop data if successful
     *                 Structure: ['output_path' => string, 'dimensions' => array]
     */
    public static function crop($filePath, array $options = [])
    {
        // Load language for static method
        Fastlang::load('files', APP_LANG);

        // Validate file path
        if (empty($filePath) || !is_string($filePath)) {
            Logger::error('ImageOptimizer::crop - Invalid file path: ' . json_encode([
                'filePath' => $filePath
            ]));
            return [
                'success' => false,
                'error' => Fastlang::_e('invalid file path provided'),
                'data' => null
            ];
        }

        // Check if file exists
        if (!file_exists($filePath)) {
            Logger::error('ImageOptimizer::crop - File does not exist: ' . json_encode([
                'filePath' => $filePath
            ]));
            return [
                'success' => false,
                'error' => Fastlang::_e('file does not exist', $filePath),
                'data' => null
            ];
        }

        // Validate crop parameters
        $x = $options['x'] ?? 0;
        $y = $options['y'] ?? 0;
        $width = $options['width'] ?? 0;
        $height = $options['height'] ?? 0;
        $quality = $options['quality'] ?? 90;

        if ($width <= 0 || $height <= 0) {
            Logger::error('ImageOptimizer::crop - Invalid crop dimensions: ' . json_encode([
                'width' => $width,
                'height' => $height
            ]));
            return [
                'success' => false,
                'error' => Fastlang::_e('invalid crop dimensions'),
                'data' => null
            ];
        }

        try {
            $dir = dirname($filePath);
            $base = pathinfo($filePath, PATHINFO_FILENAME);
            $ext = pathinfo($filePath, PATHINFO_EXTENSION);

            // Security: Strip EXIF metadata before processing
            SecurityManager::stripExifMetadata($filePath);

            $img = ImageManager::load($filePath);

            // Get original dimensions
            $originalWidth = $img->getWidth();
            $originalHeight = $img->getHeight();

            // Validate crop coordinates
            if ($x < 0 || $y < 0 || $x + $width > $originalWidth || $y + $height > $originalHeight) {
                Logger::error('ImageOptimizer::crop - Crop coordinates out of bounds: ' . json_encode([
                    'x' => $x,
                    'y' => $y,
                    'width' => $width,
                    'height' => $height,
                    'original_width' => $originalWidth,
                    'original_height' => $originalHeight
                ]));
                return [
                    'success' => false,
                    'error' => Fastlang::_e('crop coordinates out of bounds'),
                    'data' => null
                ];
            }

            // Perform crop
            $img->crop($x, $y, $width, $height);

            // Determine output path
            $outputPath = $options['save_as'] ?? "$dir/{$base}_cropped_{$width}x{$height}.{$ext}";

            // Ensure output directory exists
            $outputDir = dirname($outputPath);
            if (!is_dir($outputDir)) {
                mkdir($outputDir, 0755, true);
            }

            // Save cropped image
            $img->save($outputPath, $quality);
            $img->destroy();
            return [
                'success' => true,
                'error' => null,
                'data' => [
                    'output_path' => $outputPath,
                    'dimensions' => ['width' => $width, 'height' => $height],
                    'crop_info' => ['x' => $x, 'y' => $y, 'width' => $width, 'height' => $height]
                ]
            ];
        } catch (\Exception $e) {
            Logger::error('ImageOptimizer::crop - Exception during crop: ' . json_encode([
                'error' => $e->getMessage(),
                'filePath' => $filePath
            ]));
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Crop an image by ratio (e.g., 16:9, 4:3, 1:1).
     * 
     * Crops an image to maintain a specific aspect ratio.
     * 
     * @param string $filePath Path to the original image file
     * @param array $options Crop options
     *                        - 'ratio_width' => int: Ratio width (e.g., 16 for 16:9)
     *                        - 'ratio_height' => int: Ratio height (e.g., 9 for 16:9)
     *                        - 'position' => string: Crop position ('center', 'top-left', 'top-right', 'bottom-left', 'bottom-right')
     *                        - 'save_as' => string: Output filename (optional)
     *                        - 'quality' => int: Image quality (1-100, default: 90)
     * 
     * @return array Crop result with structure:
     *               - success: bool - Crop success status
     *               - error: string|null - Error message if any
     *               - data: array|null - Crop data if successful
     *                 Structure: ['output_path' => string, 'dimensions' => array, 'ratio' => string]
     */
    public static function cropByRatio($filePath, array $options = [])
    {
        // Load language for static method
        Fastlang::load('files', APP_LANG);

        // Validate file path
        if (empty($filePath) || !is_string($filePath)) {
            Logger::error('ImageOptimizer::cropByRatio - Invalid file path: ' . json_encode([
                'filePath' => $filePath
            ]));
            return [
                'success' => false,
                'error' => Fastlang::_e('invalid file path provided'),
                'data' => null
            ];
        }

        // Check if file exists
        if (!file_exists($filePath)) {
            Logger::error('ImageOptimizer::cropByRatio - File does not exist: ' . json_encode([
                'filePath' => $filePath
            ]));
            return [
                'success' => false,
                'error' => Fastlang::_e('file does not exist', $filePath),
                'data' => null
            ];
        }

        // Validate ratio parameters
        $ratioWidth = $options['ratio_width'] ?? 1;
        $ratioHeight = $options['ratio_height'] ?? 1;
        $position = $options['position'] ?? 'center';
        $quality = $options['quality'] ?? 90;

        if ($ratioWidth <= 0 || $ratioHeight <= 0) {
            Logger::error('ImageOptimizer::cropByRatio - Invalid ratio: ' . json_encode([
                'ratio_width' => $ratioWidth,
                'ratio_height' => $ratioHeight
            ]));
            return [
                'success' => false,
                'error' => Fastlang::_e('invalid ratio dimensions'),
                'data' => null
            ];
        }

        try {
            $dir = dirname($filePath);
            $base = pathinfo($filePath, PATHINFO_FILENAME);
            $ext = pathinfo($filePath, PATHINFO_EXTENSION);

            // Security: Strip EXIF metadata before processing
            SecurityManager::stripExifMetadata($filePath);

            $img = ImageManager::load($filePath);

            // Get original dimensions
            $originalWidth = $img->getWidth();
            $originalHeight = $img->getHeight();

            // Calculate crop dimensions based on ratio
            $targetRatio = $ratioWidth / $ratioHeight;
            $currentRatio = $originalWidth / $originalHeight;

            if ($currentRatio > $targetRatio) {
                // Image is wider than target ratio, crop width
                $cropWidth = (int)($originalHeight * $targetRatio);
                $cropHeight = $originalHeight;
            } else {
                // Image is taller than target ratio, crop height
                $cropWidth = $originalWidth;
                $cropHeight = (int)($originalWidth / $targetRatio);
            }

            // Calculate crop position
            switch ($position) {
                case 'top-left':
                    $x = 0;
                    $y = 0;
                    break;
                case 'top-right':
                    $x = $originalWidth - $cropWidth;
                    $y = 0;
                    break;
                case 'bottom-left':
                    $x = 0;
                    $y = $originalHeight - $cropHeight;
                    break;
                case 'bottom-right':
                    $x = $originalWidth - $cropWidth;
                    $y = $originalHeight - $cropHeight;
                    break;
                case 'center':
                default:
                    $x = (int)(($originalWidth - $cropWidth) / 2);
                    $y = (int)(($originalHeight - $cropHeight) / 2);
                    break;
            }

            // Perform crop
            $img->cropByRatio($ratioWidth, $ratioHeight);

            // Determine output path
            $ratioStr = "{$ratioWidth}x{$ratioHeight}";
            $outputPath = $options['save_as'] ?? "$dir/{$base}_ratio_{$ratioStr}.{$ext}";

            // Ensure output directory exists
            $outputDir = dirname($outputPath);
            if (!is_dir($outputDir)) {
                mkdir($outputDir, 0755, true);
            }

            // Save cropped image
            $img->save($outputPath, $quality);
            $img->destroy();
            return [
                'success' => true,
                'error' => null,
                'data' => [
                    'output_path' => $outputPath,
                    'dimensions' => ['width' => $cropWidth, 'height' => $cropHeight],
                    'ratio' => $ratioStr,
                    'position' => $position,
                    'crop_info' => ['x' => $x, 'y' => $y, 'width' => $cropWidth, 'height' => $cropHeight]
                ]
            ];
        } catch (\Exception $e) {
            Logger::error('ImageOptimizer::cropByRatio - Exception during crop: ' . json_encode([
                'error' => $e->getMessage(),
                'filePath' => $filePath
            ]));
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => null
            ];
        }
    }
}
