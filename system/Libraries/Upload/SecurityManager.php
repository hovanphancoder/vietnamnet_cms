<?php

namespace System\Libraries\Upload;

use App\Libraries\Fastlang;
use System\Libraries\Logger;

/**
 * SecurityManager - Handle security measures for file uploads
 * 
 * Main features:
 * - Sanitize SVG content to prevent XSS attacks
 * - Strip EXIF metadata from images to prevent information leakage
 * - Validate file content beyond MIME type checking
 * - Security validation for uploaded files
 * - Support for multiple image formats (JPEG, PNG, WebP)
 * 
 * @package System\Libraries\Upload
 * @since 1.0.0
 */
class SecurityManager
{
    /**
     * Sanitize SVG content to prevent XSS attacks.
     * 
     * Removes dangerous elements and attributes from SVG content.
     * Blocks script tags, object tags, event handlers, and javascript: URLs.
     * Maintains SVG structure while removing security risks.
     * 
     * @param string $svgContent Raw SVG content to sanitize
     * @param array $options Sanitization options (optional)
     *                        - 'remove_scripts' => bool: Remove script elements (default: true)
     *                        - 'remove_objects' => bool: Remove object elements (default: true)
     *                        - 'remove_events' => bool: Remove event handlers (default: true)
     *                        - 'remove_javascript' => bool: Remove javascript: URLs (default: true)
     * 
     * @return string Sanitized SVG content
     */
    public static function sanitizeSvg($svgContent, $options = [])
    {
        $defaultOptions = [
            'remove_scripts' => true,
            'remove_objects' => true,
            'remove_events' => true,
            'remove_javascript' => true
        ];

        $options = array_merge($defaultOptions, $options);

        $removedItems = [];

        // Remove script tags and their contents
        if ($options['remove_scripts']) {
            $beforeCount = substr_count($svgContent, '<script');
            $svgContent = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $svgContent);
            $afterCount = substr_count($svgContent, '<script');
            $removedItems['scripts'] = $beforeCount - $afterCount;
        }

        // Remove object tags and their contents
        if ($options['remove_objects']) {
            $beforeCount = substr_count($svgContent, '<object');
            $svgContent = preg_replace('/<object[^>]*>.*?<\/object>/is', '', $svgContent);
            $afterCount = substr_count($svgContent, '<object');
            $removedItems['objects'] = $beforeCount - $afterCount;
        }

        // Remove event handlers (onload, onclick, etc.)
        if ($options['remove_events']) {
            $beforeCount = preg_match_all('/\s+on\w+\s*=\s*["\'][^"\']*["\']/i', $svgContent);
            $svgContent = preg_replace('/\s+on\w+\s*=\s*["\'][^"\']*["\']/i', '', $svgContent);
            $afterCount = preg_match_all('/\s+on\w+\s*=\s*["\'][^"\']*["\']/i', $svgContent);
            $removedItems['events'] = $beforeCount - $afterCount;
        }

        // Remove javascript: URLs
        if ($options['remove_javascript']) {
            $beforeCount = substr_count(strtolower($svgContent), 'javascript:');
            $svgContent = preg_replace('/javascript:\s*[^"\']*/i', '', $svgContent);
            $afterCount = substr_count(strtolower($svgContent), 'javascript:');
            $removedItems['javascript_urls'] = $beforeCount - $afterCount;
        }

        // Remove any remaining dangerous attributes
        $dangerousAttributes = [
            'onabort',
            'onblur',
            'onchange',
            'onclick',
            'ondblclick',
            'onerror',
            'onfocus',
            'onkeydown',
            'onkeypress',
            'onkeyup',
            'onload',
            'onmousedown',
            'onmousemove',
            'onmouseout',
            'onmouseover',
            'onmouseup',
            'onreset',
            'onselect',
            'onsubmit',
            'onunload'
        ];

        foreach ($dangerousAttributes as $attr) {
            $beforeCount = preg_match_all('/\s+' . $attr . '\s*=\s*["\'][^"\']*["\']/i', $svgContent);
            $svgContent = preg_replace('/\s+' . $attr . '\s*=\s*["\'][^"\']*["\']/i', '', $svgContent);
            $afterCount = preg_match_all('/\s+' . $attr . '\s*=\s*["\'][^"\']*["\']/i', $svgContent);
            $removedItems['dangerous_attributes'] = ($removedItems['dangerous_attributes'] ?? 0) + ($beforeCount - $afterCount);
        }

        return $svgContent;
    }

    /**
     * Strip EXIF metadata from images to prevent information leakage.
     * 
     * Removes EXIF, IPTC, and other metadata from image files.
     * Supports JPEG, PNG, and WebP formats.
     * Uses ImageMagick if available, falls back to GD.
     * 
     * @param string $imagePath Path to the image file
     * @param array $options Stripping options (optional)
     *                        - 'preserve_orientation' => bool: Keep orientation data (default: false)
     *                        - 'quality' => int: JPEG quality for GD fallback (default: 90)
     * 
     * @return array Strip result with structure:
     *               - success: bool - Strip success status
     *               - error: string|null - Error message if any
     *               - data: array|null - Strip data if successful
     *                 Structure: [
     *                   'method' => string - Method used ('imagick' or 'gd'),
     *                   'file_path' => string - Path to processed file,
     *                   'reason' => string|null - Reason if skipped (optional)
     *                 ]
     */
    public static function stripExifMetadata($imagePath, $options = [])
    {
        $defaultOptions = [
            'preserve_orientation' => false,
            'quality' => 90
        ];

        $options = array_merge($defaultOptions, $options);

        if (!file_exists($imagePath)) {
            return [
                'success' => false,
                'error' => Fastlang::_e('file does not exist', $imagePath),
                'data' => null
            ];
        }

        $extension = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));

        // Only process supported image formats
        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'])) {
            return [
                'success' => true,
                'error' => null,
                'data' => [
                    'reason' => 'File type does not need metadata stripping',
                    'file_type' => $extension
                ]
            ];
        }

        // Try ImageMagick first (more reliable for metadata removal)
        if (extension_loaded('imagick')) {
            $result = self::_stripExifWithImageMagick($imagePath, $options);
            return [
                'success' => $result,
                'error' => $result ? null : 'Failed to strip EXIF with ImageMagick',
                'data' => [
                    'method' => 'imagick',
                    'file_path' => $imagePath
                ]
            ];
        }

        // Fallback to GD
        $result = self::_stripExifWithGD($imagePath, $options);
        return [
            'success' => $result,
            'error' => $result ? null : 'Failed to strip EXIF with GD',
            'data' => [
                'method' => 'gd',
                'file_path' => $imagePath
            ]
        ];
    }

    /**
     * Validate file content beyond MIME type checking.
     * 
     * Performs content-based validation for uploaded files.
     * Checks for malicious content, validates image dimensions,
     * and ensures file integrity.
     * 
     * @param string $filePath Path to the file to validate
     * @param string $mimeType MIME type of the file
     * 
     * @return array Validation result with structure:
     *               - valid: bool - Whether file content is valid
     *               - error: string|null - Error message if validation failed
     *               - data: array|null - Additional validation data
     */
    public static function validateFileContent($filePath, $mimeType)
    {
        if (!file_exists($filePath)) {
            return [
                'success' => false,
                'error' => Fastlang::_e('file does not exist', $filePath),
                'data' => null
            ];
        }

        $fileSize = filesize($filePath);
        if ($fileSize === false || $fileSize === 0) {
            return [
                'success' => false,
                'error' => Fastlang::_e('invalid file size'),
                'data' => null
            ];
        }

        // Check for common malicious patterns
        $content = file_get_contents($filePath, false, null, 0, 8192); // Read first 8KB
        if ($content === false) {
            return [
                'success' => false,
                'error' => Fastlang::_e('cannot read file content'),
                'data' => null
            ];
        }

        // Check for PHP code in non-PHP files
        if ($mimeType !== 'text/x-php' && $mimeType !== 'application/x-httpd-php') {
            if (strpos($content, '<?php') !== false || strpos($content, '<?=') !== false) {
                return [
                    'success' => false,
                    'error' => Fastlang::_e('php code detected in non php file'),
                    'data' => null
                ];
            }
        }

        // Validate SVG content
        if ($mimeType === 'image/svg+xml' || strpos($filePath, '.svg') !== false) {
            $svgContent = file_get_contents($filePath);
            if ($svgContent !== false) {
                // Check for dangerous SVG patterns
                $dangerousPatterns = [
                    '/<script/i',
                    '/javascript:/i',
                    '/on\w+\s*=/i',
                    '/<object/i',
                    '/<iframe/i'
                ];

                foreach ($dangerousPatterns as $pattern) {
                    if (preg_match($pattern, $svgContent)) {
                        return [
                            'success' => false,
                            'error' => Fastlang::_e('dangerous svg content detected'),
                            'data' => null
                        ];
                    }
                }
            }
        }

        // Validate image dimensions for image files
        if (strpos($mimeType, 'image/') === 0) {
            $imageInfo = getimagesize($filePath);
            if ($imageInfo === false) {
                return [
                    'success' => false,
                    'error' => Fastlang::_e('invalid image file'),
                    'data' => null
                ];
            }

            $width = $imageInfo[0];
            $height = $imageInfo[1];

            // Check for reasonable dimensions (prevent memory exhaustion attacks)
            $maxDimension = 10000; // 10,000 pixels
            if ($width > $maxDimension || $height > $maxDimension) {
                return [
                    'success' => false,
                    'error' => Fastlang::_e('image dimensions too large'),
                    'data' => [
                        'width' => $width,
                        'height' => $height,
                        'max_dimension' => $maxDimension
                    ]
                ];
            }

            // Check for zero dimensions
            if ($width <= 0 || $height <= 0) {
                return [
                    'success' => false,
                    'error' => Fastlang::_e('invalid image dimensions'),
                    'data' => [
                        'width' => $width,
                        'height' => $height
                    ]
                ];
            }
        }

        return [
            'success' => true,
            'error' => null,
            'data' => [
                'size' => $fileSize,
                'mime_type' => $mimeType
            ]
        ];
    }

    /**
     * Strip EXIF metadata using ImageMagick.
     * 
     * Uses ImageMagick to remove all metadata from image files.
     * More reliable than GD for metadata removal.
     * 
     * @param string $imagePath Path to the image file
     * @param array $options Stripping options
     * 
     * @return bool True if successful, false otherwise
     */
    private static function _stripExifWithImageMagick($imagePath, $options)
    {
        try {
            $imagick = new \Imagick($imagePath);

            // Strip all profiles (EXIF, IPTC, etc.)
            $imagick->stripImage();

            // Preserve orientation if requested
            if ($options['preserve_orientation']) {
                $orientation = $imagick->getImageOrientation();
                if ($orientation !== \Imagick::ORIENTATION_UNDEFINED) {
                    $imagick->setImageOrientation($orientation);
                }
            }

            // Write back to file
            $imagick->writeImage($imagePath);
            $imagick->destroy();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Strip EXIF metadata using GD.
     * 
     * Uses GD to remove metadata by re-encoding the image.
     * Less reliable than ImageMagick but works as fallback.
     * 
     * @param string $imagePath Path to the image file
     * @param array $options Stripping options
     * 
     * @return bool True if successful, false otherwise
     */
    private static function _stripExifWithGD($imagePath, $options)
    {
        try {
            $extension = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));

            // Load image based on type
            switch ($extension) {
                case 'jpg':
                case 'jpeg':
                    $image = imagecreatefromjpeg($imagePath);
                    break;
                case 'png':
                    $image = imagecreatefrompng($imagePath);
                    break;
                case 'webp':
                    $image = imagecreatefromwebp($imagePath);
                    break;
                default:
                    return false;
            }

            if ($image === false) {
                return false;
            }

            // Save image back (this removes metadata)
            $success = false;
            switch ($extension) {
                case 'jpg':
                case 'jpeg':
                    $success = imagejpeg($image, $imagePath, $options['quality']);
                    break;
                case 'png':
                    $success = imagepng($image, $imagePath);
                    break;
                case 'webp':
                    $success = imagewebp($image, $imagePath, $options['quality']);
                    break;
            }

            imagedestroy($image);
            return $success;
        } catch (\Exception $e) {
            return false;
        }
    }
}
