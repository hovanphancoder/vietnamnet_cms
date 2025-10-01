<?php

namespace App\Controllers\Api\V1;

use App\Controllers\ApiController;
use App\Models\FilesModel;
use System\Core\AppException;

use App\Libraries\Fastlang as Flang;
use Exception;
use System\Libraries\Files;

class FilesController extends ApiController
{
    // File properties
    protected $base_dir;
    protected $allowed_types;
    protected $images_types;
    protected $max_file_size;
    protected $max_file_count;
    protected $limit;

    protected $filesModel;
    protected $user;


    public function __construct()
    {
        parent::__construct();
        // Verify User first.
        // $this->user = $this->_auth();
        $this->filesModel = new FilesModel();
        // set files properties
        $config_files = config('files');
        $uploads_dir = $config_files['path'] ?? 'writeable/uploads';
        $this->base_dir = realpath(rtrim(PATH_ROOT, '/') . DIRECTORY_SEPARATOR . trim($uploads_dir, '/') . DIRECTORY_SEPARATOR);
        $this->allowed_types = $config_files['allowed_types'] ?? ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'docx', 'doc', 'xls', 'xlsx', 'csv', 'ppt', 'pptx', 'txt', 'rar', 'zip', 'iso', 'mp3', 'wav', 'mkv', 'mp4', 'srt'];
        $this->images_types = $config_files['images_types'] ?? ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $this->max_file_size = $config_files['max_file_size'] ?? 10485760; // 10MB mặc định
        $this->max_file_count = $config_files['max_file_count'] ?? 10; // Số lượng file tối đa
        $this->limit = $config_files['limit'] ?? 40; // Giới hạn số lượng tệp tin trên mỗi trang phân trang
        if (empty($this->base_dir)) {
            throw new AppException('Uploads directory does not exist.');
        }
    }
    public function index()
    {
        $this->_check_permission('index');
        try {
            // Get query parameters
            $page = (int) (S_GET('page') ?? 1);
            $limit = (int) (S_GET('limit') ?? $this->limit);
            $sort = S_GET('sort') ?? 'created_at_desc';
            $search = S_GET('q') ?? '';
            $isOnlyImage = S_GET('type') ?? false;

            // Prepare sorting
            $orderBy = Files::getOrderBy($sort);

            // Prepare search condition
            $where = '';
            $params = [];

            if (!empty($search)) {
                $where = 'name LIKE ?';
                $params[] = '%' . $search . '%';
            }
            if ($isOnlyImage && !empty($this->images_types)) {
                if ($where != '') {
                    $where .= ' AND ';
                }
                $typeString = implode(',', array_fill(0, count($this->images_types), '?')); // ?,?,?
                $where .= " type IN ($typeString)";
                $params = array_merge($params, $this->images_types);
            }

            // Fetch files from the database
            $filesPage = $this->filesModel->getFiles($where, $params, $orderBy, $page, $limit);

            // Prepare response data
            $responseData = [
                'items' => $filesPage['data'],
                'isnext' => $filesPage['is_next'],
                'page' => $filesPage['page']
            ];

            // Return JSON response
            $this->success($responseData, 'Files retrieved successfully.');
        } catch (\Exception $e) {
            $this->error($e->getMessage(), [], 500);
        }
    }

    /* --------------------------------------------------------------------------
    *  Upload files – MIGRATED to new Upload library
    * -------------------------------------------------------------------------- */
    public function upload()
    {
        $this->_check_permission('add');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->error('Only POST method is allowed', [], 405);
        }
        if (empty($_FILES['files']['name'])) {
            return $this->error('No files uploaded', [], 400);
        }

        $config = json_decode($_POST['config'] ?? '', true) ?? [];
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->error('Invalid config', [], 400);
        }

        $pathReq   = $_POST['path'] ?? '';
        $dbprefixFolder  = Files::sanitizeFolderPath(str_replace(':', '/', $pathReq));

        // Process configuration using Files library
        $config = Files::processUploadConfig($config);

        // Use Files::upload for all uploads (single/multiple)
        $uploadResult = Files::upload($_FILES['files'], array_merge(['folder' => $dbprefixFolder], $config));
        if (!$uploadResult['success']) {
            return $this->error($uploadResult['error'] ?? 'Upload failed', $uploadResult['data'] ?? [], 400);
        }
        return $this->success(['uploaded_files' => $uploadResult['data']], 'Files uploaded successfully.');
    }


    /**
     * New endpoint to check chunk upload progress
     */
    public function chunk_progress()
    {
        $uploadId = S_GET('uploadId');
        if (empty($uploadId)) {
            return $this->error('Upload ID is required', [], 400);
        }

        $progress = Files::getChunkUploadProgress($uploadId);

        if (!$progress['success']) {
            return $this->error($progress['error'], [], 400);
        }

        return $this->success($progress['data'], 'Progress retrieved successfully.');
    }

    // DEPRECATED: Database operations now handled by Files library
    // private function _dbAdd($name, $prefix, $size, $ext, $resize = '') { ... } // REMOVED
    // DEPRECATED: Use Files::getErrorMessage() instead
    // private function _errorMessage($error_code) { ... } // REMOVED - use Files::getErrorMessage()
    // DEPRECATED: File moving now handled by Files library
    // private function _movefile($file_source, $file_dest) { ... } // REMOVED

    public function rename_file()
    {
        // MIGRATED: Use new Upload library
        $this->_check_permission('edit');

        try {
            $id = S_POST('id');
            $type = S_POST('type');
            $newName = Files::sanitizeFileName(S_POST('newname'));

            if (empty($id) || empty($type) || empty($newName)) {
                throw new AppException('Missing required parameters.');
            }

            $file = $this->filesModel->getFileById($id);
            if (!$file || empty($file['path'])) {
                return $this->error(Flang::_e('file not found in database'), [], 500);
            }

            // Use new library
            $result = Files::rename($file, $newName);

            if (!$result['success']) {
                return $this->error($result['error'], [], 500);
            }

            // Update database
            $this->filesModel->updateFile($id, [
                'name' => $result['data']['name'],
                'path' => $result['data']['path'],
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            $this->success(['data' => array_merge($file, $result['data'])], 'Item renamed successfully.');
        } catch (\Exception $e) {
            $this->error($e->getMessage(), [], 500);
        }
    }


    // DEPRECATED: Use Files::resolvePath() instead
    // private function _fullPath($path) { ... } // REMOVED - use Files::resolvePath()
    // DEPRECATED: Path generation now handled by Files library
    // private function _sizePath($fullpath, $size, $type = 'jpg') { ... } // REMOVED
    // DEPRECATED: Use Files::removeExtension() instead
    // private function _removeExtension($filePath) { ... } // REMOVED - use Files::removeExtension()

    // DEPRECATED: Use Files::getUniqueName() instead
    // private function _uniqueName(string $dir, string $base, string $ext): string { ... } // REMOVED - use Files::getUniqueName()
    // DEPRECATED: Use Files::sanitizeFileName() instead
    // private function _sanitizeFileName($name) { ... } // REMOVED - use Files::sanitizeFileName()
    // DEPRECATED: Use Files::sanitizeFolderPath() instead
    // private function _sanitizeFolderName(string $folder): string { ... } // REMOVED - use Files::sanitizeFolderPath()
    // DEPRECATED: Folder validation now handled by Files library
    // private function _validFolder($dir) { ... } // REMOVED

    // DEPRECATED: Use Files::isValidFileName() instead
    // private function _validFileName(string $filename) { ... } // REMOVED - use Files::isValidFileName()

    private function _check_permission($method)
    {
        return true;
        $user = $this->user;
        $permissions = is_string($user['permissions']) ? json_decode($user['permissions'], true) : $user['permissions'];
        $permissions = $permissions['Api\Files'];
        if (in_array($method, $permissions)) {
            return true;
        } else {
            return $this->error(Flang::_e('permission_denied'), [], 403);
        }
    }
    // DEPRECATED: Use Files::getOrderBy() instead
    // private function _getOrderBy($sort) { ... } // REMOVED - use Files::getOrderBy()

    /**
     * DEPRECATED: Process images according to provided configuration
     * MIGRATED TO: Files::optimize() in new Upload library
     * @deprecated Use Files::optimize() instead
     */
    // private function _processImage($config, $sourceFile) { ... } // REMOVED - use Files::optimize()

    /**
     * Apply watermark to image
     * MIGRATED TO: Files::optimize() with watermark config
     * @deprecated Use Files::optimize() with watermark options instead
     */
    // private function _applyWatermark($image, $watermarkConfig = []) { ... } // REMOVED - use Files::optimize()

    /**
     * Create output path for processed images
     * MIGRATED TO: Files::getOutputPath()
     * @deprecated Use Files::getOutputPath() instead
     */
    // private function _getOutputPath($sourceFile, $size, $format = null) { ... } // REMOVED - use Files::getOutputPath()

    /**
     * Create temporary file from base64 data
     * @param string $base64Data Base64 encoded image data
     * @param string $type Image type (jpg, png, etc.)
     * @return string Path to temporary file
     */
    private function _createTempFileFromBase64($base64Data, $type)
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'upload_') . '.' . $type;
        $decodedData = base64_decode($base64Data);
        file_put_contents($tempFile, $decodedData);
        return $tempFile;
    }

    /**
     * Receive JSON (format as example) and save all images + resize versions.
     * By default, always ensure **one** unique base-name for all resize versions:
     *   swim.jpg  → swim_1.jpg, swim_1_300x200.jpg, swim_1_300x200.jpg.webp …
     */
    /**
     * Save base64 images with optimization using Files library
     * OPTIMIZED: Uses Files library for all operations
     */
    public function saves()
    {
        // Read and validate input
        $payload = json_decode(file_get_contents('php://input'), true);
        if (!$payload || !isset($payload['images']) || !is_array($payload['images'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid data']);
            return;
        }

        $outputPath = $payload['path'] ?? date('Y/m/d');
        $imagesData = $payload['images'];
        $savedFiles = [];
        $originalRecords = [];

        foreach ($imagesData as $sizeKey => $group) {
            if (!is_array($group)) continue;

            // Choose best item (prioritize non-webp)
            $chosen = null;
            foreach ($group as $item) {
                if (!isset($item['data'], $item['filename'], $item['type'], $item['size'])) {
                    continue;
                }
                if (strtolower($item['type']) !== 'webp') {
                    $chosen = $item;
                    break;
                }
                $chosen = $item;
            }
            if (!$chosen) continue;

            try {
                // Create temp file from base64
                $tempFile = $this->_createTempFileFromBase64($chosen['data'], $chosen['type']);

                // Prepare file array for Files library
                $fileArray = [
                    'name' => $chosen['filename'],
                    'type' => 'image/' . $chosen['type'],
                    'tmp_name' => $tempFile,
                    'error' => 0,
                    'size' => filesize($tempFile)
                ];

                // Prepare options for Files library
                $options = [
                    'folder' => str_replace(':', '/', $outputPath),
                    'quality' => $chosen['quality'] ?? 90
                ];

                // Add resize config if not original
                if (strtolower($sizeKey) !== 'original' && strpos($sizeKey, 'x') !== false) {
                    [$width, $height] = array_map('intval', explode('x', $sizeKey));
                    $options['resizes'] = [['width' => $width, 'height' => $height]];
                }

                // Use Files library for upload and optimization
                $result = Files::upload($fileArray, $options, true);

                if ($result['success']) {
                    $savedFiles[] = $result['data']['path'];

                    // Record original file for database
                    if (strtolower($sizeKey) === 'original') {
                        $originalRecords[] = [
                            'id' => $result['data']['db']['id'],
                            'name' => $result['data']['name'],
                            'path' => $result['data']['path'],
                            'pth' => $outputPath,
                        ];
                    }
                } else {
                    $savedFiles[] = 'Error: ' . $result['error'];
                }

                // Cleanup temp file
                @unlink($tempFile);
            } catch (\Exception $e) {
                $savedFiles[] = 'Error: ' . $e->getMessage();
            }
        }

        echo json_encode([
            'status' => 'success',
            'files' => array_values(array_unique($savedFiles)),
            'original_db' => $originalRecords,
        ]);
    }

    /**
     * Upload file from URL using Files library
     * OPTIMIZED: Uses Files library for all operations
     */
    public function uploadbylink()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->error('Only POST method is allowed', [], 405);
        }

        $url = $_POST['url'] ?? '';
        $sizes = $_POST['sizes'] ?? [];
        $name = $_POST['name'] ?? '';
        $type = $_POST['type'] ?? 'webp';
        $quality = (int)($_POST['quality'] ?? 90);
        $path = $_POST['path'] ?? date('Y/m/d');

        if (empty($url)) {
            return $this->error('URL is required', [], 400);
        }

        // Decode sizes if it's a string
        if (is_string($sizes)) {
            $sizes = json_decode($sizes, true) ?? [];
        }

        // Prepare options for Files library
        $options = [
            'custom_name' => $name,
            'type' => $type,
            'quality' => $quality,
            'sizes' => $sizes  // Files library handles this directly
        ];

        try {
            // Use Files library for download and processing
            $result = Files::downloadFromUrl($url, $path, $options);

            if (!$result['success']) {
                return $this->error($result['error'], [], 400);
            }

            // Convert response to old format for backward compatibility
            $uploaded = $this->_convertToOldFormat($result['data']);

            return $this->success(['uploaded_files' => $uploaded], 'File uploaded successfully.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), [], 500);
        }
    }





    // DEPRECATED: Use Files::buildFileName() instead
    // private function _buildFileName(string $base, string $sizeKey, string $ext): string { ... } // REMOVED - use Files::buildFileName()


    // MIGRATED: Delete multiple items using new Upload library
    public function delete_multiple()
    {
        if (!HAS_POST('items')) {
            return $this->error('Only DELETE method is allowed', [], 405);
        }

        $items = $_POST['items'];
        $items = is_string($items) ? json_decode($items, true) : $items;

        if (empty($items)) {
            return $this->error('No items provided', [], 400);
        }

        $deleteIds = [];
        $errors = [];

        foreach ($items as $item) {
            $id = $item['id'];
            $itemArray = $this->filesModel->getFileById($id);

            if (empty($itemArray) || !isset($itemArray['path'])) {
                $errors[] = "File ID {$id} not found in database";
                continue;
            }

            try {
                // Try to delete file from filesystem (may fail if file doesn't exist)
                $result = Files::deleteFile($itemArray);

                // If file deletion succeeded, clean up empty parent folders
                if ($result['success']) {
                    $this->_cleanupEmptyFolders($itemArray['path']);
                }

                if (!$result['success']) {
                    // Log warning but continue - file might not exist on filesystem
                    error_log("Warning: Failed to delete file from filesystem for ID {$id}: " . $result['error']);
                }

                // Always try to delete from database (cleanup orphaned records)
                $dbResult = $this->filesModel->deleteFile($id);

                if ($dbResult) {
                    $deleteIds[$id] = true;
                } else {
                    $errors[] = "Failed to delete file ID {$id} from database";
                }
            } catch (\Exception $e) {
                // Log error but still try to delete from database
                error_log("Exception deleting file {$id}: " . $e->getMessage());

                $dbResult = $this->filesModel->deleteFile($id);
                if ($dbResult) {
                    $deleteIds[$id] = true;
                } else {
                    $errors[] = "Failed to delete file ID {$id} from database after exception";
                }
            }
        }

        if (!empty($deleteIds)) {
            $response = [
                'deleted_ids' => array_keys($deleteIds),
                'total_deleted' => count($deleteIds),
                'total_requested' => count($items)
            ];

            if (!empty($errors)) {
                $response['warnings'] = $errors;
            }

            $this->success($response, 'Items deleted successfully.');
        } else {
            $this->error('Failed to delete any items', ['errors' => $errors], 500);
        }
    }

    // MIGRATED: Delete item using new Upload library
    public function delete()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->error('Only POST method is allowed', [], 405);
        }

        $id = S_POST('id');
        $path = S_POST('path');

        if (empty($id) || empty($path)) {
            return $this->error('ID and path are required', [], 400);
        }

        $itemArray = $this->filesModel->getFileById($id);
        if (empty($itemArray)) {
            return $this->error('File not found in database', [], 404);
        }

        try {
            // Try to delete file from filesystem (may fail if file doesn't exist)
            $result = Files::deleteFile($itemArray);

            if (!$result['success']) {
                // Log warning but continue - file might not exist on filesystem
                error_log("Warning: Failed to delete file from filesystem for ID {$id}: " . $result['error']);
            }

            // Always try to delete from database (cleanup orphaned records)
            $dbResult = $this->filesModel->deleteFile($id);
            if ($dbResult) {
                // Clean up empty parent folders if file deletion succeeded
                if ($result['success']) {
                    $this->_cleanupEmptyFolders($itemArray['path']);
                }

                $response = [
                    'id' => $id,
                    'filesystem_deleted' => $result['success'],
                    'database_deleted' => true
                ];

                if (!$result['success']) {
                    $response['warning'] = 'File not found on filesystem, but record removed from database';
                }

                $this->success($response, 'Item deleted successfully.');
            } else {
                $this->error('Failed to delete item from database', [], 500);
            }
        } catch (\Exception $e) {
            // Log error but still try to delete from database
            error_log("Exception deleting file {$id}: " . $e->getMessage());

            $dbResult = $this->filesModel->deleteFile($id);
            if ($dbResult) {
                $response = [
                    'id' => $id,
                    'filesystem_deleted' => false,
                    'database_deleted' => true,
                    'warning' => 'Exception occurred during filesystem deletion, but record removed from database'
                ];
                $this->success($response, 'Item deleted successfully.');
            } else {
                $this->error('Failed to delete item from database after exception', [], 500);
            }
        }
    }






    // DEPRECATED: Use Files::toSlug() instead
    // private function _toSlug($str) { ... } // REMOVED - use Files::toSlug()

    // DEPRECATED: Debug method removed - no longer needed
    // public function debug_delete() { ... } // REMOVED

    /**
     * Clean up empty parent folder after file deletion (only direct parent, not recursive)
     */
    private function _cleanupEmptyFolders($filePath)
    {
        try {
            // Get parent folder path (only direct parent)
            $parentDir = dirname($filePath);

            // Convert to absolute path for checking
            $absoluteParentDir = $this->base_dir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $parentDir);

            // Check if parent folder is empty
            if (is_dir($absoluteParentDir)) {
                $files = array_diff(scandir($absoluteParentDir), ['.', '..']);

                if (empty($files)) {
                    // Folder is empty, try to delete it
                    rmdir($absoluteParentDir);
                }
            }
        } catch (\Exception $e) {
            // Silent fail - don't log errors for folder cleanup
        }
    }

    // DEPRECATED: Debug logging removed - no longer needed
    // private function _logDebug($message) { ... } // REMOVED

    /**
     * ===================================
     * NEW MIGRATION HELPER METHODS
     * ===================================
     */

    // DEPRECATED: Use Files::processUploadConfig() instead
    // private function _mapConfigToNewFormat($config) { ... } // REMOVED - use Files::processUploadConfig()

    /**
     * Convert response from new library to old format to maintain backward compatibility
     */
    private function _convertToOldFormat($data)
    {
        $uploaded = [];

        // Handle different response structures
        if (isset($data['name'])) {
            // Single file - convert to array
            $data = [$data];
        } elseif (isset($data[0]['success'])) {
            // Multiple files with success wrapper
            $cleanData = [];
            foreach ($data as $item) {
                if (isset($item['data'])) {
                    $cleanData[] = $item['data'];
                }
            }
            $data = $cleanData;
        }

        foreach ($data as $fileInfo) {
            // Extract resize info from optimized data
            $resize = '';
            if (!empty($fileInfo['optimized']['data']['resizes'])) {
                $sizes = [];
                foreach ($fileInfo['optimized']['data']['resizes'] as $resizePath) {
                    // Extract size from filename like "test_300x200.jpg"
                    if (preg_match('/_(\d+x\d+)\./', basename($resizePath), $matches)) {
                        $sizes[] = $matches[1];
                    }
                }
                $resize = implode(';', $sizes);
            }

            // Convert absolute path to relative path
            $relativePath = $fileInfo['path'];
            if (strpos($relativePath, $this->base_dir) === 0) {
                $relativePath = str_replace($this->base_dir . '/', '', $relativePath);
                $relativePath = str_replace('\\', '/', $relativePath);
            }

            $uploaded[] = [
                'id' => $fileInfo['db']['id'] ?? null,
                'name' => $fileInfo['name'],
                'path' => $relativePath,
                'pth' => dirname($relativePath),
                'resize' => $resize
            ];
        }

        return $uploaded;
    }
}
