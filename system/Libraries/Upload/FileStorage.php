<?php

namespace System\Libraries\Upload;

use App\Libraries\Fastlang;
use System\Libraries\Files;
use System\Libraries\Logger;

/**
 * FileStorage - Handle file storage operations with security and cache management
 * 
 * Main features:
 * - Generate unique file names with caching for performance
 * - Delete files and directories with security validation
 * - Manage file variants (resizes, WebP, etc.)
 * - Cache management for unique name generation
 * - Path validation and security checks
 * 
 * @package System\Libraries\Upload
 * @since 1.0.0
 */
class FileStorage
{
    /**
     * Cache for unique name generation to improve performance.
     * 
     * Stores generated unique names to avoid repeated file system checks.
     * Structure: ['directory_path' => ['base_name' => 'unique_name']]
     * 
     * @var array
     */
    private static $uniqueNameCache = [];

    /**
     * Generate a unique file name in a directory (avoid overwriting existing files).
     * 
     * Creates a unique filename by appending a number if the base name already exists.
     * Uses caching to improve performance for large file counts.
     * Handles file extension properly and maintains original base name.
     *
     * @param string $dir Directory path where file will be created
     * @param string $base Base file name (without extension)
     * @param string $ext File extension (e.g. 'jpg', 'png', 'pdf')
     * @param bool $useCache Whether to use cache for performance (default: true)
     * @param bool $overwrite Whether to allow overwriting existing files (default: false)
     * 
     * @return string Unique file name with extension
     */
    public static function getUniqueName($dir, $base, $ext, $useCache = true, $overwrite = false)
    {
        $cacheKey = $dir . '/' . $base . '_' . ($overwrite ? 'overwrite' : 'unique');

        if ($useCache && isset(self::$uniqueNameCache[$cacheKey])) {
            return self::$uniqueNameCache[$cacheKey];
        }

        if ($overwrite) {
            // For overwrite mode, return the exact name without checking for conflicts
            $uniqueName = $base . '.' . $ext;
        } else {
            // Original behavior: generate unique name
            $candidate = $base;
            $i = 1;

            while (file_exists($dir . DIRECTORY_SEPARATOR . $candidate . '.' . $ext)) {
                $candidate = $base . '_' . $i++;
            }

            $uniqueName = $candidate . '.' . $ext;
        }

        if ($useCache) {
            self::$uniqueNameCache[$cacheKey] = $uniqueName;
        }

        return $uniqueName;
    }

    /**
     * Clear the unique name cache.
     * 
     * Removes cached unique names to ensure consistency when files are added/removed.
     * Can clear cache for specific directory or entire cache.
     *
     * @param string|null $dir Optional directory to clear cache for specific directory only
     * 
     * @return void
     */
    public static function clearUniqueNameCache($dir = null)
    {
        if ($dir === null) {
            // Clear entire cache
            self::$uniqueNameCache = [];
        } else {
            // Clear cache for specific directory
            $dir = rtrim($dir, DIRECTORY_SEPARATOR);
            foreach (self::$uniqueNameCache as $key => $value) {
                if (strpos($key, $dir) === 0) {
                    unset(self::$uniqueNameCache[$key]);
                }
            }
        }
    }

    /**
     * Delete a file and all its variants (resize, webp, ...).
     * 
     * For file info arrays from DB: Deletes the entire parent folder containing the file.
     * This is optimized for image files stored in dedicated folders named after the file.
     * For single file paths: Deletes only the specified file.
     * Performs security validation before deletion.
     *
     * @param string|array $path File path (relative/absolute) or file info array from DB
     *                           Structure: ['path' => string, 'type' => string, 'resize' => string, ...]
     * 
     * @return array Delete result with structure:
     *               - success: bool - Delete success status
     *               - error: string|null - Error message if any
     *               - data: mixed - Additional data (usually null)
     */
    public static function deleteFile($path)
    {
        // Load language for static method
        Fastlang::load('files', APP_LANG);

        if (is_array($path)) {
            // File info array from DB - delete entire folder containing the file
            $filePath = $path['path'];

            // Simply get parent folder by removing filename from path
            $parentFolder = dirname($filePath);

            // Resolve absolute path for the parent folder
            $absoluteParentFolder = self::_resolveAbsolutePath($parentFolder);

            // Delete entire folder recursively
            if (is_dir($absoluteParentFolder)) {
                return self::deleteFolderRecursive($absoluteParentFolder);
            } else {
                return [
                    'success' => false,
                    'error' => 'Parent folder does not exist: ' . $absoluteParentFolder,
                    'data' => null
                ];
            }
        } else {
            // Single file path
            // Resolve absolute path
            $absolutePath = self::_resolveAbsolutePath($path);

            // Check if file exists
            $fileExists = file_exists($absolutePath);

            if (!self::_canDeleteFile($path)) {
                return [
                    'success' => false,
                    'error' => Fastlang::_e('file cannot be deleted'),
                    'data' => [
                        'absolute_path' => $absolutePath,
                        'file_exists' => $fileExists
                    ]
                ];
            }

            if ($fileExists) {
                $deleteResult = unlink($absolutePath);

                if (!$deleteResult) {
                    $error = error_get_last();
                    return [
                        'success' => false,
                        'error' => Fastlang::_e('failed to delete file', $absolutePath),
                        'data' => [
                            'absolute_path' => $absolutePath,
                            'php_error' => $error
                        ]
                    ];
                }
            }

            return [
                'success' => true,
                'error' => null,
                'data' => [
                    'absolute_path' => $absolutePath,
                    'file_existed' => $fileExists,
                    'deleted' => $fileExists
                ]
            ];
        }
    }

    /**
     * Recursively delete all files and subfolders inside the parent folder of the specified file, then delete the parent folder itself.
     * 
     * Removes everything inside the parent directory, regardless of whether it is empty.
     * Performs security validation before deletion.
     *
     * @param string $filePath File path (relative or absolute) whose parent folder will be deleted
     * 
     * @return array Delete result with structure:
     *               - success: bool - Delete success status
     *               - error: string|null - Error message if any
     *               - data: mixed - Additional data (usually null)
     */
    public static function deleteWithParentFolder($filePath)
    {
        // Load language for static method
        Fastlang::load('files', APP_LANG);

        // First, try to delete the file itself
        $fileResult = self::deleteFile($filePath);

        // Then delete the parent directory
        $parentDir = dirname($filePath);
        $absoluteFile = self::_resolveAbsolutePath($filePath);
        $parentDir = dirname($absoluteFile);

        // Clean up double slashes in parent directory path
        $parentDir = str_replace(['//', '\\\\'], ['/', '\\'], $parentDir);

        if (!self::_canDeleteDirectory($parentDir)) {
            return [
                'success' => false,
                'error' => Fastlang::_e('parent directory cannot be deleted'),
                'data' => [
                    'file_result' => $fileResult,
                    'parent_dir' => $parentDir
                ]
            ];
        }

        $dirResult = self::deleteFolderRecursive($parentDir);

        return [
            'success' => $fileResult['success'] && $dirResult['success'],
            'error' => !$fileResult['success'] ? $fileResult['error'] : (!$dirResult['success'] ? $dirResult['error'] : null),
            'data' => [
                'file_result' => $fileResult,
                'directory_result' => $dirResult
            ]
        ];
    }

    /**
     * Recursively delete all files and subfolders in a directory, then delete the directory itself.
     * 
     * Removes all files and subdirectories within the specified directory.
     * Performs security validation before deletion.
     *
     * @param string $dir Absolute or relative directory path
     * 
     * @return array Delete result with structure:
     *               - success: bool - Delete success status
     *               - error: string|null - Error message if any
     *               - data: mixed - Additional data (usually null)
     */
    public static function deleteFolderRecursive($dir)
    {
        // Load language for static method
        Fastlang::load('files', APP_LANG);

        if (!self::_canDeleteDirectory($dir)) {
            return [
                'success' => false,
                'error' => Fastlang::_e('directory cannot be deleted'),
                'data' => null
            ];
        }

        return self::_deleteFolderRecursiveInternal($dir);
    }

    /**
     * Internal method to recursively delete directory contents.
     * 
     * Performs the actual recursive deletion of files and directories.
     * Called by deleteFolderRecursive() after security validation.
     *
     * @param string $dir Directory path to delete
     * 
     * @return array Delete result with structure:
     *               - success: bool - Delete success status
     *               - error: string|null - Error message if any
     *               - data: mixed - Additional data (usually null)
     */
    private static function _deleteFolderRecursiveInternal($dir)
    {
        // Resolve absolute path for directory
        $absoluteDir = self::_resolveAbsolutePath($dir);

        if (!is_dir($absoluteDir)) {
            return [
                'success' => true,
                'error' => null,
                'data' => [
                    'directory' => $absoluteDir,
                    'existed' => false
                ]
            ];
        }

        $files = array_diff(scandir($absoluteDir), ['.', '..']);
        $deletedFiles = 0;
        $deletedDirs = 0;
        $errors = [];

        foreach ($files as $file) {
            $path = $absoluteDir . DIRECTORY_SEPARATOR . $file;

            if (is_dir($path)) {
                $result = self::_deleteFolderRecursiveInternal($path);
                if (!$result['success']) {
                    return $result;
                }
                $deletedDirs++;
            } else {
                if (!unlink($path)) {
                    $error = error_get_last();
                    return [
                        'success' => false,
                        'error' => Fastlang::_e('failed to delete file', $path),
                        'data' => [
                            'file_path' => $path,
                            'php_error' => $error
                        ]
                    ];
                }
                $deletedFiles++;
            }
        }

        if (!rmdir($absoluteDir)) {
            $error = error_get_last();
            return [
                'success' => false,
                'error' => Fastlang::_e('failed to delete directory', $absoluteDir),
                'data' => [
                    'dir_path' => $absoluteDir,
                    'php_error' => $error
                ]
            ];
        }

        return [
            'success' => true,
            'error' => null,
            'data' => [
                'deleted_files' => $deletedFiles,
                'deleted_directories' => $deletedDirs,
                'directory' => $absoluteDir,
                'existed' => true
            ]
        ];
    }

    /**
     * Check if a file can be safely deleted (permissions and path safety).
     *
     * Validates that the file can be safely deleted by checking:
     * - File exists and is within allowed directories
     * - File is writable
     * - Parent directory is writable
     * - Path is not outside allowed directories (prevents directory traversal)
     * 
     * @param string $filePath Absolute file path to check
     * 
     * @return bool True if file can be safely deleted, false otherwise
     */
    private static function _canDeleteFile($filePath)
    {
        if (empty($filePath)) {
            return false;
        }

        // Normalize path separators
        $filePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $filePath);

        // If path is relative, make it absolute
        if (!Files::isAbsolutePath($filePath)) {
            $baseUpload = PATH_WRITE . 'uploads';
            $filePath = rtrim($baseUpload, '/\\') . DIRECTORY_SEPARATOR . ltrim($filePath, '/\\');
        }

        // Resolve real path to prevent symlink attacks
        $realFilePath = realpath($filePath);
        if ($realFilePath === false) {
            $realFilePath = $filePath;
        }

        // Check if path is within allowed directories using realpath
        $allowedDirs = [
            PATH_WRITE . 'uploads',
            PATH_ROOT . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads',
        ];

        // Also check for subdirectories of uploads
        $uploadsDir = PATH_WRITE . 'uploads';
        $publicUploadsDir = PATH_ROOT . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads';

        $fileDir = dirname($realFilePath);
        $isAllowed = false;

        // Check if file is within uploads directory
        if (strpos($realFilePath, $uploadsDir) === 0 || strpos($realFilePath, $publicUploadsDir) === 0) {
            $isAllowed = true;
        }

        // Also check against allowed directories
        foreach ($allowedDirs as $allowedDir) {
            $realAllowedDir = realpath($allowedDir);
            if ($realAllowedDir && ($fileDir === $realAllowedDir || strpos($fileDir, $realAllowedDir . DIRECTORY_SEPARATOR) === 0)) {
                $isAllowed = true;
                break;
            }
        }

        if (!$isAllowed) {
            return false;
        }

        // Check if file exists
        if (!file_exists($realFilePath)) {
            return true; // File doesn't exist, so "can delete" it
        }

        // Check if it's actually a file, not a directory
        if (is_dir($realFilePath)) {
            return false; // This is a directory, not a file
        }

        // Check if file is writable
        if (!is_writable($realFilePath)) {
            return false;
        }

        // Check if parent directory is writable
        $parentDir = dirname($realFilePath);
        if (!is_writable($parentDir)) {
            return false;
        }

        return true;
    }

    /**
     * Check if a directory can be safely deleted (permissions and path safety).
     *
     * Validates that the directory can be safely deleted by checking:
     * - Directory exists and is within allowed directories
     * - Directory is writable
     * - Parent directory is writable
     * - Path is not outside allowed directories (prevents directory traversal)
     * 
     * @param string $dirPath Absolute directory path to check
     * 
     * @return bool True if directory can be safely deleted, false otherwise
     */
    private static function _canDeleteDirectory($dirPath)
    {
        if (empty($dirPath)) {
            return false;
        }

        // Normalize path separators
        $dirPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dirPath);

        // If path is relative, make it absolute
        if (!Files::isAbsolutePath($dirPath)) {
            $baseUpload = PATH_WRITE . 'uploads';
            $dirPath = rtrim($baseUpload, '/\\') . DIRECTORY_SEPARATOR . ltrim($dirPath, '/\\');
        }

        // Resolve real path to prevent symlink attacks
        $realDirPath = realpath($dirPath);
        if ($realDirPath === false) {
            // If realpath fails, try the original path
            $realDirPath = $dirPath;
        }

        // Check if path is within allowed directories using realpath
        $allowedDirs = [
            PATH_WRITE . 'uploads',
            PATH_ROOT . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads',
            PATH_ROOT . DIRECTORY_SEPARATOR . 'plugins',
            PATH_ROOT . DIRECTORY_SEPARATOR . 'themes',
        ];

        // Also check for subdirectories of allowed directories
        $uploadsDir = PATH_WRITE . 'uploads';
        $publicUploadsDir = PATH_ROOT . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads';
        $pluginsDir = PATH_ROOT . DIRECTORY_SEPARATOR . 'plugins';
        $themesDir = PATH_ROOT . DIRECTORY_SEPARATOR . 'themes';

        $isAllowed = false;

        // Check if directory is within allowed directories
        if (
            strpos($realDirPath, $uploadsDir) === 0 ||
            strpos($realDirPath, $publicUploadsDir) === 0 ||
            strpos($realDirPath, $pluginsDir) === 0 ||
            strpos($realDirPath, $themesDir) === 0
        ) {
            $isAllowed = true;
        }

        // Also check against allowed directories
        foreach ($allowedDirs as $allowedDir) {
            $realAllowedDir = realpath($allowedDir);
            if ($realAllowedDir && ($realDirPath === $realAllowedDir || strpos($realDirPath, $realAllowedDir . DIRECTORY_SEPARATOR) === 0)) {
                $isAllowed = true;
                break;
            }
        }

        if (!$isAllowed) {
            return false;
        }

        // Check if directory exists
        if (!is_dir($realDirPath)) {
            return false;
        }

        // Check if directory is writable
        if (!is_writable($realDirPath)) {
            return false;
        }

        // Check if parent directory is writable
        $parentDir = dirname($realDirPath);
        if (!is_writable($parentDir)) {
            return false;
        }

        return true;
    }

    /**
     * Resolve absolute path from relative or absolute path.
     * 
     * @param string $path File path (relative or absolute)
     * @return string Absolute path
     */
    private static function _resolveAbsolutePath($path)
    {
        // Clean up double slashes and normalize path
        $cleanPath = str_replace(['//', '\\\\'], ['/', '\\'], $path);

        if (Files::isAbsolutePath($cleanPath)) {
            return realpath($cleanPath) ?: $cleanPath;
        }

        // Fix: Use PATH_WRITE directly instead of PATH_ROOT + PATH_WRITE
        $baseUpload = PATH_WRITE . 'uploads';
        $absolutePath = rtrim($baseUpload, '/\\') . DIRECTORY_SEPARATOR . ltrim($cleanPath, '/\\');

        return realpath($absolutePath) ?: $absolutePath;
    }
}
