<?php

namespace System\Libraries;

use App\Libraries\Fastlang;
use System\Drivers\Image\ImageManager;
use System\Libraries\Upload\FileStorage;
use System\Libraries\Upload\UploadManager;
use System\Libraries\Upload\VariantManager;
use System\Libraries\Upload\SecurityManager;
use System\Libraries\Upload\ChunkManager;
use System\Libraries\Upload\ImageOptimizer;

/**
 * Files - Main file handling library with facade pattern
 * 
 * Main features:
 * - File upload management with security and validation
 * - Image optimization and processing
 * - File storage and variant management
 * - Chunk upload with resume capability
 * - Security measures (SVG sanitization, EXIF stripping)
 * - Path utilities and file system operations
 * - Database integration for file metadata
 * 
 * This class acts as a facade for the specialized Upload classes:
 * - UploadManager: Handles file uploads
 * - ImageOptimizer: Processes image optimization
 * - FileStorage: Manages file storage operations
 * - VariantManager: Handles file variants
 * - SecurityManager: Applies security measures
 * - ChunkManager: Manages chunk uploads
 * 
 * @package System\Libraries
 * @since 1.0.0
 */
class Files
{
    // ========================================
    // PUBLIC METHODS - PATH UTILITIES
    // ========================================

    /**
     * Get the full absolute path from a base directory and a relative path.
     *
     * @param string $baseDir Base directory (absolute path)
     * @param string $path Relative path to combine with base directory
     * @return string Full absolute path combining base directory and relative path
     */
    public static function getFullPath($baseDir, $path)
    {
        return rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
    }

    /**
     * Remove the extension from a file path.
     *
     * @param string $filePath File path to process
     * @param int $extensions Number of extensions to remove (default: 1)
     * @return string File path without the specified number of extensions
     */
    public static function removeExtension($filePath, $extensions = 1)
    {
        if ($extensions <= 0) {
            return $filePath;
        }

        $path = $filePath;
        $removed = 0;

        for ($i = 0; $i < $extensions; $i++) {
            $ext = pathinfo($path, PATHINFO_EXTENSION);
            if ($ext !== '') {
                $path = substr($path, 0, - (strlen($ext) + 1));
                $removed++;
            } else {
                break;
            }
        }

        if (substr($path, -1) === '.') {
            $originalExt = pathinfo($filePath, PATHINFO_EXTENSION);
            if ($originalExt === '') {
                $path = rtrim($path, '.');
            }
        }
        return $path;
    }

    /**
     * Sanitize a file name (remove dangerous characters, normalize spaces, etc).
     *
     * @param string $name File name
     * @return string Sanitized file name
     */
    public static function sanitizeFileName($name)
    {
        if (!function_exists('remove_accents')) {
            require_once PATH_SYS . 'Helpers/String_helper.php';
        }
        $name = remove_accents($name);
        $name = preg_replace('/\.php\./i', '.', $name);
        $name = preg_replace('/\.php$/i', '.', $name);
        $name = preg_replace('/[^.\p{L}\p{N}\s\-_]+/u', '_', $name);
        $name = preg_replace('/\s+/', '_', $name);
        $name = preg_replace('/_+/', '_', $name);
        $name = trim($name, '_');
        return $name;
    }

    /**
     * Check if a path is absolute (Windows, Unix, or UNC style).
     *
     * @param string $path
     * @return bool
     */
    public static function isAbsolutePath($path)
    {
        if (empty($path)) {
            return false;
        }

        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

        if (preg_match('#^[a-zA-Z]:' . preg_quote(DIRECTORY_SEPARATOR, '#') . '#', $path)) {
            return true;
        }

        if (strpos($path, DIRECTORY_SEPARATOR) === 0) {
            return true;
        }

        if (preg_match('#^' . preg_quote(DIRECTORY_SEPARATOR, '#') . preg_quote(DIRECTORY_SEPARATOR, '#') . '[^' . preg_quote(DIRECTORY_SEPARATOR, '#') . ']+' . preg_quote(DIRECTORY_SEPARATOR, '#') . '[^' . preg_quote(DIRECTORY_SEPARATOR, '#') . ']+#', $path)) {
            return true;
        }

        return false;
    }

    /**
     * Resolve and normalize a file path, handling both absolute and relative paths safely.
     *
     * @param string $path File path (absolute or relative)
     * @param string|null $basePath Base path for relative paths (default: uploads directory)
     * @return string Normalized absolute path
     */
    public static function resolvePath($path, $basePath = null)
    {
        if (empty($path)) {
            return '';
        }

        if (self::isAbsolutePath($path)) {
            return realpath($path) ?: $path;
        }

        if ($basePath === null) {
            $baseUpload = config('files')['path'] ?? 'writeable/uploads';
            $basePath = rtrim(PATH_ROOT, '/\\') . DIRECTORY_SEPARATOR . trim($baseUpload, '/\\');
        }

        $normalizedPath = ltrim($path, '/\\');
        $fullPath = rtrim($basePath, '/\\') . DIRECTORY_SEPARATOR . $normalizedPath;

        $realPath = realpath($fullPath);
        return $realPath ?: $fullPath;
    }

    // ========================================
    // PUBLIC METHODS - FILE UPLOAD (FACADE)
    // ========================================

    /**
     * Upload a file or multiple files, create a subfolder for each file, and optionally save to DB.
     * 
     * @param array $fileArr File array from $_FILES or single file array
     * @param array $options Upload options (optional)
     * @param bool $save_db Whether to save file info to DB (default: true)
     * @return array Response array with structure: ['success' => bool, 'error' => string|null, 'data' => array|array[]]
     */
    public static function upload(array $fileArr, array $options = [], $save_db = true)
    {
        return UploadManager::upload($fileArr, $options, $save_db);
    }

    /**
     * Handle chunk upload for large files with resume capability.
     * 
     * @param array $chunkInfo Chunk information
     * @param array $fileArr File array from $_FILES
     * @param array $options Upload options (optional)
     * @return array Chunk upload result
     */
    public static function uploadChunk($chunkInfo, $fileArr, $options = [])
    {
        return UploadManager::uploadChunk($chunkInfo, $fileArr, $options);
    }

    /**
     * Resume interrupted upload
     *
     * @param string $uploadId Upload identifier
     * @param array $options Upload options
     * @return array
     */
    public static function resumeUpload($uploadId, $options = [])
    {
        return UploadManager::resumeUpload($uploadId, $options);
    }

    // ========================================
    // PUBLIC METHODS - FILE VALIDATION (FACADE)
    // ========================================

    /**
     * Validate uploaded file (size, type, MIME, content security).
     * 
     * @param array $fileArr File array from $_FILES or single file
     * @param array $options Validation options (optional)
     * @return array Validation result
     */
    public static function validateUploadedFile($fileArr, $options = [])
    {
        return UploadManager::validateUploadedFile($fileArr, $options);
    }

    /**
     * Validate a file name (alphanumeric, dash, underscore, dot allowed).
     *
     * @param string $name File name
     * @return bool
     */
    public static function isValidFileName($name)
    {
        return preg_match('/^[a-zA-Z0-9_\-\.]+$/', $name);
    }

    // ========================================
    // PUBLIC METHODS - FILE STORAGE (FACADE)
    // ========================================

    /**
     * Generate a unique file name in a directory (avoid overwriting existing files).
     *
     * @param string $dir Directory path
     * @param string $base Base file name (without extension)
     * @param string $ext File extension
     * @param bool $useCache Whether to use cache (default: true)
     * @return string Unique file name
     */
    public static function getUniqueName($dir, $base, $ext, $useCache = true)
    {
        return FileStorage::getUniqueName($dir, $base, $ext, $useCache);
    }

    /**
     * Clear the unique name cache.
     *
     * @param string|null $dir Optional directory to clear cache for specific directory only
     * @return void
     */
    public static function clearUniqueNameCache($dir = null)
    {
        FileStorage::clearUniqueNameCache($dir);
    }

    /**
     * Delete a file and all its variants (resize, webp, ...).
     *
     * @param string|array $path File path (relative/absolute) or file info array from DB
     * @return array ['success'=>bool, 'error'=>string|null, 'data'=>mixed]
     */
    public static function deleteFile($path)
    {
        return FileStorage::deleteFile($path);
    }

    /**
     * Recursively delete all files and subfolders inside the parent folder of the specified file, then delete the parent folder itself.
     *
     * @param string $filePath File path (relative or absolute) whose parent folder will be deleted
     * @return array Result array: success, error, data
     */
    public static function deleteWithParentFolder($filePath)
    {
        return FileStorage::deleteWithParentFolder($filePath);
    }

    /**
     * Recursively delete all files and subfolders in a directory, then delete the directory itself.
     *
     * @param string $dir Absolute or relative directory path
     * @return array Result array: success, error, data
     */
    public static function deleteFolderRecursive($dir)
    {
        return FileStorage::deleteFolderRecursive($dir);
    }

    // ========================================
    // PUBLIC METHODS - FILE VARIANTS (FACADE)
    // ========================================

    /**
     * Get all file variants (original, webp, resizes, etc) for a file info array.
     *
     * @param array $fileInfo File info array (with keys 'path', 'type', 'resize')
     * @return array List of file paths
     */
    public static function getVariants($fileInfo)
    {
        return VariantManager::getAllVariants($fileInfo);
    }

    /**
     * Get the best variant for a specific size and format.
     *
     * @param array $fileInfo File info array
     * @param string $size Size specification (e.g. '300x200')
     * @param string|null $format Format preference (optional)
     * @return string|null Best variant path or null if not found
     */
    public static function getBestVariant($fileInfo, $size, $format = null)
    {
        return VariantManager::getBestVariant($fileInfo, $size, $format);
    }

    /**
     * Delete all variants of a file.
     *
     * @param array $fileInfo File info array
     * @return array Delete result
     */
    public static function deleteAllVariants($fileInfo)
    {
        return VariantManager::deleteAllVariants($fileInfo);
    }

    /**
     * Optimize an image: resize, watermark, convert to webp, etc.
     *
     * @param string $filePath Path to the original image
     * @param array $options Options: 'resizes', 'webp', 'watermark', ...
     * @return array Result info: resizes, watermark, webp, error
     */
    public static function optimize($filePath, array $options = [])
    {
        return ImageOptimizer::optimize($filePath, $options);
    }

    /**
     * Crop an image to specific dimensions.
     *
     * @param string $filePath Path to the original image
     * @param array $options Crop options
     *                        - 'x' => int: X coordinate for crop start
     *                        - 'y' => int: Y coordinate for crop start
     *                        - 'width' => int: Crop width
     *                        - 'height' => int: Crop height
     *                        - 'save_as' => string: Output filename (optional)
     *                        - 'quality' => int: Image quality (1-100, default: 90)
     * @return array Result info: success, error, data
     */
    public static function crop($filePath, array $options = [])
    {
        return ImageOptimizer::crop($filePath, $options);
    }

    /**
     * Crop an image by ratio (e.g., 16:9, 4:3, 1:1).
     *
     * @param string $filePath Path to the original image
     * @param array $options Crop options
     *                        - 'ratio_width' => int: Ratio width (e.g., 16 for 16:9)
     *                        - 'ratio_height' => int: Ratio height (e.g., 9 for 16:9)
     *                        - 'position' => string: Crop position ('center', 'top-left', 'top-right', 'bottom-left', 'bottom-right')
     *                        - 'save_as' => string: Output filename (optional)
     *                        - 'quality' => int: Image quality (1-100, default: 90)
     * @return array Result info: success, error, data
     */
    public static function cropByRatio($filePath, array $options = [])
    {
        return ImageOptimizer::cropByRatio($filePath, $options);
    }

    /**
     * Generate output path for processed image.
     *
     * @param string $sourceFile
     * @param string $size
     * @param string|null $format
     * @return string
     */
    public static function getOutputPath($sourceFile, $size, $format = null)
    {
        return ImageOptimizer::getOutputPath($sourceFile, $size, $format);
    }

    // ========================================
    // PUBLIC METHODS - SECURITY (FACADE)
    // ========================================

    /**
     * Sanitize SVG content to prevent XSS attacks.
     * 
     * @param string $svgContent Raw SVG content to sanitize
     * @param array $options Sanitization options (optional)
     * @return string Sanitized SVG content
     */
    public static function sanitizeSvg($svgContent, $options = [])
    {
        return SecurityManager::sanitizeSvg($svgContent, $options);
    }

    /**
     * Strip EXIF metadata from images to prevent information leakage.
     * 
     * @param string $imagePath Path to the image file
     * @param array $options Stripping options (optional)
     * @return bool True if metadata was stripped successfully, false otherwise
     */
    public static function stripExifMetadata($imagePath, $options = [])
    {
        return SecurityManager::stripExifMetadata($imagePath, $options);
    }

    /**
     * Validate file content beyond MIME type checking.
     * 
     * @param string $filePath Path to the file to validate
     * @param string $mimeType MIME type of the file
     * @return array Validation result
     */
    public static function validateFileContent($filePath, $mimeType)
    {
        return SecurityManager::validateFileContent($filePath, $mimeType);
    }

    // ========================================
    // PUBLIC METHODS - CHUNK UPLOAD MANAGEMENT (FACADE)
    // ========================================

    /**
     * Get detailed upload progress for a chunk upload session.
     * 
     * @param string $uploadId Unique upload session identifier
     * @return array Progress information
     */
    public static function getChunkUploadProgress($uploadId)
    {
        return ChunkManager::getUploadProgress($uploadId);
    }

    /**
     * Resume upload from specific chunk
     *
     * @param string $uploadId Upload identifier
     * @param int $startChunk Starting chunk number
     * @return array ['success' => bool, 'error' => string|null, 'data' => array]
     */
    public static function resumeFromChunk($uploadId, $startChunk)
    {
        return ChunkManager::resumeFromChunk($uploadId, $startChunk);
    }

    /**
     * Clean up expired upload sessions
     *
     * @param int $maxAge Maximum age in hours (default: 24)
     * @return array ['cleaned_sessions' => int, 'errors' => array]
     */
    public static function cleanupExpiredChunkSessions($maxAge = 24)
    {
        return ChunkManager::cleanupExpiredSessions($maxAge);
    }

    /**
     * Get list of active upload sessions
     *
     * @return array List of active sessions with metadata
     */
    public static function getActiveChunkSessions()
    {
        return ChunkManager::getActiveSessions();
    }

    /**
     * Delete a specific upload session
     *
     * @param string $uploadId Upload identifier
     * @return bool Success status
     */
    public static function deleteChunkSession($uploadId)
    {
        return ChunkManager::deleteSession($uploadId);
    }

    // ========================================
    // PUBLIC METHODS - DATABASE OPERATIONS (FACADE)
    // ========================================

    /**
     * Save file info to the database (using FilesModel).
     *
     * @param array $fileInfo File info array
     * @param object|null $model FilesModel instance (optional)
     * @return array|false DB record (with id) or false on failure
     */
    public static function saveToDb($fileInfo, $model = null)
    {
        return UploadManager::saveToDb($fileInfo, $model);
    }

    // ========================================
    // PUBLIC METHODS - UTILITY FUNCTIONS
    // ========================================

    /**
     * Get a human-readable error message for a file upload error code.
     *
     * @param int $error_code PHP file upload error code
     * @return string Error message
     */
    public static function getErrorMessage($error_code)
    {
        switch ($error_code) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return Fastlang::_e('file exceeds the allowed size');
            case UPLOAD_ERR_PARTIAL:
                return Fastlang::_e('file was only partially uploaded');
            case UPLOAD_ERR_NO_FILE:
                return Fastlang::_e('no file was uploaded');
            case UPLOAD_ERR_NO_TMP_DIR:
                return Fastlang::_e('missing temporary folder');
            case UPLOAD_ERR_CANT_WRITE:
                return Fastlang::_e('failed to write file to disk');
            case UPLOAD_ERR_EXTENSION:
                return Fastlang::_e('file upload stopped by a PHP extension');
            default:
                return Fastlang::_e('unknown upload error');
        }
    }

    /**
     * Check if a file array is a multiple upload (from $_FILES).
     *
     * @param array $fileArr
     * @return bool
     */
    public static function isMultiple($fileArr)
    {
        return UploadManager::isMultiple($fileArr);
    }

    /**
     * Sanitize a folder path to allow only safe characters and prevent path traversal.
     *
     * @param string $folder Folder path
     * @return string Sanitized folder path
     */
    public static function sanitizeFolderPath($folder)
    {
        // For date-based paths, preserve directory structure
        if (preg_match('/^\d{4}[:\/]\d{2}[:\/]\d{2}$/', $folder)) {
            // Convert : to / for consistency
            $folder = str_replace(':', '/', $folder);
            return $folder;
        }
        
        // For other paths, apply sanitization
        $folder = str_replace(['..', './', '\\', '//'], '', $folder);
        $folder = preg_replace('/[^a-zA-Z0-9_\/-]+/', '', $folder);
        $folder = trim($folder, '/-');
        return $folder;
    }

    /**
     * Clean and normalize a path (remove dangerous characters, normalize separators).
     *
     * @param string $path
     * @return string
     */
    public static function cleanPath($path)
    {
        $path = preg_replace('/\.php\./i', '.', $path);
        $path = preg_replace('/\.php$/i', '.', $path);
        $path = preg_replace('/(\.+:)/', '_', $path);
        $path = preg_replace('/(\.+)/', '.', $path);
        $path = preg_replace('/[^.\p{L}\p{N}\s\-_:\/]+/u', '_', $path);
        $path = preg_replace('/\s+/', '_', $path);
        $path = preg_replace('/_+/', '_', $path);
        $path = trim($path, '_');
        return $path;
    }

    /**
     * Convert a string to a slug (lowercase, remove accents, only a-z0-9_-).
     *
     * @param string $str
     * @return string
     */
    public static function toSlug($str)
    {
        $str = mb_strtolower($str, 'UTF-8');
        $str = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str);
        $str = preg_replace('/[^a-z0-9_-]/', '', $str);
        return $str;
    }

    /**
     * Generate a unique base name for a file in a folder (avoid collisions).
     *
     * @param string $dir Directory path
     * @param string $rawName Raw file name
     * @return array [baseName, ext]
     */
    public static function uniqueBaseName($dir, $rawName)
    {
        $rawName = self::sanitizeFileName($rawName);
        $info = pathinfo($rawName);
        $base = $info['filename'];
        $ext = strtolower($info['extension'] ?? 'jpg');
        $base = preg_replace('/_\d+x\d+$/', '', $base);
        $candidate = $base;
        $i = 1;
        while (file_exists($dir . DIRECTORY_SEPARATOR . $candidate . '.' . $ext)) {
            $candidate = $base . '_' . $i++;
        }
        return [$candidate, $ext];
    }

    /**
     * Rename a file and all its variants (resize, webp, ...).
     *
     * @param array $file File info array from DB (must have 'name', 'path', 'type', 'resize')
     * @param string $newName New base name (without extension)
     * @return array ['success'=>bool, 'error'=>string|null, 'data'=>array]
     */
    public static function rename($file, $newName)
    {
        if (empty($file['path']) || empty($file['name']) || empty($file['type'])) {
            return [
                'success' => false,
                'error' => 'Invalid file info',
                'data' => null
            ];
        }

        $baseName = self::removeExtension($file['name']);
        $fullPath = $file['path'];
        $ext = $file['type'];
        $dir = dirname($fullPath);
        $fullPathAbs = $fullPath;
        if (!self::isAbsolutePath($fullPathAbs)) {
            $baseUpload = config('files')['path'] ?? 'writeable/uploads';
            $fullPathAbs = rtrim(PATH_ROOT, '/\\') . '/' . trim($baseUpload, '/\\') . '/' . ltrim($fullPath, '/\\');
        }
        $newBase = $newName;
        $newFullPath = str_replace($baseName, $newBase, $fullPathAbs);

        if (file_exists($fullPathAbs) && !file_exists($newFullPath)) {
            if (rename($fullPathAbs, $newFullPath)) {
                $newNameWithExt = $newBase . '.' . $ext;
                $newPath = str_replace($baseName, $newBase, $file['path']);
                return [
                    'success' => true,
                    'error' => null,
                    'data' => [
                        'name' => $newNameWithExt,
                        'path' => $newPath
                    ]
                ];
            }
        }

        return [
            'success' => false,
            'error' => 'Failed to rename file',
            'data' => null
        ];
    }

    /**
     * Build the final file name for a variant (original or resized).
     *
     * @param string $base Base file name (without extension)
     * @param string $sizeKey Size key (e.g. 'original', '300x200')
     * @param string $ext File extension
     * @return string
     */
    public static function buildFileName($base, $sizeKey, $ext)
    {
        return $sizeKey !== 'original'
            ? "{$base}_{$sizeKey}.{$ext}"
            : "{$base}.{$ext}";
    }

    /**
     * Get order by clause for database queries.
     *
     * @param string $sort Sort parameter
     * @return string Order by clause
     */
    public static function getOrderBy($sort)
    {
        switch ($sort) {
            case 'created_at_asc':
                return 'created_at ASC';
            case 'created_at_desc':
                return 'created_at DESC';
            case 'updated_at_asc':
                return 'updated_at ASC';
            case 'updated_at_desc':
                return 'updated_at DESC';
            case 'name':
            case 'name_az':
                return 'name ASC';
            case 'name_za':
                return 'name DESC';
            case 'size_asc':
            case 'size_az':
                return 'size ASC';
            case 'size_desc':
            case 'size_za':
                return 'size DESC';
            default:
                return 'created_at DESC';
        }
    }

    /**
     * Download a file from a URL, validate, and save to the target folder.
     *
     * @param string $url
     * @param string $targetFolder Relative folder path from PATH_ROOT
     * @param array $options ['allowed_types'=>[], 'allowed_mimes'=>[], 'max_size'=>int, 'custom_name'=>string, 'overwrite'=>bool, 'sizes'=>array, 'type'=>string, 'quality'=>int]
     * @return array ['success'=>bool, 'error'=>string|null, 'data'=>array]
     */
    /**
     * Download a file from a URL, validate, and save to the target folder.
     * 
     * Downloads a file from a remote URL, validates its type and size,
     * saves it to the specified target folder, and optionally processes
     * image optimization (resize, watermark, format conversion).
     * 
     * @param string $url Remote URL to download file from
     * @param string $targetFolder Relative folder path from PATH_ROOT
     * @param array $options Download and processing options
     *                        - 'allowed_types' => array: Allowed file extensions
     *                        - 'allowed_mimes' => array: Allowed MIME types
     *                        - 'max_size' => int: Maximum file size in bytes
     *                        - 'custom_name' => string: Custom filename
     *                        - 'overwrite' => bool: Overwrite existing files
     *                        - 'sizes' => array: Image resize configurations
     *                        - 'type' => string: Convert to specific format
     *                        - 'quality' => int: Image quality (1-100)
     * 
     * @return array Download result with structure:
     *               - success: bool - Download success status
     *               - error: string|null - Error message if any
     *               - data: array|null - Array of created file info if successful
     */
    public static function downloadFromUrl($url, $targetFolder, $options = [])
    {
        return UploadManager::downloadFromUrl($url, $targetFolder, $options);
    }
}
