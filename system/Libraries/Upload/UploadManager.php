<?php

namespace System\Libraries\Upload;

use App\Libraries\Fastlang;
use System\Libraries\Files;
use System\Drivers\Image\ImageManager;
use System\Libraries\Logger;
use System\Libraries\Upload\SecurityManager;
use System\Libraries\Upload\ChunkManager;
use System\Libraries\Upload\VariantManager;

/**
 * UploadManager - Handle file uploads with validation, security, and chunk support
 * 
 * Main features:
 * - Upload single/multiple files with comprehensive validation
 * - Chunk upload for large files with resume capability
 * - Security measures (SVG sanitization, EXIF stripping)
 * - Database integration with FilesModel
 * - Progress tracking and error handling
 * 
 * @package System\Libraries\Upload
 * @since 1.0.0
 */
class UploadManager
{
    /**
     * Upload a file or multiple files, create a subfolder for each image, and optionally save to DB.
     * 
     * Supports single file or multiple files upload from $_FILES array.
     * For images: automatically creates subfolder with sanitized filename (without extension) to contain the image and its variants.
     * For non-images: uploads directly to the specified folder without subfolder.
     * Applies security measures (SVG sanitization, EXIF stripping).
     * Uses unique naming to prevent file overwrites when uploading duplicate filenames.
     * When overwrite=true: deletes existing files and replaces with new ones using timestamp-based naming to avoid cache issues.
     * 
     * @param array $fileArr File array from $_FILES or single file array
     *                        Structure: ['name' => string, 'type' => string, 'tmp_name' => string, 
     *                                   'error' => int, 'size' => int]
     * @param array $options Upload options (optional)
     *                        - 'folder' => string: Subfolder path (default: '')
     *                        - 'resizes' => array: Image resize options
     *                        - 'watermark' => array: Watermark options
     *                        - 'webp' => bool: Convert to WebP
     *                        - 'overwrite' => bool: Overwrite existing files with timestamp naming (default: false)
     * @param bool $save_db Whether to save file info to DB (default: true)
     * 
     * @return array Response array with structure:
     *               - success: bool - Upload success status
     *               - error: string|null - Error message if any
     *               - data: array|array[] - File info array or array of file info arrays
     *                 Structure: ['name' => string, 'path' => string, 'finalPath' => string, 'size' => int, 
     *                            'type' => string, 'folder' => string, 'base' => string,
     *                            'resize' => string, 'db' => array|null]
     * 
     * @throws \Exception When upload process fails
     */
    public static function upload(array $fileArr, array $options = [], $save_db = true)
    {
        // Load language for static method
        Fastlang::load('files', APP_LANG);

        try {
            // Handle multiple files
            if (self::isMultiple($fileArr)) {
                return self::_uploadMultipleFiles($fileArr, $options, $save_db);
            }

            // Handle single file upload
            return self::_uploadSingleFile($fileArr, $options, $save_db);
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Check if the file array contains multiple files.
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
        return isset($fileArr['name']) && is_array($fileArr['name']);
    }

    /**
     * Handle multiple file uploads
     * 
     * Processes multiple files from $_FILES array by iterating through
     * each file and calling single file upload for each.
     * 
     * @param array $fileArr Multiple files array from $_FILES
     *                        Structure: ['name' => array, 'type' => array, 'tmp_name' => array, 
     *                                   'error' => array, 'size' => array]
     * @param array $options Upload options passed to single file upload
     * @param bool $save_db Whether to save each file to DB
     * 
     * @return array Multiple upload result with structure:
     *               - success: bool - Overall success status (true if all files succeeded)
     *               - error: string|null - Error message if any file failed
     *               - data: array[] - Array of individual file upload results
     */
    private static function _uploadMultipleFiles($fileArr, $options, $save_db)
    {
        $results = [];
        $hasError = false;
        $count = count($fileArr['name']);

        for ($i = 0; $i < $count; $i++) {
            $single = [
                'name'     => $fileArr['name'][$i],
                'type'     => $fileArr['type'][$i],
                'tmp_name' => $fileArr['tmp_name'][$i],
                'error'    => $fileArr['error'][$i],
                'size'     => $fileArr['size'][$i],
            ];
            $res = self::_uploadSingleFile($single, $options, $save_db);
            if (!$res['success']) $hasError = true;
            $results[] = $res;
        }

        return [
            'success' => !$hasError,
            'error' => $hasError ? 'Some files failed to upload' : null,
            'data' => $results
        ];
    }

    /**
     * Handle single file upload
     * 
     * Processes single file upload with validation, directory creation,
     * file moving, security measures, and database saving.
     * 
     * @param array $fileArr Single file array from $_FILES
     *                        Structure: ['name' => string, 'type' => string, 'tmp_name' => string, 
     *                                   'error' => int, 'size' => int]
     * @param array $options Upload options for directory creation and processing
     * @param bool $save_db Whether to save file info to DB
     * 
     * @return array Single upload result with structure:
     *               - success: bool - Upload success status
     *               - error: string|null - Error message if any
     *               - data: array|null - File info if successful
     *                 Structure: ['name' => string, 'path' => string, 'finalPath' => string, 'size' => int, 
     *                            'type' => string, 'folder' => string, 'base' => string,
     *                            'resize' => string, 'db' => array|null]
     */
    private static function _uploadSingleFile($fileArr, $options, $save_db)
    {
        // Step 1: Validate file
        $validation = self::validateUploadedFile($fileArr, $options);
        if (!$validation['success']) {
            return $validation;
        }

        // Step 2: Create upload directory
        $dirResult = self::_createUploadDirectory($fileArr, $options);
        if (!$dirResult['success']) {
            return $dirResult;
        }

        // Step 3: Move uploaded file
        $moveResult = self::_moveUploadedFile($fileArr, $dirResult['data']);
        if (!$moveResult['success']) {
            return $moveResult;
        }

        // Step 4: Build result array
        $result = [
            'name'     => $moveResult['data']['finalName'],
            'finalPath' => $moveResult['data']['finalPath'], // Keep original path for optimization
            'path'     => $moveResult['data']['finalPath'], // Will be overridden for DB
            'size'     => $moveResult['data']['size'],
            'type'     => $moveResult['data']['type'],
            'is_image' => in_array($moveResult['data']['type'], ['jpg', 'jpeg', 'png', 'gif', 'webp'], true),
            'error'    => null,
            'folder'   => $dirResult['data']['dbFolder'],
            'base'     => $dirResult['data']['safeBase'],
        ];

        // Step 5: Process image optimization
        $optResult = ImageOptimizer::processImageOptimization($result, $options);
        if (!$optResult['success']) {
            $result['optimize_error'] = $optResult['error'];
        } else {
            $result['optimized'] = $optResult['data']['optimized'];
            $result['resize'] = $optResult['data']['resizeStr'];
        }

        // Step 6: Save to database if needed
        if ($save_db) {
            $result['path'] = $dirResult['data']['dbFolder'] . '/' . $result['name'];
            $dbItem = self::saveToDb($result);
            if ($dbItem) {
                $result['db'] = $dbItem;
            }
        }

        // Step 7: Invalidate cache
        Files::clearUniqueNameCache($dirResult['data']['targetFolder']);

        return [
            'success' => true,
            'error' => null,
            'data' => $result
        ];
    }

    /**
     * Validate uploaded file (size, type, MIME, content security).
     * 
     * Performs comprehensive validation for uploaded file:
     * - Check upload error codes
     * - Validate file size limits
     * - Check allowed file types and MIME types
     * - Content validation for SVG and images
     * - Special handling for chunk uploads
     * 
     * @param array $fileArr File array from $_FILES or single file
     *                        Structure: ['name' => string, 'type' => string, 'tmp_name' => string, 
     *                                   'error' => int, 'size' => int]
     * @param array $options Validation options (optional)
     *                        - 'is_upload_chunk' => bool: Chunk upload mode (default: false)
     *                        - 'filename' => string: Original filename for chunk upload
     *                        - 'total_chunks' => int: Total chunks for chunk upload
     * 
     * @return array Validation result with structure:
     *               - success: bool - Validation success status
     *               - error: string|null - Error message if any
     *               - data: array|null - Validation data if successful
     *                 Structure: ['ext' => string, 'mimeType' => string, 'size' => int, 
     *                            'tmp' => string, 'name' => string]
     */
    public static function validateUploadedFile($fileArr, $options = [])
    {
        // Load language for static method
        Fastlang::load('files', APP_LANG);

        $config = config('files') ?? [];
        $allowed_types = $config['allowed_types'] ?? ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!empty($options['allowed_types'])) {
            $allowed_types = array_merge($allowed_types, $options['allowed_types']);
        }
        $allowed_mimes = $config['allowed_mimes'] ?? [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
        ];
        if (!empty($options['allowed_mimes'])) {
            $allowed_mimes = array_merge($allowed_mimes, $options['allowed_mimes']);
        }
        $max_file_size = ($options['is_upload_chunk'] ?? false) ? ($config['max_size_upload_chunks'] ?? 1 * 1024 * 1024 * 1024) : ($config['max_file_size'] ?? 10 * 1024 * 1024);
        if (!empty($options['max_file_size'])) {
            $max_file_size = $options['max_file_size'];
        }
        $error = $fileArr['error'] ?? UPLOAD_ERR_NO_FILE;
        $size = $fileArr['size'] ?? 0;
        $tmp = $fileArr['tmp_name'] ?? '';
        $name = $fileArr['name'] ?? '';

        // For chunk upload, use original filename from options
        if ($options['is_upload_chunk'] ?? false) {
            $originalFilename = $options['filename'] ?? $name;
            $ext = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
        } else {
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        }

        // Check upload error
        if ($error !== UPLOAD_ERR_OK) {
            return [
                'success' => false,
                'error' => Files::getErrorMessage($error),
                'data' => null
            ];
        }

        // Check file size
        if ($options['is_upload_chunk'] ?? false) {
            // For chunk upload, calculate total file size based on chunks
            $totalChunks = $options['total_chunks'] ?? 1;
            $estimatedTotalSize = $size * $totalChunks;

            $allow_per_chunk = $config['max_size_chunks'] ?? 10 * 1024 * 1024;
            if ($size > $allow_per_chunk) {
                return [
                    'success' => false,
                    'error' => Fastlang::_e('file size exceeds maximum allowed size', $config['max_size_chunks']),
                    'data' => null
                ];
            }
            if ($estimatedTotalSize > $config['max_size']) {
                return [
                    'success' => false,
                    'error' => Fastlang::_e('estimated total file size exceeds maximum allowed size'),
                    'data' => null
                ];
            }
        } else {
            // For regular upload, check current file size
            if ($size > $max_file_size) {
                return [
                    'success' => false,
                    'error' => Fastlang::_e('file exceeds maximum allowed size'),
                    'data' => null
                ];
            }
        }

        // Check file type
        if (!in_array($ext, $allowed_types, true)) {
            return [
                'success' => false,
                'error' => Fastlang::_e('file type not allowed', implode(',', $allowed_types)),
                'data' => null
            ];
        }

        // MIME validation (skip for chunk upload)
        if (!($options['is_upload_chunk'] ?? false)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $tmp);
            finfo_close($finfo);

            if (!in_array($mimeType, $allowed_mimes)) {
                return [
                    'success' => false,
                    'error' => Fastlang::_e('file mime type not allowed', $mimeType),
                    'data' => null
                ];
            }

            // Extension-MIME match
            if (isset($allowed_mimes[$ext]) && $allowed_mimes[$ext] !== $mimeType) {
                return [
                    'success' => false,
                    'error' => Fastlang::_e('file extension does not match mime type'),
                    'data' => null
                ];
            }

            // Security: Validate file content for malicious content
            $contentValidation = SecurityManager::validateFileContent($tmp, $mimeType);
            if (!$contentValidation['success']) {
                return [
                    'success' => false,
                    'error' => $contentValidation['error'],
                    'data' => null
                ];
            }
        } else {
            // For chunk upload, set a default MIME type
            $mimeType = 'application/octet-stream';
        }

        return [
            'success' => true,
            'error' => null,
            'data' => [
                'ext' => $ext,
                'mimeType' => $mimeType,
                'size' => $size,
                'tmp' => $tmp,
                'name' => $name
            ]
        ];
    }

    /**
     * Create upload directory structure
     * 
     * Creates the target directory structure for file upload.
     * For images: creates subfolder with filename (without extension) to contain the image and its variants.
     * For non-images: uploads directly to the specified folder without subfolder.
     * 
     * @param array $fileArr File array with name for directory creation
     *                        Structure: ['name' => string, ...]
     * @param array $options Upload options containing folder path
     *                        - 'folder' => string: Base folder path (optional)
     * 
     * @return array Directory creation result with structure:
     *               - success: bool - Directory creation success status
     *               - error: string|null - Error message if any
     *               - data: array|null - Directory info if successful
     *                 Structure: ['targetFolder' => string, 'dbFolder' => string, 'safeBase' => string]
     */
    private static function _createUploadDirectory($fileArr, $options = [])
    {
        $name = $fileArr['name'] ?? '';
        $folder = $options['folder'] ?? '';
        $overwrite = $options['overwrite'] ?? false;

        // Sanitize folder path
        $folder = Files::sanitizeFolderPath($folder);

        // Generate unique base name
        $rawName = pathinfo($name, PATHINFO_FILENAME);
        $safeBase = substr(Files::sanitizeFileName($rawName), 0, 32);
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION) ?: 'jpg');

        // Check if file is an image
        $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);

        // Build paths
        $baseUpload = PATH_WRITE . 'uploads';

        // Check if baseUpload is already absolute path
        if (Files::isAbsolutePath($baseUpload)) {
            $baseFolderPath = $baseUpload . '/' . ltrim($folder, '/\\');
        } else {
            $baseFolderPath = PATH_ROOT . '/' . trim($baseUpload, '/\\') . '/' . ltrim($folder, '/\\');
        }

        // For images: create subfolder with filename (without extension)
        if ($isImage) {
            $filenameWithoutExt = pathinfo($name, PATHINFO_FILENAME);
            $safeFilename = Files::sanitizeFileName($filenameWithoutExt);

            if ($overwrite) {
                // For overwrite mode: use exact folder name, don't generate unique
                $targetFolder = $baseFolderPath . '/' . $safeFilename;
                $dbFolder = $folder . '/' . $safeFilename;

                // Delete existing folder and all its contents if it exists
                if (is_dir($targetFolder)) {
                    self::_deleteExistingFolder($targetFolder);
                }
            } else {
                // Check if folder already exists and generate unique folder name if needed
                $candidateFolder = $baseFolderPath . '/' . $safeFilename;
                $i = 1;
                while (is_dir($candidateFolder)) {
                    $candidateFolder = $baseFolderPath . '/' . $safeFilename . '_' . $i++;
                }

                $targetFolder = $candidateFolder;
                $dbFolder = $folder . '/' . basename($candidateFolder);
            }
        } else {
            // For non-images: upload directly to folder without subfolder
            $targetFolder = $baseFolderPath;
            $dbFolder = $folder;

            if ($overwrite) {
                // For overwrite mode: delete existing files with same base name
                self::_deleteExistingFilesByBaseName($targetFolder, $safeBase, $ext);
            }
        }

        // Create directory
        if (!is_dir($targetFolder) && !mkdir($targetFolder, 0777, true)) {
            return [
                'success' => false,
                'error' => Fastlang::_e('cannot create upload folder', $targetFolder),
                'data' => null
            ];
        }

        return [
            'success' => true,
            'error' => null,
            'data' => [
                'targetFolder' => $targetFolder,
                'dbFolder' => $dbFolder,
                'safeBase' => $safeBase,
                'overwrite' => $overwrite
            ]
        ];
    }

    /**
     * Move uploaded file to target location
     * 
     * Moves or copies the uploaded file to the target directory.
     * Handles both real uploaded files and assembled chunk files.
     * Applies security measures after successful move.
     * 
     * @param array $fileArr File array with tmp_name and name
     *                        Structure: ['tmp_name' => string, 'name' => string, ...]
     * @param array $dirInfo Directory information from _createUploadDirectory
     *                        Structure: ['targetFolder' => string, 'dbFolder' => string, 'safeBase' => string, 'overwrite' => bool]
     * 
     * @return array File move result with structure:
     *               - success: bool - File move success status
     *               - error: string|null - Error message if any
     *               - data: array|null - File info if successful
     *                 Structure: ['finalPath' => string, 'finalName' => string, 'size' => int, 'type' => string]
     */
    private static function _moveUploadedFile($fileArr, $dirInfo)
    {
        $tmp = $fileArr['tmp_name'] ?? '';
        $name = $fileArr['name'] ?? '';
        $overwrite = $dirInfo['overwrite'] ?? false;

        // Get extension from original filename
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        $safeBase = $dirInfo['safeBase'];
        $targetFolder = $dirInfo['targetFolder'];

        // Use sanitized name for consistency with folder naming
        $originalName = pathinfo($name, PATHINFO_FILENAME);
        $sanitizedName = Files::sanitizeFileName($originalName);

        if ($overwrite) {
            // For overwrite mode: add timestamp to filename to avoid cache issues
            $timestamp = time();
            $finalName = $sanitizedName . '-' . $timestamp . '.' . $ext;
            $finalPath = $targetFolder . '/' . $finalName;
            // Delete existing files with same base name (without timestamp)
            self::_deleteExistingFilesByBaseName($targetFolder, $sanitizedName, $ext);
        } else {
            // Generate unique filename to avoid conflicts
            $finalName = FileStorage::getUniqueName($targetFolder, $sanitizedName, $ext);
            $finalPath = $targetFolder . '/' . $finalName;
        }

        // Check if it's a real uploaded file or a regular file (for chunk uploads)
        $isUploadedFile = is_uploaded_file($tmp);

        if ($isUploadedFile) {
            // Real uploaded file
            if (!move_uploaded_file($tmp, $finalPath)) {
                return [
                    'success' => false,
                    'error' => Fastlang::_e('failed to move uploaded file'),
                    'data' => null
                ];
            }
        } else {
            // Regular file (for chunk uploads, assembled files, etc.)
            if (!copy($tmp, $finalPath)) {
                return [
                    'success' => false,
                    'error' => Fastlang::_e('failed to copy file'),
                    'data' => null
                ];
            }
        }

        // Security: Process file after successful move
        $securityResult = self::_applySecurityMeasures($finalPath, $ext);
        if (!$securityResult['success']) {
            // Don't fail the upload, just continue
        }

        return [
            'success' => true,
            'error' => null,
            'data' => [
                'finalPath' => $finalPath,
                'finalName' => $finalName,
                'size' => filesize($finalPath),
                'type' => $ext
            ]
        ];
    }

    /**
     * Save file info to the database using FilesModel.
     * 
     * Saves file information to database with FilesModel.
     * Automatically creates timestamps and handles required fields.
     * 
     * @param array $fileInfo File info array
     *                        Structure: ['name' => string, 'path' => string, 'size' => int, 
     *                                   'type' => string, 'resize' => string|null, 'folder' => string, 
     *                                   'base' => string]
     * @param object|null $model FilesModel instance (optional, auto-create if null)
     * 
     * @return array|false DB record with id if successful, false if failed
     *                     Structure: ['id' => int, 'name' => string, 'path' => string, 
     *                                'size' => int, 'type' => string, 'resize' => string, 
     *                                'autoclean' => int, 'created_at' => string, 'updated_at' => string]
     */
    public static function saveToDb($fileInfo, $model = null)
    {
        try {
            $model = $model ?: (class_exists('App\\Models\\FilesModel') ? new \App\Models\FilesModel() : null);
            if (!$model) return false;

            // Simplify: use path directly if available, otherwise create from path + name
            $dbPath = $fileInfo['path'] ?? '';
            if (empty($dbPath)) {
                $prefix = $fileInfo['pth'] ?? '';
                $dbPath = $prefix ? (rtrim($prefix, '/') . '/' . $fileInfo['name']) : $fileInfo['name'];
            }

            $addItem = [
                'name'       => $fileInfo['name'],
                'path'       => $dbPath,
                'size'       => $fileInfo['size'],
                'type'       => $fileInfo['type'],
                'autoclean'  => 0,
                'resize'     => $fileInfo['resize'] ?? '',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            $id = $model->addFile($addItem);
            if (!$id) return false;
            $addItem['id'] = $id;
            return $addItem;
        } catch (\Exception $e) {
            error_log('UploadManager::saveToDb error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Assemble all chunks into a single file.
     * 
     * Combines all chunks into a complete file.
     * Sorts chunks in order and validates integrity.
     * Automatically cleans up chunks after assembly.
     * 
     * @param string $tempDir Temporary directory containing chunks
     * @param int $chunks Total number of chunks expected
     * @param string $filename Original filename
     * @param array $options Upload options (optional)
     *                        - 'folder' => string: Target folder
     *                        - 'resizes' => array: Image resize options
     *                        - 'watermark' => array: Watermark options
     * 
     * @return array Assembly result with structure:
     *               - success: bool - Assembly success status
     *               - error: string|null - Error message if any
     *               - data: array|null - File info if successful
     *                 Structure: ['name' => string, 'path' => string, 'finalPath' => string, 'size' => int, 
     *                            'type' => string, 'folder' => string, 'base' => string,
     *                            'resize' => string, 'db' => array|null]
     */
    public static function assembleChunks($tempDir, $chunks, $filename, $options)
    {
        // Load language for static method
        Fastlang::load('files', APP_LANG);

        $assembledFile = $tempDir . '/assembled_' . $filename;

        $handle = fopen($assembledFile, 'wb');
        if (!$handle) {
            return [
                'success' => false,
                'error' => Fastlang::_e('cannot create assembled file'),
                'data' => null
            ];
        }

        // Get all chunk files and sort them
        $chunkFiles = glob($tempDir . '/chunk_*');

        sort($chunkFiles, SORT_NATURAL); // Natural sort: chunk_0, chunk_1, chunk_10, etc.

        if (empty($chunkFiles)) {
            fclose($handle);
            return [
                'success' => false,
                'error' => Fastlang::_e('no chunks found'),
                'data' => null
            ];
        }

        // Combine all chunks in order
        $totalSize = 0;
        foreach ($chunkFiles as $index => $chunkFile) {
            if (!file_exists($chunkFile)) {
                fclose($handle);
                return [
                    'success' => false,
                    'error' => Fastlang::_e('missing chunk file', $chunkFile),
                    'data' => null
                ];
            }

            $chunkSize = filesize($chunkFile);

            $chunkData = file_get_contents($chunkFile);
            $written = fwrite($handle, $chunkData);

            $totalSize += $written;
            unlink($chunkFile); // Clean up chunk
        }

        fclose($handle);

        // Verify assembled file
        if (!file_exists($assembledFile)) {
            return [
                'success' => false,
                'error' => Fastlang::_e('assembled file not found'),
                'data' => null
            ];
        }

        // Create file array for upload
        $mimeType = function_exists('mime_content_type') ? mime_content_type($assembledFile) : 'application/octet-stream';

        $fileArr = [
            'name' => $filename,
            'type' => $mimeType,
            'tmp_name' => $assembledFile,
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($assembledFile)
        ];

        // Upload the assembled file without validation (already validated during chunk upload)
        $result = self::uploadAssembledFile($fileArr, $options, true);

        // Clean up
        if (file_exists($assembledFile)) {
            unlink($assembledFile);
        }
        if (is_dir($tempDir) && count(scandir($tempDir)) <= 2) { // Only . and ..
            rmdir($tempDir);
        }

        return $result;
    }

    /**
     * Delete existing folder and all its contents for overwrite mode.
     * 
     * Recursively deletes a folder and all its contents when overwrite is enabled.
     * Used to clean up existing files before replacing with new ones.
     * 
     * @param string $folderPath Path to the folder to delete
     * @return bool True if deletion was successful, false otherwise
     */
    private static function _deleteExistingFolder($folderPath)
    {
        if (!is_dir($folderPath)) {
            return true;
        }

        try {
            $files = array_diff(scandir($folderPath), ['.', '..']);
            foreach ($files as $file) {
                $filePath = $folderPath . DIRECTORY_SEPARATOR . $file;
                if (is_dir($filePath)) {
                    self::_deleteExistingFolder($filePath);
                } else {
                    unlink($filePath);
                }
            }
            return rmdir($folderPath);
        } catch (\Exception $e) {
            Logger::error('Failed to delete existing folder: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete existing file and all its variants for overwrite mode.
     * 
     * Deletes a file and all its associated variants (resizes, WebP, etc.)
     * when overwrite is enabled.
     * 
     * @param string $filePath Path to the file to delete
     * @return bool True if deletion was successful, false otherwise
     */
    private static function _deleteExistingFile($filePath)
    {
        if (!file_exists($filePath)) {
            return true;
        }

        try {
            // Get file info for variant deletion
            $fileInfo = [
                'path' => $filePath,
                'type' => strtolower(pathinfo($filePath, PATHINFO_EXTENSION)),
                'name' => basename($filePath)
            ];

            // Delete all variants first
            $variants = VariantManager::getAllVariants($fileInfo);
            foreach ($variants as $variantPath) {
                if (file_exists($variantPath)) {
                    unlink($variantPath);
                }
            }

            // Delete main file
            return unlink($filePath);
        } catch (\Exception $e) {
            Logger::error('Failed to delete existing file: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete existing files by base name for overwrite mode with timestamp.
     * 
     * Deletes all files that match the base name pattern (with or without timestamp)
     * and all their variants. This ensures clean overwrite when using timestamp naming.
     * 
     * @param string $targetFolder Target folder to search in
     * @param string $baseName Base name without extension
     * @param string $ext File extension
     * @return bool True if deletion was successful, false otherwise
     */
    private static function _deleteExistingFilesByBaseName($targetFolder, $baseName, $ext)
    {
        if (!is_dir($targetFolder)) {
            return true;
        }

        try {
            $files = scandir($targetFolder);

            foreach ($files as $file) {
                if ($file === '.' || $file === '..') {
                    continue;
                }

                $filePath = $targetFolder . '/' . $file;

                // Check if file matches base name pattern (with or without timestamp)
                if (preg_match('/^' . preg_quote($baseName, '/') . '(?:-\d+)?\.' . preg_quote($ext, '/') . '$/', $file)) {
                    // Delete the file and its variants
                    self::_deleteExistingFile($filePath);
                }
            }

            return true;
        } catch (\Exception $e) {
            Logger::error('Failed to delete existing files by base name: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Upload assembled file without validation (for chunk uploads).
     * 
     * Uploads assembled file from chunks without validation
     * since it was already validated during chunk upload process.
     * 
     * @param array $fileArr File array with assembled file
     *                        Structure: ['name' => string, 'type' => string, 'tmp_name' => string, 
     *                                   'error' => int, 'size' => int]
     * @param array $options Upload options (optional)
     *                        - 'folder' => string: Target folder
     *                        - 'resizes' => array: Image resize options
     *                        - 'watermark' => array: Watermark options
     * @param bool $save_db Whether to save to DB (default: true)
     * 
     * @return array Upload result with structure:
     *               - success: bool - Upload success status
     *               - error: string|null - Error message if any
     *               - data: array|null - File info if successful
     *                 Structure: ['name' => string, 'path' => string, 'finalPath' => string, 'size' => int, 
     *                            'type' => string, 'folder' => string, 'base' => string,
     *                            'resize' => string, 'db' => array|null]
     */
    public static function uploadAssembledFile($fileArr, $options, $save_db)
    {
        // Skip validation since file was already validated during chunk upload

        // Step 1: Create upload directory
        $dirResult = self::_createUploadDirectory($fileArr, $options);
        if (!$dirResult['success']) {
            return $dirResult;
        }

        // Step 2: Move assembled file
        $moveResult = self::_moveUploadedFile($fileArr, $dirResult['data']);
        if (!$moveResult['success']) {
            return $moveResult;
        }

        // Step 3: Build result array
        $result = [
            'name'     => $moveResult['data']['finalName'],
            'finalPath' => $moveResult['data']['finalPath'], // Keep original path for optimization
            'path'     => $moveResult['data']['finalPath'], // Will be overridden for DB
            'size'     => $moveResult['data']['size'],
            'type'     => $moveResult['data']['type'],
            'is_image' => in_array($moveResult['data']['type'], ['jpg', 'jpeg', 'png', 'gif', 'webp'], true),
            'error'    => null,
            'folder'   => $dirResult['data']['dbFolder'],
            'base'     => $dirResult['data']['safeBase'],
        ];

        // Step 4: Process image optimization
        $optResult = ImageOptimizer::processImageOptimization($result, $options);
        if (!$optResult['success']) {
            $result['optimize_error'] = $optResult['error'];
        } else {
            $result['optimized'] = $optResult['data']['optimized'];
            $result['resize'] = $optResult['data']['resizeStr'];
        }

        // Step 5: Save to database if needed
        if ($save_db) {
            $result['path'] = $dirResult['data']['dbFolder'] . '/' . $result['name'];
            $dbItem = self::saveToDb($result);
            if ($dbItem) {
                $result['db'] = $dbItem;
            }
        }

        // Step 6: Invalidate cache
        Files::clearUniqueNameCache($dirResult['data']['targetFolder']);

        return [
            'success' => true,
            'error' => null,
            'data' => $result
        ];
    }

    /**
     * Apply security measures to uploaded file.
     * 
     * Applies security measures to uploaded file:
     * - Strip EXIF metadata from images (JPEG, PNG, WebP)
     * - Sanitize SVG content to prevent XSS
     * - Validate file content for malicious code
     * 
     * @param string $filePath Path to uploaded file
     * @param string $ext File extension (e.g. 'jpg', 'svg', 'png')
     * 
     * @return array Security result with structure:
     *               - success: bool - Security processing success status
     *               - error: string|null - Error message if any
     */
    private static function _applySecurityMeasures($filePath, $ext)
    {
        try {
            // Strip EXIF metadata from images
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $stripResult = SecurityManager::stripExifMetadata($filePath);
                if (!$stripResult) {
                    return [
                        'success' => false,
                        'error' => Fastlang::_e('failed to strip exif metadata')
                    ];
                }
            }

            // Sanitize SVG files
            if ($ext === 'svg') {
                $svgContent = file_get_contents($filePath);
                if ($svgContent !== false) {
                    $sanitizedContent = SecurityManager::sanitizeSvg($svgContent);
                    if ($sanitizedContent !== $svgContent) {
                        // Only write back if content was actually sanitized
                        $writeResult = file_put_contents($filePath, $sanitizedContent);
                        if ($writeResult === false) {
                            return [
                                'success' => false,
                                'error' => Fastlang::_e('failed to write sanitized svg content')
                            ];
                        }
                    }
                }
            }

            return [
                'success' => true,
                'error' => null
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => Fastlang::_e('security processing error', $e->getMessage())
            ];
        }
    }

    /**
     * Handle chunk upload for large files with resume capability.
     * 
     * Processes individual chunks of a large file upload.
     * Validates chunk upload using ChunkManager.
     * Tracks progress and enables resume functionality.
     * 
     * @param array $chunkInfo Chunk information
     *                        Structure: ['uploadId' => string, 'chunk' => int, 'chunks' => int, 
     *                                   'filename' => string]
     * @param array $fileArr File array from $_FILES
     *                        Structure: ['name' => string, 'type' => string, 'tmp_name' => string, 
     *                                   'error' => int, 'size' => int]
     * @param array $options Upload options (optional)
     *                        - 'folder' => string: Target folder
     *                        - 'resizes' => array: Image resize options
     *                        - 'watermark' => array: Watermark options
     * 
     * @return array Chunk upload result with structure:
     *               - success: bool - Chunk upload success status
     *               - error: string|null - Error message if any
     *               - data: array|null - Upload progress data if successful
     *                 Structure: ['uploaded_chunks' => int, 'total_chunks' => int, 
     *                            'percentage' => float, 'can_resume' => bool]
     */
    public static function uploadChunk($chunkInfo, $fileArr, $options = [])
    {
        // Load language for static method
        Fastlang::load('files', APP_LANG);

        $uploadId = $chunkInfo['uploadId'] ?? '';
        $chunk = $chunkInfo['chunk'] ?? 0;
        $chunks = $chunkInfo['chunks'] ?? 1;
        $filename = $chunkInfo['filename'] ?? '';

        if (empty($uploadId) || empty($filename)) {
            return [
                'success' => false,
                'error' => Fastlang::_e('uploadid and filename are required for chunk upload'),
                'data' => null
            ];
        }

        // Create temporary directory for chunks using uploadId
        $baseUpload = PATH_WRITE . 'uploads';
        $baseTempDir = $baseUpload . '/temp/chunks/';

        if (!is_dir($baseTempDir)) {
            if (!mkdir($baseTempDir, 0755, true)) {
                return [
                    'success' => false,
                    'error' => Fastlang::_e('failed to create base temp directory'),
                    'data' => null
                ];
            }
        }

        $tempDir = $baseTempDir . $uploadId;

        if (!is_dir($tempDir)) {
            if (!mkdir($tempDir, 0755, true)) {
                return [
                    'success' => false,
                    'error' => Fastlang::_e('failed to create temp directory'),
                    'data' => null
                ];
            }
        }

        // Save chunk file
        $chunkFile = $tempDir . '/chunk_' . $chunk;
        if (!move_uploaded_file($fileArr['tmp_name'], $chunkFile)) {
            return [
                'success' => false,
                'error' => Fastlang::_e('failed to save chunk'),
                'data' => null
            ];
        }

        // Save metadata
        $metadataFile = $tempDir . '/metadata.json';
        $metadata = [
            'uploadId' => $uploadId,
            'filename' => $filename,
            'chunks' => $chunks,
            'uploaded_chunks' => $chunk,
            'last_chunk_time' => time()
        ];

        if (!file_put_contents($metadataFile, json_encode($metadata))) {
            return [
                'success' => false,
                'error' => Fastlang::_e('failed to save chunk'),
                'data' => null
            ];
        }

        // Validate chunk upload and get progress
        $validation = ChunkManager::validateChunkUpload($uploadId, $chunk, $chunks);

        // Get detailed progress
        $progress = ChunkManager::getUploadProgress($uploadId);

        // Ensure progress has required keys
        if (!is_array($progress)) {
            $progress = [
                'percentage' => 0,
                'uploaded_chunks' => 0,
                'total_chunks' => 0,
                'missing_chunks' => [],
                'status' => 'error'
            ];
        }

        $result = [
            'success' => true,
            'error' => null,
            'data' => [
                'chunk' => $chunk,
                'chunks' => $chunks,
                'uploaded_count' => $progress['uploaded_count'] ?? 0,
                'total_chunks' => $progress['total_chunks'] ?? $chunks
            ]
        ];

        return $result;
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
        // Load language for static method
        Fastlang::load('files', APP_LANG);

        $baseTempDir = PATH_WRITE . 'uploads/temp/chunks/';
        $tempDir = $baseTempDir . $uploadId;

        if (!is_dir($tempDir)) {
            return [
                'success' => false,
                'error' => Fastlang::_e('upload_session_not_found'),
                'data' => null
            ];
        }

        // Count existing chunks
        $chunkFiles = glob($tempDir . '/chunk_*');
        $chunks = count($chunkFiles);

        if ($chunks === 0) {
            return [
                'success' => false,
                'error' => Fastlang::_e('no chunks found'),
                'data' => null
            ];
        }

        // Get filename from options or use default
        $filename = '';

        if (!empty($options['filename'])) {
            $filename = $options['filename'];
        } else {
            // Fallback: use default name with uploadId
            $filename = 'assembled_file_' . $uploadId;
        }

        // Assemble all chunks
        $result = self::assembleChunks($tempDir, $chunks, $filename, $options);

        return $result;
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
        // Load language for static method
        Fastlang::load('files', APP_LANG);

        $allowed_types = $options['allowed_types'] ?? ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $allowed_mimes = $options['allowed_mimes'] ?? [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
        ];
        $max_size = $options['max_size'] ?? 10 * 1024 * 1024;
        $custom_name = $options['custom_name'] ?? null;
        $overwrite = $options['overwrite'] ?? false;
        $sizes = $options['sizes'] ?? [];
        if (is_string($sizes)) {
            $sizes = json_decode($sizes, true);
            if (!is_array($sizes)) $sizes = [];
        }
        $convert_type = $options['type'] ?? null;
        $quality = isset($options['quality']) ? (int)$options['quality'] : 90;

        $baseUpload = PATH_WRITE . 'uploads';
        $baseFolderPath = PATH_ROOT . '/' . trim($baseUpload, '/\\') . '/' . ltrim($targetFolder, '/\\');

        if (!is_dir($baseFolderPath)) {
            if (!mkdir($baseFolderPath, 0777, true)) {
                return ['success' => false, 'error' => Fastlang::_e('failed to create base folder', $baseFolderPath), 'data' => null];
            }
        }

        $urlInfo = pathinfo(parse_url($url, PHP_URL_PATH));
        $ext = strtolower($urlInfo['extension'] ?? '');
        if (!in_array($ext, $allowed_types)) {
            return ['success' => false, 'error' => Fastlang::_e('file type not allowed', $ext), 'data' => null];
        }

        $rawFileName = $custom_name ? $custom_name : $urlInfo['filename'];
        $safeBase = substr(Files::sanitizeFileName(pathinfo($rawFileName, PATHINFO_FILENAME)), 0, 32);
        $ext = strtolower(pathinfo($rawFileName, PATHINFO_EXTENSION) ?: $ext);

        // Upload directly to folder without subfolder
        $targetFolderPath = $baseFolderPath;
        $dbFolder = $targetFolder;

        if (!is_dir($targetFolderPath) && !mkdir($targetFolderPath, 0777, true)) {
            return ['success' => false, 'error' => Fastlang::_e('cannot create upload folder', $targetFolderPath), 'data' => null];
        }

        $finalName = $safeBase . '.' . $ext;
        $targetPath = $targetFolderPath . '/' . $finalName;

        if (!is_dir($targetFolderPath)) {
            if (!mkdir($targetFolderPath, 0777, true)) {
                return ['success' => false, 'error' => Fastlang::_e('cannot create upload folder', $targetFolderPath), 'data' => null];
            }
        }

        // Download file from URL
        $context = stream_context_create([
            'http' => [
                'timeout' => 30,
                'user_agent' => 'Mozilla/5.0 (compatible; FileDownloader/1.0)'
            ]
        ]);

        $fileContent = file_get_contents($url, false, $context);
        if ($fileContent === false) {
            return ['success' => false, 'error' => Fastlang::_e('failed to download file from url'), 'data' => null];
        }

        if (strlen($fileContent) > $max_size) {
            return ['success' => false, 'error' => Fastlang::_e('file exceeds maximum allowed size'), 'data' => null];
        }

        // MIME validation
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_buffer($finfo, $fileContent);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowed_mimes)) {
            return ['success' => false, 'error' => Fastlang::_e('file mime type not allowed', $mimeType), 'data' => null];
        }

        // Save file
        if (!file_put_contents($targetPath, $fileContent)) {
            return ['success' => false, 'error' => Fastlang::_e('failed to save file'), 'data' => null];
        }

        $fileInfo = [
            'name' => $finalName,
            'path' => $dbFolder . '/' . $finalName,
            'size' => filesize($targetPath),
            'type' => $ext,
            'mime' => $mimeType,
            'url'  => $url,
            'folder' => $dbFolder,
            'base' => $safeBase,
        ];
        $createdFiles = [$fileInfo];

        $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
        $resizeList = [];

        if ($isImage && (!empty($sizes) || $convert_type || $quality)) {
            try {
                $img = ImageManager::load($targetPath);

                foreach ($sizes as $size) {
                    if (preg_match('/^(\d+)x(\d+)$/', $size, $m)) {
                        $w = (int)$m[1];
                        $h = (int)$m[2];

                        $img2 = clone $img;
                        $img2->resize($w, $h, true, true);

                        $resizeExt = $convert_type ?: $ext;
                        $resizeName = $safeBase . "_{$w}x{$h}.{$resizeExt}";
                        $resizePath = $targetFolderPath . '/' . $resizeName;

                        $img2->convert($resizeExt)->save($resizePath, $quality);

                        $createdFiles[] = [
                            'name' => $resizeName,
                            'path' => $dbFolder . '/' . $resizeName,
                            'size' => filesize($resizePath),
                            'type' => $resizeExt,
                            'resize' => "{$w}x{$h}",
                        ];
                        $resizeList[] = "{$w}x{$h}";
                        $img2->destroy();
                    }
                }

                if ($convert_type && $convert_type !== $ext) {
                    $convertName = $safeBase . ".{$convert_type}";
                    $convertPath = $targetFolderPath . '/' . $convertName;
                    $img->convert($convert_type)->save($convertPath, $quality);
                    $createdFiles[] = [
                        'name' => $convertName,
                        'path' => $dbFolder . '/' . $convertName,
                        'size' => filesize($convertPath),
                        'type' => $convert_type,
                        'resize' => null,
                    ];
                }

                $img->destroy();
            } catch (\Exception $e) {
                error_log('UploadManager::downloadFromUrl optimize error: ' . $e->getMessage());
            }
        }

        $dbItem = self::saveToDb([
            'name' => $finalName,
            'path' => $fileInfo['path'],
            'size' => $fileInfo['size'],
            'type' => $fileInfo['type'],
            'resize' => !empty($resizeList) ? implode(';', $resizeList) : '',
            'autoclean' => 0,
        ]);

        if ($dbItem) {
            $fileInfo['db'] = $dbItem;
            $createdFiles[0]['db'] = $dbItem;
        }

        return ['success' => true, 'error' => null, 'data' => $createdFiles];
    }
}
