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
        // $this->user = $this->_authentication();
        $this->filesModel = new FilesModel();
        // set files properties
        $config_files = config('files');
        $uploads_dir = $config_files['path'] ?? 'writeable/uploads';
        $this->base_dir = realpath(rtrim(PATH_ROOT, '/') . DIRECTORY_SEPARATOR . trim($uploads_dir, '/') . DIRECTORY_SEPARATOR);
        $this->allowed_types = $config_files['allowed_types'] ?? ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'docx', 'doc', 'xls', 'xlsx', 'csv', 'ppt', 'pptx', 'txt', 'rar', 'zip', 'iso' , 'mp3', 'wav', 'mkv', 'mp4', 'srt'];
        $this->images_types = $config_files['images_types'] ?? ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $this->max_file_size = $config_files['max_file_size'] ?? 10485760; // 10MB mặc định
        $this->max_file_count = $config_files['max_file_count'] ?? 10; // Số lượng file tối đa
        $this->limit = $config_files['limit'] ?? 40; // Giới hạn số lượng tệp tin trên mỗi trang phân trang
        if (empty($this->base_dir)) {
            throw new AppException('Uploads directory does not exist.');
        }
    }
    public function index() {
        $this->_check_permission('index');
        try {
            // Get query parameters
            $page = (int) (S_GET('page') ?? 1);
            $limit = (int) (S_GET('limit') ?? $this->limit);
            $sort = S_GET('sort') ?? 'created_at_desc';
            $search = S_GET('q') ?? '';
            $isOnlyImage = S_GET('type') ?? false;

            // Prepare sorting
            $orderBy = $this->_getOrderBy($sort);

            // Prepare search condition
            $where = '';
            $params = [];

            if (!empty($search)) {
                $where = 'name LIKE ?';
                $params[] = '%' . $search . '%';
            }
            if ($isOnlyImage && !empty($this->images_types)){
                if ($where != ''){
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

        // Prepare options for new library
        $uploadOptions = $this->_mapConfigToNewFormat($config);
        $uploadOptions['folder'] = $this->_sanitizeFolderName($_POST['path'] ?? date('Y/m/d'));

        // Check for existing files and generate unique names
        $uploadDir = $this->base_dir . '/' . str_replace('/', DIRECTORY_SEPARATOR, $uploadOptions['folder']);
        $uniqueFiles = [];
        
        foreach ($_FILES['files']['name'] as $index => $originalName) {
            $uniqueName = $this->checkFileExists($originalName, $uploadDir, $uploadOptions['folder']);
            $_FILES['files']['name'][$index] = $uniqueName;
        }
        
        try {
            // ===== CHUNK UPLOAD CHECK =====
                if (isset($_POST['chunkIndex'], $_POST['totalChunks'])) {
                $chunkInfo = [
                    'uploadId' => $_POST['uploadToken'] ?? uniqid('upload_'),
                    'chunk' => (int)$_POST['chunkIndex'],
                    'chunks' => (int)$_POST['totalChunks'],
                    'filename' => $_POST['originalFileName'] ?? $_FILES['files']['name'][0]
                ];
                
                $chunkResult = Files::uploadChunk($chunkInfo, $_FILES['files'], $uploadOptions);
                
                if (!$chunkResult['success']) {
                    return $this->error($chunkResult['error'], [], 400);
                }
                
                // If upload completed, return file information
                if (!empty($chunkResult['data']['completed']) && !empty($chunkResult['data']['file'])) {
                    $uploaded = $this->_convertToOldFormat($chunkResult['data']['file']);
                    return $this->success(['uploaded_files' => $uploaded], 'Chunk upload completed successfully.');
                        } else {
                    // Chunk upload in progress
                    return $this->success([
                        'chunk_uploaded' => true,
                        'progress' => $chunkResult['data']
                    ], 'Chunk uploaded successfully.');
                }
            }

            // ===== NORMAL UPLOAD =====
            // Use new library
            $result = Files::upload($_FILES['files'], $uploadOptions, true);
            
            if (!$result['success']) {
                return $this->error($result['error'], [], 400);
            }

            // Convert response to match old format
            $uploaded = $this->_convertToOldFormat($result['data']);

            return $this->success(['uploaded_files' => $uploaded], 'Files uploaded successfully.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), [], 500);
        }
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

    private function _dbAdd($name, $prefix, $size, $ext, $resize = '')
    {
        $dbPath = $prefix ? "{$prefix}/{$name}" : $name;
        $addItem = [
            'name'       => $name,
            'path'       => $dbPath,
            'size'       => $size,
            'type'       => $ext,
            'autoclean'  => 0,
            'created_at' => DateTime(),
            'updated_at' => DateTime(),
            'resize'     => $resize,
        ];
        $id = $this->filesModel->addFile($addItem);
        if (!$id) return $this->error(Flang::_e('failed to insert file info into database'), [], 500);
        $addItem['id'] = $id;
        return ['id' => $id, 'name' => $name, 'path' => $dbPath, 'pth' => $prefix, 'resize' => $resize];
    }
    private function _errorMessage($error_code)
    {
        switch ($error_code) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return 'File exceeds the allowed size';
            case UPLOAD_ERR_PARTIAL:
                return 'File was only partially uploaded';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing temporary folder';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk';
            case UPLOAD_ERR_EXTENSION:
                return 'File upload stopped by a PHP extension';
            default:
                return 'Unknown upload error';
        }
    }
    private function _movefile($file_source, $file_dest) {
        if (move_uploaded_file($file_source, $file_dest)) {
            return true;
        } else {
            return false;
        }
    }

    public function rename_file()
    {
        // MIGRATED: Use new Upload library
        $this->_check_permission('edit');

        try {
            $id = S_POST('id');
            $type = S_POST('type');
            $newName = $this->_sanitizeFileName(S_POST('newname'));

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

    
    private function _fullPath($path) {
        return $this->base_dir . '/' . $path;
    }
    private function _sizePath($fullpath, $size, $type = 'jpg'){
        $ext = pathinfo($fullpath, PATHINFO_EXTENSION);
        if ($ext !== '') {
            $fullpath = substr($fullpath, 0, -(strlen($ext) + 1));
        }
        return $fullpath . '_' . $size . '.'.$type;
    }
    private function _removeExtension($filePath) {
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);
        if ($ext !== '') {
            return substr($filePath, 0, -(strlen($ext) + 1));
        }
        return $filePath;
    }

    private function _uniqueName(string $dir, string $base, string $ext): string
    {
        $name  = "{$base}.{$ext}";
        $count = 1;
        while (file_exists($dir . DIRECTORY_SEPARATOR . $name)) {
            $name = "{$base}_{$count}.{$ext}";
            $count++;
        }
        return $name;
    }
    private function _sanitizeFileName($name)
    {
        $name = preg_replace('/\.php\./i', '.', $name);
        $name = preg_replace('/\.php$/i', '.', $name);
        $name = preg_replace('/[^.\p{L}\p{N}\s\-_]+/u', '_', $name);
        $name = preg_replace('/\s+/', '_', $name);
        $name = preg_replace('/_+/', '_', $name);
        $name = trim($name, '_');
        return $name;
    }
    /**
     * Keep a-z A-Z 0-9 _ - :
     */
    private function _sanitizeFolderName(string $folder): string
    {
        // For date-based paths, preserve directory structure
        if (preg_match('/^\d{4}[:\/]\d{2}[:\/]\d{2}$/', $folder)) {
            // Convert : to / for consistency
            $folder = str_replace(':', '/', $folder);
            return $folder;
        }
        
        // For other paths, apply sanitization
        $folder = preg_replace('/[^a-zA-Z0-9_\-:\/]+/', '_', $folder);
        $folder = preg_replace('/_+/', '_', $folder);
        return trim($folder, '_:-');
    }
    private function _validFolder($dir){
        if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            $this->error(Flang::_e('can not create folder').' :'.$dir, [], 403);
        }
    }

    private function _validFileName(string $filename)
    {
        return (bool) preg_match('/\A[a-zA-Z0-9._-]+\z/', $filename);
    }

    private function _check_permission($method) {
        return true;
        $user = $this->user;
        //print_r($method);
        $permissions = is_string($user['permissions']) ? json_decode($user['permissions'], true) : $user['permissions'];
        $permissions = $permissions['Api\Files'];
        if(in_array($method, $permissions)) {
            return true;
        } else {
            return $this->error(Flang::_e('permission_denied'), [], 403);
        }
    }
    private function _getOrderBy($sort) {
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
            case 'name':
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
    public function saves()
    {
        /*---------------------------------------------------------------
        * 0. Read data & validation
        *-------------------------------------------------------------*/
        $payload = json_decode(file_get_contents('php://input'), true);
        if (!$payload || !isset($payload['images']) || !is_array($payload['images'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid data']);
            return;
        }

        /*---------------------------------------------------------------
        * 1. Configuration – save directory
        *-------------------------------------------------------------*/
        $cfg           = config('files');
        $allowed_types = $cfg['allowed_types'] ?? ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        // Parent directory path (example: 2025/05/02)
        $outputPath    = $payload['path'] ?? date('Y/m/d');
        // For date-based paths, don't use _cleanPath to preserve directory structure
        $pathDatabase  = $outputPath;    // 2025/05/02
        $folderReal    = str_replace('/', DIRECTORY_SEPARATOR, $outputPath);
        $uploadDir     = $this->base_dir . DIRECTORY_SEPARATOR . $folderReal;

        if (!is_dir($uploadDir) && !$this->_createFolder($outputPath)) {
            http_response_code(500);
            echo json_encode(['error' => 'Unable to create destination directory']);
            return;
        }

        /*---------------------------------------------------------------
        * 2. Image processing loop
        *-------------------------------------------------------------*/
        $imagesData      = $payload['images'];
        $savedFiles      = [];
        $originalRecords = [];
        $resizeList      = '';           // "300x200;600x400"
        $finalBaseName   = null;         // cry_1
        $finalExt        = null;

        foreach ($imagesData as $sizeKey => $group) {
            if (!is_array($group)) continue;

            /*-------------------------------------------------------
            * 2.0 Choose only 1 item to process for $sizeKey
            *      – Prioritize non-webp items
            *-----------------------------------------------------*/
            $chosen = null;
            foreach ($group as $item) {
                if (
                    !isset($item['data'], $item['filename'], $item['type'], $item['size']) ||
                    !in_array(strtolower($item['type']), $allowed_types, true)
                ) {
                    continue;
                }
                if (strtolower($item['type']) !== 'webp') {
                    // Found non-webp file → select immediately and stop searching
                    $chosen = $item;
                    break;
                }
                // Temporarily keep webp version, but continue searching for non-webp
                $chosen = $item;
            }
            // No valid item found
            if (!$chosen) continue;

            // Record resizeList (once per sizeKey)
            if (strpos($sizeKey, 'x') !== false) {
                $resizeList .= ($resizeList ? ';' : '') . $sizeKey;
            }

            /*-------------------------------------------------------
            * 2.1   Variables for selected item
            *-----------------------------------------------------*/
            $type        = strtolower($chosen['type']);     // jpg|png|gif|webp
            $rawFilename = $chosen['filename'];

            /* Determine unique baseName (only once) */
            if ($finalBaseName === null) {
                // Check if file exists and generate unique name
                $uniqueFilename = $this->checkFileExists($rawFilename, $uploadDir, $outputPath);
                $info = pathinfo($uniqueFilename);
                $finalBaseName = $info['filename'];
                $finalExt = strtolower($info['extension'] ?? 'jpg');
            }

            /* Target file name */
            $filename = $this->_buildFileName($finalBaseName, $sizeKey, ($type === 'webp' ? $finalExt : $type));
            $destPath = $uploadDir . DIRECTORY_SEPARATOR . $filename;

            /* Size, quality */
            $desiredSize = (strpos($sizeKey, 'x') !== false) ? $sizeKey : $chosen['size'];
            [$targetW, $targetH] = array_map('intval', explode('x', $desiredSize) + [0, 0]);
            $quality = isset($chosen['quality']) ? (int) $chosen['quality'] : 90;

            /*-------------------------------------------------------
            * 2.2   Process & save using new library
            *-----------------------------------------------------*/
            try {
                // Decode base64 data và tạo temporary file
                $tempFile = $this->_createTempFileFromBase64($chosen['data'], $chosen['type']);
                
                // Chuẩn bị file array cho thư viện mới
                $fileArray = [
                    'name' => $chosen['filename'],
                    'type' => 'image/' . $chosen['type'],
                    'tmp_name' => $tempFile,
                    'error' => 0,
                    'size' => filesize($tempFile)
                ];
                
                // Chuẩn bị options cho thư viện mới
                $options = [
                    'folder' => str_replace(':', '/', $outputPath),
                    'quality' => $quality
                ];
                
                // Nếu không phải original, thêm resize config
                if (strtolower($sizeKey) !== 'original' && $targetW && $targetH) {
                    $options['resizes'] = [
                        ['width' => $targetW, 'height' => $targetH]
                    ];
                }
                
                // Upload bằng thư viện mới
                $result = Files::upload($fileArray, $options, true);
                
                if ($result['success']) {
                    $savedFiles[] = $result['data']['path'];
                    
                    // Ghi DB cho original
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
            'status'      => 'success',
            'files'       => array_values(array_unique($savedFiles)),
            'original_db' => $originalRecords,
        ]);
    }

    public function uploadbylink() {
        // MIGRATED: Use new Upload library
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->error('Only POST method is allowed', [], 405);
        }
        
        $url = $_POST['url'] ?? '';
        $sizes = $_POST['sizes'] ?? [];
        $name = $_POST['name'] ?? '';
        $type = $_POST['type'] ?? 'webp';
        $quality = (int)($_POST['quality'] ?? 90);
        $path = $_POST['path'] ?? date('Y/m/d') . '/';
    
        if (empty($url)) {
            return $this->error('URL is required', [], 400);
        }
        
        // Decode sizes if it's a string
        if (is_string($sizes)) {
            $sizes = json_decode($sizes, true) ?? [];
        }
        
        // Check if filename exists and generate unique name
        if (!empty($name)) {
            $uploadDir = $this->base_dir . '/' . str_replace('/', DIRECTORY_SEPARATOR, $path);
            $name = $this->checkFileExists($name . '.' . $type, $uploadDir, $path);
            // Remove extension for custom_name
            $name = pathinfo($name, PATHINFO_FILENAME);
        }
        
        // Prepare options for new library
        $options = [
            'custom_name' => $name,
            'type' => $type,
            'quality' => $quality
        ];
        
        // Convert sizes to resizes format
        if (!empty($sizes)) {
            $options['resizes'] = [];
            foreach ($sizes as $size) {
                if (preg_match('/(\d+)x(\d+)/', $size, $matches)) {
                    $options['resizes'][] = [
                        'width' => (int)$matches[1],
                        'height' => (int)$matches[2]
                    ];
                }
            }
        }
        
        try {
            // Use new library
            $result = Files::downloadFromUrl($url, $path, $options);
            
            if (!$result['success']) {
                return $this->error($result['error'], [], 400);
            }
            
            // Convert response to old format
            $uploaded = $this->_convertToOldFormat($result['data']);
            
            return $this->success(['uploaded_files' => $uploaded], 'File uploaded successfully.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), [], 500);
        }
    }

    private function _createFolder($relativePath)
    {
        $fullPath = $this->_getFullPath($relativePath);
        if (file_exists($fullPath)) {
           return true;
        }

        if (!mkdir($fullPath, 0755, true)) {
            return false;
        }
        return true;
    }

    /**
     * Create unique base filename: swim.jpg → swim_1.jpg if exists.
     * Checks both filesystem and database for uniqueness.
     * Returns array [baseName, ext] – baseName WITHOUT extension.
     */
    private function _uniqueBaseName(string $dir, string $rawName, string $folder = ''): array
    {
        $rawName      = $this->_sanitizeFileName($rawName);
        $info         = pathinfo($rawName);
        $base         = $info['filename'];
        $ext          = strtolower($info['extension'] ?? 'jpg');

        // If name already has size suffix (_300x200) then remove it
        $base = preg_replace('/_\d+x\d+$/', '', $base);

        // Generate unique name - check both filesystem and database
        $candidate = $base;
        $i = 1;
        while ($this->_isFileNameExists($candidate, $ext, $dir, $folder)) {
            $candidate = $base . '_' . $i++;
        }
        return [$candidate, $ext];
    }

    /**
     * Check if filename exists in both filesystem and database
     * @param string $baseName Base name without extension
     * @param string $ext File extension
     * @param string $dir Directory path
     * @param string $folder Folder path for database check
     * @return bool True if file exists
     */
    private function _isFileNameExists(string $baseName, string $ext, string $dir, string $folder = ''): bool
    {
        $filename = $baseName . '.' . $ext;
        
        // Check filesystem
        if (file_exists($dir . DIRECTORY_SEPARATOR . $filename)) {
            return true;
        }
        
        // Check database
        $dbPath = $folder ? $folder . '/' . $filename : $filename;
        $existingFile = $this->filesModel->getFileByPath($dbPath);
        
        return !empty($existingFile);
    }

    /**
     * Check if file exists and generate unique filename
     * @param string $originalName Original filename (with extension)
     * @param string $uploadDir Upload directory path
     * @param string $folder Folder path for database check
     * @return string Unique filename
     */
    private function checkFileExists(string $originalName, string $uploadDir, string $folder = ''): string
    {
        $info = pathinfo($originalName);
        $baseName = $info['filename'];
        $ext = strtolower($info['extension'] ?? 'jpg');
        
        // Remove size suffix if exists (e.g., _300x200)
        $baseName = preg_replace('/_\d+x\d+$/', '', $baseName);
        
        $candidate = $baseName;
        $counter = 1;
        
        // Keep checking until we find a unique name
        while ($this->_isFileNameExists($candidate, $ext, $uploadDir, $folder)) {
            $candidate = $baseName . '_' . $counter;
            $counter++;
        }
        
        return $candidate . '.' . $ext;
    }

    /** Tạo tên file cuối cùng cho từng biến thể */
    private function _buildFileName(string $base, string $sizeKey, string $ext): string
    {
        return $sizeKey !== 'original'
            ? "{$base}_{$sizeKey}.{$ext}"
            : "{$base}.{$ext}";
    }

    
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

    public function delete_file($path){
        try {
            if (file_exists($path)){
                @unlink($path);
            }
        } catch (AppException $e) {
            return false;
        }
        return true;
    }

    function _get_list_file($data) {
        $list_file = [];
        
        // Add original file
        $list_file[] = $this->_fullPath($data['path']);
        
        // Add webp file of original file
        $list_file[] = $this->_fullPath($data['path']) . '.webp';
        
        // Process resize files if available
        if (!empty($data['resize'])) {
            $resizes = explode(';', $data['resize']);
            $base_path = $this->_removeExtension($data['path']);
            
            foreach ($resizes as $size) {
                $path_resize = $base_path . '_' . $size . '.' . $data['type'];
                $fullpath_resize = $this->_fullPath($path_resize);
                // Add jpg resize file
                $list_file[] =  $fullpath_resize;
                // Add webp resize file
                $list_file[] =  $fullpath_resize . '.webp';
            }
        }
        
        return $list_file;
    }


    protected function _cleanPath($path)
    {
        // Remove PHP file names
        $path = preg_replace('/\.php\./i', '.', $path);
        $path = preg_replace('/\.php$/i', '.', $path);
        // Remove strings like .:, ..:, or .:.: (strings that could cause security errors)
        $path = preg_replace('/(\.+:)/', '_', $path); // Remove any string in format .: or ..:
        // Replace multiple dots (...., .....) with single dot
        $path = preg_replace('/(\.+)/', '.', $path);
        // Remove invalid characters and replace with underscore
        $path = preg_replace('/[^.\p{L}\p{N}\s\-_:\/]+/u', '_', $path); // Allow '/' as directory separator
        $path = preg_replace('/\s+/', '_', $path);  // Replace spaces with underscore
        $path = preg_replace('/_+/', '_', $path);
        $path = trim($path, '_');
        
        return $path;
    }

    private function _getFullPath($relativePath)
    {
        // Get base path from configuration
        $basePath = dirname(dirname(dirname(dirname(__DIR__)))) . '/uploads/';
        
        // Convert : to directory separator
        $relativePath = str_replace(':', DIRECTORY_SEPARATOR, $relativePath);
        
        // Combine base path with relative path
        $fullPath = $basePath . $relativePath;
        
        // Normalize path
        $fullPath = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $fullPath);
        
        // Create directory if it doesn't exist
        if (!is_dir($fullPath)) {
            if (!mkdir($fullPath, 0755, true)) {
                throw new AppException('Unable to create directory: ' . $fullPath);
            }
        }
        
        // Check if path is within uploads directory
        $realBasePath = realpath($basePath);
        $realFullPath = realpath($fullPath);
        
        if (!$realFullPath || strpos($realFullPath, $realBasePath) !== 0) {
            throw new AppException('Invalid path: ' . $fullPath);
        }
        
        return $fullPath;
    }

    private function _toSlug($str) {
        // Convert to lowercase
        $str = mb_strtolower($str, 'UTF-8');
        // Use iconv to remove accents and special characters
        $str = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str);
        // Remove non-alphanumeric characters (if needed)
        $str = preg_replace('/[^a-z0-9_-]/', '', $str);
        return $str;
    }

    /**
     * ===================================
     * NEW MIGRATION HELPER METHODS
     * ===================================
     */

    /**
     * Map config from old format to new format for Upload library
     */
    private function _mapConfigToNewFormat($config)
    {
        $newConfig = [];
        
        // Map resizes
        if (!empty($config['resizes'])) {
            $newConfig['resizes'] = $config['resizes'];
        }
        
        // Map sizes (alternative format) 
        if (!empty($config['sizes'])) {
            $newConfig['resizes'] = [];
            foreach ($config['sizes'] as $size) {
                if (preg_match('/(\d+)x(\d+)/', $size, $matches)) {
                    $newConfig['resizes'][] = [
                        'width' => (int)$matches[1],
                        'height' => (int)$matches[2]
                    ];
                }
            }
        }
        
        // Map watermark
        if (!empty($config['watermark']) && !empty($config['watermark_img'])) {
            $newConfig['watermark'] = [
                'file' => $config['watermark_img'],
                'position' => $config['watermark_position'] ?? 'bottom-right',
                'opacity' => $config['watermark_opacity'] ?? 80
            ];
        }
        
        // Map output formats - always enable WebP
        $newConfig['webp'] = true;
        
        // Map quality
        if (!empty($config['quality'])) {
            $newConfig['quality'] = $config['quality'];
        }
        
        return $newConfig;
    }

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