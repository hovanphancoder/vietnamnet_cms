<?php
namespace App\Controllers\Api\V1;

use App\Controllers\ApiController;
use App\Models\FilesModel;
use System\Core\AppException;

// use App\Libraries\iMagify;
use App\Libraries\iMagify;
use App\Libraries\Fastlang as Flang;
use Exception;

class FilesController extends ApiController
{
    //Files properties
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
    *  Upload files – hỗ trợ chunk upload & multi-files
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
        load_helpers(['string']);

        $config = json_decode($_POST['config'] ?? '', true) ?? [];
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->error('Invalid config', [], 400);
        }

        // ---- Config & Target Folder Uploads -------------------------------------------
        $config_files       = config('files');
        $config_files['allowed_types']     = $config_files['allowed_types']  ?? [];
        $config_files['max_file_size']   = $config_files['max_file_size']  ?? 10 * 1024 * 1024;
        $config_files['max_file_count']  = $config_files['max_file_count'] ?? 10;

        $pathReq   = $this->_sanitizeFolderName($_POST['path'] ?? '');
        $dbprefixFolder  = str_replace(':', '/', $pathReq);
        $folder    = str_replace(':', DIRECTORY_SEPARATOR, $pathReq);
        $uploadFolder = rtrim($this->base_dir, '/') . ($folder ? DIRECTORY_SEPARATOR.$folder : '');

        $this->_validFolder($uploadFolder); //Show error if folder not exist & can not create

        // ---- Kiểm tra tổng số file ------------------------------------------
        $totalFiles = count($_FILES['files']['name']);
        if ($totalFiles > $config_files['max_file_count']) {
            return $this->error("Too many files uploaded. Maximum allowed is {$config_files['max_file_count']}", [], 400);
        }

        $uploaded = [];

        try {
            foreach ($_FILES['files']['name'] as $idx => $original) {
                $error = $_FILES['files']['error'][$idx];
                $size  = $_FILES['files']['size'][$idx];
                $tmp   = $_FILES['files']['tmp_name'][$idx];
                $ext   = strtolower(pathinfo($original, PATHINFO_EXTENSION));

                // --- các guard clause ngắn gọn --------------------------------
                if ($error !== UPLOAD_ERR_OK) return $this->error($this->_errorMessage($error), [], 400);
                if ($size > $config_files['max_file_size'])  return $this->error(Flang::_e('file exceeds maximum allowed size'), [], 400); 
                if (!in_array($ext, $config_files['allowed_types']))   return $this->error(Flang::_e('file type not allowed').': '.implode(',', $config_files['allowed_types']), [], 400);

                /* ---------- Chunk upload? ------------------------------------ */
                if (isset($_POST['chunkIndex'], $_POST['totalChunks'])) {
                    /*
                    $this->_handleChunk(
                        (int)$_POST['chunkIndex'],
                        (int)$_POST['totalChunks'],
                        $uploadFolder,
                        $tmp,
                        $original,
                        $_POST['originalFileName'] ?? '',
                        $_POST['uploadToken']      ?? '',
                        $dbprefixFolder,
                        $ext,
                        $uploaded
                    );
                    continue;   // xử lý file tiếp theo
                    */
                }

                /* ---------- Upload thường ----------------------------------- */
                // Tạo tên file an toàn
                $safeBase = url_slug(pathinfo($original, PATHINFO_FILENAME));
                $finalName = $this->_uniqueName($uploadFolder, $safeBase, $ext);
                $finalPath = $uploadFolder . DIRECTORY_SEPARATOR . $finalName;

                // Di chuyển file tạm vào thư mục đích
                if (!$this->_movefile($tmp, $finalPath)) {
                    return $this->error(Flang::_e('failed to move uploaded file'), [], 400); 
                }

                // Nếu là file ảnh và có config xử lý
                if (in_array($ext, $this->images_types) && (!empty($config['sizes']) || !empty($config['watermark']))) {
                    try {
                        // Process image theo config
                        $processedResults = $this->_processImage($config, $finalPath);
                        if(!empty($processedResults['sizes'])){
                            // Loại bỏ các kích thước trùng lặp và nối lại thành chuỗi
                            $resize = implode(';', array_unique(array_column($processedResults['sizes'], 'size')));
                        } else {
                            $resize = '';
                        }
                        // Save thông tin vào database
                        $uploaded[] = $this->_dbAdd($finalName, $dbprefixFolder, filesize($finalPath), $ext, $resize);

                        // Xóa file gốc nếu không cần giữ lại
                        if (empty($config['original'])) {
                            @unlink($finalPath);
                        }
                    } catch (\Exception $e) {
                        // Nếu xử lý ảnh thất bại, vẫn lưu file gốc
                        $uploaded[] = $this->_dbAdd($finalName, $dbprefixFolder, filesize($finalPath), $ext);
                    }
                } else {
                    // Nếu không phải ảnh hoặc không có config xử lý, lưu file bình thường
                    $uploaded[] = $this->_dbAdd($finalName, $dbprefixFolder, filesize($finalPath), $ext);
                }
            }

            return $this->success(['uploaded_files' => $uploaded], 'Files uploaded successfully.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), [], 500);
        }
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
            'created_at' => _DateTime(),
            'updated_at' => _DateTime(),
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
        $this->_check_permission('edit');

        try {
            /*-------------------------------------------------------
            | 0. Đọc & kiểm tra input
            |------------------------------------------------------*/
            $id       = S_POST('id');
            $type     = S_POST('type');
            $newName  = $this->_sanitizeFileName(S_POST('newname'));

            if (empty($id) || empty($type) || empty($newName)) {
                throw new AppException('Missing required parameters.');
            }

            $file = $this->filesModel->getFileById($id);
            if (!$file || empty($file['path'])) {
                $this->error(Flang::_e('file not found in database'), [], 500);
            }
            if (!in_array($type, ['file', 'folder'], true)) {
                $this->error(Flang::_e('invalid type'), [], 500);
            }

            /*-------------------------------------------------------
            | 1. Tính toán tên mới
            |------------------------------------------------------*/
            $baseName     = $this->_removeExtension($file['name']); // cry_1
            $fullPath     = $this->_fullPath($file['path']);        // .../cry_1.jpg
            $fullPathNew  = str_replace($baseName, $newName, $fullPath);

            /*-------------------------------------------------------
            | 2. Gom tất cả cặp path cần đổi tên
            |------------------------------------------------------*/
            $renamePairs = [
                [$fullPath, $fullPathNew]
            ];

            // Nếu có file webp "đuôi kép" (jpg.webp) → rename thêm
            $webpOld = $fullPath . '.webp';
            $webpNew = $fullPathNew . '.webp';
            if (is_file($webpOld)) {
                $renamePairs[] = [$webpOld, $webpNew];
            }

            // Các biến thể đã resize (300x200;600x400…)
            if (!empty($file['resize'])) {
                foreach (explode(';', $file['resize']) as $size) {
                    $sizePath    = $this->_sizePath($fullPath, $size, $file['type']);          // .../cry_1_300x200.jpg
                    $sizePathNew = str_replace($baseName, $newName, $sizePath);               // .../newName_300x200.jpg
                    $renamePairs[] = [$sizePath, $sizePathNew];

                    // kèm biến thể webp của size (nếu tồn tại)
                    $sizeWebpOld = $sizePath . '.webp';
                    if (is_file($sizeWebpOld)) {
                        $renamePairs[] = [$sizeWebpOld, $sizePathNew . '.webp'];
                    }
                }
            }

            /*-------------------------------------------------------
            | 3. Thực hiện rename
            |------------------------------------------------------*/
            foreach ($renamePairs as [$old, $new]) {
                if (is_file($old) && !is_file($new)) {
                    @rename($old, $new);
                }
            }

            /*-------------------------------------------------------
            | 4. Cập nhật DB
            |------------------------------------------------------*/
            $file['name'] = str_replace($baseName, $newName, $file['name']);
            $file['path'] = str_replace($baseName, $newName, $file['path']);

            $this->filesModel->updateFile($id, [
                'name'       => $file['name'],
                'path'       => $file['path'],
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            $this->success(['data' => $file], 'Item renamed successfully.');
        }
        catch (\Exception $e) {
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
        $folder = preg_replace('/[^a-zA-Z0-9_\-:]+/', '_', $folder);
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
     * Xử lý ảnh theo cấu hình được cung cấp
     * @param array $config Cấu hình xử lý ảnh
     * @param string $sourceFile Đường dẫn file ảnh gốc
     * @return array Kết quả xử lý ảnh
     */
    private function _processImage($config, $sourceFile) {
        $results = [];
        try {
            // Load ảnh gốc
            $image = iMagify::load($sourceFile);
            
            // Danh sách các phiên bản ảnh cần xử lý
            $versions = [];
            
            // Thêm các phiên bản resize
            if (!empty($config['resizes'])) {
                foreach ($config['resizes'] as $size) {
                    $versions[] = [
                        'width' => $size['width'],
                        'height' => $size['height'],
                        'suffix' => '_' . $size['width'] . 'x' . $size['height']
                    ];
                }
            }
            
            // Xử lý từng phiên bản resize trước
            foreach ($versions as $version) {
                // Clone ảnh để xử lý
                $processedImage = clone $image;
                // Resize
                $processedImage->resize($version['width'], $version['height'], true, true);
                // Gắn watermark nếu có
                if (!empty($config['watermark'])) {
                    $this->_applyWatermark($processedImage, [
                        'src' => $config['watermark_img']
                    ]);
                }
                
                // Xuất ra các định dạng
                foreach ($config['output'] as $format => $formatConfig) {
                    // Tạo tên file mới
                    $info = pathinfo($sourceFile);
                    $newFilename = $info['filename'] . $version['suffix'];
                    $outputPath = $info['dirname'] . DIRECTORY_SEPARATOR . $newFilename . '.' . $formatConfig['name'];
                    
                    // Chuyển đổi và lưu
                    $processedImage->convert($format)
                                 ->save($outputPath, $formatConfig['q']);
                    
                    // Thêm vào kết quả
                    $results['sizes'][] = [
                        'path' => $outputPath,
                        'size' => $version['width'] . 'x' . $version['height']
                    ];
                }
                
                // Giải phóng bộ nhớ
                $processedImage->destroy();
            }
            
            // Process image gốc sau cùng
            if (!empty($config['original'])) {
                // Gắn watermark nếu có
                if (!empty($config['watermark'])) {
                    $this->_applyWatermark($image, [
                        'src' => $config['watermark_img']
                    ]);
                }
                
                // Xuất ra các định dạng
                foreach ($config['output'] as $format => $formatConfig) {
                    // Tạo tên file mới
                    $info = pathinfo($sourceFile);
                    $newFilename = $info['filename'];
                    $outputPath = $info['dirname'] . DIRECTORY_SEPARATOR . $newFilename . '.' . $formatConfig['name'];
                    
                    // Chuyển đổi và lưu
                    $image->convert($format)
                         ->save($outputPath, $formatConfig['q']);
                    
                    // Thêm vào kết quả
                    $results['original'] = $outputPath;
                }
            }
            
            // Giải phóng bộ nhớ ảnh gốc
            $image->destroy();
            
            return $results;
            
        } catch (\Exception $e) {
            throw new AppException('Lỗi xử lý ảnh: ' . $e->getMessage());
        }
    }

    /**
     * Áp dụng watermark cho ảnh
     * @param iMagify $image Đối tượng ảnh
     * @param array $watermarkConfig Cấu hình watermark
     */
    private function _applyWatermark($image, $watermarkConfig = []) {
        if (empty($watermarkConfig['src'])) {
            return;
        }

        // Lấy cấu hình từ config hoặc dùng giá trị mặc định
        $position = $watermarkConfig['position'] ?? (option('watermark_position') ?? 'bottom-right');
        $padding = (int)($watermarkConfig['padding'] ?? (option('watermark_padding') ?? 10));
        $opacity = (int)($watermarkConfig['opacity'] ?? (option('watermark_opacity') ?? 100));
        
        // Đảm bảo các giá trị là số
        $watermark_width = (int)($watermarkConfig['watermark_width'] ?? (option('watermark_width') ?? 70));
        $watermark_max_width = (int)($watermarkConfig['watermark_max_width'] ?? (option('watermark_max_width') ?? 20));

        // Tính toán kích thước watermark dựa trên phần trăm của ảnh gốc
        $image_width = (int)$image->getWidth();
        $max_width_px = (int)(($image_width * $watermark_max_width) / 100); // Chuyển % thành pixel
        
        // Nếu kích thước watermark lớn hơn kích thước tối đa cho phép thì lấy kích thước tối đa
        if ($watermark_width > $max_width_px) {
            $watermark_width = $max_width_px;
        }

        $watermarkPath = $this->base_dir . '/' . $watermarkConfig['src'];
        if (!file_exists($watermarkPath)) {
            // Nếu không tìm thấy watermark, chỉ log và tiếp tục
            error_log('Không tìm thấy file watermark: ' . $watermarkPath);
            return;
        }

        try {
            // Load và resize watermark
            $watermark = iMagify::load($watermarkPath);
            $watermark->resize($watermark_width, $watermark_width, true); // true để giữ tỷ lệ
            
            // Save watermark đã resize vào file tạm
            $tempWatermarkPath = $this->base_dir . '/temp_watermark_' . uniqid() . '.png';
            $watermark->save($tempWatermarkPath);
            
            // Áp dụng watermark
            $image->addWatermark(
                $tempWatermarkPath,
                $position,
                $padding,
                $opacity
            );

            // Giải phóng bộ nhớ và xóa file tạm
            $watermark->destroy();
            @unlink($tempWatermarkPath);
        } catch (\Exception $e) {
            // Nếu có lỗi trong quá trình xử lý watermark, chỉ log và tiếp tục
            error_log('Lỗi khi xử lý watermark: ' . $e->getMessage());
            return;
        }
    }

    /**
     * Tạo đường dẫn output cho ảnh đã xử lý
     * @param string $sourceFile Đường dẫn file gốc
     * @param string $size Kích thước hoặc 'original'
     * @param string $format Định dạng file
     * @return string Đường dẫn output
     */
    private function _getOutputPath($sourceFile, $size, $format = null) {
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
     * Nhận JSON (định dạng như ví dụ) và lưu toàn bộ ảnh + các phiên bản resize.
     * Mặc định luôn bảo đảm **một** base‑name duy nhất cho mọi bản resize:
     *   swim.jpg  → swim_1.jpg, swim_1_300x200.jpg, swim_1_300x200.jpg.webp …
     */
    public function saves()
    {
        /*---------------------------------------------------------------
        * 0. Đọc dữ liệu & kiểm tra
        *-------------------------------------------------------------*/
        $payload = json_decode(file_get_contents('php://input'), true);
        if (!$payload || !isset($payload['images']) || !is_array($payload['images'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Dữ liệu không hợp lệ']);
            return;
        }

        /*---------------------------------------------------------------
        * 1. Cấu hình – thư mục lưu
        *-------------------------------------------------------------*/
        $cfg           = config('files');
        $allowed_types = $cfg['allowed_types'] ?? ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        // Đường dẫn thư mục cha (ví dụ 2025/05/02)
        $outputPath    = $payload['path'] ?? date('Y:m:d');
        $cleanPath     = $this->_cleanPath($outputPath);       // 2025:05:02
        $pathDatabase  = str_replace(':', '/', $cleanPath);    // 2025/05/02
        $folderReal    = str_replace(':', DIRECTORY_SEPARATOR, $cleanPath);
        $uploadDir     = $this->base_dir . DIRECTORY_SEPARATOR . $folderReal;

        if (!is_dir($uploadDir) && !$this->_createFolder($cleanPath)) {
            http_response_code(500);
            echo json_encode(['error' => 'Không tạo được thư mục đích']);
            return;
        }

        /*---------------------------------------------------------------
        * 2. Vòng lặp ảnh
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
            * 2.0 Chọn duy nhất 1 item để xử lý cho $sizeKey
            *      – Ưu tiên item không phải webp
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
                    // Gặp file không phải webp → chọn ngay và dừng tìm
                    $chosen = $item;
                    break;
                }
                // Tạm giữ bản webp, nhưng vẫn tiếp tục tìm bản không webp
                $chosen = $item;
            }
            // Không có item hợp lệ
            if (!$chosen) continue;

            // Ghi lại resizeList (1 lần cho mỗi sizeKey)
            if (strpos($sizeKey, 'x') !== false) {
                $resizeList .= ($resizeList ? ';' : '') . $sizeKey;
            }

            /*-------------------------------------------------------
            * 2.1   Các biến cho item đã chọn
            *-----------------------------------------------------*/
            $type        = strtolower($chosen['type']);     // jpg|png|gif|webp
            $rawFilename = $chosen['filename'];

            /* Xác định baseName duy nhất (chỉ 1 lần) */
            if ($finalBaseName === null) {
                [$finalBaseName, $finalExt] = $this->_uniqueBaseName($uploadDir, $rawFilename);
            }

            /* Tên file đích */
            $filename = $this->_buildFileName($finalBaseName, $sizeKey, ($type === 'webp' ? $finalExt : $type));
            $destPath = $uploadDir . DIRECTORY_SEPARATOR . $filename;

            /* Kích thước, chất lượng */
            $desiredSize = (strpos($sizeKey, 'x') !== false) ? $sizeKey : $chosen['size'];
            [$targetW, $targetH] = array_map('intval', explode('x', $desiredSize) + [0, 0]);
            $quality = isset($chosen['quality']) ? (int) $chosen['quality'] : 90;

            /*-------------------------------------------------------
            * 2.2   Xử lý & lưu
            *-----------------------------------------------------*/
            try {
                $img = iMagify::loadFromBase64($chosen['data']);

                if (strtolower($sizeKey) !== 'original' && $targetW && $targetH) {
                    $img->resize($targetW, $targetH);
                }

                // Bảo đảm thư mục tồn tại
                if (!is_dir(dirname($destPath))) {
                    mkdir(dirname($destPath), 0755, true);
                }

                /* ----- Save bản gốc (jpg/png/gif/webp) ----- */
                $img->convert(($type === 'webp' ? $finalExt : $type), $quality);
                if (!$img->save($destPath, $quality)) {
                    throw new \Exception("Không thể lưu $destPath");
                }
                $savedFiles[] = $destPath;

                /* ----- Save bản webp (nếu gốc KHÔNG phải webp) ----- */
                if ($type !== 'webp') {
                    $webpPath = $destPath . '.webp';            // cry_1_300x200.jpg.webp
                    $img->convert('webp', $quality);
                    if ($img->save($webpPath, $quality)) {
                        $savedFiles[] = $webpPath;
                    }
                }

                /* ----- Ghi DB cho ORIGINAL duy nhất & không phải webp ----- */
                if (strtolower($sizeKey) === 'original' && $type !== 'webp') {
                    $dbPath = $pathDatabase ? $pathDatabase . '/' . $filename : $filename;
                    $insert = [
                        'name'       => $filename,
                        'path'       => $dbPath,
                        'size'       => filesize($destPath),
                        'type'       => $type,
                        'resize'     => $resizeList,
                        'autoclean'  => 0,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ];
                    if ($id = $this->filesModel->addFile($insert)) {
                        $originalRecords[] = [
                            'id'   => $id,
                            'name' => $filename,
                            'path' => $dbPath,
                            'pth'  => $cleanPath,
                        ];
                    }
                }

                $img->destroy();

            } catch (\Exception $e) {
                $savedFiles[] = 'Lỗi: ' . $e->getMessage();
            }
        }

        echo json_encode([
            'status'      => 'success',
            'files'       => array_values(array_unique($savedFiles)),
            'original_db' => $originalRecords,
        ]);
    }

    public function uploadbylink() {
        // Chỉ cho phép POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->error('Only POST method is allowed', [], 405);
        }
        
        // Lấy các giá trị từ POST
        $url     = $_POST['url'] ?? '';
        $sizes   = $_POST['sizes'] ?? []; // Mảng các kích thước ví dụ: ['100x100','200x200','300x300']
        $name    = $_POST['name'] ?? '';
        $type    = $_POST['type'] ?? 'webp'; // mặc định chỉ hỗ trợ webp theo yêu cầu
        $quality = $_POST['quality'] ?? 90;   // chất lượng ảnh mặc định 90
        $path    = $_POST['path'] ?? date('Y/m/d') . '/'; // Đường dẫn lưu file (nếu có)
    
        if (empty($url)) {
            return $this->error('URL is required', [], 400);
        }
        // decode  $sizes 
        if (is_string($sizes)) {
            $sizes = json_decode($sizes, true);
        }

        
        // Lấy nội dung ảnh từ URL
        $fileContent = file_get_contents($url);
        if ($fileContent === false) {
            return $this->error('Lỗi: Không thể tải ảnh từ URL', [], 400);
        }
        
        // Tạo image resource từ nội dung ảnh
        $image = imagecreatefromstring($fileContent);
        if (!$image) {
            return $this->error('Lỗi: Không thể tạo image từ dữ liệu tải về', [], 400);
        }
        
        // Nếu không có tên file được truyền vào, lấy tên từ URL
        if (empty($name)) {
            $name = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_FILENAME);
        }
        // Chuẩn hóa tên file (sử dụng hàm có sẵn _toSlug)
        $basename = $this->_toSlug($name);
    
        // Xác định đường dẫn lưu file theo cấu trúc ngày tháng (ví dụ: 2024/10/23/)
        $folder_real = str_replace('/', DIRECTORY_SEPARATOR, $path);
        $upload_dir  = $this->base_dir . DIRECTORY_SEPARATOR . $folder_real;
        
        // Nếu thư mục chưa tồn tại, tạo mới
        if (!is_dir($upload_dir)) {
            try {
                $this->_createFolder($path);
            } catch (AppException $e) {
                return $this->error('Failed to create upload directory: ' . $e->getMessage(), [], 500);
            }
        }
        
        // Process image gốc:
        // Nếu ảnh vượt quá kích thước tối đa, resize lại (giống như trong uploadImgbyLink)
        $maxWidth  = 1920;
        $maxHeight = 1080;
        $origWidth  = imagesx($image);
        $origHeight = imagesy($image);
        $ratio = min($maxWidth / $origWidth, $maxHeight / $origHeight, 1);
        if ($ratio < 1) {
            $newWidth  = round($origWidth * $ratio);
            $newHeight = round($origHeight * $ratio);
            $newImage  = imagecreatetruecolor($newWidth, $newHeight);
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
            imagedestroy($image);
            $image = $newImage;
        }
        
        // Đặt tên file gốc: name.webp
        $originalFileName = $basename . '.' . $type;
        $targetPathOriginal = $upload_dir . DIRECTORY_SEPARATOR . $originalFileName;
        
        // Nếu file đã tồn tại, thêm hậu tố số tăng dần
        if (file_exists($targetPathOriginal)) {
            $counter = 1;
            while(file_exists($upload_dir . DIRECTORY_SEPARATOR . $basename . '_' . $counter . '.' . $type)){
                $counter++;
            }
            $originalFileName = $basename . '_' . $counter . '.' . $type;
            $targetPathOriginal = $upload_dir . DIRECTORY_SEPARATOR . $originalFileName;
        }
        
        // Save ảnh gốc chuyển đổi sang webp với chất lượng đã chọn
        if ($type == 'webp') {
            // Đảm bảo thư mục tồn tại trước khi lưu file
            if (!is_dir($upload_dir)) {
                if (!mkdir($upload_dir, 0755, true)) {
                    imagedestroy($image);
                    return $this->error('Không thể tạo thư mục upload', [], 500);
                }
            }
            
            if (!imagewebp($image, $targetPathOriginal, $quality)) {
                imagedestroy($image);
                return $this->error('Lỗi: Không thể chuyển đổi sang webp', [], 500);
            }
        } else {
            imagedestroy($image);
            return $this->error('Chỉ hỗ trợ chuyển đổi sang định dạng webp', [], 400);
        }
        
        // Save các phiên bản resize (nếu có)
        $resizeList = [];
        // Để đảm bảo chất lượng ảnh resize, tạo resource mới từ file gốc ban đầu
        // (Save ý: nếu muốn dùng ảnh gốc nguyên bản, ta có thể khởi tạo lại từ $fileContent)
        $originalResource = imagecreatefromstring($fileContent);
        if (!$originalResource) {
            // Nếu không tạo được resource mới thì tiếp tục dùng ảnh đã resize
            $originalResource = $image;
        }
        // Nếu đã dùng ảnh $image để lưu gốc, đảm bảo không phá hủy resource gốc cho các resize
        foreach ($sizes as $resizeSize) {
            $parts = explode('x', $resizeSize);
            if (count($parts) !== 2) continue;
        
            list($w, $h) = $parts;
            $w = (int)$w;
            $h = (int)$h;
            if ($w <= 0 || $h <= 0) continue;
        
            // Lấy kích thước gốc
            $origW = imagesx($originalResource);
            $origH = imagesy($originalResource);
        
            // Tính tỷ lệ để crop center theo tỉ lệ "cover"
            $targetRatio = $w / $h;
            $origRatio = $origW / $origH;
        
            if ($origRatio > $targetRatio) {
                // Image gốc rộng hơn => crop chiều ngang
                $newWidth = (int)($origH * $targetRatio);
                $newHeight = $origH;
                $srcX = (int)(($origW - $newWidth) / 2);
                $srcY = 0;
            } else {
                // Image gốc cao hơn => crop chiều dọc
                $newWidth = $origW;
                $newHeight = (int)($origW / $targetRatio);
                $srcX = 0;
                $srcY = (int)(($origH - $newHeight) / 2);
            }
        
            // Tạo ảnh mới với kích thước mong muốn
            $resizedImage = imagecreatetruecolor($w, $h);
            imagealphablending($resizedImage, false);
            imagesavealpha($resizedImage, true);
        
            // Crop trung tâm và resize
            imagecopyresampled(
                $resizedImage,
                $originalResource,
                0, 0,               // destination x, y
                $srcX, $srcY,       // source x, y (crop start)
                $w, $h,             // destination width, height
                $newWidth, $newHeight // source width, height (crop size)
            );
        
            // Tạo tên file
            $baseForResized = pathinfo($originalFileName, PATHINFO_FILENAME);
            $resizedFileName = $baseForResized . '_' . $resizeSize . '.' . $type;
            $targetPathResized = $upload_dir . DIRECTORY_SEPARATOR . $resizedFileName;
        
            if (file_exists($targetPathResized)) {
                $counter = 1;
                while (file_exists($upload_dir . DIRECTORY_SEPARATOR . $baseForResized . '_' . $resizeSize . '_' . $counter . '.' . $type)) {
                    $counter++;
                }
                $resizedFileName = $baseForResized . '_' . $resizeSize . '_' . $counter . '.' . $type;
                $targetPathResized = $upload_dir . DIRECTORY_SEPARATOR . $resizedFileName;
            }
        
            // Save ảnh
            if ($type === 'webp') {
                imagewebp($resizedImage, $targetPathResized, $quality);
            }
        
            imagedestroy($resizedImage);
            $resizeList[] = $resizeSize;
        }
        
        // Nếu resource tạm được tạo cho resize khác với resource $image gốc, hủy nó đi
        if ($originalResource !== $image) {
            imagedestroy($originalResource);
        }
        
        // Lấy kích thước file của ảnh gốc
        $sizeOriginal = filesize($targetPathOriginal);
        
        // Tạo dữ liệu insert vào database (chỉ lưu thông tin ảnh gốc)
        $insertItem = [
            'name'       => $originalFileName,
            'path'       => $path . $originalFileName, // VD: 2024/10/23/name.webp
            'size'       => $sizeOriginal,
            'type'       => $type,
            'resize'     => implode(';', $resizeList), // lưu theo định dạng 100x100;200x200;300x300
            'autoclean'  => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $insertId = @$this->filesModel->addFile($insertItem);
        if (!$insertId) {
            return $this->error('Failed to insert file info into database', [], 500);
        }
        
        // Trả về thông tin file đã upload thành công
        $uploadedFile = [
            'id'     => $insertId,
            'name'   => $originalFileName,
            'path'   => $path . $originalFileName,
            'resize' => implode(';', $resizeList)
        ];
        return $this->success(['uploaded_files' => [$uploadedFile]], 'File uploaded successfully.');
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
     * Tạo tên file gốc duy nhất: swim.jpg → swim_1.jpg nếu đã tồn tại.
     * Trả về mảng [baseName, ext] – baseName KHÔNG kèm đuôi.
     */
    private function _uniqueBaseName(string $dir, string $rawName): array
    {
        $rawName      = $this->_sanitizeFileName($rawName);
        $info         = pathinfo($rawName);
        $base         = $info['filename'];
        $ext          = strtolower($info['extension'] ?? 'jpg');

        // Nếu tên đang mang sẵn hậu tố kích thước (_300x200) thì cắt bỏ
        $base = preg_replace('/_\d+x\d+$/', '', $base);

        $candidate = $base;
        $i = 1;
        while (file_exists($dir . DIRECTORY_SEPARATOR . $candidate . '.' . $ext)) {
            $candidate = $base . '_' . $i++;
        }
        return [$candidate, $ext];
    }

    /** Tạo tên file cuối cùng cho từng biến thể */
    private function _buildFileName(string $base, string $sizeKey, string $ext): string
    {
        return $sizeKey !== 'original'
            ? "{$base}_{$sizeKey}.{$ext}"
            : "{$base}.{$ext}";
    }

    
    // Xóa nhiều mục (file hoặc folder)
    public function delete_multiple()
    {
        // Kiểm tra phương thức HTTP
        if (!HAS_POST('items')) {
            return $this->error('Only DELETE method is allowed', [], 405);
        }

        // Lấy dữ liệu từ $_DELETE
        $items = $_POST['items'];
        // decode json
        $items = is_string($items) ?  json_decode($items, true) : $items;
        if (empty($items)) {
            return $this->error('No items provided', [], 400);
        }

        foreach ($items as $item) {
            $id = $item['id'];
            $itemArray = $this->filesModel->getFileById($id);
            $list_file = $this->_get_list_file($itemArray);
            
            if (empty($itemArray) || !isset($itemArray['path'])) {
                continue;
            }
            if(!empty($list_file)){
                foreach ($list_file as $file) {
                    $this->delete_file($file);
                }
            }
            $delete_ids[$id] = $this->filesModel->deleteFile($id);
        }

       
        if (!empty($delete_ids)) {
            $this->success($delete_ids, 'Items deleted successfully.');
        } else {
            $this->error('Failed to delete items', [], 500);
        }
    }

    // Xóa mục (file hoặc folder)
    public function delete()
    {
        // Kiểm tra phương thức HTTP
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->error('Only POST method is allowed', [], 405);
        }

        // Lấy dữ liệu từ $_POST
        $id = S_POST('id');
        $path = S_POST('path');

        // Kiểm tra dữ liệu đầu vào
        if (empty($id) || empty($path)) {
            return $this->error('ID and path are required', [], 400);
        }

        // Lấy thông tin file từ database
        $itemArray = $this->filesModel->getFileById($id);
        if (empty($itemArray)) {
            return $this->error('File not found in database', [], 404);
        }

        // Lấy danh sách các file cần xóa
        $list_file = $this->_get_list_file($itemArray);
        
        // Xóa các file
        if(!empty($list_file)) {
            foreach ($list_file as $file) {
                $this->delete_file($file);
            }
        }

        // Xóa record trong database
        $result = $this->filesModel->deleteFile($id);
        if ($result) {
            $this->success(['id' => $id], 'Item deleted successfully.');
        } else {
            $this->error('Failed to delete item', [], 500);
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
        
        // Thêm file gốc
        $list_file[] = $this->_fullPath($data['path']);
        
        // Thêm file webp của file gốc
        $list_file[] = $this->_fullPath($data['path']) . '.webp';
        
        // Xử lý các file resize nếu có
        if (!empty($data['resize'])) {
            $resizes = explode(';', $data['resize']);
            $base_path = $this->_removeExtension($data['path']);
            
            foreach ($resizes as $size) {
                $path_resize = $base_path . '_' . $size . '.' . $data['type'];
                $fullpath_resize = $this->_fullPath($path_resize);
                // Thêm file jpg resize
                $list_file[] =  $fullpath_resize;
                // Thêm file webp resize
                $list_file[] =  $fullpath_resize . '.webp';
            }
        }
        
        return $list_file;
    }


    protected function _cleanPath($path)
    {
        // Loại bỏ các tên tệp tin PHP
        $path = preg_replace('/\.php\./i', '.', $path);
        $path = preg_replace('/\.php$/i', '.', $path);
        // Loại bỏ các chuỗi như .:, ..:, hoặc .:.: (các chuỗi có thể gây ra lỗi bảo mật)
        $path = preg_replace('/(\.+:)/', '_', $path); // Loại bỏ bất kỳ chuỗi nào có dạng .: hoặc ..:
        // Thay thế chuỗi nhiều dấu chấm (...., .....) thành dấu .
        $path = preg_replace('/(\.+)/', '.', $path);
        // Loại bỏ các ký tự không hợp lệ và thay thế bằng dấu gạch dưới
        $path = preg_replace('/[^.\p{L}\p{N}\s\-_:\/]+/u', '_', $path); // Cho phép '/' làm phân cách thư mục
        $path = preg_replace('/\s+/', '_', $path);  // Thay thế khoảng trắng bằng dấu gạch dưới
        $path = preg_replace('/_+/', '_', $path);
        $path = trim($path, '_');
        
        return $path;
    }

    private function _getFullPath($relativePath)
    {
        // Lấy đường dẫn gốc từ cấu hình
        $basePath = dirname(dirname(dirname(dirname(__DIR__)))) . '/uploads/';
        
        // Chuyển đổi dấu : thành dấu phân cách thư mục
        $relativePath = str_replace(':', DIRECTORY_SEPARATOR, $relativePath);
        
        // Kết hợp đường dẫn gốc với đường dẫn tương đối
        $fullPath = $basePath . $relativePath;
        
        // Chuẩn hóa đường dẫn
        $fullPath = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $fullPath);
        
        // Tạo thư mục nếu chưa tồn tại
        if (!is_dir($fullPath)) {
            if (!mkdir($fullPath, 0755, true)) {
                throw new AppException('Không thể tạo thư mục: ' . $fullPath);
            }
        }
        
        // Kiểm tra xem đường dẫn có nằm trong thư mục uploads không
        $realBasePath = realpath($basePath);
        $realFullPath = realpath($fullPath);
        
        if (!$realFullPath || strpos($realFullPath, $realBasePath) !== 0) {
            throw new AppException('Đường dẫn không hợp lệ: ' . $fullPath);
        }
        
        return $fullPath;
    }

    private function _toSlug($str) {
        // Chuyển thành chữ thường
        $str = mb_strtolower($str, 'UTF-8');
        // Sử dụng iconv để loại bỏ dấu và ký tự đặc biệt
        $str = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str);
        // Loại bỏ các ký tự không phải chữ cái và số (nếu cần)
        $str = preg_replace('/[^a-z0-9_-]/', '', $str);
        return $str;
    }

}