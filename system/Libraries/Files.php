<?php

namespace System\Libraries;

use App\Libraries\Fastlang;
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

        for ($i = 0; $i < $extensions; $i++) {
            $ext = pathinfo($path, PATHINFO_EXTENSION);
            if ($ext !== '') {
                $path = substr($path, 0, - (strlen($ext) + 1));
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
            $baseUpload = PATH_WRITE . 'uploads';
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
     * Upload a file or multiple files with automatic folder management and optional DB storage.
     * 
     * For images: automatically creates subfolder with sanitized filename (without extension) 
     * to contain the image and its variants (resizes, WebP, etc.).
     * For non-images: uploads directly to the specified folder without subfolder.
     * 
     * Applies security measures (SVG sanitization, EXIF stripping) and uses unique naming 
     * to prevent file overwrites when uploading duplicate filenames.
     * When overwrite=true: deletes existing files and replaces with new ones instead of generating unique names.
     * 
     * @param array $fileArr File array from $_FILES or single file array
     *                        Structure: ['name' => string, 'type' => string, 'tmp_name' => string, 
     *                                   'error' => int, 'size' => int]
     * @param array $options Upload options (optional)
     *                        - 'folder' => string: Subfolder path (default: '')
     *                        - 'resizes' => array: Image resize options
     *                        - 'watermark' => array: Watermark options
     *                        - 'webp' => bool: Convert to WebP
     *                        - 'overwrite' => bool: Overwrite existing files (default: false)
     * @param bool $save_db Whether to save file info to DB (default: true)
     * 
     * @return array Response array with structure:
     *               - success: bool - Upload success status
     *               - error: string|null - Error message if any
     *               - data: array|array[] - File info array or array of file info arrays
     *                 Structure: ['name' => string, 'path' => string, 'finalPath' => string, 'size' => int, 
     *                            'type' => string, 'folder' => string, 'base' => string,
     *                            'resize' => string, 'db' => array|null]
     */
    public static function upload(array $fileArr, array $options = [], $save_db = true)
    {
        return UploadManager::upload($fileArr, $options, $save_db);
    }

    /**
     * Handle chunk upload for large files with resume capability.
     * 
     * Processes individual chunks of a large file upload, allowing for resumable uploads
     * when network issues occur. Each chunk is validated and stored temporarily until
     * all chunks are received and can be assembled into the final file.
     * 
     * @param array $chunkInfo Chunk information
     *                        - 'uploadId' => string: Unique upload session identifier
     *                        - 'chunkNumber' => int: Current chunk number (0-based)
     *                        - 'totalChunks' => int: Total number of chunks expected
     *                        - 'fileName' => string: Original filename
     * @param array $fileArr File array from $_FILES containing the chunk data
     * @param array $options Upload options (optional)
     *                        - 'folder' => string: Target folder for final file
     *                        - 'max_size' => int: Maximum file size in bytes
     *                        - 'allowed_types' => array: Allowed file extensions
     *                        - 'overwrite' => bool: Overwrite existing files (default: false)
     * 
     * @return array Chunk upload result with structure:
     *               - success: bool - Chunk upload success status
     *               - error: string|null - Error message if any
     *               - data: array|null - Upload progress info if successful
     *                 Structure: ['uploaded_chunks' => int, 'total_chunks' => int, 
     *                            'is_complete' => bool, 'final_file' => array|null]
     */
    public static function uploadChunk($chunkInfo, $fileArr, $options = [])
    {
        return UploadManager::uploadChunk($chunkInfo, $fileArr, $options);
    }

    /**
     * Resume an interrupted chunk upload from where it left off.
     *
     * Checks the current upload progress and allows the client to continue
     * uploading from the next missing chunk. Useful for handling network
     * interruptions or browser crashes during large file uploads.
     *
     * @param string $uploadId Upload session identifier
     * @param array $options Upload options (optional)
     *                        - 'folder' => string: Target folder for final file
     *                        - 'max_size' => int: Maximum file size in bytes
     *                        - 'allowed_types' => array: Allowed file extensions
     *                        - 'overwrite' => bool: Overwrite existing files (default: false)
     * 
     * @return array Resume result with structure:
     *               - success: bool - Resume operation success status
     *               - error: string|null - Error message if any
     *               - data: array|null - Upload progress info if successful
     *                 Structure: ['uploaded_chunks' => int, 'total_chunks' => int, 
     *                            'missing_chunks' => array, 'can_resume' => bool]
     */
    public static function resumeUpload($uploadId, $options = [])
    {
        return UploadManager::resumeUpload($uploadId, $options);
    }

    // ========================================
    // PUBLIC METHODS - FILE VALIDATION (FACADE)
    // ========================================

    /**
     * Validate uploaded file with comprehensive security and format checks.
     * 
     * Performs multiple layers of validation including file size limits, MIME type
     * verification, content security scanning, and format-specific validation.
     * Applies security measures to prevent malicious file uploads.
     * 
     * @param array $fileArr File array from $_FILES or single file
     *                        Structure: ['name' => string, 'type' => string, 'tmp_name' => string, 
     *                                   'error' => int, 'size' => int]
     * @param array $options Validation options (optional)
     *                        - 'max_size' => int: Maximum file size in bytes
     *                        - 'allowed_types' => array: Allowed file extensions
     *                        - 'allowed_mimes' => array: Allowed MIME types
     *                        - 'check_content' => bool: Validate file content (default: true)
     *                        - 'scan_malware' => bool: Scan for malware patterns (default: false)
     * 
     * @return array Validation result with structure:
     *               - success: bool - Validation success status
     *               - error: string|null - Error message if validation failed
     *               - data: array|null - Validation details if successful
     *                 Structure: ['file_size' => int, 'mime_type' => string, 
     *                            'is_safe' => bool, 'warnings' => array]
     */
    public static function validateUploadedFile($fileArr, $options = [])
    {
        return UploadManager::validateUploadedFile($fileArr, $options);
    }

    /**
     * Validate a file name for security and compatibility.
     *
     * Checks that the filename contains only safe characters (alphanumeric,
     * dash, underscore, dot) and does not contain dangerous patterns that
     * could be used for path traversal or other security exploits.
     *
     * @param string $name File name to validate
     * @return bool True if filename is valid and safe, false otherwise
     */
    public static function isValidFileName($name)
    {
        return preg_match('/^[a-zA-Z0-9_\-\.]+$/', $name);
    }

    // ========================================
    // PUBLIC METHODS - FILE STORAGE (FACADE)
    // ========================================

    /**
     * Generate a unique file name in a directory to avoid overwriting existing files.
     *
     * Creates a unique filename by appending a number if the base name already exists.
     * Uses caching to improve performance for large file counts. Handles file extension
     * properly and maintains original base name while ensuring uniqueness.
     *
     * @param string $dir Directory path where file will be created
     * @param string $base Base file name (without extension)
     * @param string $ext File extension (e.g. 'jpg', 'png', 'pdf')
     * @param bool $useCache Whether to use cache for performance (default: true)
     * 
     * @return string Unique file name with extension
     */
    public static function getUniqueName($dir, $base, $ext, $useCache = true)
    {
        return FileStorage::getUniqueName($dir, $base, $ext, $useCache);
    }

    /**
     * Clear the unique name generation cache.
     *
     * Removes cached unique names to free memory or force regeneration.
     * Can clear cache for a specific directory or all directories.
     *
     * @param string|null $dir Optional directory to clear cache for specific directory only.
     *                         If null, clears cache for all directories.
     * @return void
     */
    public static function clearUniqueNameCache($dir = null)
    {
        FileStorage::clearUniqueNameCache($dir);
    }

    /**
     * Delete a file and all its variants (resizes, WebP, etc.).
     *
     * Removes the main file and all associated variants created during upload
     * or optimization. Handles both direct file paths and database file info arrays.
     * Includes safety checks to prevent accidental deletion of important files.
     *
     * @param string|array $path File path (relative/absolute) or file info array from DB
     *                           If array, must contain 'path' key with file path
     * 
     * @return array Delete result with structure:
     *               - success: bool - Delete operation success status
     *               - error: string|null - Error message if any
     *               - data: array|null - Deletion details if successful
     *                 Structure: ['deleted_files' => array, 'deleted_variants' => array, 
     *                            'total_size_freed' => int]
     */
    public static function deleteFile($path)
    {
        return FileStorage::deleteFile($path);
    }

    /**
     * Recursively delete all files and subfolders in the parent folder of the specified file.
     *
     * Deletes the entire parent directory containing the specified file, including
     * all files and subdirectories within it. Use with caution as this is a destructive
     * operation that cannot be undone.
     *
     * @param string $filePath File path (relative or absolute) whose parent folder will be deleted
     * 
     * @return array Delete result with structure:
     *               - success: bool - Delete operation success status
     *               - error: string|null - Error message if any
     *               - data: array|null - Deletion details if successful
     *                 Structure: ['deleted_folder' => string, 'deleted_files' => int, 
     *                            'total_size_freed' => int]
     */
    public static function deleteWithParentFolder($filePath)
    {
        return FileStorage::deleteWithParentFolder($filePath);
    }

    /**
     * Recursively delete all files and subfolders in a directory, then delete the directory itself.
     *
     * Completely removes a directory and all its contents. Performs safety checks
     * to prevent deletion of system directories or important files. Use with extreme
     * caution as this operation is irreversible.
     *
     * @param string $dir Absolute or relative directory path to delete
     * 
     * @return array Delete result with structure:
     *               - success: bool - Delete operation success status
     *               - error: string|null - Error message if any
     *               - data: array|null - Deletion details if successful
     *                 Structure: ['deleted_directory' => string, 'deleted_files' => int, 
     *                            'deleted_folders' => int, 'total_size_freed' => int]
     */
    public static function deleteFolderRecursive($dir)
    {
        return FileStorage::deleteFolderRecursive($dir);
    }

    // ========================================
    // PUBLIC METHODS - FILE VARIANTS (FACADE)
    // ========================================

    /**
     * Get all file variants (original, WebP, resizes, etc) for a file info array.
     *
     * Generates a comprehensive list of all file variants based on file info.
     * Includes original file, WebP version, and all resized variants.
     * Handles both database file info and direct file paths.
     *
     * @param array $fileInfo File info array (with keys 'path', 'type', 'resize')
     *                        Structure: ['path' => string, 'type' => string, 'resize' => string, 'base' => string]
     * 
     * @return array List of all variant file paths
     */
    public static function getVariants($fileInfo)
    {
        return VariantManager::getAllVariants($fileInfo);
    }

    /**
     * Get the best variant for a specific size and format.
     *
     * Selects the most appropriate file variant based on size requirements
     * and format preferences. Prioritizes exact matches, then closest sizes,
     * and finally format conversions if needed.
     *
     * @param array $fileInfo File info array (with keys 'path', 'type', 'resize')
     * @param string $size Size specification (e.g. '300x200', 'original')
     * @param string|null $format Format preference (optional) - 'webp', 'jpg', 'png', etc.
     * 
     * @return string|null Best variant path or null if not found
     */
    public static function getBestVariant($fileInfo, $size, $format = null)
    {
        return VariantManager::getBestVariant($fileInfo, $size, $format);
    }

    /**
     * Delete all variants of a file.
     *
     * Removes all generated variants (resizes, WebP, etc.) while keeping
     * the original file intact. Useful for cleanup when file variants
     * are no longer needed.
     *
     * @param array $fileInfo File info array (with keys 'path', 'type', 'resize')
     * 
     * @return array Delete result with structure:
     *               - success: bool - Delete operation success status
     *               - error: string|null - Error message if any
     *               - data: array|null - Deletion details if successful
     *                 Structure: ['deleted_variants' => array, 'total_size_freed' => int]
     */
    public static function deleteAllVariants($fileInfo)
    {
        return VariantManager::deleteAllVariants($fileInfo);
    }

    /**
     * Optimize an image with resize, watermark, and format conversion options.
     *
     * Processes an image file to create optimized versions including resizes,
     * WebP conversion, and watermark application. Maintains original file
     * while creating optimized variants for different use cases.
     *
     * @param string $filePath Path to the original image file
     * @param array $options Optimization options
     *                        - 'resizes' => array: Resize configurations [['width' => 300, 'height' => 200]]
     *                        - 'webp' => bool|array: Convert to WebP format
     *                        - 'watermark' => array: Watermark settings
     *                        - 'quality' => int: Image quality (1-100, default: 90)
     * 
     * @return array Optimization result with structure:
     *               - success: bool - Optimization success status
     *               - error: string|null - Error message if any
     *               - data: array|null - Optimization details if successful
     *                 Structure: ['resizes' => array, 'webp' => array, 'watermark' => array]
     */
    public static function optimize($filePath, array $options = [])
    {
        return ImageOptimizer::optimize($filePath, $options);
    }

    /**
     * Crop an image to specific dimensions.
     *
     * Crops an image to the specified dimensions starting from the given coordinates.
     * Creates a new cropped image file while preserving the original.
     *
     * @param string $filePath Path to the original image file
     * @param array $options Crop options
     *                        - 'x' => int: X coordinate for crop start (0-based)
     *                        - 'y' => int: Y coordinate for crop start (0-based)
     *                        - 'width' => int: Crop width in pixels
     *                        - 'height' => int: Crop height in pixels
     *                        - 'save_as' => string: Output filename (optional)
     *                        - 'quality' => int: Image quality (1-100, default: 90)
     * 
     * @return array Crop result with structure:
     *               - success: bool - Crop operation success status
     *               - error: string|null - Error message if any
     *               - data: array|null - Crop details if successful
     *                 Structure: ['cropped_file' => string, 'original_size' => array, 
     *                            'cropped_size' => array]
     */
    public static function crop($filePath, array $options = [])
    {
        return ImageOptimizer::crop($filePath, $options);
    }

    /**
     * Crop an image by aspect ratio (e.g., 16:9, 4:3, 1:1).
     *
     * Crops an image to maintain a specific aspect ratio while preserving
     * the most important part of the image based on the specified position.
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
     *               - success: bool - Crop operation success status
     *               - error: string|null - Error message if any
     *               - data: array|null - Crop details if successful
     *                 Structure: ['cropped_file' => string, 'aspect_ratio' => string, 
     *                            'crop_position' => string]
     */
    public static function cropByRatio($filePath, array $options = [])
    {
        return ImageOptimizer::cropByRatio($filePath, $options);
    }

    /**
     * Generate output path for processed image variant.
     *
     * Creates the appropriate file path for a processed image variant
     * based on the source file, size specification, and optional format.
     *
     * @param string $sourceFile Path to the source image file
     * @param string $size Size specification (e.g. '300x200', 'original')
     * @param string|null $format Format preference (optional) - 'webp', 'jpg', etc.
     * 
     * @return string Generated output path for the processed image
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
     * Removes dangerous elements and attributes from SVG content while
     * preserving the visual appearance. Blocks script tags, object tags,
     * event handlers, and javascript: URLs.
     * 
     * @param string $svgContent Raw SVG content to sanitize
     * @param array $options Sanitization options (optional)
     *                        - 'remove_scripts' => bool: Remove script elements (default: true)
     *                        - 'remove_objects' => bool: Remove object elements (default: true)
     *                        - 'remove_events' => bool: Remove event handlers (default: true)
     *                        - 'remove_javascript' => bool: Remove javascript: URLs (default: true)
     * 
     * @return string Sanitized SVG content safe for display
     */
    public static function sanitizeSvg($svgContent, $options = [])
    {
        return SecurityManager::sanitizeSvg($svgContent, $options);
    }

    /**
     * Strip EXIF metadata from images to prevent information leakage.
     * 
     * Removes EXIF metadata from image files to protect user privacy and
     * prevent information leakage. Supports JPEG, PNG, and WebP formats.
     * 
     * @param string $imagePath Path to the image file
     * @param array $options Stripping options (optional)
     *                        - 'backup_original' => bool: Create backup before stripping (default: false)
     *                        - 'preserve_color_profile' => bool: Keep color profile data (default: true)
     * 
     * @return bool True if metadata was stripped successfully, false otherwise
     */
    public static function stripExifMetadata($imagePath, $options = [])
    {
        return SecurityManager::stripExifMetadata($imagePath, $options);
    }

    /**
     * Validate file content beyond MIME type checking.
     * 
     * Performs deep content validation to ensure file integrity and safety.
     * Checks file headers, content structure, and potential security threats
     * beyond basic MIME type verification.
     * 
     * @param string $filePath Path to the file to validate
     * @param string $mimeType MIME type of the file
     * 
     * @return array Validation result with structure:
     *               - success: bool - Validation success status
     *               - error: string|null - Error message if validation failed
     *               - data: array|null - Validation details if successful
     *                 Structure: ['file_type' => string, 'is_safe' => bool, 'warnings' => array]
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
     * Returns comprehensive progress information for a chunk upload session,
     * including uploaded chunks, missing chunks, and completion status.
     * 
     * @param string $uploadId Unique upload session identifier
     * 
     * @return array Progress information with structure:
     *               - success: bool - Operation success status
     *               - error: string|null - Error message if any
     *               - data: array|null - Progress data if successful
     *                 Structure: ['uploaded_count' => int, 'total_chunks' => int, 
     *                            'missing_chunks' => array, 'is_complete' => bool, 
     *                            'last_activity' => string]
     */
    public static function getChunkUploadProgress($uploadId)
    {
        return ChunkManager::getUploadProgress($uploadId);
    }

    /**
     * Resume upload from specific chunk number.
     *
     * Allows resuming a chunk upload from a specific chunk number,
     * useful when some chunks were successfully uploaded but others failed.
     *
     * @param string $uploadId Upload session identifier
     * @param int $startChunk Starting chunk number (0-based)
     * 
     * @return array Resume result with structure:
     *               - success: bool - Resume operation success status
     *               - error: string|null - Error message if any
     *               - data: array|null - Resume details if successful
     *                 Structure: ['resume_from' => int, 'total_chunks' => int, 
     *                            'can_resume' => bool]
     */
    public static function resumeFromChunk($uploadId, $startChunk)
    {
        return ChunkManager::resumeFromChunk($uploadId, $startChunk);
    }

    /**
     * Clean up expired chunk upload sessions.
     *
     * Removes chunk upload sessions that have exceeded the maximum age,
     * freeing up disk space and preventing accumulation of temporary files.
     *
     * @param int $maxAge Maximum age in hours (default: 24)
     * 
     * @return array Cleanup result with structure:
     *               - success: bool - Cleanup operation success status
     *               - error: string|null - Error message if any
     *               - data: array|null - Cleanup details if successful
     *                 Structure: ['cleaned_sessions' => int, 'freed_space' => int, 
     *                            'errors' => array]
     */
    public static function cleanupExpiredChunkSessions($maxAge = 24)
    {
        return ChunkManager::cleanupExpiredSessions($maxAge);
    }

    /**
     * Get list of active chunk upload sessions.
     *
     * Returns information about all currently active chunk upload sessions,
     * including progress, file details, and session metadata.
     *
     * @return array List of active sessions with structure:
     *               - success: bool - Operation success status
     *               - error: string|null - Error message if any
     *               - data: array|null - Session list if successful
     *                 Structure: ['sessions' => array, 'total_count' => int]
     */
    public static function getActiveChunkSessions()
    {
        return ChunkManager::getActiveSessions();
    }

    /**
     * Delete a specific chunk upload session.
     *
     * Removes a specific chunk upload session and all its associated
     * temporary files. Use this to clean up individual sessions.
     *
     * @param string $uploadId Upload session identifier
     * 
     * @return bool True if session was deleted successfully, false otherwise
     */
    public static function deleteChunkSession($uploadId)
    {
        return ChunkManager::deleteSession($uploadId);
    }

    // ========================================
    // PUBLIC METHODS - DATABASE OPERATIONS (FACADE)
    // ========================================

    /**
     * Save file info to the database using FilesModel.
     *
     * Stores file metadata in the database for tracking and management.
     * Creates a database record with file information including path,
     * size, type, and other metadata.
     *
     * @param array $fileInfo File info array
     *                        Structure: ['name' => string, 'path' => string, 'size' => int, 
     *                                   'type' => string, 'folder' => string]
     * @param object|null $model FilesModel instance (optional)
     *                          If null, uses default FilesModel
     * 
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
     * Converts PHP file upload error codes into user-friendly error messages
     * for better user experience and debugging.
     *
     * @param int $error_code PHP file upload error code (UPLOAD_ERR_* constants)
     * 
     * @return string Human-readable error message
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
     * Determines if file array contains multiple files by checking
     * if the 'name' key is an array.
     *
     * @param array $fileArr File array from $_FILES
     *                        Structure: ['name' => string|array, 'type' => string|array, 
     *                                   'tmp_name' => string|array, 'error' => int|array, 
     *                                   'size' => int|array]
     * 
     * @return bool True if multiple files, false if single file
     */
    public static function isMultiple($fileArr)
    {
        return UploadManager::isMultiple($fileArr);
    }

    /**
     * Sanitize a folder path to allow only safe characters and prevent path traversal.
     *
     * Removes dangerous characters and path traversal attempts from folder paths.
     * Preserves date-based paths (YYYY/MM/DD) while sanitizing other paths.
     *
     * @param string $folder Folder path to sanitize
     * 
     * @return string Sanitized folder path safe for use
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
     * Removes dangerous characters, normalizes path separators, and ensures
     * the path is safe for file system operations.
     *
     * @param string $path Path to clean and normalize
     * 
     * @return string Cleaned and normalized path
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
     * Converts a string into a URL-friendly slug by removing accents,
     * converting to lowercase, and keeping only alphanumeric characters,
     * underscores, and hyphens.
     *
     * @param string $str String to convert to slug
     * 
     * @return string URL-friendly slug
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
     * Creates a unique base name by appending a number if the name already exists.
     * Sanitizes the filename and removes existing size suffixes before generating
     * the unique name.
     *
     * @param string $dir Directory path where file will be created
     * @param string $rawName Raw file name to make unique
     * 
     * @return array Array containing [baseName, ext] where baseName is unique
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
     * Rename a file and all its variants (resizes, WebP, etc.).
     *
     * Renames the main file and all associated variants while maintaining
     * the file structure. Creates new folders if needed and cleans up empty folders.
     *
     * @param array $file File info array from DB (must have 'name', 'path', 'type', 'resize')
     * @param string $newName New base name (without extension)
     * 
     * @return array Rename result with structure:
     *               - success: bool - Rename operation success status
     *               - error: string|null - Error message if any
     *               - data: array|null - Rename details if successful
     *                 Structure: ['name' => string, 'path' => string, 
     *                            'renamed_variants' => array, 'errors' => array]
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
            $baseUpload = PATH_WRITE . 'uploads';
            $fullPathAbs = rtrim($baseUpload, '/\\') . DIRECTORY_SEPARATOR . ltrim($fullPath, '/\\');
        }

        // Fix path separator for Windows
        $fullPathAbs = str_replace('/', DIRECTORY_SEPARATOR, $fullPathAbs);
        $newBase = $newName;
        $newFullPath = str_replace($baseName, $newBase, $fullPathAbs);

        // Fix path separator for new path too
        $newFullPath = str_replace('/', DIRECTORY_SEPARATOR, $newFullPath);

        // Check if file actually exists
        if (!file_exists($fullPathAbs)) {
            return [
                'success' => false,
                'error' => 'Source file does not exist: ' . $fullPathAbs,
                'data' => null
            ];
        }

        if (file_exists($newFullPath)) {
            return [
                'success' => false,
                'error' => 'Target file already exists: ' . $newFullPath,
                'data' => null
            ];
        }

        if (file_exists($fullPathAbs) && !file_exists($newFullPath)) {
            // Get all variants of the file
            $variants = VariantManager::getAllVariants($file);
            $renamedFiles = [];
            $errors = [];

            // Create new folder if it doesn't exist
            $newFolderPath = dirname($newFullPath);
            if (!is_dir($newFolderPath)) {
                if (!mkdir($newFolderPath, 0777, true)) {
                    return [
                        'success' => false,
                        'error' => 'Failed to create new folder',
                        'data' => null
                    ];
                }
            }

            // Rename main file FIRST
            if (rename($fullPathAbs, $newFullPath)) {

                // Now rename all variants
                foreach ($variants as $variantPath) {
                    $variantAbs = $variantPath;
                    if (!self::isAbsolutePath($variantAbs)) {
                        $variantAbs = rtrim($baseUpload, '/\\') . DIRECTORY_SEPARATOR . ltrim($variantPath, '/\\');
                    }

                    // Fix path separator for variant
                    $variantAbs = str_replace('/', DIRECTORY_SEPARATOR, $variantAbs);

                    $newVariantPath = str_replace($baseName, $newBase, $variantAbs);
                    $newVariantPath = str_replace('/', DIRECTORY_SEPARATOR, $newVariantPath);

                    if (file_exists($variantAbs)) {
                        if (rename($variantAbs, $newVariantPath)) {
                            $renamedFiles[] = $variantPath;
                        } else {
                            $errors[] = "Failed to rename variant: {$variantPath}";
                        }
                    }
                }
                $newNameWithExt = $newBase . '.' . $ext;
                $newPath = str_replace($baseName, $newBase, $file['path']);

                // Rename folder if it's named after the file
                $oldFolderPath = dirname($fullPathAbs);
                $newSanitizedBaseName = self::sanitizeFileName($newBase);
                $newFolderPath = dirname($oldFolderPath) . DIRECTORY_SEPARATOR . $newSanitizedBaseName;

                // Check if folder name matches file base name (with sanitization)
                $sanitizedBaseName = self::sanitizeFileName($baseName);
                $folderBasename = basename($oldFolderPath);

                // Skip folder rename - just clean up old folder if empty
                // Note: We don't rename folder because it may contain files

                // Clean up old folder if it's empty and different from new folder
                if (is_dir($oldFolderPath) && $oldFolderPath !== $newFolderPath) {
                    $files = array_diff(scandir($oldFolderPath), ['.', '..']);
                    if (empty($files)) {
                        rmdir($oldFolderPath);
                    }
                }

                return [
                    'success' => true,
                    'error' => null,
                    'data' => [
                        'name' => $newNameWithExt,
                        'path' => $newPath,
                        'renamed_variants' => $renamedFiles,
                        'errors' => $errors
                    ]
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to rename file',
                    'data' => null
                ];
            }
        } else {
            return [
                'success' => false,
                'error' => 'Cannot rename: file not found or target exists',
                'data' => null
            ];
        }
    }

    /**
     * Build the final file name for a variant (original or resized).
     *
     * Constructs the appropriate filename for a file variant based on
     * the base name, size specification, and file extension.
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
     * Get order by clause for database queries.
     *
     * Converts sort parameters into SQL ORDER BY clauses for database queries.
     * Supports various sorting options including date, name, and size sorting.
     *
     * @param string $sort Sort parameter
     *                    Supported values: 'created_at_asc', 'created_at_desc',
     *                    'updated_at_asc', 'updated_at_desc', 'name', 'name_az',
     *                    'name_za', 'size_asc', 'size_desc', etc.
     * 
     * @return string SQL ORDER BY clause
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
     * Downloads a file from a remote URL, validates its type and size,
     * saves it to the specified target folder, and optionally processes
     * image optimization (resize, watermark, format conversion).
     * 
     * @param string $url Remote URL to download file from
     * @param string $targetFolder Relative folder path from PATH_ROOT
     * @param array $options Download and processing options
     *                        - 'allowed_types' => array: Allowed file extensions (default: ['jpg', 'jpeg', 'png', 'gif', 'webp'])
     *                        - 'allowed_mimes' => array: Allowed MIME types
     *                        - 'max_size' => int: Maximum file size in bytes (default: 10MB)
     *                        - 'custom_name' => string: Custom filename
     *                        - 'overwrite' => bool: Overwrite existing files (default: false)
     *                        - 'sizes' => array: Image resize configurations
     *                        - 'type' => string: Convert to specific format
     *                        - 'quality' => int: Image quality (1-100, default: 90)
     * 
     * @return array Download result with structure:
     *               - success: bool - Download success status
     *               - error: string|null - Error message if any
     *               - data: array|null - Array of created file info if successful
     *                 Structure: ['name' => string, 'path' => string, 'size' => int, 
     *                            'type' => string, 'folder' => string]
     */
    public static function downloadFromUrl($url, $targetFolder, $options = [])
    {
        return UploadManager::downloadFromUrl($url, $targetFolder, $options);
    }

    // ========================================
    // PUBLIC METHODS - ZIP FILE HANDLING
    // ========================================

    /**
     * Extract ZIP file to target directory with validation and security checks.
     * 
     * Extracts a ZIP file with comprehensive security measures including path traversal
     * protection, file count limits, and size restrictions. Automatically detects and
     * skips single root folder in ZIP files for cleaner extraction.
     * 
     * @param string $zipPath Path to the ZIP file
     * @param string $targetDir Target directory for extraction
     * @param array $options Extraction options
     *                        - 'overwrite' => bool: Overwrite existing files (default: false)
     *                        - 'validate_structure' => bool: Validate ZIP structure (default: true)
     *                        - 'required_files' => array: Required files in ZIP (default: [])
     *                        - 'max_files' => int: Maximum number of files to extract (default: 1000)
     *                        - 'max_size' => int: Maximum total extraction size (default: 100MB)
     * 
     * @return array Extraction result with structure:
     *               - success: bool - Extraction success status
     *               - error: string|null - Error message if any
     *               - data: array|null - Extraction info if successful
     *                 Structure: ['extracted_files' => array, 'extracted_size' => int, 
     *                            'target_dir' => string, 'file_count' => int]
     */
    public static function extractZip($zipPath, $targetDir, $options = [])
    {
        // Get default options from config
        $config = config('files');
        $zipConfig = $config['zip'] ?? [];
        $defaultOptions = [
            'overwrite' => $zipConfig['overwrite'] ?? false,
            'validate_structure' => $zipConfig['validate_structure'] ?? true,
            'required_files' => $zipConfig['required_files'] ?? [],
            'max_files' => $zipConfig['max_files'] ?? 1000,
            'max_size' => $zipConfig['max_size'] ?? 100 * 1024 * 1024, // 100MB
        ];
        $options = array_merge($defaultOptions, $options);

        try {
            // Validate ZIP file
            if (!file_exists($zipPath)) {
                return [
                    'success' => false,
                    'error' => 'ZIP file not found',
                    'data' => null
                ];
            }

            $zip = new \ZipArchive();
            $result = $zip->open($zipPath, \ZipArchive::CHECKCONS);

            if ($result !== TRUE) {
                return [
                    'success' => false,
                    'error' => 'Cannot open ZIP file: ' . self::getZipErrorMessage($result),
                    'data' => null
                ];
            }

            // Validate ZIP structure
            if ($options['validate_structure']) {
                $validation = self::validateZipStructure($zip, $options);
                if (!$validation['success']) {
                    $zip->close();
                    return $validation;
                }
            }

            // Create target directory if not exists
            if (!is_dir($targetDir)) {
                if (!mkdir($targetDir, 0755, true)) {
                    $zip->close();
                    return [
                        'success' => false,
                        'error' => 'Cannot create target directory',
                        'data' => null
                    ];
                }
            }

            // Detect if we need to skip the root folder (when ZIP has single root folder)
            $firstEntry = $zip->getNameIndex(0);
            $skipRootFolder = false;
            $rootFolderName = '';

            if ($firstEntry) {
                // Check if first entry is a directory
                if (substr($firstEntry, -1) === '/') {
                    $rootFolderName = rtrim($firstEntry, '/');
                    $skipRootFolder = true;
                }
            }

            // Extract files
            $extractedFiles = [];
            $extractedSize = 0;
            $fileCount = 0;

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $fileCount++;
                if ($fileCount > $options['max_files']) {
                    $zip->close();
                    return [
                        'success' => false,
                        'error' => 'Too many files in ZIP (max: ' . $options['max_files'] . ')',
                        'data' => null
                    ];
                }

                $fileInfo = $zip->statIndex($i);
                $fileName = $fileInfo['name'];
                $fileSize = $fileInfo['size'];

                // Skip directories
                if (substr($fileName, -1) === '/') {
                    continue;
                }

                // Check total size
                $extractedSize += $fileSize;
                if ($extractedSize > $options['max_size']) {
                    $zip->close();
                    return [
                        'success' => false,
                        'error' => 'ZIP extraction size too large (max: ' . ($options['max_size'] / 1024 / 1024) . 'MB)',
                        'data' => null
                    ];
                }

                // Security: Check for path traversal
                if (strpos($fileName, '../') !== false || strpos($fileName, '..\\') !== false) {
                    $zip->close();
                    return [
                        'success' => false,
                        'error' => 'Path traversal detected in ZIP file',
                        'data' => null
                    ];
                }

                // Skip root folder if needed
                if ($skipRootFolder && strpos($fileName, $rootFolderName . '/') === 0) {
                    $fileName = substr($fileName, strlen($rootFolderName) + 1);
                }

                // Extract file
                $targetPath = $targetDir . DIRECTORY_SEPARATOR . $fileName;
                $targetDirPath = dirname($targetPath);

                // Create subdirectory if needed
                if (!is_dir($targetDirPath)) {
                    if (!mkdir($targetDirPath, 0755, true)) {
                        $zip->close();
                        return [
                            'success' => false,
                            'error' => 'Cannot create subdirectory: ' . $targetDirPath,
                            'data' => null
                        ];
                    }
                }

                // Check if file exists and overwrite option
                if (file_exists($targetPath) && !$options['overwrite']) {
                    continue;
                }

                // Extract file
                $content = $zip->getFromIndex($i);
                if ($content === false) {
                    $zip->close();
                    return [
                        'success' => false,
                        'error' => 'Cannot extract file: ' . $fileName,
                        'data' => null
                    ];
                }

                // Write file
                if (file_put_contents($targetPath, $content) === false) {
                    $zip->close();
                    return [
                        'success' => false,
                        'error' => 'Cannot write file: ' . $targetPath,
                        'data' => null
                    ];
                }

                $extractedFiles[] = $targetPath;
            }

            $zip->close();

            return [
                'success' => true,
                'error' => null,
                'data' => [
                    'extracted_files' => $extractedFiles,
                    'extracted_size' => $extractedSize,
                    'target_dir' => $targetDir,
                    'file_count' => count($extractedFiles)
                ]
            ];
        } catch (\Exception $e) {
            if (isset($zip)) {
                $zip->close();
            }
            return [
                'success' => false,
                'error' => 'ZIP extraction failed: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Validate ZIP file structure and security.
     * 
     * Performs security and structure validation on a ZIP file before extraction.
     * Checks for required files, validates ZIP integrity, and ensures safe extraction.
     * 
     * @param \ZipArchive $zip ZipArchive instance
     * @param array $options Validation options
     *                        - 'required_files' => array: Required files in ZIP
     *                        - 'max_files' => int: Maximum number of files allowed
     *                        - 'max_size' => int: Maximum total size allowed
     * 
     * @return array Validation result with structure:
     *               - success: bool - Validation success status
     *               - error: string|null - Error message if validation failed
     *               - data: array|null - Validation details if successful
     */
    private static function validateZipStructure($zip, $options = [])
    {
        // Check if ZIP is empty
        if ($zip->numFiles === 0) {
            return [
                'success' => false,
                'error' => 'ZIP file is empty',
                'data' => null
            ];
        }

        // Check required files
        if (!empty($options['required_files'])) {
            $zipFiles = [];
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $zipFiles[] = $zip->getNameIndex($i);
            }

            foreach ($options['required_files'] as $requiredFile) {
                $found = false;
                foreach ($zipFiles as $zipFile) {
                    if (strpos($zipFile, $requiredFile) !== false) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    return [
                        'success' => false,
                        'error' => 'Required file not found: ' . $requiredFile,
                        'data' => null
                    ];
                }
            }
        }

        return [
            'success' => true,
            'error' => null,
            'data' => null
        ];
    }

    /**
     * Get human-readable ZIP error message.
     * 
     * Converts ZipArchive error codes into user-friendly error messages
     * for better debugging and user experience.
     * 
     * @param int $errorCode ZipArchive error code
     * 
     * @return string Human-readable error message
     */
    private static function getZipErrorMessage($errorCode)
    {
        switch ($errorCode) {
            case \ZipArchive::ER_OK:
                return 'No error';
            case \ZipArchive::ER_MULTIDISK:
                return 'Multi-disk zip archives not supported';
            case \ZipArchive::ER_RENAME:
                return 'Renaming temporary file failed';
            case \ZipArchive::ER_CLOSE:
                return 'Closing zip archive failed';
            case \ZipArchive::ER_SEEK:
                return 'Seek error';
            case \ZipArchive::ER_READ:
                return 'Read error';
            case \ZipArchive::ER_WRITE:
                return 'Write error';
            case \ZipArchive::ER_CRC:
                return 'CRC error';
            case \ZipArchive::ER_ZIPCLOSED:
                return 'Containing zip archive was closed';
            case \ZipArchive::ER_NOENT:
                return 'No such file';
            case \ZipArchive::ER_EXISTS:
                return 'File already exists';
            case \ZipArchive::ER_OPEN:
                return 'Can\'t open file';
            case \ZipArchive::ER_TMPOPEN:
                return 'Failure to create temporary file';
            case \ZipArchive::ER_ZLIB:
                return 'Zlib error';
            case \ZipArchive::ER_MEMORY:
                return 'Memory allocation failure';
            case \ZipArchive::ER_CHANGED:
                return 'Entry has been changed';
            case \ZipArchive::ER_COMPNOTSUPP:
                return 'Compression method not supported';
            case \ZipArchive::ER_EOF:
                return 'Premature EOF';
            case \ZipArchive::ER_INVAL:
                return 'Invalid argument';
            case \ZipArchive::ER_NOZIP:
                return 'Not a zip archive';
            case \ZipArchive::ER_INTERNAL:
                return 'Internal error';
            case \ZipArchive::ER_INCONS:
                return 'Zip archive inconsistent';
            case \ZipArchive::ER_REMOVE:
                return 'Can\'t remove file';
            case \ZipArchive::ER_DELETED:
                return 'Entry has been deleted';
            default:
                return 'Unknown error (' . $errorCode . ')';
        }
    }

    /**
     * Extract theme/plugin ZIP file with specific validation.
     * 
     * Extracts ZIP files with additional validation for themes and plugins.
     * Validates config file existence and structure after extraction.
     * Cleans up extracted files if validation fails.
     * 
     * @param string $zipPath Path to the ZIP file
     * @param string $targetDir Target directory for extraction
     * @param string $type Type of item ('theme' or 'plugin')
     * @param array $options Extraction options
     *                        - 'overwrite' => bool: Overwrite existing files
     *                        - 'required_files' => array: Required files in ZIP
     *                        - 'max_files' => int: Maximum number of files to extract
     *                        - 'max_size' => int: Maximum total extraction size
     * 
     * @return array Extraction result with validation status
     *               - success: bool - Extraction and validation success status
     *               - error: string|null - Error message if any
     *               - data: array|null - Extraction info if successful
     */
    public static function extractThemePlugin($zipPath, $targetDir, $type, $options = [])
    {
        // Get default options from config
        $config = config('files');
        $zipConfig = $config['zip'] ?? [];
        $typeConfig = $zipConfig[$type] ?? [];

        $defaultOptions = [
            'overwrite' => $zipConfig['overwrite'] ?? false,
            'required_files' => $typeConfig['required_files'] ?? ['Config/Config.php'],
            'max_files' => $zipConfig['max_files'] ?? 1000,
            'max_size' => $typeConfig['max_size'] ?? $zipConfig['max_size'] ?? 50 * 1024 * 1024, // 50MB
        ];
        $options = array_merge($defaultOptions, $options);

        // Extract ZIP with duplicate folder fix
        $result = self::extractZip($zipPath, $targetDir, $options);

        if (!$result['success']) {
            return $result;
        }

        // Additional validation for theme/plugin
        $configPath = $targetDir . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Config.php';

        if (!file_exists($configPath)) {
            // Clean up extracted files
            self::deleteFolderRecursive($targetDir);
            return [
                'success' => false,
                'error' => ucfirst($type) . ' is invalid or missing config file',
                'data' => null
            ];
        }

        // Validate config file content
        $configContent = file_get_contents($configPath);
        if (empty($configContent) || strpos($configContent, '<?php') !== 0) {
            self::deleteFolderRecursive($targetDir);
            return [
                'success' => false,
                'error' => 'Invalid config file format',
                'data' => null
            ];
        }

        // Validate config structure
        $config = include $configPath;
        if (!is_array($config) || !isset($config[$type])) {
            self::deleteFolderRecursive($targetDir);
            return [
                'success' => false,
                'error' => 'Invalid ' . $type . ' configuration',
                'data' => null
            ];
        }

        return $result;
    }






    /**
     * Process and normalize upload configuration from various formats.
     * 
     * Handles different config formats and converts them to standardized format
     * for the Upload library. Supports legacy formats and new object-based configs.
     * Normalizes watermark file paths and ensures proper formatting.
     * 
     * Supported formats:
     * - resizes: ["200x300"] or [{"width": "200", "height": "300"}]
     * - output: {"jpg": {"q": 80}, "webp": {"q": 80}}
     * - watermark: {"file": "path/to/watermark.png", "position": "bottom-right"}
     * 
     * @param array $config Raw configuration array
     * 
     * @return array Normalized configuration array with standardized format
     */
    public static function processUploadConfig($config)
    {
        $normalizedConfig = $config;

        // Process output configuration - extract webp and jpg settings
        if (!empty($config['output']) && is_array($config['output'])) {
            // Extract webp configuration from output
            if (!empty($config['output']['webp']) && is_array($config['output']['webp'])) {
                $normalizedConfig['webp'] = $config['output']['webp'];
            }

            // Extract jpg configuration from output (for quality)
            if (!empty($config['output']['jpg']) && is_array($config['output']['jpg'])) {
                $normalizedConfig['jpg_quality'] = $config['output']['jpg']['q'] ?? 90;
            }
        }

        // Process resizes format - handle both string array and object array
        if (!empty($config['resizes']) && is_array($config['resizes'])) {
            $resizes = [];
            foreach ($config['resizes'] as $size) {
                if (is_string($size)) {
                    // Handle string format: "200x300"
                    if (preg_match('/(\d+)x(\d+)/', $size, $matches)) {
                        $resizes[] = [
                            'width' => (int)$matches[1],
                            'height' => (int)$matches[2]
                        ];
                    }
                } elseif (is_array($size) && isset($size['width']) && isset($size['height'])) {
                    // Handle object format: {"width": "200", "height": "300"}
                    $resizes[] = [
                        'width' => (int)$size['width'],
                        'height' => (int)$size['height']
                    ];
                }
            }
            $normalizedConfig['resizes'] = $resizes;
        }

        // Process watermark configuration
        if (!empty($config['watermark']) && is_array($config['watermark'])) {
            // Ensure watermark file path is properly formatted
            if (!empty($config['watermark']['file'])) {
                $watermarkPath = $config['watermark']['file'];
                // If it's a relative path, make sure it's properly formatted
                if (!self::isAbsolutePath($watermarkPath)) {
                    $normalizedConfig['watermark']['file'] = ltrim($watermarkPath, '/');
                }
            }
        }

        return $normalizedConfig;
    }

    /**
     * Get the root folder name from a ZIP file without extracting.
     * 
     * Inspects a ZIP file to determine the root folder name without performing
     * full extraction. Useful for validation and folder structure analysis.
     * 
     * @param string $zipPath Path to the ZIP file
     * 
     * @return array Result with structure:
     *               - success: bool - Operation success status
     *               - error: string|null - Error message if any
     *               - data: array|null - Data if successful
     *                 Structure: ['folder_name' => string, 'is_valid' => bool]
     */
    public static function getZipRootFolder($zipPath)
    {
        try {
            if (!file_exists($zipPath)) {
                return [
                    'success' => false,
                    'error' => 'ZIP file not found',
                    'data' => null
                ];
            }

            $zip = new \ZipArchive();
            $result = $zip->open($zipPath, \ZipArchive::CHECKCONS);

            if ($result !== TRUE) {
                return [
                    'success' => false,
                    'error' => 'Cannot open ZIP file: ' . self::getZipErrorMessage($result),
                    'data' => null
                ];
            }

            // Get first entry to determine root folder
            $firstEntry = $zip->getNameIndex(0);
            if (!$firstEntry) {
                $zip->close();
                return [
                    'success' => false,
                    'error' => 'ZIP file is empty',
                    'data' => null
                ];
            }

            $folderName = explode('/', $firstEntry)[0];
            $isValid = !empty($folderName) && $folderName !== '.';

            $zip->close();

            return [
                'success' => true,
                'error' => null,
                'data' => [
                    'folder_name' => $folderName,
                    'is_valid' => $isValid
                ]
            ];
        } catch (\Exception $e) {
            if (isset($zip)) {
                $zip->close();
            }
            return [
                'success' => false,
                'error' => 'Failed to read ZIP file: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
}
