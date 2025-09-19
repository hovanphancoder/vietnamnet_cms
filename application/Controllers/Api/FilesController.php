<?php
namespace App\Controllers\Api;

use App\Controllers\ApiController;
use App\Models\FilesModel;
use System\Core\AppException;
use App\Libraries\iMagify;
use App\Models\LanguagesModel;
use System\Libraries\Logger;

class FilesController extends ApiController
{
    protected $filesModel;
    protected $base_dir;
    protected $allowed_types;
    protected $languagesModel;


    public function __construct()
    {
        parent::__construct();
        $this->languagesModel = new LanguagesModel();
        $this->filesModel = new FilesModel();

        // Set base_dir from application configuration
        $config_files = config('files');
        $this->allowed_types = $config_files['allowed_types'] ?? [];
        $uploads_dir = $config_files['storage_path'] ?? 'writeable/uploads';
        $this->base_dir = realpath(rtrim(PATH_ROOT, '/') . DIRECTORY_SEPARATOR . trim($uploads_dir, '/') . DIRECTORY_SEPARATOR);

        if ($this->base_dir === false) {
            throw new AppException('Uploads directory does not exist.');
        }
    }

    /**
     * Handles GET requests to /files/index/
     * Accepts parameters: page, limit, sort, q (search query)
     * Returns JSON response with files data
     */
    public function index() {
        // $this->data('title', Flang::_e('tile_languages'));
        try {
            // Get query parameters
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
            $sort = isset($_GET['sort']) ? $_GET['sort'] : 'name';
            $search = isset($_GET['q']) ? $_GET['q'] : '';

            // Prepare sorting
            $orderBy = $this->_getOrderBy($sort);

            // Prepare search condition
            $where = '';
            $params = [];

            if (!empty($search)) {
                $where = 'name LIKE ?';
                $params[] = '%' . $search . '%';
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

    /**
     * Maps sort parameter to SQL ORDER BY clause
     *
     * @param string $sort
     * @return string
     */
    private function _getOrderBy($sort) {
        switch ($sort) {
            case 'name':
                return 'name ASC';
            case 'name_za':
                return 'name DESC';
            case 'size_asc':
                return 'size ASC';
            case 'size_desc':
                return 'size DESC';
            case 'created_at_asc':
                return 'created_at ASC';
            case 'created_at_desc':
                return 'created_at DESC';
            case 'updated_at_asc':
                return 'updated_at ASC';
            case 'updated_at_desc':
                return 'updated_at DESC';
            default:
                return 'name ASC';
        }
    }


    // Get list of all items in directory
    public function dirs()
    {
        try {
            $target_dir = '';
            if (HAS_GET('dir')) {
                $target_dir = S_GET('dir');
                $target_dir = $this->_sanitizeFolderName($target_dir); // doc dir nen phai loc bao mat dang folder

            }
            $target_dir_full = $this->_getFolderRealPath($target_dir); // chuyen ve full path de doc cau truc
            // var_dump($target_dir_full);
            if ($target_dir !== '') {
                if (!$this->_isFolder($target_dir)) {
            // print_r($target_dir_full);die;

                    return $this->error('Folder not found', [], 404);
                }
            }

            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 3;
            $offset = ($page - 1) * $limit;  // Calculate offset
            $sort = isset($_GET['sort']) ? $_GET['sort'] : 'name';  // Get sort from request
            // Get search parameter 'q'
            $query = S_GET('q') ?? '';

            // Get list of subdirectories and files
            $items = scandir($target_dir_full);
            $folders = [];
            $files = [];
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') continue;
                $itemPath = rtrim($target_dir_full,'/') . '/' . $item;
                $itemInfo = $this->_getitem($itemPath);
                // Filter by name if search parameter 'q' exists
                if ($query !== '' && $itemInfo['type'] != 'folder') {
                    if (stripos($itemInfo['name'], $query) === false && stripos($this->_searchString($itemInfo['name']), $this->_searchString($query)) === false) continue; // Skip items that don't match search keyword
                }

                // Classify directories and files
                if ($itemInfo['type'] === 'folder') {
                    $folders[] = $itemInfo; // Save directories to separate array
                } else {
                    $files[] = $itemInfo; // Save files to separate array
                }
            }

            // Sort directories first (only sort by name)
            usort($folders, function($a, $b) use ($sort) {
                return strcmp($this->_normalizeString($a['name']), $this->_normalizeString($b['name']));
            });

            // Sort files based on sort parameters
            usort($files, function($a, $b) use ($sort) {
                switch ($sort) {
                    case 'name_za':
                        return strcmp($this->_normalizeString($b['name']), $this->_normalizeString($a['name']));
                    case 'size_asc':
                        return ($a['size'] ?? 0) - ($b['size'] ?? 0);
                    case 'size_desc':
                        return ($b['size'] ?? 0) - ($a['size'] ?? 0);
                    case 'created_at_asc':
                        return strtotime($a['created_at']) - strtotime($b['created_at']);
                    case 'created_at_desc':
                        return strtotime($b['created_at']) - strtotime($a['created_at']);
                    case 'updated_at_asc':
                        return strtotime($a['updated_at']) - strtotime($b['updated_at']);
                    case 'updated_at_desc':
                        return strtotime($b['updated_at']) - strtotime($a['updated_at']);
                    default:  // 'name' or any other value
                        return strcmp($this->_normalizeString($a['name']), $this->_normalizeString($b['name']));
                }
            });

            // Pagination only on files
            $pagedFiles = array_slice($files, $offset, $limit + 1); // Take extra 1 to check isNext
            $isNext = count($pagedFiles) > $limit; // Check if there's next page
            if ($isNext) {
                array_pop($pagedFiles); // Remove extra item
            }

            // Combine directory and paginated file list
            $responseData = array_merge($folders, $pagedFiles);

            // Return result
            $this->success([
                'items' => $responseData,
                'isnext' => $isNext
            ], 'Items retrieved successfully.');
        } catch (AppException $e) {
            $this->error($e->getMessage(), [], 500);
        }
    }


    // Get information of an item (folder or file)
    protected function _getitem($path) {
        $relativePath = str_replace($this->base_dir, '', realpath($path));
        // Change '/' to ':'
        $relativePath = ltrim(str_replace('/', ':', $relativePath), ':');
        $id = md5($relativePath);
        $name = basename($path);
        $type = is_dir($path) ? 'folder' : 'file';
        $size = is_file($path) ? filesize($path) / 1024 : null; // Size in KB
        $created_at = date("Y-m-d H:i:s", filectime($path));
        $updated_at = date("Y-m-d H:i:s", filemtime($path));

        return [
            'id' => $id,
            'name' => $name,
            'path' => $relativePath,
            'type' => $type,
            'size' => is_file($path) ? round($size, 2) : null,
            'created_at' => $created_at,
            'updated_at' => $updated_at
        ];
    }

    // Create new directory
    public function add()
    {
        // Check HTTP method
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->error('Only POST method is allowed', [], 405);
        }

        // Get data from request body as JSON
        $input = json_decode(file_get_contents('php://input'), true);

        // Get data from request body
        $path = $input['path'] ?? '';
        $name = $input['name'] ?? '';

        // Check input data
        if (empty($name)) {
            return $this->error('Folder name is required', [], 400);
        }

        // Sanitization
        $sanitizedName = $this->_sanitizeFolderName($name);
        $sanitizedPath = $this->_sanitizeFolderName($path);
        $newFolderPath = $sanitizedPath === '' ? $sanitizedName : $sanitizedPath . ':' . $sanitizedName;

        try {
            // Call function to create new directory
            $this->_createFolder($newFolderPath);
            $this->success(['destinationPath' => $newFolderPath, 'destinationName'=> $sanitizedName], 'Folder created successfully.');
        } catch (AppException $e) {
            $this->error($e->getMessage(), [], 500);
        }
    }


    // Rename item (file or folder)
    // public function edit()
    // {

    //     // Kiểm tra phương thức HTTP
    //     if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    //         return $this->error('Only PUT method is allowed', [], 405);
    //     }

    //     // Lấy dữ liệu từ request body
    //     $input = json_decode(file_get_contents('php://input'), true);
    //     $oldPath = $this->_sanitizePath($input['oldPath']) ?? '';
    //     $newName = $this->_sanitizePath($input['newName']) ?? '';
    //     $newName = str_replace(':','-', $newName);
    //     // Kiểm tra dữ liệu đầu vào
    //     if (empty($oldPath) || empty($newName)) {
    //         return $this->error('Old path and new name are required', [], 400);
    //     }

    //     try {
    //         // Lay path cua folder cha de noi vao newName tao thanh newPath.
    //         $pathParts = explode(':', $oldPath);
    //         array_pop($pathParts); // Loại bỏ tên mục hiện tại
    //         $parentPath = implode(':', $pathParts);

    //         // Xác định loại mục (folder hoặc file)
    //         if ($this->_isFolder($oldPath)) {
    //             // Sanitize tên thư mục mới
    //             $sanitizedNewName = $this->_sanitizeFolderName($newName);
    //             $ext = 'folder';
    //             $newPath = $parentPath === '' ? $sanitizedNewName : $parentPath . ':' . $sanitizedNewName;
    //             $newPathReal = $this->_getFileRealPath($newPath);
    //             if (!empty($sanitizedNewName)) {

    //                 $oldPathReal = $this->_getFileRealPath($oldPath);
    //                 var_dump($oldPathReal);die;

    //                 $filesInFolder = scandir($oldPathReal);

    //                 try {
    //                     $this->_renameItem($oldPath, $newPath);
    //                 } catch (AppException $e) {
    //                     // Xử lý lỗi nếu đổi tên thất bại
    //                     return $this->error($e->getMessage(), [], 500);
    //                 }
    //                 foreach ($filesInFolder as $file) {
    //                     // Bỏ qua các thư mục đặc biệt
    //                     if ($file === '.' || $file === '..') {
    //                         continue;
    //                     }

    //                      // Tạo đường dẫn cũ và mới cho từng tệp
    //                     $fileOldPath = $oldPath . ':' . $file;
    //                     // var_dump($oldPath, $file);die;

    //                     $file_db = $this->filesModel->getFileByPath(str_replace(':', '/', $fileOldPath));

    //                     // Kiểm tra xem $file_db có phải là mảng và có chứa khóa 'id' hay không
    //                     if (!empty($file_db['id'])) {

    //                           // Tạo đường dẫn mới cho tệp
    //                         $fileNewPath = $newPath . ':' . $file;
    //                         $pathDB = str_replace($this->base_dir, '', $this->_getFileRealPath($fileNewPath));
    //                         $pathDB = ltrim($pathDB, '/');
    //                         var_dump($pathDB);die;
    //                         $updateItem = [
    //                             'path' => $pathDB,
    //                             'updated_at' => date('Y-m-d H:i:s')
    //                         ];

    //                         // Cập nhật đường dẫn trong cơ sở dữ liệu
    //                         $this->filesModel->updateFile($file_db['id'], $updateItem);
    //                     } else {
    //                         return $this->error('Failed to move updated file', [], 500);
    //                     }
    //                 }
    //             } else {
    //                 return $this->error('Invalid new folder name.', [], 400);
    //             }
    //         }

    //         elseif ($this->_isFile($oldPath)) {
    //             $sanitizedNewName = $this->_sanitizeFileName($newName);
    //             $ext = $this->_extension($sanitizedNewName);
    //             // Kiểm tra loại file
    //             if (!in_array($ext, $this->allowed_types)) {
    //                 return $this->error("File type not allowed: $sanitizedNewName", [], 400);
    //             }
    //             // Kiem tra ton tai trong db hay khong
    //             $pathDB = str_replace(':','/', $oldPath);
    //             $pathDB = ltrim($pathDB, '/');
    //             $file_db = $this->filesModel->getFileByPath($pathDB);

    //             if (empty($file_db['id'])){
    //                 return $this->error('Source file not exist in database.', [], 404);
    //             }
    //             //thong tin newPath (path dang duoc ma hoa dang dau : ) va thong tin newPathDB (dang /) de update csdl.
    //             $newPath = $parentPath === '' ? $sanitizedNewName : $parentPath . ':' . $sanitizedNewName;
    //             $newPathDB = str_replace(':', '/', $newPath);
    //             $newPathReal = $this->_getFileRealPath($newPath);
    //             if (!empty($sanitizedNewName)){
    //                 // Đổi tên mục
    //                 @$this->_renameItem($oldPath, $newPath);
    //                 $updateItem= [
    //                     'name'=>$sanitizedNewName,
    //                     'path'=>$newPathDB, // 2024/10/23/tenfile.ext
    //                     'size'=>filesize($newPathReal), // /etc/nginx/www/domain.com/writeables/uploads/2024/10/23/tenfile.ext
    //                     'type'=>$ext,
    //                     'autoclean'=>0,
    //                     'created_at'=>date('Y-m-d H:i:s'),
    //                     'updated_at'=>date('Y-m-d H:i:s')
    //                 ];
    //                 @$this->filesModel->updateFile($file_db['id'], $updateItem);
    //             } else {
    //                 return $this->error('Failed to move updated file', [], 500);
    //             }
    //         } else {
    //             return $this->error('Source path does not exist', [], 404);
    //         }

    //         $this->success(['newName'=>$sanitizedNewName, 'newPath' => $newPath], 'Item renamed successfully.');

    //     } catch (AppException $e) {
    //         $this->error($e->getMessage(), [], 500);
    //     }
    // }

    public function edit()
{
            // Check HTTP method
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            return $this->error('Only PUT method is allowed', [], 405);
        }

        // Get data from request body
        $input = json_decode(file_get_contents('php://input'), true);
        $oldPath = $this->_sanitizePath($input['oldPath']) ?? '';
        $newName = $this->_sanitizePath($input['newName']) ?? '';
        $newName = str_replace(':', '-', $newName);

        // Check input data
    if (empty($oldPath) || empty($newName)) {
        return $this->error('Old path and new name are required', [], 400);
    }

    try {
        // Get parent folder path to concatenate with newName to form newPath.
        $pathParts = explode(':', $oldPath);
        array_pop($pathParts); // Remove current item name
        $parentPath = implode(':', $pathParts);

        // Determine item type (folder or file)
        if ($this->_isFolder($oldPath)) {
            // Sanitize new directory name
            $sanitizedNewName = $this->_sanitizeFolderName($newName);
            $ext = 'folder';
            $newPath = $parentPath === '' ? $sanitizedNewName : $parentPath . ':' . $sanitizedNewName;
            $newPathReal = $this->_getFileRealPath($newPath);

            if (!empty($sanitizedNewName)) {
                // Get real path of old directory
                $oldPathReal = $this->_getFileRealPath($oldPath);

                // Check if old directory exists
                if (!is_dir($oldPathReal)) {
                    return $this->error('Old folder does not exist.', [], 404);
                }

                // Collect all files and subdirectories before renaming
                $filesToUpdate = [];
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($oldPathReal, \RecursiveDirectoryIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::SELF_FIRST
                );
                foreach ($iterator as $item) {
                    // Get relative path of file or subdirectory compared to old directory
                    $relativePath = str_replace($oldPathReal . '/', '', $item->getPathname());
                    $filesToUpdate[] = $relativePath;
                }

                // Rename directory
                try {
                    $this->_renameItem($oldPath, $newPath);
                } catch (AppException $e) {
                    // Handle error if renaming fails
                    return $this->error($e->getMessage(), [], 500);
                }

                // Update paths of files and subdirectories in database
                foreach ($filesToUpdate as $relativePath) {
                    // Create old and new paths for each file or subdirectory
                    $fileOldPath = $oldPath . ':' . $relativePath;
                    $fileNewPath = $newPath . ':' . $relativePath;

                    // Convert paths to format used in database
                    $fileOldPathDB = str_replace(':', '/', $fileOldPath);
                    $fileNewPathDB = str_replace(':', '/', $fileNewPath);

                    // Get file information from database based on old path
                    $file_db = $this->filesModel->getFileByPath($fileOldPathDB);

                    if (!empty($file_db['id'])) {
                        // Update new path in database
                        $updateItem = [
                            'path' => $fileNewPathDB,
                            'updated_at' => date('Y-m-d H:i:s')
                        ];
                        $this->filesModel->updateFile($file_db['id'], $updateItem);
                    } else {
                        error_log("File not found in database: " . $fileOldPathDB);
                    }
                }
            } else {
                return $this->error('Invalid new folder name.', [], 400);
            }
        }
        // Handle when object is file
        elseif ($this->_isFile($oldPath)) {
            $sanitizedNewName = $this->_sanitizeFileName($newName);
            $ext = $this->_extension($sanitizedNewName);
            // Check file type
            if (!in_array($ext, $this->allowed_types)) {
                return $this->error("File type not allowed: $sanitizedNewName", [], 400);
            }
            // Check if exists in database
            $pathDB = str_replace(':', '/', $oldPath);
            $pathDB = ltrim($pathDB, '/');
            $file_db = $this->filesModel->getFileByPath($pathDB);

            if (empty($file_db['id'])) {
                return $this->error('Source file does not exist in database.', [], 404);
            }
            // newPath information (path encoded with : format) and newPathDB information (with / format) to update database.
            $newPath = $parentPath === '' ? $sanitizedNewName : $parentPath . ':' . $sanitizedNewName;
            $newPathDB = str_replace(':', '/', $newPath);
            $newPathReal = $this->_getFileRealPath($newPath);
            if (!empty($sanitizedNewName)) {
                // Rename item
                try {
                    $this->_renameItem($oldPath, $newPath);
                } catch (AppException $e) {
                    return $this->error($e->getMessage(), [], 500);
                }

                $updateItem = [
                    'name' => $sanitizedNewName,
                    'path' => $newPathDB, // 2024/10/23/tenfile.ext
                    'size' => filesize($newPathReal), // /etc/nginx/www/domain.com/writeables/uploads/2024/10/23/tenfile.ext
                    'type' => $ext,
                    'autoclean' => 0,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                $this->filesModel->updateFile($file_db['id'], $updateItem);
            } else {
                return $this->error('Failed to move updated file', [], 500);
            }
        } else {
            return $this->error('Source path does not exist', [], 404);
        }

        $this->success(['newName' => $sanitizedNewName, 'newPath' => $newPath], 'Item renamed successfully.');

    } catch (AppException $e) {
        $this->error($e->getMessage(), [], 500);
    }
}


    // Delete item (file or folder)
    public function delete()
    {
        // Check HTTP method
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            return $this->error('Only DELETE method is allowed', [], 405);
        }
        // Get data from request body
        $input = json_decode(file_get_contents('php://input'), true);
        $path = $input['path'] ?? '';
        // Check input data
        if (empty($path)) {
            return $this->error('Path is required', [], 400);
        }
        // Check if path exists
        if (!$this->_isFolder($path) && !$this->_isFile($path)) {
             return $this->error('Path does not exist', [], 404);
        }

        // Sanitization
        $sanitizedPath = $this->_sanitizePath($path);

        try {
            @$this->_deleteItem($sanitizedPath);
            // deleted file in db
            // $this->filesModel-
            $this->success(['path' => $sanitizedPath], 'Item deleted successfully.');
        } catch (AppException $e) {
            $this->error($e->getMessage(), [], 500);
        }
    }

    // Copy item (file or folder)
    // public function copy()
    // {

    //     // Kiểm tra phương thức HTTP
    //     if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    //         return $this->error('Only POST method is allowed', [], 405);
    //     }

    //     // Lấy dữ liệu từ request body dưới dạng JSON
    //     $input = json_decode(file_get_contents('php://input'), true);

    //     // Lấy dữ liệu từ request body
    //     $sourcePath = $input['sourcePath'] ?? '';
    //     $destinationPath = $input['destinationPath'] ?? '';
    //     $type = $input['type'] ?? ''; // Lấy thông tin type nếu có

    //     // Kiểm tra dữ liệu đầu vào
    //     if (empty($sourcePath) || empty($destinationPath)) {
    //         return $this->error('Source path and destination path are required', [], 400);
    //     }

    //     // Sanitization
    //     $sanitizedSourcePath = $this->_sanitizePath($sourcePath);
    //     $sanitizedDestinationPath = $this->_sanitizePath($destinationPath);

    //     try {
    //         // Xác định loại mục (folder hoặc file) dựa trên thông tin type nếu có
    //         if ($type === 'folder') {
    //             $isFolder = true;
    //         } elseif ($type === 'file') {
    //             $isFolder = false;
    //         } else {
    //             // Nếu không cung cấp type, tự động xác định
    //             if ($this->_isFolder($sanitizedSourcePath)) {
    //                 $isFolder = true;
    //             } elseif ($this->_isFile($sanitizedSourcePath)) {
    //                 $isFolder = false;
    //             } else {
    //                 throw new AppException('Invalid source path - not found 404');
    //             }
    //         }

    //         if ($isFolder) {
    //             $sourceFullPath = $this->_getFolderRealPath($sanitizedSourcePath);
    //             $destinationFullPath = $this->_getFolderRealPath($sanitizedDestinationPath);
    //         } else {
    //             $sourceFullPath = $this->_getFileRealPath($sanitizedSourcePath);
    //             $destinationFullPath = $this->_getFileRealPath($sanitizedDestinationPath);
    //         }

    //         // if (!realpath($sourceFullPath)){
    //         //     throw new AppException('Invalid source path - not found 404');
    //         // }
    //         // Kiểm tra xem đường dẫn đích đã tồn tại chưa
    //         if (file_exists($destinationFullPath)) {
    //             // Tạo đường dẫn mới với hậu tố _copy hoặc _copy1, _copy2, ...
    //             $sanitizedDestinationPath = $this->_generateUniquePath($sanitizedDestinationPath, $isFolder);

    //             $destinationFullPath = $isFolder ? $this->_getFolderRealPath($sanitizedDestinationPath) : $this->_getFileRealPath($sanitizedDestinationPath);

    //         }
    //         $destinationName = explode(':', $sanitizedDestinationPath);
    //         $destinationName = array_pop($destinationName);

    //         $sanitizedDestinationPath = str_replace(':', '/', $sanitizedDestinationPath);

    //         if ($isFolder) {
    //             $sourceRealPath = realpath($sourceFullPath);
    //             $destinationRealPath = realpath($destinationFullPath);

    //             // Nếu thư mục đích chưa tồn tại, xây dựng đường dẫn thực tế
    //             if ($destinationRealPath === false) {
    //                 $destinationParentPath = realpath(dirname($destinationFullPath));
    //                 $destinationRealPath = $destinationParentPath . '/' . basename($destinationFullPath);
    //             }

    //             if (!$this->_copyDir($sourceFullPath, $destinationFullPath)) {
    //                 throw new AppException('Failed to copy folder');
    //             }

    //             $filesInFolder = scandir($destinationFullPath);
    //             if(!empty($filesInFolder)) {
    //                 foreach ($filesInFolder as $file) {
    //                     if ($file === '.' || $file === '..') continue;

    //                     // Lấy thông tin từng file
    //                     $filePath = $destinationFullPath . '/' . $file;
    //                     if (is_file($filePath)) {
    //                         $fileInfo = pathinfo($filePath);
    //                         $fileName = $fileInfo['basename'];
    //                         $ext = $fileInfo['extension'];

    //                         if (!in_array($ext, $this->allowed_types)) {
    //                             return $this->error("File type not allowed: $destinationName", [], 400);
    //                         }
    //                         // Chỉ xử lý file với loại file hợp lệ
    //                         if (!empty($fileInfo)) {
    //                             $copyItem = [
    //                                 'name' => $fileName,
    //                                 'path' => str_replace(':', '/', $sanitizedDestinationPath . '/' . $fileName),
    //                                 'size' => filesize($filePath),
    //                                 'type' => $ext,
    //                                 'autoclean' => 0,
    //                                 'created_at' => date('Y-m-d H:i:s'),
    //                                 'updated_at' => date('Y-m-d H:i:s')
    //                             ];
    //                             // Save thông tin file vào database
    //                             @$this->filesModel->addFile($copyItem);
    //                         }
    //                     }
    //                 }
    //             } else {
    //                 return $this->error("Files are empty", [], 400);
    //             }
    //         } else {
    //             if (!copy($sourceFullPath, $destinationFullPath)) {
    //                 throw new AppException('Failed to copy file');
    //             }

    //             $ext = $this->_extension($destinationName);
    //             if (!in_array($ext, $this->allowed_types)) {
    //                 return $this->error("File type not allowed: $destinationName", [], 400);
    //             }

    //             if (!empty($destinationName)) {
    //                 $copyItem= [
    //                     'name'=>$destinationName,
    //                     'path'=>$sanitizedDestinationPath, // 2024/10/23/tenfile.ext
    //                     'size'=>filesize($destinationFullPath), // /etc/nginx/www/domain.com/writeables/uploads/2024/10/23/tenfile.ext
    //                     'type'=>$ext,
    //                     'autoclean'=>0,
    //                     'created_at'=>date('Y-m-d H:i:s'),
    //                     'updated_at'=>date('Y-m-d H:i:s')
    //                 ];
    //                 @$this->filesModel->addFile($copyItem);
    //             } else {
    //                 return $this->error('Failed to move copied file', [], 500);
    //             }
    //         }
    //         $this->success(['destinationName'=>$destinationName, 'destinationPath' => $sanitizedDestinationPath], 'Item copied successfully.');
    //     } catch (AppException $e) {
    //         $this->error($e->getMessage(), [], 500);
    //     }

    // }

    public function copy()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return $this->error('Only POST method is allowed', [], 405);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $sourcePath = $input['sourcePath'] ?? '';
    $destinationPath = $input['destinationPath'] ?? '';
    $type = $input['type'] ?? '';

    if (empty($sourcePath) || empty($destinationPath)) {
        return $this->error('Source path and destination path are required', [], 400);
    }

    $sanitizedSourcePath = $this->_sanitizePath($sourcePath);
    $sanitizedDestinationPath = $this->_sanitizePath($destinationPath);

    try {
        $isFolder = ($type === 'folder') || (!$type && $this->_isFolder($sanitizedSourcePath));
        $sourceFullPath = $isFolder ? $this->_getFolderRealPath($sanitizedSourcePath) : $this->_getFileRealPath($sanitizedSourcePath);
        $destinationFullPath = $isFolder ? $this->_getFolderRealPath($sanitizedDestinationPath) : $this->_getFileRealPath($sanitizedDestinationPath);

        // Check if destination is within source
        $realSourcePath = realpath($sourceFullPath);
        $realDestinationParent = realpath(dirname($destinationFullPath));

        if ($realSourcePath && $realDestinationParent && strpos($realDestinationParent, $realSourcePath) === 0) {
            // If destination is within source, create completely different name to avoid recursion
            $sanitizedDestinationPath = $this->_generateUniquePath($sanitizedDestinationPath, $isFolder);
            $destinationFullPath = $isFolder ? $this->_getFolderRealPath($sanitizedDestinationPath) : $this->_getFileRealPath($sanitizedDestinationPath);
        }

        // If destination directory already exists, create new name for destination directory
        if (file_exists($destinationFullPath)) {
            $sanitizedDestinationPath = $this->_generateUniquePath($sanitizedDestinationPath, $isFolder);
            $destinationFullPath = $isFolder ? $this->_getFolderRealPath($sanitizedDestinationPath) : $this->_getFileRealPath($sanitizedDestinationPath);
        }

        $destinationName = basename($sanitizedDestinationPath);
        $sanitizedDestinationPathForDB = str_replace(':', '/', $sanitizedDestinationPath);

        if ($isFolder) {
            // Ensure destination directory exists
            $destinationDir = dirname($destinationFullPath);
            if (!is_dir($destinationDir) && !mkdir($destinationDir, 0755, true)) {
                throw new AppException('Failed to create destination directory');
            }

            // Copy directory with exclusion of destination directory to avoid recursion
            $test = $this->_copyDir($sourceFullPath, $destinationFullPath, [$destinationFullPath]);
            if (!$test) {
                throw new AppException('Failed to copy folder');
            }

            // Loop through all files in newly copied directory and add to database
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($destinationFullPath, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $item) {
                if ($item->isFile()) { // Only process files, skip directories
                    $relativePath = str_replace($this->base_dir . '/', '', $item->getPathname());
                    $fileInfo = pathinfo($item->getPathname());
                    $fileName = $fileInfo['basename'];
                    $ext = isset($fileInfo['extension']) ? $fileInfo['extension'] : '';

                    if (!in_array($ext, $this->allowed_types)) {
                        continue; // Skip file types not allowed
                    }

                    // Check if file already exists in DB to avoid duplicates
                    $existingFile = $this->filesModel->getFileByPath($relativePath);
                    if (!$existingFile) {
                        $copyItem = [
                            'name' => $fileName,
                            'path' => $relativePath,
                            'size' => filesize($item->getPathname()),
                            'type' => $ext,
                            'autoclean' => 0,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ];
                        $this->filesModel->addFile($copyItem);
                    }
                }
            }
        } else {
            // Copy file
            $destinationDir = dirname($destinationFullPath);
            if (!is_dir($destinationDir) && !mkdir($destinationDir, 0755, true)) {
                throw new AppException('Failed to create destination directory');
            }
            if (!copy($sourceFullPath, $destinationFullPath)) {
                throw new AppException('Failed to copy file');
            }

            $ext = $this->_extension(basename($sanitizedDestinationPathForDB));
            if (!in_array($ext, $this->allowed_types)) {
                return $this->error("File type not allowed", [], 400);
            }

            $relativePath = str_replace($this->base_dir . '/', '', $destinationFullPath);
            $relativePath = str_replace('/', '/', $relativePath);

            // Check if file already exists in DB to avoid duplicates
            $existingFile = $this->filesModel->getFileByPath($relativePath);
            if (!$existingFile) {
                $copyItem = [
                    'name' => basename($sanitizedDestinationPathForDB),
                    'path' => $relativePath,
                    'size' => filesize($destinationFullPath),
                    'type' => $ext,
                    'autoclean' => 0,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                $this->filesModel->addFile($copyItem);
            }
        }

        $this->success([
            'destinationName' => basename($sanitizedDestinationPathForDB),
            'destinationPath' => $sanitizedDestinationPath
        ], 'Item copied successfully.');
    } catch (AppException $e) {
        $this->error($e->getMessage(), [], 500);
    }
}


    // Hàm tạo đường dẫn mới nếu đã tồn tại
    // protected function _generateUniquePath($destinationPath, $isFolder)
    // {
    //     $pathParts = explode(':', $destinationPath);
    //     $itemName = array_pop($pathParts);
    //     $parentPath = implode(':', $pathParts);

    //     $baseName = $itemName;
    //     $extension = '';

    //     if (!$isFolder) {
    //         // Tách tên tệp và phần mở rộng
    //         $extension = $this->_extension($itemName);
    //         $dotPosition = strrpos($itemName, '.');
    //         if ($dotPosition !== false) {
    //             $baseName = substr($itemName, 0, $dotPosition);
    //         }
    //     }

    //     $newName = $baseName . '_copy' . $extension;
    //     $newPath = $parentPath === '' ? $newName : $parentPath . ':' . $newName;

    //     $counter = 1;
    //     // Kiểm tra và tạo tên mới nếu đã tồn tại
    //     while (file_exists($isFolder ? $this->_getFolderRealPath($newPath) : $this->_getFileRealPath($newPath))) {
    //         if ($isFolder) {
    //             $newName = $baseName . '_copy' . $counter;
    //             $newPath = $parentPath === '' ? $newName : $parentPath . ':' . $newName;
    //         } else {
    //             $newName = $baseName . '_copy' . $counter . $extension;
    //             $newPath = $parentPath === '' ? $newName : $parentPath . ':' . $newName;
    //         }
    //         $counter++;
    //     }

    //     return $newPath;
    // }

    // protected function _generateUniquePath($destinationPath, $isFolder)
    // {
    //     $pathParts = explode(':', $destinationPath);
    //     $itemName = array_pop($pathParts);
    //     $parentPath = implode(':', $pathParts);

    //     $baseName = $itemName;
    //     $extension = '';

    //     if (!$isFolder) {
    //         // Tách tên tệp và phần mở rộng
    //         $extension = $this->_extension($itemName);
    //         $dotPosition = strrpos($itemName, '.');
    //         if ($dotPosition !== false) {
    //             $baseName = substr($itemName, 0, $dotPosition);
    //         }

    //         // Đảm bảo phần mở rộng có dấu .
    //         $extension = '.' . $extension;
    //     }

    //     $newName = $baseName . '_copy' . $extension;
    //     $newPath = $parentPath === '' ? $newName : $parentPath . ':' . $newName;

    //     $counter = 1;
    //     // Kiểm tra và tạo tên mới nếu đã tồn tại
    //     while (file_exists($isFolder ? $this->_getFolderRealPath($newPath) : $this->_getFileRealPath($newPath))) {
    //         if ($isFolder) {
    //             $newName = $baseName . '_copy' . $counter;
    //             $newPath = $parentPath === '' ? $newName : $parentPath . ':' . $newName;
    //         } else {
    //             $newName = $baseName . '_copy' . $counter . $extension;
    //             $newPath = $parentPath === '' ? $newName : $parentPath . ':' . $newName;
    //         }
    //         $counter++;
    //     }

    //     return $newPath;
    // }


    protected function _generateUniquePath($destinationPath, $isFolder)
    {
        // Sử dụng '/' làm phân cách thư mục
        $pathParts = explode('/', $destinationPath);
        $itemName = array_pop($pathParts);
        $parentPath = implode('/', $pathParts);

        $baseName = $itemName;
        $extension = '';

        if (!$isFolder) {
            // Tách tên tệp và phần mở rộng
            $extension = $this->_extension($itemName);
            $dotPosition = strrpos($itemName, '.');
            if ($dotPosition !== false) {
                $baseName = substr($itemName, 0, $dotPosition);
            }

            // Đảm bảo phần mở rộng có dấu .
            $extension = '.' . $extension;
        }

        // Sử dụng số đếm để tạo tên duy nhất
        $counter = 1;
        do {
            if ($isFolder) {
                $newName = $baseName . '_copy' . ($counter > 1 ? $counter : '');

            } else {
                $newName = $baseName . '_copy' . ($counter > 1 ? $counter : '') . $extension;
            }
            $newPath = $parentPath === '' ? $newName : $parentPath . '/' . $newName;

            $counter++;
        } while (file_exists($isFolder ? $this->_getFolderRealPath($newPath) : $this->_getFileRealPath($newPath)));
        return $newPath;
    }


    // Di chuyển mục (file hoặc folder)
    // public function move()
    // {
    //     // Kiểm tra phương thức HTTP
    //     if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    //         return $this->error('Only POST method is allowed', [], 405);
    //     }

    //     // Lấy dữ liệu từ request body dưới dạng JSON
    //     $input = json_decode(file_get_contents('php://input'), true);

    //     // Lấy dữ liệu từ request body
    //     $sourcePath = $input['sourcePath'] ?? '';
    //     $destinationPath = $input['destinationPath'] ?? '';
    //     $type = $input['type'] ?? ''; // Lấy thông tin type nếu có

    //     // Kiểm tra dữ liệu đầu vào
    //     if (empty($sourcePath) || empty($destinationPath)) {
    //         return $this->error('Source path and destination path are required', [], 400);
    //     }

    //     // Sanitization
    //     $sanitizedSourcePath = $this->_sanitizePath($sourcePath);
    //     $sanitizedDestinationPath = $this->_sanitizePath($destinationPath);

    //     try {
    //         // Xác định loại mục (folder hoặc file) dựa trên thông tin type nếu có
    //         if ($type === 'folder') {
    //             $isFolder = true;
    //         } elseif ($type === 'file') {
    //             $isFolder = false;
    //         } else {
    //             // Nếu không cung cấp type, tự động xác định
    //             if ($this->_isFolder($sanitizedSourcePath)) {
    //                 $isFolder = true;
    //             } elseif ($this->_isFile($sanitizedSourcePath)) {
    //                 $isFolder = false;
    //             } else {
    //                 throw new AppException('Invalid source path - not found 404');
    //             }
    //         }

    //         if ($isFolder) {
    //             $sourceFullPath = $this->_getFolderRealPath($sanitizedSourcePath);
    //             $destinationFullPath = $this->_getFolderRealPath($sanitizedDestinationPath);
    //         } else {
    //             $sourceFullPath = $this->_getFileRealPath($sanitizedSourcePath);
    //             $destinationFullPath = $this->_getFileRealPath($sanitizedDestinationPath);
    //         }

    //         // Kiểm tra xem đường dẫn nguồn có hợp lệ không
    //         if (!realpath($sourceFullPath)) {
    //             throw new AppException('Invalid source path - not found 404');
    //         }

    //         // Kiểm tra xem đường dẫn đích đã tồn tại chưa
    //         if (file_exists($destinationFullPath)) {
    //             // Tạo đường dẫn mới với hậu tố _copy hoặc _copy1, _copy2, ...
    //             throw new AppException('Destination source path is exists!');
    //         }

    //         if($isFolder) {
    //             // Di chuyển mục
    //             if (!rename($sourceFullPath, $destinationFullPath)) {
    //                 throw new AppException('Failed to move item');
    //             }

    //             // Kiểm tra và tạo thư mục đích nếu chưa tồn tại
    //             if (!is_dir($destinationFullPath)) {
    //                 if (!mkdir($destinationFullPath, 0777, true)) {
    //                     throw new AppException('Failed to create destination directory');
    //                 }
    //             }

    //             // Duyệt đệ quy tất cả các file và thư mục con để cập nhật đường dẫn trong DB
    //             $iterator = new \RecursiveIteratorIterator(
    //                 new \RecursiveDirectoryIterator($destinationFullPath, \RecursiveDirectoryIterator::SKIP_DOTS),
    //                 \RecursiveIteratorIterator::SELF_FIRST
    //             );

    //             foreach ($iterator as $item) {
    //                 $itemPath = $item->getPathname();

    //                 // Lấy đường dẫn tương đối so với base_dir
    //                 $relativePath = str_replace($this->base_dir, '', $itemPath);
    //                 $relativePath = ltrim($relativePath, '/');

    //                 // Cập nhật đường dẫn trong DB
    //                 $file_db = $this->filesModel->getFileByPath($relativePath);
    //                 var_dump($relativePath, $file_db);die;

    //                 if (!empty($file_db['id'])) {
    //                     $newPathDB = str_replace($this->base_dir, '', $itemPath);
    //                     $newPathDB = ltrim($newPathDB, '/');

    //                     $movedItem = [
    //                         'path' => $newPathDB,
    //                     ];
    //                     @$this->filesModel->updateFile($file_db['id'], $movedItem);
    //                 } else {
    //                     // Xử lý khi không tìm thấy bản ghi trong DB
    //                     // Bạn có thể ghi log hoặc bỏ qua
    //                     // Ví dụ: Ghi log
    //                     return $this->error('Failed to move uploaded file', [], 500);
    //                 }
    //             }
    //         } else {
    //             // Di chuyển mục
    //             if (!rename($sourceFullPath, $destinationFullPath)) {
    //                 throw new AppException('Failed to move item');
    //             }

    //             $sourcePath = str_replace(':', '/', $sourcePath);
    //             $newPath = str_replace('/', ':', $sanitizedDestinationPath);
    //             $pathDB = str_replace(':', '/', $newPath);
    //             $file_db = $this->filesModel->getFileByPath($sourcePath);

    //             if (!empty($file_db['id'])) {
    //                 $moveItem= [
    //                     'path'=>$pathDB, // 2024/10/23/tenfile.ext
    //                 ];
    //                 @$this->filesModel->updateFile($file_db['id'], $moveItem);
    //             } else {
    //                 return $this->error('Failed to move uploaded file', [], 500);
    //             }
    //         }
    //         $this->success(['destinationPath' => $sanitizedDestinationPath], 'Item moved successfully.');
    //     } catch (AppException $e) {
    //         $this->error($e->getMessage(), [], 500);
    //     }
    // }

    // Di chuyển mục (file hoặc folder)
    public function move()
    {
        // Check HTTP method
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->error('Only POST method is allowed', [], 405);
        }

        // Get data from request body as JSON
        $input = json_decode(file_get_contents('php://input'), true);

        // Get data from request body
        $sourcePath = $input['sourcePath'] ?? '';
        $destinationPath = $input['destinationPath'] ?? '';
        $type = $input['type'] ?? ''; // Get type information if available

        // Check input data
        if (empty($sourcePath) || empty($destinationPath)) {
            return $this->error('Source path and destination path are required', [], 400);
        }

        // Sanitization
        $sanitizedSourcePath = $this->_sanitizePath($sourcePath);
        $sanitizedDestinationPath = $this->_sanitizePath($destinationPath);
        try {
            // Determine item type (folder or file) based on type information if available
            if ($type === 'folder') {
                $isFolder = true;
            } elseif ($type === 'file') {
                $isFolder = false;
            } else {
                // If type not provided, automatically determine
                if ($this->_isFolder($sanitizedSourcePath)) {
                    $isFolder = true;
                } elseif ($this->_isFile($sanitizedSourcePath)) {
                    $isFolder = false;
                } else {
                    throw new AppException('Invalid source path - not found 404');
                }
            }

            if ($isFolder) {
                $sourceFullPath = $this->_getFolderRealPath($sanitizedSourcePath);
                $destinationFullPath = $this->_getFolderRealPath($sanitizedDestinationPath);
            } else {
                $sourceFullPath = $this->_getFileRealPath($sanitizedSourcePath);
                $destinationFullPath = $this->_getFileRealPath($sanitizedDestinationPath);
            }

            // Kiểm tra xem đường dẫn nguồn có hợp lệ không
            if (!realpath($sourceFullPath)) {
                throw new AppException('Invalid source path - not found 404');
            }

                    // Check if destination path already exists
        if (file_exists($destinationFullPath)) {
            // Create new path with _copy suffix or _copy1, _copy2, ...
                throw new AppException('Destination source path already exists!');
            }

            if ($isFolder) {
                // **Nhúng hàm isSubPath vào trong hàm move**
                $isSubPath = function($parentPath, $childPath) {
                    $parentPath = realpath($parentPath);
                    $childPath = realpath($childPath);

                    if ($parentPath === false || $childPath === false) {
                        return false;
                    }

                    // Thêm dấu phân cách ở cuối để tránh sai lệch (ví dụ: /path/to/dir1 và /path/to/dir10)
                    $parentPath = rtrim($parentPath, '/') . '/';
                    $childPath = rtrim($childPath, '/') . '/';

                    return strpos($childPath, $parentPath) === 0;
                };

                // Kiểm tra nếu destination nằm trong source
                $realSourcePath = realpath($sourceFullPath);
                $realDestinationParent = realpath(dirname($destinationFullPath));

                // if ($realSourcePath && $realDestinationParent && $isSubPath($realSourcePath, $realDestinationParent)) {
                //     // Nếu destination nằm trong source, tạo tên mới hoàn toàn khác để tránh đệ quy
                //     $sanitizedDestinationPath = $this->_generateUniquePath($sanitizedDestinationPath, $isFolder);
                //     $destinationFullPath = $this->_getFolderRealPath($sanitizedDestinationPath);
                // }

                // // Nếu thư mục đích đã tồn tại, tạo tên mới cho thư mục đích
                // if (file_exists($destinationFullPath)) {
                //     $sanitizedDestinationPath = $this->_generateUniquePath($sanitizedDestinationPath, $isFolder);
                //     $destinationFullPath = $this->_getFolderRealPath($sanitizedDestinationPath);
                // }

                if ($realSourcePath && $realDestinationParent && $isSubPath($realSourcePath, $realDestinationParent)) {
                    throw new AppException('Cannot move a directory into itself or its subdirectory.');
                }

                // **Step 1: Thu thập danh sách các file và đường dẫn cũ trước khi di chuyển**
                $sourceIterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($sourceFullPath, \RecursiveDirectoryIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::SELF_FIRST
                );

                $filesToUpdate = [];

                foreach ($sourceIterator as $item) {
                    if ($item->isFile()) {
                        $itemPath = $item->getPathname();
                        // Tính toán đường dẫn tương đối dựa trên base_dir
                        $relativePath = substr($itemPath, strlen($this->base_dir) + 1); // +1 để loại bỏ dấu phân cách
                        $filesToUpdate[] = $relativePath;
                    }
                }

                // **Step 2: Di chuyển thư mục**
                if (!rename($sourceFullPath, $destinationFullPath)) {
                    throw new AppException('Failed to move item');
                }

                // Kiểm tra và tạo thư mục đích nếu chưa tồn tại
                if (!is_dir($destinationFullPath)) {
                    if (!mkdir($destinationFullPath, 0777, true)) {
                        throw new AppException('Failed to create destination directory');
                    }
                }

                // **Step 3: Cập nhật các đường dẫn mới trong DB**
                foreach ($filesToUpdate as $oldRelativePath) {
                    // Tính toán đường dẫn mới bằng cách thay thế phần đường dẫn cũ bằng đường dẫn mới
                    $sanitizedDestinationPath = str_replace(':', '/', $sanitizedDestinationPath);
                    $sanitizedSourcePath = str_replace(':', '/', $sanitizedSourcePath);
                    $newRelativePath = str_replace($sanitizedSourcePath, $sanitizedDestinationPath, $oldRelativePath);
                    // Cập nhật đường dẫn trong DB
                    $file_db = $this->filesModel->getFileByPath($oldRelativePath);
                    if (!empty($file_db['id'])) {
                        $movedItem = [
                            'path' => $newRelativePath,
                        ];
                        @$this->filesModel->updateFile($file_db['id'], $movedItem);
                    } else {
                        return $this->error('Failed to move uploaded file', [], 500);
                    }
                }
            } else {
                // **Di chuyển tệp tin đơn lẻ**

                // Di chuyển mục (tệp tin)
                if (!rename($sourceFullPath, $destinationFullPath)) {
                    throw new AppException('Failed to move item');
                }

                // Cập nhật đường dẫn trong DB cho tệp tin
                // Tính toán đường dẫn tương đối dựa trên base_dir
                $relativePathOld = substr($sourceFullPath, strlen($this->base_dir) + 1);
                $relativePathOld = str_replace('/', '/', $relativePathOld);

                $relativePathNew = substr($destinationFullPath, strlen($this->base_dir) + 1);
                $relativePathNew = str_replace('/', '/', $relativePathNew);

                // Kiểm tra loại file
                $ext = pathinfo($relativePathNew, PATHINFO_EXTENSION);
                if (!in_array($ext, $this->allowed_types)) {
                    return $this->error("File type not allowed", [], 400);
                }

                // Cập nhật đường dẫn trong DB
                $file_db = $this->filesModel->getFileByPath($relativePathOld);
                if (!empty($file_db['id'])) {
                    $moveItem = [
                        'path' => $relativePathNew,
                    ];
                    @$this->filesModel->updateFile($file_db['id'], $moveItem);
                } else {
                    return $this->error('Failed to move uploaded file', [], 500);
                }
            }

            $this->success(['destinationPath' => $sanitizedDestinationPath], 'Item moved successfully.');
        } catch (AppException $e) {
            $this->error($e->getMessage(), [], 500);
        }
    }

    public function download()
    {
        // Kiểm tra phương thức HTTP
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            return $this->error('Only GET method is allowed', [], 405);
        }

        // Lấy dữ liệu từ query string
        $path = $_GET['path'] ?? '';

        // Kiểm tra dữ liệu đầu vào
        if (empty($path)) {
            return $this->error('Path is required', [], 400);
        }

        // Sanitization
        $sanitizedPath = $this->_sanitizePath($path);

        try {
            // Kiểm tra xem đường dẫn có phải là tệp tin không
            if (!$this->_isFile($sanitizedPath)) {
                throw new AppException('File does not exist', 404);
            }

            // Lấy đường dẫn đầy đủ của tệp tin
            $fileFullPath = $this->_getFileRealPath($sanitizedPath);
            $fileFullPath = realpath($fileFullPath);

            // Kiểm tra quyền truy cập (nếu cần)
            // Ví dụ: kiểm tra xem người dùng có quyền tải xuống tệp này không

            // Gửi tệp tin tới người dùng
            if (file_exists($fileFullPath)) {
                // Xác định loại tệp tin
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $fileFullPath);
                finfo_close($finfo);

                // Lấy tên tệp tin
                $fileName = basename($fileFullPath);

                // Gửi headers
                header('Content-Description: File Transfer');
                header('Content-Type: ' . $mimeType);
                header('Content-Disposition: attachment; filename="' . $fileName . '"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($fileFullPath));
                flush(); // Flush system output buffer

                // Đọc tệp tin và gửi tới người dùng
                readfile($fileFullPath);
                exit;
            } else {
                throw new AppException('File not found', 404);
            }
        } catch (AppException $e) {
            return $this->error($e->getMessage(), [], $e->getCode() ?: 500);
        }
    }

    public function saves(){
        header('Content-Type: application/json; charset=utf-8');

        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if (!$data || !isset($data['images']) || !is_array($data['images'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Dữ liệu không hợp lệ']);
            exit;
        }

        // Lấy cấu hình từ config files (tham khảo hàm upload)
        $config_files = config('files');
        $allowed_types = isset($config_files['allowed_types']) ? $config_files['allowed_types'] : ['jpg','jpeg','png','gif','webp'];

        // Xử lý outputPath: dùng để xác định thư mục lưu file
        $outputPath = isset($data['path']) ? $data['path'] : date('Y:m:d');
        $path = $this->_sanitizePath($outputPath);
        $path_database = str_replace(':', '/', $path);
        $folder_real = str_replace(':', DIRECTORY_SEPARATOR, $path);
        $upload_dir = $this->base_dir . DIRECTORY_SEPARATOR . $folder_real;
        if (!empty($folder_real) && !is_dir($upload_dir)) {
            try {
                $this->_createFolder($path);
            } catch (AppException $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to create upload directory: ' . $e->getMessage()]);
                exit;
            }
        }

        $imagesData = $data['images'];
        $savedFiles = [];
        $originalDBRecords = []; // Mảng lưu thông tin DB của file gốc
        $resize = '';
        foreach ($imagesData as $sizeKey => $group) {
            if (!is_array($group)) {
                continue;
            }
            // resize update
            if (strpos($sizeKey, 'x')) {
                $resize .= (!empty($resize) ? ';' : ''). $sizeKey;
            }
            foreach ($group as $item) {
                // Kiểm tra các trường cần thiết
                if (!isset($item['data'], $item['filename'], $item['type'], $item['size'])) {
                    continue;
                }
                $type = strtolower($item['type']);
                if (!in_array($type, $allowed_types)) {
                    continue; // Bỏ qua nếu định dạng không cho phép
                }
                $rawName = $item['filename'];
                $sanitizedName = $this->_sanitizeFileName($rawName);
                if (strpos($sanitizedName, ".") === false) {
                    $sanitizedName .= "." . $type;
                }
                // Xác định đường dẫn tương đối và tuyệt đối đến file
                $destinationPath = $this->base_dir . DIRECTORY_SEPARATOR . $folder_real . DIRECTORY_SEPARATOR . $sanitizedName;

                // Nếu file đã tồn tại, thêm hậu tố số (theo cách của hàm upload)
                $basename = pathinfo($sanitizedName, PATHINFO_FILENAME);
                $ext = pathinfo($sanitizedName, PATHINFO_EXTENSION);
                $counter = 1;
                while (file_exists($destinationPath)) {
                    $newFileName = $basename . '_' . $counter . '.' . $ext;
                    $destinationPath = $this->base_dir . DIRECTORY_SEPARATOR . $folder_real . DIRECTORY_SEPARATOR . $newFileName;
                    $counter++;
                }

                // Lấy kích thước mong muốn từ trường 'size' (ví dụ: "853x853")
                $desiredSize = (strpos($sizeKey, 'x') !== false) ? $sizeKey : $item['size'];
                $dimensions = explode("x", $desiredSize);
                if (count($dimensions) != 2) {
                    continue;
                }
                $targetWidth = intval($dimensions[0]);
                $targetHeight = intval($dimensions[1]);

                // Lấy chất lượng ảnh, nếu không có thì mặc định là 90
                $quality = isset($item['quality']) ? intval($item['quality']) : 90;

                try {
                    // Tạo instance ảnh từ base64
                    $imgProcessor = iMagify::loadFromBase64($item['data']);

                    // Nếu không phải file original, resize theo kích thước mong muốn
                    if (strtolower($sizeKey) !== 'original' && $targetWidth > 0 && $targetHeight > 0) {
                        $imgProcessor->resize($targetWidth, $targetHeight);
                    }

                    // Chuyển đổi ảnh sang định dạng mong muốn
                    $imgProcessor->convert($type, $quality);

                    // Đảm bảo thư mục lưu file tồn tại
                    $dir = dirname($destinationPath);
                    if (!is_dir($dir)) {
                        mkdir($dir, 0755, true);
                    }

                    if ($imgProcessor->save($destinationPath, $quality)) {
                        $savedFiles[] = $destinationPath;

                        // Nếu đây là file original, lưu thêm thông tin vào database
                        if (strtolower($sizeKey) === 'original') {
                            if ($ext == 'webp') continue;
                            $item_path = (!empty($path_database)) ? $path_database . '/' . basename($destinationPath) : basename($destinationPath);
                            $insertItem = [
                                'name' => basename($destinationPath),
                                'path' => $item_path,
                                'size' => filesize($destinationPath),
                                'type' => $ext,
                                'resize'    => $resize,
                                'autoclean' => 0,
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s')
                            ];
                            $insertId = @$this->filesModel->addFile($insertItem);
                            if ($insertId) {
                                $originalDBRecords[] = [
                                    'id' => $insertId,
                                    'name' => basename($destinationPath),
                                    'path' => $item_path,
                                    'pth' => $path
                                ];
                            } else {
                                http_response_code(500);
                                echo json_encode(['error' => 'Failed to insert file info into database']);
                                exit;
                            }
                        }
                    } else {
                        $savedFiles[] = "Lỗi lưu file: " . $destinationPath;
                    }
                    $imgProcessor->destroy();
                } catch (\Exception $e) {
                    $savedFiles[] = "Lỗi: " . $e->getMessage();
                }
            }
        }

        echo json_encode([
            'status' => 'success',
            'files' => $savedFiles,
            'original_db' => $originalDBRecords
        ]);
    }


     // new upload function
     function upload()
     {
         if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
             Logger::error('Error 1: Only POST method is allowed');
             return $this->error('Only POST method is allowed', [], 405);
         }
 
         if (!isset($_FILES['files'])) {
             Logger::error('Error 2: No files uploaded');
             return $this->error('No files uploaded', [], 400);
         }
 
         $config_files = config('files');
         $allowed_types = $config_files['allowed_types'] ?? [];
         $max_file_size = $config_files['max_file_size'] ?? 10485760; // 10MB mặc định
         $max_file_count = $config_files['max_file_count'] ?? 10;
 
         $path = $_POST['path'] ?? '';
         $path = $this->_sanitizePath($path);
         $path_database = str_replace(':', '/', $path);
         $folder_real = str_replace(':', DIRECTORY_SEPARATOR, $path);
         $upload_dir = $this->base_dir . DIRECTORY_SEPARATOR . $folder_real;
 
         if (!empty($folder_real) && !is_dir($upload_dir)) {
             try {
                 $this->_createFolder($path);
             } catch (AppException $e) {
                 return $this->error('Failed to create upload directory: ' . $e->getMessage(), [], 500);
             }
         }
 
         $uploadedFiles = [];
         foreach ($_FILES['files']['name'] as $key => $name) {
             $tmp_name = $_FILES['files']['tmp_name'][$key];
             $size = $_FILES['files']['size'][$key];
             $error = $_FILES['files']['error'][$key];
             $ext = $this->_extension($name); // Lấy phần mở rộng: mp4
             // Debug file tạm
             error_log("Processing file: $name, tmp_name: $tmp_name, size: $size, error: $error");
 
             if ($error !== UPLOAD_ERR_OK) {
                 Logger::error("Error 3: Upload error for $name - " . $this->_fileUploadErrorMessage($error));
                 return $this->error($this->_fileUploadErrorMessage($error), [], 400);
             }
             if ($size > $max_file_size) {
                 Logger::error("Error 4: File $name exceeds maximum allowed size");
                 return $this->error('File exceeds maximum allowed size', [], 400);
             }
             if (!in_array($ext, $allowed_types)) {
                 Logger::error("Error 5: File type not allowed for $name");
                 return $this->error("File type not allowed: $name", [], 400);
             }
             $fileCount = count($_FILES['files']['name']);
             if ($fileCount > $max_file_count) {
                 Logger::error("Error 6: Too many files uploaded: $fileCount");
                 return $this->error('Too many files uploaded. Maximum allowed is ' . $max_file_count, [], 400);
             }
 
             // Xử lý chunk nếu có chunkIndex và totalChunks
             if (isset($_POST['chunkIndex']) && isset($_POST['totalChunks'])) {
                 $chunkIndex = (int)$_POST['chunkIndex'];
                 $totalChunks = (int)$_POST['totalChunks'];
 
                 // Lấy originalFileName nếu có, dùng để làm fallback nếu không có uploadToken
                 $originalFileName = $_POST['originalFileName'] ?? '';
                 if (empty($originalFileName)) {
                     // Nếu không có originalFileName, tự động cắt _part_X từ tên file
                     $originalFileName = preg_replace('/_part_\d+/', '', pathinfo($name, PATHINFO_FILENAME));
                     $originalFileName = $this->_toSlug($originalFileName);
                 } else {
                     // Loại bỏ đuôi file từ originalFileName nếu có
                     $originalFileName = pathinfo($originalFileName, PATHINFO_FILENAME);
                 }
 
                 // Lấy uploadToken từ POST nếu có
                 $uploadToken = $_POST['uploadToken'] ?? '';
                 // Nếu có uploadToken thì dùng nó để tạo thư mục tạm, ngược lại dùng originalFileName
                 $tempDir = $upload_dir . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . $uploadToken . DIRECTORY_SEPARATOR;
 
                 if (!is_dir($tempDir) && !mkdir($tempDir, 0777, true) && !is_dir($tempDir)) {
                     return $this->error("Không thể tạo thư mục tạm: $tempDir", [], 500);
                 }
 
                 $tempPath = $tempDir . "_part_$chunkIndex.$ext";
 
                 // Di chuyển file tạm vào thư mục chunk
                 if (!$this->_movefile($tmp_name, $tempPath)) {
                     return $this->error("Failed to move chunk to $tempPath", [], 500);
                 }
 
                 // Khi đủ chunk, ghép file
                 if ($chunkIndex + 1 === $totalChunks) {
                     // Đặt tên file đầu ra, sử dụng originalFileName để giữ nhất quán
                     $newFileName = $originalFileName . '.' . $ext;
                     $finalPath = $upload_dir . DIRECTORY_SEPARATOR . $newFileName;
                     if (file_exists($finalPath)) {
                         if (!unlink($finalPath)) {
                             Logger::error("Failed to delete existing file: $finalPath");
                             return $this->error("Không thể xóa file cũ: $finalPath", [], 500);
                         }
                     }
                     if ($this->_isMaliciousFile($newFileName, $tempPath, $config_files)) {
                         return $this->error('File is malicious or not allowed', [], 400);
                     }
 
                     $output = @fopen($finalPath, 'wb');
                     if ($output === false) {
                         return $this->error("Không thể tạo file cuối cùng: $finalPath", [], 500);
                     }
 
                     try {
                         for ($i = 0; $i < $totalChunks; $i++) {
                             $partPath = $tempDir . "_part_$i.$ext";
                             error_log("Checking chunk: $partPath");
                             if (!file_exists($partPath)) {
                                 Logger::error("Missing chunk: $partPath");
                                 throw new AppException("Thiếu chunk $i");
                             }
                             $chunk = @fopen($partPath, 'rb');
                             if ($chunk === false) {
                                 Logger::error("Cannot open chunk: $partPath");
                                 throw new AppException("Không thể mở chunk $i");
                             }
                             stream_copy_to_stream($chunk, $output);
                             fclose($chunk);
                             unlink($partPath); // Xóa chunk sau khi ghép
                         }
                         fclose($output);
                         // Xóa thư mục tạm chứa các chunk
                         if (is_dir($tempDir)) {
                             array_map('unlink', glob($tempDir . '*'));
                             rmdir($tempDir);
                         }
                         // Kiểm tra file sau khi ghép
                         if (!file_exists($finalPath)) {
                             throw new AppException("Final file $finalPath not created.");
                         }
                         $fileSize = filesize($finalPath);
                         if ($fileSize === false) {
                             throw new AppException("Failed to get size of $finalPath.");
                         }
 
                         // Save thông tin file vào database
                         $item_path = !empty($path_database) ? $path_database . '/' . $newFileName : $newFileName;
                         $insertItem = [
                             'name' => $newFileName,
                             'path' => $item_path,
                             'size' => $fileSize,
                             'type' => $ext,
                             'autoclean' => 0,
                             'created_at' => date('Y-m-d H:i:s'),
                             'updated_at' => date('Y-m-d H:i:s')
                         ];
                         $insertId = $this->filesModel->addFile($insertItem);
                         if ($insertId) {
                             $uploadedFiles[] = [
                                 'id' => $insertId,
                                 'name' => $newFileName,
                                 'path' => $item_path,
                                 'pth' => $path
                             ];
                         } else {
                             return $this->error('Failed to insert file info into database', [], 500);
                         }
                     } catch (AppException $e) {
                         if (file_exists($finalPath)) {
                             unlink($finalPath); // Xóa file nếu xảy ra lỗi
                         }
                         return $this->error($e->getMessage(), [], 500);
                     }
 
                     // Sau khi ghép xong, xóa thư mục tạm
                     if (is_dir($tempDir)) {
                         rmdir($tempDir);
                     }
                 }
             } else {
                 // Xử lý file không phải chunk
                 $basename = $this->_toSlug(pathinfo($name, PATHINFO_FILENAME));
                 $newFileName = $basename . '.' . $ext;
                 $targetPath = $upload_dir . DIRECTORY_SEPARATOR . $newFileName;
                 $counter = 1;
                 while (file_exists($targetPath)) {
                     $newFileName = $basename . '_' . $counter . '.' . $ext;
                     $targetPath = $upload_dir . DIRECTORY_SEPARATOR . $newFileName;
                     $counter++;
                 }
                 if ($this->_movefile($tmp_name, $targetPath)) {
                     $item_path = !empty($path_database) ? $path_database . '/' . $newFileName : $newFileName;
                     $insertItem = [
                         'name' => $newFileName,
                         'path' => $item_path,
                         'size' => filesize($targetPath),
                         'type' => $ext,
                         'autoclean' => 0,
                         'created_at' => date('Y-m-d H:i:s'),
                         'updated_at' => date('Y-m-d H:i:s')
                     ];
                     $insertId = $this->filesModel->addFile($insertItem);
                     if ($insertId) {
                         $uploadedFiles[] = [
                             'id' => $insertId,
                             'name' => $newFileName,
                             'path' => $item_path,
                             'pth' => $path
                         ];
                     } else {
                         return $this->error('Failed to insert file info into database', [], 500);
                         exit();
                     }
                 } else {
                     return $this->error('Failed to move uploaded file', [], 500);
                     exit();
                 }
             }
         }
         return $this->success(['uploaded_files' => $uploadedFiles], 'Files uploaded successfully.');
         exit();
    }

    public function checkUploadStatus()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Logger::error('Error: Only POST method is allowed for checkUploadStatus');
            return $this->error('Only POST method is allowed', [], 405);
        }

        if (!isset($_POST['uploadToken'])) {
            Logger::error('Error: Missing uploadToken');
            return $this->error('Missing uploadToken', [], 400);
        }

        $uploadToken = $_POST['uploadToken'];
        $path = $_POST['path'] ?? '';

        // Sử dụng _sanitizePath() nếu cần và chuyển đổi $path thành folder_real như trong upload()
        $path = $this->_sanitizePath($path);
        $folder_real = str_replace(':', DIRECTORY_SEPARATOR, $path);
        $upload_dir = $this->base_dir . DIRECTORY_SEPARATOR . $folder_real;

        // Temp folder được đặt trong $upload_dir/temp/<uploadToken>/
        $tempDir = $upload_dir . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . $uploadToken . DIRECTORY_SEPARATOR;

        // Nếu thư mục không tồn tại, nghĩa là chưa có chunk nào được lưu.
        if (!is_dir($tempDir)) {
            return $this->success(['uploadedChunks' => []], 'No chunks found');
        }

        $files = scandir($tempDir);
        $uploadedChunks = [];

        // Duyệt qua các file và tìm các file theo mẫu _part_<chunkIndex>.<ext>
        foreach ($files as $file) {
            if (preg_match('/_part_(\d+)\./', $file, $matches)) {
                $uploadedChunks[] = (int)$matches[1];
            }
        }
        sort($uploadedChunks);

        return $this->success(['uploadedChunks' => $uploadedChunks], 'Found uploaded chunks');
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
    

    private function _movefile($file_source, $file_dest) {
        // Di chuyển tệp tin đến thư mục đích
        if (move_uploaded_file($file_source, $file_dest)) {
            return true;
        } else {
            return false;
        }
    }

    // Hàm lấy phần mở rộng tệp tin
    public function _extension($filename) {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $list_ext = [
            // Hình ảnh
            'jpg', 'jpeg', 'png', 'gif', 'bmp', 'tiff', 'svg', 'webp', 'ico', 'heic', 'tga', 'psb',
            // Video
            'mp4', 'avi', 'mkv', 'mov', 'wmv', 'flv', 'webm', '3gp', 'm4v', 'mpeg', 'mpg', 'mts', 'm2ts', 'vob', 'mxf', 'rm', 'rmvb',
            // Âm thanh
            'mp3', 'wav', 'aac', 'flac', 'ogg', 'wma', 'm4a', 'alac', 'aiff', 'amr', 'opus', 'mid', 'midi', 'ra',
            // Tài liệu
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'rtf', 'odt', 'ods', 'odp', 'csv', 'epub', 'mobi', 'md', 'tex', 'key', 'pages', 'numbers',
            // Tệp nén và lưu trữ
            'zip', 'rar', '7z', 'tar', 'gz', 'bz2', 'iso', 'dmg', 'xz', 'cab', 'z', 'lzma', 'tgz', 'tbz2',
            // Tệp thực thi và cài đặt
            'exe', 'bat', 'sh', 'msi', 'apk', 'deb', 'rpm', 'bin', 'jar', 'com', 'vbs', 'cmd', 'app', 'run', 'scr', 'pif', 'gadget',
            // Tệp web và lập trình
            'html', 'htm', 'css', 'js', 'php', 'xml', 'json', 'asp', 'aspx', 'jsp', 'vue', 'jsx', 'ts', 'tsx', 'scss', 'sass', 'less',
            // Tệp nguồn lập trình
            'c', 'cpp', 'cs', 'java', 'py', 'rb', 'php', 'go', 'rs', 'swift', 'kt', 'lua', 'pl', 'scala', 'vb', 'h', 'm', 'r', 'jl', 'sh', 'bash', 'zsh', 'f90', 'f95', 'asm', 'pas', 'js', 'ts', 'vb', 'd', 'dart', 'erl', 'ex', 'exs', 'lisp', 'ml',
            // Cơ sở dữ liệu
            'sql', 'db', 'dbf', 'mdb', 'sqlite', 'accdb', 'bak', 'ldif', 'frm', 'myd', 'ibd', 'ndf', 'ora', 'pdb', 'db2', 'sqlite3',
            // Tệp đồ họa và 3D
            'psd', 'ai', 'eps', 'indd', 'cdr', 'dwg', 'dxf', 'obj', 'stl', 'fbx', 'blend', '3ds', 'max', 'skp', 'lwo', 'lws', 'prt', 'stp', 'igs', 'step', 'sldprt', 'sldasm', 'slddrw', 'x_t', 'x_b', 'sat',
            // Tệp phông chữ
            'ttf', 'otf', 'woff', 'woff2', 'eot', 'fon', 'pfb', 'pfm',
            // Tệp hệ thống và log
            'dll', 'sys', 'ini', 'log', 'cfg', 'tmp', 'dat', 'bak', 'bin', 'img', 'vhd', 'vhdx', 'ovf', 'ova', 'vmdk', 'qcow2', 'r00', 'part', '001', 'bin', 'cue',
            // Tệp bảng tính và thống kê
            'xls', 'xlsx', 'csv', 'tsv', 'ods', 'spss', 'sav', 'dta', 'sas7bdat',
            // Tệp hình ảnh động và mô phỏng
            'gif', 'fla', 'swf', 'blender', 'mdd', 'vmd', 'bvh', 'dae', 'abc',
            // Tệp email
            'eml', 'msg', 'pst', 'ost', 'mbox', 'ics', 'vcf', 'ldif',
            // Tệp liên quan đến game
            'pak', 'sav', 'gbl', 'rom', 'iso', 'bin', 'cue', 'dat', 'nes', 'gba', 'sfc', 'smd', 'nds', 'n64', 'vpk', 'wad',
            // Tệp máy ảo và đĩa ảo
            'vmdk', 'vdi', 'ova', 'ovf', 'qcow2', 'vhd', 'iso', 'raw',
            // Tệp sao lưu và phục hồi
            'bak', 'tmp', 'old', 'bkp', 'gho', 'bkf', 'arc', 'dmp', 'swp', 'gpt', 'fpt', 'nbk', 'snp', 'wbk',
            // tệp phụ đề
            'srt'
        ];

        // Kiểm tra phần mở rộng có nằm trong danh sách cho phép hay không
        if (in_array($extension, $list_ext)) {
            return $extension;
        }
        // Nếu phần mở rộng không hợp lệ, trả về null
        return null;
    }


    // Kiểm tra các lỗi PHP upload và trả về thông báo chi tiết
    private function _fileUploadErrorMessage($error_code)
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


    // Hàm kiểm tra file có độc hại hay không
    protected function _isMaliciousFile($fileName, $fileTmpName, $config_files)
    {
        // Kiểm tra kích thước tệp
        if (filesize($fileTmpName) === 0 || filesize($fileTmpName) > $config_files['max_file_size']) {
            return true; // Tệp trống hoặc vượt quá giới hạn kích thước
        }

        // Kiểm tra loại tệp (MIME type)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $fileTmpName);
        finfo_close($finfo);

        $allowedTypes = $config_files['allowed_types'];
        $fileExt = $this->_extension($fileName);

        // Kiểm tra xem MIME type có hợp lệ và khớp với phần mở rộng không
        if (!in_array($fileExt, $allowedTypes)) {
            return true; // Loại tệp không hợp lệ hoặc MIME không phù hợp với đuôi tệp
        }

        // Kiểm tra nội dung đầu tệp để tránh tệp giả mạo (Magic Bytes)
        $fileHeader = file_get_contents($fileTmpName, false, null, 0, 5); // Lấy 5 byte đầu tiên
        $dangerousHeaders = [
            "\x4D\x5A", // .exe, .dll, .com (Windows executables)
            "<?php",    // PHP script
            "\xFF\xD8", // JPEG
            "PK\x03\x04", // ZIP or DOCX, XLSX (but needs further checking)
        ];

        foreach ($dangerousHeaders as $header) {
            if (strpos($fileHeader, $header) !== false) {
                // Kiểm tra các tệp đặc biệt (ZIP/DOCX/JPEG)
                if ($fileExt !== 'jpg' && $fileExt !== 'zip' && $fileExt !== 'docx') {
                    return true; // Phát hiện tệp nguy hiểm không khớp với phần mở rộng hợp lệ
                }
            }
        }

        return false; // Không phát hiện tệp độc hại
    }





    // -----------------------------------
    // Protected Helper Methods
    // -----------------------------------

    /**
 * Cập nhật đường dẫn của tất cả các tệp và thư mục con sau khi đổi tên thư mục cha.
 *
 * @param string $oldPath Đường dẫn cũ của thư mục cha.
 * @param string $newPath Đường dẫn mới của thư mục cha.
 * @return void
 */

    // Tạo thư mục mới
    protected function _createFolder($relativePath)
    {
        $fullPath = $this->_getFolderRealPath($relativePath);
        if (file_exists($fullPath)) {
            throw new AppException('Folder already exists');
        }

        if (!mkdir($fullPath, 0755, true)) {
            throw new AppException('Failed to create folder');
        }
        return true;
    }

    // Đổi tên mục
    protected function _renameItem($oldRelativePath, $newRelativePath)
    {
        // Kiểm tra loại mục
        if ($this->_isFolder($oldRelativePath)) {
            $isFolder = true;
            $sanitizedNewPath = $this->_sanitizePath($newRelativePath);
            $newFullPath = $this->base_dir . '/' . str_replace(':', '/', $sanitizedNewPath);
        } elseif ($this->_isFile($oldRelativePath)) {
            $isFolder = false;
            $sanitizedNewPath = $this->_sanitizePath($newRelativePath);
            $newFullPath = $this->base_dir . '/' . str_replace(':', '/', $sanitizedNewPath);
        } else {
            throw new AppException('Source path does not exist');
        }

        // Lấy đường dẫn đầy đủ nguồn
        if ($isFolder) {
            $oldFullPath = $this->_getFolderRealPath($oldRelativePath);
        } else {
            $oldFullPath = $this->_getFileRealPath($oldRelativePath);
        }

        // Kiểm tra xem đường dẫn đích đã tồn tại chưa
        if (file_exists($newFullPath)) {
            throw new AppException('Destination path already exists');
        }
        if (!rename($oldFullPath, $newFullPath)) {
            throw new AppException('Failed to rename item');
        }
    }

    // Xóa mục
    protected function _deleteItem($sanitizedPath)
    {
        // Kiểm tra loại mục
        if ($this->_isFolder($sanitizedPath)) {
            $isFolder = true;
            $fullPath = $this->_getFolderRealPath($sanitizedPath);
        } elseif ($this->_isFile($sanitizedPath)) {
            $isFolder = false;
            $fullPath = $this->_getFileRealPath($sanitizedPath);
        } else {
            throw new AppException('Path does not exist 2');
        }

        $this->_deleteRecursively($fullPath);
    }

    // xóa file 
    protected function _deleteFile($sanitizedPath)
    {
        if (file_exists($sanitizedPath)) {
            $fullPath = $this->_getFileRealPath($sanitizedPath);
            unlink($fullPath);
        } 
    }

    // Sao chép mục
    protected function _copyItem($sanitizedSourcePath, $sanitizedDestinationPath)
    {
        // Kiểm tra loại mục
        if ($this->_isFolder($sanitizedSourcePath)) {
            $isFolder = true;
            $sourceFullPath = $this->_getFolderRealPath($sanitizedSourcePath);
            $destinationFullPath = $this->_getFolderRealPath($sanitizedDestinationPath);
        } elseif ($this->_isFile($sanitizedSourcePath)) {
            $isFolder = false;
            $sourceFullPath = $this->_getFileRealPath($sanitizedSourcePath);
            $destinationFullPath = $this->_getFileRealPath($sanitizedDestinationPath);
        } else {
            throw new AppException('Source path does not exist');
        }

        // Kiểm tra xem đường dẫn đích đã tồn tại chưa
        if (file_exists($destinationFullPath)) {
            throw new AppException('Destination path already exists');
        }

        if ($isFolder) {
            if (!$this->_copyDir($sourceFullPath, $destinationFullPath)) {
                throw new AppException('Failed to copy folder');
            }
        } else {
            if (!copy($sourceFullPath, $destinationFullPath)) {
                throw new AppException('Failed to copy file');
            }
        }
    }

    // Di chuyển mục
    protected function _moveItem($sanitizedSourcePath, $sanitizedDestinationPath)
    {
        // Kiểm tra loại mục
        if ($this->_isFolder($sanitizedSourcePath)) {
            $isFolder = true;
            $sourceFullPath = $this->_getFolderRealPath($sanitizedSourcePath);
            $destinationFullPath = $this->_getFolderRealPath($sanitizedDestinationPath);
        } elseif ($this->_isFile($sanitizedSourcePath)) {
            $isFolder = false;
            $sourceFullPath = $this->_getFileRealPath($sanitizedSourcePath);
            $destinationFullPath = $this->_getFileRealPath($sanitizedDestinationPath);
        } else {
            throw new AppException('Source path does not exist');
        }

        // Kiểm tra xem đường dẫn đích đã tồn tại chưa
        if (file_exists($destinationFullPath)) {
            throw new AppException('Destination path already exists');
        }

        if (!rename($sourceFullPath, $destinationFullPath)) {
            throw new AppException('Failed to move item');
        }
    }

    // Lấy đường dẫn đầy đủ từ đường dẫn tương đối cho thư mục
    //Tuc la convert duong dan: folder:sub1:sub2... ve dang: /writable/uploads/folder/sub1/sub2...
    protected function _getFolderRealPath($relativePath)
    {
        $sanitizedPath = $this->_sanitizePath($relativePath);
        $fullPath = $this->base_dir . '/' . str_replace(':', '/', $sanitizedPath);
        // Bao mat: Folder khong duoc co dau . de tranh exploit ../../
        $fullPath = str_replace('.', '', $fullPath);
        // Bảo mật: đảm bảo rằng đường dẫn không vượt ra ngoài thư mục gốc
        if ($fullPath === false || strpos($fullPath, $this->base_dir) !== 0) {
            throw new AppException('Invalid folder path');
        }
        return $fullPath;
    }

    // Lấy đường dẫn đầy đủ từ đường dẫn tương đối cho tệp tin
    //Tuc la convert duong dan: folder:sub1:sub2...:tenfile.ext ve dang: /writable/uploads/folder/sub1/sub2.../tenfile.ext
    protected function _getFileRealPath($relativePath)
    {
        $sanitizedPath = $this->_sanitizePath($relativePath);
        $fullPath = $this->base_dir . '/' . str_replace(':', '/', $sanitizedPath);
        // Bảo mật: đảm bảo rằng đường dẫn không vượt ra ngoài thư mục gốc
        if ($fullPath === false || strpos($fullPath, $this->base_dir) !== 0) {
            throw new AppException('Invalid file path');
        }
        return $fullPath;
    }

    // Lấy đường dẫn đầy đủ từ đường dẫn tương đối: ko quan tam Folder or Files
    protected function _getRealPath($relativePath)
    {
        $sanitizedPath = $this->_sanitizePath($relativePath);
        $fullPath = $this->base_dir . str_replace(':', '/', $sanitizedPath);
        // Bảo mật: đảm bảo rằng đường dẫn không vượt ra ngoài thư mục gốc
        $realFullPath = realpath($fullPath);
        if ($realFullPath === false || strpos($realFullPath, $this->base_dir) !== 0) {
            throw new AppException('Invalid path');
        }
        return $realFullPath;
    }

    // Xóa thư mục hoặc tệp tin đệ quy
    protected function _deleteRecursively($fullpath)
    {
        if (is_dir($fullpath)) {
            $items = scandir($fullpath);
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') continue;
                $itemPath = $fullpath . '/' . $item;
                $this->_deleteRecursively($itemPath);
            }
            if (!rmdir($fullpath)) {
                throw new AppException('Failed to remove Directory: ' . $fullpath);
            }
            return;
        } else {
            if (!unlink($fullpath)) {
                throw new AppException('Failed to delete File: ' . $fullpath);
            }else{
                //needcode
                //get files trong db ra tu path.
                //xoa trong database.
                $pathDB = str_replace($this->base_dir,'', $fullpath);
                $pathDB = ltrim($pathDB, '/');
                $file_db = $this->filesModel->getFileByPath($pathDB);
                if (empty($file_db['id'])){
                    return;
                }
                return $this->filesModel->deleteFile($file_db['id']);
            }
        }
    }

    // Hàm hỗ trợ sao chép thư mục đệ quy
    // protected function _copyDir($src, $dst) {
    //     $dir = opendir($src);
    //     if (!@mkdir($dst)) {
    //         return false;
    //     }
    //     while(false !== ($file = readdir($dir))) {
    //         if (($file != '.') && ($file != '..')) {
    //             $srcPath = $src . '/' . $file;
    //             $dstPath = $dst . '/' . $file;
    //             if (is_dir($srcPath)) {
    //                 if (!$this->_copyDir($srcPath, $dstPath)) {
    //                     closedir($dir);
    //                     return false;
    //                 }
    //             }
    //             else {
    //                 if (!copy($srcPath, $dstPath)) {
    //                     closedir($dir);
    //                     return false;
    //                 }
    //             }
    //         }
    //     }
    //     closedir($dir);
    //     return true;
    // }

    protected function _copyDir($source, $destination, $excludePaths = [])
    {
        $dir = opendir($source);
        if (!$dir) {
            return false;
        }

        // Tạo thư mục đích nếu chưa tồn tại
        if (!@mkdir($destination) && !is_dir($destination)) {
            closedir($dir);
            return false;
        }

        while (false !== ($file = readdir($dir))) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $srcPath = $source . DIRECTORY_SEPARATOR . $file;
            $dstPath = $destination . DIRECTORY_SEPARATOR . $file;

            // Kiểm tra xem srcPath có nằm trong excludePaths không
            $realSrcPath = realpath($srcPath);
            if ($realSrcPath) {
                foreach ($excludePaths as $excludePath) {
                    $realExcludePath = realpath($excludePath);
                    if ($realExcludePath && strpos($realSrcPath, $realExcludePath) === 0) {
                        // Nếu srcPath nằm trong excludePath, bỏ qua
                        continue 2; // Tiếp tục vòng lặp bên ngoài
                    }
                }
            }

            if (is_dir($srcPath)) {
                if (!$this->_copyDir($srcPath, $dstPath, $excludePaths)) {
                    closedir($dir);
                    return false;
                }
            } else {
                if (!copy($srcPath, $dstPath)) {
                    closedir($dir);
                    return false;
                }
            }
        }

        closedir($dir);
        return true;
    }

    // Hàm an toàn để xử lý tên thư mục
    //Xoa cac ki tu dac biet cua 1 folder input truyen vao dang folder:sub1:sub2...
    protected function _sanitizeFolderName($name)
    {
        // Loại bỏ các ký tự không hợp lệ và thay thế khoảng trắng bằng dấu gạch dưới
        $name = preg_replace('/[^\p{L}\p{N}\s\-_:]+/u', '-', $name);
        $name = preg_replace('/\s+/', '_', $name);  // Thay thế khoảng trắng bằng dấu gạch dưới
        $name = preg_replace('/-+/', '-', $name);   // Loại bỏ các dấu gạch ngang thừa
        $name = trim($name, '-_');                  // Loại bỏ các dấu gạch ngang hoặc gạch dưới ở đầu và cuối
        return $name;
    }

    // Hàm an toàn để xử lý tên tệp tin
    //Xoa cac ki tu dac biet cua 1 file input truyen vao dang file.ext
    protected function _sanitizeFileName($name)
    {
        // Loại bỏ các ký tự không hợp lệ nhưng giữ lại dấu .
        $name = preg_replace('/\.php\./i', '.', $name);
        $name = preg_replace('/\.php$/i', '.', $name);
        $name = preg_replace('/[^.\p{L}\p{N}\s\-_]+/u', '_', $name);
        $name = preg_replace('/\s+/', '_', $name);  // Thay thế khoảng trắng bằng dấu gạch dưới
        $name = preg_replace('/_+/', '_', $name);
        $name = trim($name, '_');
        return $name;
    }

    // Hàm an toàn để xử lý đường dẫn
    //Xoa cac ki tu dac biet cua 1 path (ko quan tam no la ten file, ten folder, hay la path dan den file). input truyen vao dang folder:subfolder:file
    // protected function _sanitizePath($path)
    // {
    //     //Loai bo ten tep tin php
    //     $path = preg_replace('/\.php\./i', '.', $path);
    //     $path = preg_replace('/\.php$/i', '.', $path);
    //     // Loại bỏ các chuỗi như .:, ..:, hoặc .:.: (các chuỗi có thể gây ra lỗi bảo mật)
    //     $path = preg_replace('/(\.+:)/', '_', $path); // Loại bỏ bất kỳ chuỗi nào có dạng .: hoặc ..:
    //     // Thay thế chuỗi nhiều dấu chấm (...., .....) thành dấu . ../
    //     $path = preg_replace('/(\.+)/', '.', $path);
    //     // Loại bỏ các ký tự không hợp lệ và thay thế bằng dấu gạch dưới
    //     $path = preg_replace('/[^.\p{L}\p{N}\s\-_:]+/u', '_', $path);
    //     $path = preg_replace('/\s+/', '_', $path);  // Thay thế khoảng trắng bằng dấu gạch dưới
    //     $path = preg_replace('/_+/', '_', $path);
    //     $path = trim($path, '_');
    //     return $path;
    // }

    protected function _sanitizePath($path)
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

    function _folderFromPath($filePath) {
        return dirname($filePath);
    }
    function _removeExtension($filePath) {
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);
        if ($ext !== '') {
            return substr($filePath, 0, -(strlen($ext) + 1)); // +1 để bỏ dấu chấm '.'
        }
        return $filePath; // Không có extension thì trả nguyên
    }

    // Hàm kiểm tra đường dẫn thư mục có hợp lệ và tồn tại không
    protected function _isFolder($relativePath)
    {
        try {
            $fullPath = $this->_getFolderRealPath($relativePath);
            // var_dump($fullPath, $relativePath);die;
            return is_dir($fullPath);
        } catch (AppException $e) {
            return false;
        }
    }

    // Hàm kiểm tra đường dẫn tệp tin có hợp lệ và tồn tại không
    protected function _isFile($relativePath)
    {
        try {
            $fullPath = $this->_getFileRealPath($relativePath);
            return is_file($fullPath);
        } catch (AppException $e) {
            return false;
        }
    }

    // -----------------------------------
    // Public Helper Functions (Nếu Cần Thiết)
    // -----------------------------------


    private function _normalizeString($str) {
        // Chuyển thành chữ thường
        $str = mb_strtolower($str, 'UTF-8');
        // Sử dụng iconv để loại bỏ dấu và ký tự đặc biệt
        $str = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str);
        // Loại bỏ các ký tự không phải chữ cái và số (nếu cần)
        $str = preg_replace('/[^a-z0-9\s]/', '', $str);
        return $str;
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
    private function _searchString($str) {
        // Chuyển thành chữ thường
        $str = mb_strtolower($str, 'UTF-8');
        // $str = preg_replace('/[ảãạàáăắằẳẵặâấầẩẫậ]/', 'a', $str);
        // $str = preg_replace('/[đ]/', 'd', $str);
        // $str = preg_replace('/[êếềểễệ]/', 'e', $str);
        // $str = preg_replace('/[íìỉĩị]/', 'i', $str);
        // $str = preg_replace('/[ôốồổỗộơớờỡởợ]/', 'o', $str);
        // $str = preg_replace('/[úùủũụư  ừửữựưứừửữự]/', 'u', $str);
        // $str = preg_replace('/[ýỳỷỹỵýỳỷỹỵ]/', 'y', $str);
        // Sử dụng iconv để loại bỏ dấu và ký tự đặc biệt
        $str = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str);
        // Loại bỏ các ký tự không phải chữ cái và số (nếu cần)
        $str = preg_replace('/[^a-z0-9 _-]/', '', $str);
        return $str;
    }

    // Xóa nhiều mục (file hoặc folder)
    public function delete_multiple()
    {
        // Kiểm tra phương thức HTTP
      if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' ) {
          return $this->error('Only DELETE method is allowed', [], 405);
      }

        // Lấy dữ liệu từ request body
        $input = json_decode(file_get_contents('php://input'), true);
        $items = $input['items'] ?? [];
        if (empty($items)) {
            return $this->error('No items provided', [], 400);
        }

        foreach ($items as $item) {
            $id = $item['id'];
            $itemArray = $this->filesModel->getFileById($id);
            if (empty($itemArray) || !isset($itemArray['path'])) {
                continue;
            }
            $this->delete_file($itemArray);
        }
        

        $this->success([], 'Items deleted successfully.');
    }

    public function delete_file($itemArray){
        $remove_path = array();
        $item_fullpath = $this->_getFileRealPath($itemArray['path']);
        if (file_exists($item_fullpath)) {
            $remove_path[] = $item_fullpath;
        }
        if (file_exists($item_fullpath . '.webp')) {
            $remove_path[] = $item_fullpath . '.webp';
        }
        $size_path = $this->_removeExtension($item_fullpath);
        //Remove all resize file
        if(!empty($itemArray['resize'])){
            $resize = explode(';', $itemArray['resize']);
            foreach ($resize as $size) {
                $size_fullpath = $size_path . '_' . $size.'.'.$itemArray['type'];
                if (file_exists($size_fullpath)){
                    $remove_path[] = $size_fullpath;
                }
                if (file_exists($size_fullpath . '.webp')){
                    $remove_path[] = $size_fullpath . '.webp';
                }
            }
        }
        try {
            foreach ($remove_path as $path){
                if (file_exists($path)){
                    @unlink($path);
                }
            }
        } catch (AppException $e) {
        }
        return $this->filesModel->deleteFile($itemArray['id']);
    }



    // Bạn có thể thêm các hàm public khác nếu cần
}
?>