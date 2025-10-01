<?php

namespace System\Libraries\Upload;

use System\Libraries\Files;

/**
 * VariantManager - Handle file variants management (resizes, WebP, etc.)
 * 
 * Main features:
 * - Manage file variants (original, resized, WebP versions)
 * - Generate variant paths and URLs
 * - Handle variant deletion and cleanup
 * - Support for multiple image formats and sizes
 * - Database integration for variant tracking
 * 
 * @package System\Libraries\Upload
 * @since 1.0.0
 */
class VariantManager
{
    /**
     * Get all variants of a file (original, resizes, webp, etc).
     * 
     * Generates a comprehensive list of all file variants based on file info.
     * Includes original file, WebP version, and all resized variants.
     * Handles both database file info and direct file paths.
     * 
     * @param array|string $fileInfo File info array from DB or file path string
     *                                Structure: ['path' => string, 'type' => string, 'resize' => string, 'base' => string]
     *                                OR string: Direct file path
     * 
     * @return array List of all variant file paths
     */
    public static function getAllVariants($fileInfo)
    {
        if (is_string($fileInfo)) {
            // Direct file path - scan folder for actual variants
            $path = $fileInfo;
            $dir = dirname($path);
            $filename = basename($path);
            $baseName = pathinfo($filename, PATHINFO_FILENAME);
            $extension = pathinfo($filename, PATHINFO_EXTENSION);

            $variants = [];

            // Check if original file exists
            if (file_exists($path)) {
                $variants[] = $path;
            }

            // Check if WebP version of original exists
            $webpPath = $path . '.webp';
            if (file_exists($webpPath)) {
                $variants[] = $webpPath;
            }

            // Scan directory for all variants with the same base name
            if (is_dir($dir)) {
                $files = scandir($dir);
                foreach ($files as $file) {
                    if ($file === '.' || $file === '..') {
                        continue;
                    }

                    $filePath = $dir . '/' . $file;

                    // Check if this is a variant of our base file
                    // Pattern: baseName_anything.extension or baseName_anything.extension.webp
                    if (preg_match('/^' . preg_quote($baseName, '/') . '_.*\.' . preg_quote($extension, '/') . '(\.webp)?$/', $file)) {
                        $variants[] = $filePath;
                    }
                }
            }

            return $variants;
        } else {
            // File info array from database
            if (!is_array($fileInfo) || !isset($fileInfo['path'])) {
                return [];
            }

            $path = $fileInfo['path'];
            $type = $fileInfo['type'] ?? 'jpg';

            // Use the same logic as string path to scan folder for actual variants
            $dir = dirname($path);
            $filename = basename($path);
            $baseName = pathinfo($filename, PATHINFO_FILENAME);
            $extension = pathinfo($filename, PATHINFO_EXTENSION);

            $variants = [];

            // Check if original file exists
            if (file_exists($path)) {
                $variants[] = $path;
            }

            // Check if WebP version of original exists
            $webpPath = $path . '.webp';
            if (file_exists($webpPath)) {
                $variants[] = $webpPath;
            }

            // Scan directory for all variants with the same base name
            if (is_dir($dir)) {
                $files = scandir($dir);
                foreach ($files as $file) {
                    if ($file === '.' || $file === '..') {
                        continue;
                    }

                    $filePath = $dir . '/' . $file;

                    // Check if this is a variant of our base file
                    // Pattern: baseName_anything.extension or baseName_anything.extension.webp
                    if (preg_match('/^' . preg_quote($baseName, '/') . '_.*\.' . preg_quote($extension, '/') . '(\.webp)?$/', $file)) {
                        $variants[] = $filePath;
                    }
                }
            }

            return $variants;
        }
    }

    /**
     * Remove file extension from path
     * 
     * @param string $filePath File path
     * @return string Path without extension
     */
    private static function removeExtension($filePath)
    {
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);
        if ($ext !== '') {
            return substr($filePath, 0, - (strlen($ext) + 1));
        }
        return $filePath;
    }

    /**
     * Get the best variant for a specific size requirement.
     * 
     * Finds the most appropriate file variant based on size requirements.
     * Prioritizes exact matches, then closest larger variants, then smaller variants.
     * Falls back to original if no suitable variant found.
     * 
     * @param array $fileInfo File info array from database
     *                        Structure: ['path' => string, 'type' => string, 'resize' => string, 'base' => string]
     * @param string $size Required size (e.g. '300x200', 'original')
     * @param string|null $format Preferred format (e.g. 'webp', 'jpg', null for original)
     * 
     * @return string|null Best matching variant path or null if not found
     */
    public static function getBestVariant($fileInfo, $size, $format = null)
    {
        $variants = self::getAllVariants($fileInfo);

        if ($size === 'original') {
            // Return original file or WebP if requested
            if ($format === 'webp') {
                foreach ($variants as $variant) {
                    if (strpos($variant, '.webp') !== false && strpos($variant, '_') === false) {
                        return $variant;
                    }
                }
            } else {
                // Return original (non-WebP)
                foreach ($variants as $variant) {
                    if (strpos($variant, '.webp') === false && strpos($variant, '_') === false) {
                        return $variant;
                    }
                }
            }
            return null;
        }

        // Look for exact size match
        $exactMatch = null;
        $closestLarger = null;
        $closestSmaller = null;

        foreach ($variants as $variant) {
            if (strpos($variant, '_' . $size . '.') !== false) {
                if ($format === 'webp' && strpos($variant, '.webp') !== false) {
                    return $variant;
                } elseif ($format !== 'webp' && strpos($variant, '.webp') === false) {
                    return $variant;
                }
                $exactMatch = $variant;
            }
        }

        if ($exactMatch) {
            return $exactMatch;
        }

        // Parse size to find closest match
        if (preg_match('/(\d+)x(\d+)/', $size, $matches)) {
            $targetWidth = (int)$matches[1];
            $targetHeight = (int)$matches[2];

            foreach ($variants as $variant) {
                if (preg_match('/_(\d+)x(\d+)\./', $variant, $matches)) {
                    $variantWidth = (int)$matches[1];
                    $variantHeight = (int)$matches[2];

                    if ($variantWidth >= $targetWidth && $variantHeight >= $targetHeight) {
                        if (
                            !$closestLarger ||
                            ($variantWidth < $closestLarger['width'] || $variantHeight < $closestLarger['height'])
                        ) {
                            $closestLarger = [
                                'path' => $variant,
                                'width' => $variantWidth,
                                'height' => $variantHeight
                            ];
                        }
                    } else {
                        if (
                            !$closestSmaller ||
                            ($variantWidth > $closestSmaller['width'] || $variantHeight > $closestSmaller['height'])
                        ) {
                            $closestSmaller = [
                                'path' => $variant,
                                'width' => $variantWidth,
                                'height' => $variantHeight
                            ];
                        }
                    }
                }
            }
        }

        // Return closest larger, then smaller, then original
        if ($closestLarger) {
            return $closestLarger['path'];
        }
        if ($closestSmaller) {
            return $closestSmaller['path'];
        }

        // Fallback to original
        if (is_array($fileInfo)) {
            return $fileInfo['path'];
        } else {
            return $fileInfo;
        }
    }

    /**
     * Delete all variants of a file.
     * 
     * Removes all file variants including original, resized versions, and WebP versions.
     * Uses FileStorage::deleteFile() for secure deletion with validation.
     * 
     * @param array $fileInfo File info array from database
     *                        Structure: ['path' => string, 'type' => string, 'resize' => string, 'base' => string]
     * 
     * @return array Delete result with structure:
     *               - success: bool - Delete success status
     *               - error: string|null - Error message if any
     *               - data: mixed - Additional data (usually null)
     */
    public static function deleteAllVariants($fileInfo)
    {
        return FileStorage::deleteFile($fileInfo);
    }

    /**
     * Get variant URL for web display.
     * 
     * Converts file path to web-accessible URL for variant files.
     * Handles both relative and absolute paths.
     * 
     * @param string $filePath File path (relative or absolute)
     * @param string $baseUrl Base URL for file serving (optional)
     * 
     * @return string Web-accessible URL for the file variant
     */
    public static function getVariantUrl($filePath, $baseUrl = null)
    {
        if (empty($baseUrl)) {
            $baseUrl = config('app')['base_url'] ?? '';
        }

        // Remove PATH_ROOT from file path if present
        $relativePath = str_replace(PATH_ROOT, '', $filePath);
        $relativePath = ltrim($relativePath, '/');

        return rtrim($baseUrl, '/') . '/' . $relativePath;
    }

    /**
     * Check if a variant exists on disk.
     * 
     * Verifies that a specific file variant actually exists in the file system.
     * Resolves relative paths to absolute paths for checking.
     * 
     * @param string $filePath File path to check (relative or absolute)
     * 
     * @return bool True if variant exists, false otherwise
     */
    public static function variantExists($filePath)
    {
        $absolutePath = Files::resolvePath($filePath);
        return file_exists($absolutePath);
    }

    /**
     * Get file size of a variant.
     * 
     * Returns the file size in bytes for a specific variant.
     * Returns null if file doesn't exist.
     * 
     * @param string $filePath File path (relative or absolute)
     * 
     * @return int|null File size in bytes or null if file doesn't exist
     */
    public static function getVariantSize($filePath)
    {
        $absolutePath = Files::resolvePath($filePath);
        return file_exists($absolutePath) ? filesize($absolutePath) : null;
    }

    /**
     * Get MIME type of a variant.
     * 
     * Determines the MIME type of a file variant.
     * Uses file extension and content analysis.
     * 
     * @param string $filePath File path (relative or absolute)
     * 
     * @return string|null MIME type or null if file doesn't exist
     */
    public static function getVariantMimeType($filePath)
    {
        $absolutePath = Files::resolvePath($filePath);

        if (!file_exists($absolutePath)) {
            return null;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $absolutePath);
        finfo_close($finfo);

        return $mimeType;
    }
}
