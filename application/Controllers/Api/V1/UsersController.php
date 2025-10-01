<?php
namespace App\Controllers\Api\V1;

use App\Controllers\ApiController;
use System\Core\AppException;
use App\Libraries\Fastlang as Flang;
use System\Drivers\Cache\UriCache;
use System\Libraries\Validate;

class UsersController extends ApiController {
    protected $cache;

    public function __construct(){ 
        parent::__construct();
        $this->cache = new UriCache(5, 'json');
        $this->cache->cacheLogin(true);
        // Want to get anything, just call $this->usersModel.
    }
// This function was written by me, it's below in the controller
     // Get user details
    public function info(){
        // get header all data 
        $user = $this->_auth();
        unset($user['password']);
        unset($user['location']);
        unset($user['permissions']);
        unset($user['optional']);
        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->_profile_post($user);
            $this->galleries($user);
        } else {
            $this->_profile_get($user);
        }
    }

     private function _profile_get($me)
     {
         try {
            $location = $this->usersModel->getLocation($me['id']);
            if(!empty($location)) {
                $me['location'] = $location;
            }
            $me['personal'] = !empty($me['personal']) ? json_decode($me['personal']) : [];
            $me['header'] = getallheaders();
            return $this->success(['me'=>$me], Flang::_e('get_profile'));
         } catch (AppException $e) {
            return $this->error($e->getMessage(), [], 500);
         }
    }

    public function galleries($me) {
        try {
            
            $personal = !empty($me['personal']) ? json_decode($me['personal'], true) : [];
             if (!isset($personal['galleries']) || !is_array($personal['galleries'])) {
                $personal['galleries'] = [];
            } else {
                foreach ($personal['galleries'] as $photoUrl) {
                    $filePath = PATH_WRITE. $photoUrl;
                    if (file_exists($filePath)) {
                        if (!unlink($filePath)) {
                            $this->error('Không thể xóa hình ảnh cũ: ' . $photoUrl, [], 500);
                            return;
                        }
                    }
                }
                $personal['galleries'] = [];
            }

            
            
            if (!empty($_FILES['galleries'])) { 
                foreach ($_FILES['galleries']['tmp_name'] as $key => $tmpName) {
                    if ($_FILES['galleries']['error'][$key] === UPLOAD_ERR_OK) {
                        $fileName = $_FILES['galleries']['name'][$key];
                        $fileTmpPath = $_FILES['galleries']['tmp_name'][$key];

                        $fileNameCmps = explode(".", $fileName);
                        $fileExtension = strtolower(end($fileNameCmps));
                        $allowedfileExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                        
                        if (in_array($fileExtension, $allowedfileExtensions)) {
                            $newFileName = pathinfo($fileName, PATHINFO_FILENAME) . '_' . time() . '_' . uniqid() . '.webp';
                            $base_dir = PATH_WRITE . 'uploads/users/';
                            
                            if ($base_dir === false) {
                                $this->error('Đường dẫn thư mục tải lên không hợp lệ.', [], 500);
                                return;
                            }
            
                            $uploadFileDir = $base_dir . ceil($me['id'] / 1000) . '/' . $me['id'] . '/';
            
                            if (!is_dir($uploadFileDir) && !mkdir($uploadFileDir, 0755, true)) {
                                $this->error('Unable_to_create_upload_folder', [], 500);
                                return;
                            }
            
                            $dest_path = $uploadFileDir . $newFileName;
            
                            if (!function_exists('imagewebp')) {
                                $this->error('Server không hỗ trợ WebP.', [], 500);
                                return;
                            }
                            try {
                                switch ($fileExtension) {
                                    case 'jpg':
                                    case 'jpeg':
                                        $image = imagecreatefromjpeg($fileTmpPath);
                                        break;
                                    case 'png':
                                        $image = imagecreatefrompng($fileTmpPath);
                                        break;
                                    case 'gif':
                                        $image = imagecreatefromgif($fileTmpPath);
                                        break;
                                    case 'webp':
                                        $image = imagecreatefromwebp($fileTmpPath);
                                        break;
                                    default:
                                        $this->error('Image_format_not_supported', [], 400);
                                        return;
                                }
                
                                if (!$image) {
                                    throw new AppException(Flang::_e('Unable_to_create_image_object_from_uploaded_file'));
                                }
                                $this->callFunctionWithException('imagewebp', $image, $dest_path, 80);

                                imagedestroy($image);

                                $photoUrl = '/uploads/users/' . ceil($me['id'] / 1000) . '/' . $me['id'] . '/' . $newFileName;
                                $personal['galleries'][] = $photoUrl;
                            } catch (AppException $e) {
                                // Handle WebP conversion error
                                $this->error(Flang::_e('There_was_an_error_converting'), [], 500);
                                return;
                            }
                        } else {
                            $this->error('Tệp hình ảnh thêm không hợp lệ.', [], 400);
                            return;
                        }
                    }
                }
            }
            $input['updated_at'] = _DateTime();
            $input['online'] = 1;
            $input['personal'] = json_encode($personal, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            $result = $this->usersModel->updateUser($me['id'], $input);
            if ($result) {
                $user = $this->_auth();
                unset($user['password']);
                unset($user['location']);
                unset($user['permissions']);
                unset($user['optional']);
                $user['personal'] = !empty($user['personal']) ? json_decode($user['personal']) : [];
                return $this->success($user, Flang::_e('User_updated_successfully'));
            } else {
                return $this->error(Flang::_e('User_updated_error'), [], 404);
            }
        } catch (AppException $e) {
            $this->error($e->getMessage(), [], 500);
        }
    }

    
    private function _profile_post($me) {
        try {
            $avatarPath = '';
            $input = [];
            $fields = ['fullname', 'username', 'password', 'phone', 'telegram', 'whatsapp', 'skype', 'instagram', 'facebook', 'zalo', 'about_me', 'birthday', 'gender', 'display', 'job', 'galleries'];
            $personal = !empty($me['personal']) ? json_decode($me['personal'], true) : [];

            foreach ($fields as $field) {
                $value = S_POST($field) ?? '';
                if (!empty($value)) {
                    $input[$field] = $value;  
                }
            }

            $rules =  [
                'fullname' => [
                    'rules' => [Validate::optional(
                        Validate::regex('/^[\p{L}\p{M}\s]+$/u')
                        ),
                        Validate::optional(Validate::length(3, 60))
                    ],
                    'messages' => [Flang::_e('fullname_invalid'), Flang::_e('fullname_length', 3, 60)]
                ],
                'username' => [
                    'rules' => [Validate::optional(Validate::alnum('_')), Validate::optional(Validate::length(6, 50))],
                    'messages' => [Flang::_e('username_invalid'), Flang::_e('username_length', 6, 50)]
                ],
                'phone' => [
                    'rules' => [Validate::optional(Validate::phone()), Validate::optional(Validate::length(7, 16))],
                    'messages' => [Flang::_e('phone_invalid'), Flang::_e('phone_length', 7, 16)] 
                ],
                'telegram' => [
                    'rules' => [Validate::optional(Validate::url()), Validate::optional(Validate::length(5, 100))],
                    'messages' => [Flang::_e('telegram_invalid'), Flang::_e('telegram_length', 5, 100)]
                ],
                'whatsapp' => [
                    'rules' => [Validate::optional(Validate::url()), Validate::optional(Validate::length(5, 100))],
                    'messages' => [Flang::_e('whatsapp_invalid'), Flang::_e('whatsapp_length', 5, 100)]
                ],
                'skype' => [
                    'rules' => [Validate::optional(Validate::length(3, 100))],
                    'messages' => [Flang::_e('skype_length', 5, 100)]
                ],
                'instagram' => [
                    'rules' => [Validate::optional(Validate::length(3, 100))],
                    'messages' => [ Flang::_e('instagram_length', 3, 100)]
                ],
                'facebook' => [
                    'rules' => [Validate::optional(Validate::length(5, 100))],
                    'messages' => [Flang::_e('facebook_length', 5, 100)]
                ],
                'zalo' => [
                    'rules' => [Validate::optional(Validate::phone()), Validate::optional(Validate::length(7, 16))],
                    'messages' => [Flang::_e('zalo_invalid'), Flang::_e('zalo_length', 7, 16)] 
                ],
                'about_me' => [
                    'rules' => [Validate::optional(Validate::length(0, 300))],
                    'messages' => [Flang::_e('about_me_length', 0, 300)]
                ],
                'birthday' => [
                    'rules' => [Validate::optional(Validate::maxAge(100)), Validate::optional(Validate::minAge(15))],
                    'messages' => [Flang::_e('birthday max 100'), Flang::_e('birthday min 15')]
                ],
                'gender' => [
                    'rules' => [Validate::optional(Validate::in(['male', 'female', 'other']))],
                    'messages' => [Flang::_e('gender_invalid')]
                ],
                'display' => [
                    'rules' => [Validate::optional(Validate::in([0, 1]))],
                    'messages' => [Flang::_e('display_invalid')]
                ]
            ];
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath = $_FILES['avatar']['tmp_name'];
                $fileName = $_FILES['avatar']['name'];
                $fileNameCmps = explode(".", $fileName);
                $fileExtension = strtolower(end($fileNameCmps));
                $allowedfileExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                if (in_array($fileExtension, $allowedfileExtensions)) {
                    $newFileName = pathinfo('avatar', PATHINFO_FILENAME) . '.webp';
                    $base_dir = PATH_WRITE. 'uploads/users/';
                    if ($base_dir === false) {
                        $this->error(Flang::_e('Invalid_upload_folder_path'), [], 500);
                        exit;
                    }
                    
                    $uploadFileDir = $base_dir . ceil($me['id'] / 1000) . '/' . $me['id'] . '/';
                    if (!is_dir($uploadFileDir) && !mkdir($uploadFileDir, 0755, true)) {
                        $this->error(Flang::_e('Unable_to_create_upload_folder'), [], 500);
                        exit;
                    }

                    $dest_path = $uploadFileDir . $newFileName;
                    if (!function_exists('imagewebp')) {
                        $this->error(Flang::_e('The_server_does_not_support_WebP'), [], 500);
                        exit;
                    }

                    try {
                        switch ($fileExtension) {
                            case 'jpg':
                            case 'jpeg':
                                $image = imagecreatefromjpeg($fileTmpPath);
                                break;
                            case 'png':
                                $image = imagecreatefrompng($fileTmpPath);
                                break;
                            case 'gif':
                                $image = imagecreatefromgif($fileTmpPath);
                                break;
                            default:
                                $this->error('Image_format_not_supported', [], 400);
                                return;
                        }
        
                        if (!$image) {
                            throw new AppException(Flang::_e('Unable_to_create_image_object_from_uploaded_file'));
                        }
                        $this->callFunctionWithException('imagewebp', $image, $dest_path, 80);

                        imagedestroy($image);

                        $avatarPath = '/uploads/users/' . ceil($me['id'] / 1000) . '/' . $me['id'] . '/' . $newFileName;
                    } catch (AppException $e) {
                        // Handle WebP conversion error
                        $this->error(Flang::_e('There_was_an_error_converting'), [], 500);
                        return;
                    }
                } else {
                    $this->error(Flang::_e('Invalid_avatar_file'), [], 400);
                    exit;
                }
            } elseif (!empty(S_POST('avatar')) && is_string(S_POST('avatar'))) {
                $avatarPath = $me['avatar'];
            } else {
                if(!empty($me['avatar'])) {
                    $filePath = PATH_WRITE . $me['avatar'];
                    if (file_exists($filePath)) {
                        if (!unlink($filePath)) {
                            $this->error('Không thể xóa hình ảnh cũ: ' . $me['avatar'], [], 500);
                            return;
                        }
                    }
                }
                $avatarPath = null;
            }
            if(empty($input['galleries'])) {
                $input['galleries'] = [];
            } 
            // Delete old images in writeable directory but keep $input['galleries'] (array of image links);
            if (!isset($personal['galleries']) || !is_array($personal['galleries'])) {
                $personal['galleries'] = [];
            } else {
                foreach ($personal['galleries'] as $photoUrl) {
                    $filePath = PATH_WRITE . $photoUrl;
                    if(in_array($filePath , $input['galleries'])) {
                        continue;
                    }
                    if (file_exists($filePath)) {
                        if (!unlink($filePath)) {
                            $this->error('Không thể xóa hình ảnh cũ: ' . $photoUrl, [], 500);
                            return;
                        }
                    }
                }
                $personal['galleries'] = $input['galleries'] ?? [];
            }

            // Process new images
            if (!empty($_FILES['galleries'])) { 
                foreach ($_FILES['galleries']['tmp_name'] as $key => $tmpName) {
                    if ($_FILES['galleries']['error'][$key] === UPLOAD_ERR_OK) {
                        $fileName = $_FILES['galleries']['name'][$key];
                        $fileTmpPath = $_FILES['galleries']['tmp_name'][$key];

                        $fileNameCmps = explode(".", $fileName);
                        $fileExtension = strtolower(end($fileNameCmps));
                        $allowedfileExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                        
                        if (in_array($fileExtension, $allowedfileExtensions)) {
                            $newFileName = pathinfo($fileName, PATHINFO_FILENAME) . '_' . time() . '_' . uniqid() . '.webp';
                            $base_dir = PATH_WRITE . 'uploads/users/';
                            
                            if ($base_dir === false) {
                                $this->error('Đường dẫn thư mục tải lên không hợp lệ.', [], 500);
                                return;
                            }
            
                            $uploadFileDir = $base_dir . ceil($me['id'] / 1000) . '/' . $me['id'] . '/';
            
                            if (!is_dir($uploadFileDir) && !mkdir($uploadFileDir, 0755, true)) {
                                $this->error('Unable_to_create_upload_folder', [], 500);
                                return;
                            }
            
                            $dest_path = $uploadFileDir . $newFileName;
            
                            if (!function_exists('imagewebp')) {
                                $this->error('Server không hỗ trợ WebP.', [], 500);
                                return;
                            }
                            try {
                                switch ($fileExtension) {
                                    case 'jpg':
                                    case 'jpeg':
                                        $image = imagecreatefromjpeg($fileTmpPath);
                                        break;
                                    case 'png':
                                        $image = imagecreatefrompng($fileTmpPath);
                                        break;
                                    case 'gif':
                                        $image = imagecreatefromgif($fileTmpPath);
                                        break;
                                    case 'webp' :
                                        $image = imagecreatefromwebp($fileTmpPath);
                                        break;
                                    default:
                                        $this->error('Image_format_not_supported', [], 400);
                                        return;
                                }
                
                                if (!$image) {
                                    throw new AppException(Flang::_e('Unable_to_create_image_object_from_uploaded_file'));
                                }
                                $this->callFunctionWithException('imagewebp', $image, $dest_path, 80);

                                imagedestroy($image);

                                $photoUrl = '/uploads/users/' . ceil($me['id'] / 1000) . '/' . $me['id'] . '/' . $newFileName;
                                $personal['galleries'][] = $photoUrl;
                            } catch (AppException $e) {
                                // Handle WebP conversion error
                                $this->error(Flang::_e('There_was_an_error_converting'), [], 500);
                                return;
                            }
                        } else {
                            $this->error('Tệp hình ảnh thêm không hợp lệ.', [], 400);
                            return;
                        }
                    }
                }
            }
            if (!empty($input['job'])) {
                $personal['job'] = $input['job'];
            } else {
                unset($personal['job']);
            }
            // add facebook, zalo, telegram, whatsapp, skype, instagram in key social of personal
            $social = ['facebook', 'zalo', 'instagram'];
            foreach ($social as $item) {
                if (!empty($input[$item])) {
                    if(!str_starts_with($input[$item], 'https:') ) {
                        if($item == 'facebook'){
                            $input[$item] = 'https://www.facebook.com/' . $input[$item] . '/';
                        }elseif($item == 'instagram'){
                            $input[$item] = 'https://www.instagram.com/' . $input[$item] .'/';
                        }elseif($item == 'zalo'){
                            $input[$item] = 'https://zalo.me/' . $input[$item] .'/';
                        }
                    } else {
                        if($item == 'facebook'){
                           // if not include facebook.com = '' luon
                           if(strpos($input[$item], 'facebook.com') === false){
                                $input[$item] = '';
                           }
                        }elseif($item == 'instagram'){
                            if(strpos($input[$item], 'instagram.com') === false){
                                $input[$item] = '';
                            }
                        }elseif($item == 'zalo'){
                            if(strpos($input[$item], 'zalo.me') === false){
                                $input[$item] = '';
                            }
                        } 
                    }

                    $personal['social'][$item] = $input[$item];
                    unset($input[$item]);
                } else {
                    unset($personal['social'][$item]);
                    
                    unset($input[$item]);
                }

                unset($personal[$item]);
            }

            $input['avatar'] = $avatarPath;
            $input['display'] = 1;
            $input['online'] = 1;
            
            $input['personal'] = json_encode($personal, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $input['updated_at'] = _DateTime();
                
            $validator = new Validate();
            if (!$validator->check($input, $rules)) {
                $errors = $validator->getErrors();
                $this->error('errors', $errors);
            } else {
                $errors = [];
                if (isset($input['username'])) {
                    $existingUser = $this->usersModel->getUserByUsername($input['username']);
                    if ($existingUser && $existingUser['id'] != $me['id']) {
                        $errors['username'] = [Flang::_e('username_double', $input['username'])];
                    }
                }
                if (HAS_POST('password')){
                    $password = $input['password'] ?? '';
                    $repassword = S_POST('repassword') ?? '';
                    if (!empty($password) && strlen($password) >= 6){ //user nhap change password
                        if ($repassword == $password){
                            $input['password'] = \System\Libraries\Security::hashPassword($password);
                        }else{
                            $errors['password'] = [Flang::_e('password_length', 6)];
                        }
                    }else{
                        $errors['password'] = [Flang::_e('password_repeat_invalid', $repassword )];
                    }
                }
                if (empty($errors)) {
                    $result = $this->usersModel->updateUser($me['id'], $input);
                    if ($result) {
                        $user = $this->_auth();
                        unset($user['password']);
                        unset($user['location']);
                        unset($user['permissions']);
                        unset($user['optional']);
                        $user['personal'] = !empty($user['personal']) ? json_decode($user['personal']) : [];
                        return $this->success($user, Flang::_e('User_updated_successfully'));
                    } else {
                        return $this->error(Flang::_e('User_updated_error'), [], 404);
                    }
                } else {
                    return $this->error($errors, [], 404);
                }
            } 
        } catch (AppException $e) {
            return  $this->error($e->getMessage(), [], 500);
        }
    }

    private function callFunctionWithException(callable $func, ...$args) {
        try {
            set_error_handler(function($errno, $errstr, $errfile, $errline) {
                throw new AppException($errstr, $errno);
            });
    
            $result = call_user_func_array($func, $args);
            restore_error_handler();
    
            return $result;
        } catch (AppException $e) {
            restore_error_handler();
            throw $e;
        }
    }
    

    public function location() {
        try {
            $user = $this->_auth();
            if (HAS_POST('location')) {
                $location = S_POST('location') ?? '';
                $parts = explode('__', $location);
                if (count($parts) != 4) {
                    return $this->error(Flang::_e('Invalid_location_format'), [], 400);
                }
                $latitude = $parts[0] . '.' . $parts[1];
                $longitude = $parts[2] . '.' . $parts[3];
                $latitude = (float)$latitude;
                $longitude = (float)$longitude;
                if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
                    return $this->error(Flang::_e('location_value_is_invalid'), [], 400);
                }
                $data = [
                    'location' => [
                        'expr' => "ST_GeomFromText('POINT($longitude $latitude)')",
                        'params' => []
                    ]
                ];
                $data['updated_at'] = _DateTime();
                $data['online'] = 1;
                $updatedUser = $this->usersModel->updateUser($user['id'], $data);
                return $this->success(['message' => 'Location updated successfully', 'updatedUser' => $updatedUser]);
            }
        } catch (AppException $e) {
            $this->error($e->getMessage(), [], 500);
        } 
    }

    public function favorites() {
        $user = $this->_auth();
        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->_favorites_post($user);
        } else {
            $this->_favorites_get();
        }
    }

    private function _favorites_post($me) {
        try {
                $personal = !empty($me['personal']) ? json_decode($me['personal'], true) : [];
                $inputFavorites = S_POST('favorites');
                if(!empty($inputFavorites)) {
                    if (!is_array($inputFavorites)) {
                        $inputFavorites = [];
                    }
                    $inputFavorites = array_filter($inputFavorites, function($item) {
                        return is_string($item) && !empty(trim($item));
                    });
                    $inputFavorites = array_map('htmlspecialchars', $inputFavorites);
                    $personal['favorites'] = $inputFavorites;
                    $input = [
                        'personal' => json_encode($personal)
                    ];
                    $input['updated_at'] = _DateTime();
                    $result = $this->usersModel->updateUser($me['id'], $input);
                    if ($result) {
                        $user = $this->_auth();
                            unset($user['password']);
                            unset($user['location']);
                            unset($user['permissions']);
                            unset($user['optional']);
                            $user['personal'] = !empty($user['personal']) ? json_decode($user['personal']) : [];
                        return $this->success($user, Flang::_e('User_updated_favourite_successfully'));
                    } else {
                        return $this->error(Flang::_e('User_updated_error'), [], 404);
                    }
                } else {
                    return $this->error(Flang::_e('Invalid'), [], 400);
                }

        } catch(AppException $e) {
            return $this->error($e->getMessage(), [], 500);
        }
    }

    private function _favorites_get() {
        try {
            $cachedata = $this->cache->get();
            if (!empty($cachedata)) {
                $this->cache->headers();
                echo $cachedata;
                exit();
            }else{ 
                $data = [
                    ['id' => 1, 'name' => 'Photography', 'icon' => 'photography'],
                    ['id' => 2, 'name' => 'Shopping', 'icon' => 'shopping'],
                    ['id' => 3, 'name' => 'Karaoke', 'icon' => 'karaoke'],
                    ['id' => 4, 'name' => 'Yoga', 'icon' => 'yoga'],
                    ['id' => 5, 'name' => 'cooking', 'icon' => 'cooking'],
                    ['id' => 6, 'name' => 'Tennis', 'icon' => 'tennis'],
                    ['id' => 7, 'name' => 'Run', 'icon' => 'run'],
                    ['id' => 8, 'name' => 'Swimming', 'icon' => 'swimming'],
                    ['id' => 9, 'name' => 'Art', 'icon' => 'art'],
                    ['id' => 10, 'name' => 'Traveling', 'icon' => 'traveling'],
                    ['id' => 11, 'name' => 'Extreme', 'icon' => 'extreme'],
                    ['id' => 12, 'name' => 'Music', 'icon' => 'music'],
                    ['id' => 13, 'name' => 'Drink', 'icon' => 'drink'],
                    ['id' => 14, 'name' => 'Video Games', 'icon' => 'video_gamesgames'],
                ];
            
                $result = $this->get_success($data, Flang::_e('Get_list_favourite_success'));
                $this->cache->set(json_encode($result));
                
                $this->cache->headers(0);
                echo json_encode($result);
                exit();
            }
        } catch (AppException $e) {
            $this->error($e->getMessage(), [], 500);
        }
    }

    public function jobs() {
        try {
            $cachedata = $this->cache->get();
            if (!empty($cachedata)) {
                $this->cache->headers();
                echo $cachedata;
                exit();
            }else{ 
                $data = [
                    ['id' => 1, 'name' => 'Nhà báo'],
                    ['id' => 2, 'name' => 'Giáo viên'],
                    ['id' => 3, 'name' => 'Công an'],
                    ['id' => 4, 'name' => 'PT'],
                    ['id' => 5, 'name' => 'Nhân viên văn phòng'],
                    ['id' => 6, 'name' => 'Bác sĩ'],
                    ['id' => 7, 'name' => 'Kỹ sư'],
                    ['id' => 8, 'name' => 'Luật sư'],
                    ['id' => 9, 'name' => 'Nội trợ'],
                    ['id' => 10, 'name' => 'Nông dân'],
                    ['id' => 11, 'name' => 'Thợ sửa ống nước'],
                    ['id' => 12, 'name' => 'Thợ điện'],
                    ['id' => 13, 'name' => 'Thợ xây'],
                    ['id' => 14, 'name' => 'Lái xe'],
                    ['id' => 15, 'name' => 'Kinh doanh'],
                    ['id' => 16, 'name' => 'Bán hàng'],
                    ['id' => 17, 'name' => 'Quản lý'],
                    ['id' => 18, 'name' => 'Thư ký'],
                    ['id' => 19, 'name' => 'Công nhân'],
                    ['id' => 20, 'name' => 'Phát thanh viên'],
                    ['id' => 21, 'name' => 'Nhiếp ảnh gia'],
                    ['id' => 22, 'name' => 'Phóng viên'],
                    ['id' => 23, 'name' => 'Giám đốc'],
                    ['id' => 24, 'name' => 'Bếp trưởng'],
                    ['id' => 25, 'name' => 'Phục vụ'],
                    ['id' => 26, 'name' => 'Thiết kế đồ họa'],
                    ['id' => 27, 'name' => 'Kiến trúc sư'],
                    ['id' => 28, 'name' => 'Nhà thiết kế thời trang'],
                    ['id' => 29, 'name' => 'Lập trình viên'],
                    ['id' => 30, 'name' => 'Chuyên viên bảo mật'],
                    ['id' => 31, 'name' => 'Phát triển phần mềm'],
                    ['id' => 32, 'name' => 'Hướng dẫn viên du lịch'],
                    ['id' => 33, 'name' => 'Điều dưỡng'],
                    ['id' => 34, 'name' => 'Dược sĩ'],
                    ['id' => 35, 'name' => 'Kỹ thuật viên'],
                    ['id' => 36, 'name' => 'Nhân viên bảo vệ'],
                    ['id' => 37, 'name' => 'Nhân viên lễ tân'],
                    ['id' => 38, 'name' => 'Nhân viên quán cafe'],
                    ['id' => 39, 'name' => 'Nhân viên bán hàng trực tuyến'],
                    ['id' => 40, 'name' => 'Nhân viên marketing'],
                    ['id' => 41, 'name' => 'Chuyên viên SEO'],
                    ['id' => 42, 'name' => 'Chuyên viên dữ liệu'],
                    ['id' => 43, 'name' => 'Nhà văn'],
                    ['id' => 44, 'name' => 'Nhà thơ'],
                    ['id' => 45, 'name' => 'Biên tập viên'],
                    ['id' => 46, 'name' => 'Dịch giả'],
                    ['id' => 47, 'name' => 'Thợ may'],
                    ['id' => 48, 'name' => 'Thợ cắt tóc'],
                    ['id' => 49, 'name' => 'Thợ làm móng'],
                    ['id' => 50, 'name' => 'Huấn luyện viên thể thao'],
                    ['id' => 51, 'name' => 'Chuyên viên tài chính'],
                    ['id' => 52, 'name' => 'Kế toán'],
                    ['id' => 53, 'name' => 'Kiểm toán viên'],
                    ['id' => 54, 'name' => 'Nhân viên ngân hàng'],
                    ['id' => 55, 'name' => 'Chuyên viên bất động sản'],
                    ['id' => 56, 'name' => 'Đại lý bảo hiểm'],
                    ['id' => 57, 'name' => 'Chuyên viên nhân sự'],
                    ['id' => 58, 'name' => 'Quản trị viên mạng'],
                    ['id' => 59, 'name' => 'Chuyên viên nghiên cứu'],
                    ['id' => 60, 'name' => 'Nhà khoa học'],
                    ['id' => 61, 'name' => 'Nhân viên nghiên cứu thị trường'],
                    ['id' => 62, 'name' => 'Nhà báo thời sự'],
                    ['id' => 63, 'name' => 'Nhà sản xuất phim'],
                    ['id' => 64, 'name' => 'Đạo diễn'],
                    ['id' => 65, 'name' => 'Diễn viên'],
                    ['id' => 66, 'name' => 'Ca sĩ'],
                    ['id' => 67, 'name' => 'Nhạc sĩ'],
                    ['id' => 68, 'name' => 'Nhà soạn nhạc'],
                    ['id' => 69, 'name' => 'Nhà sản xuất âm nhạc'],
                    ['id' => 70, 'name' => 'Vũ công'],
                    ['id' => 71, 'name' => 'Biên đạo múa'],
                    ['id' => 72, 'name' => 'Kiêm bếp'],
                    ['id' => 73, 'name' => 'Phục vụ tạp vụ'],
                    ['id' => 74, 'name' => 'Nhân viên bảo trì'],
                    ['id' => 75, 'name' => 'Kỹ thuật viên IT'],
                    ['id' => 76, 'name' => 'Quản trị hệ thống'],
                    ['id' => 77, 'name' => 'Chuyên viên an ninh mạng'],
                    ['id' => 78, 'name' => 'Nhà phân tích dữ liệu'],
                    ['id' => 79, 'name' => 'Chuyên viên marketing kỹ thuật số'],
                    ['id' => 80, 'name' => 'Nhà phát triển web'],
                    ['id' => 81, 'name' => 'Nhà phát triển ứng dụng di động'],
                    ['id' => 82, 'name' => 'Nhà thiết kế UX/UI'],
                    ['id' => 83, 'name' => 'Quản lý dự án CNTT'],
                    ['id' => 84, 'name' => 'Chuyên viên chăm sóc khách hàng'],
                    ['id' => 85, 'name' => 'Nhân viên tổng đài'],
                    ['id' => 86, 'name' => 'Đại lý du lịch'],
                    ['id' => 87, 'name' => 'Nhà tổ chức sự kiện'],
                    ['id' => 88, 'name' => 'Quản lý sự kiện'],
                    ['id' => 89, 'name' => 'Nhân viên vận chuyển'],
                    ['id' => 90, 'name' => 'Người lái xe tải'],
                    ['id' => 91, 'name' => 'Thợ cơ khí'],
                    ['id' => 92, 'name' => 'Thợ máy'],
                    ['id' => 93, 'name' => 'Kỹ thuật viên ô tô'],
                    ['id' => 94, 'name' => 'Nhân viên sửa chữa máy tính'],
                    ['id' => 95, 'name' => 'Chuyên viên chăm sóc động vật'],
                    ['id' => 96, 'name' => 'Huấn luyện viên thú cưng'],
                    ['id' => 97, 'name' => 'Nhân viên bảo vệ công viên'],
                    ['id' => 98, 'name' => 'Kỹ thuật viên xây dựng'],
                    ['id' => 99, 'name' => 'Quản lý khách sạn'],
                    ['id' => 100, 'name' => 'Nhân viên lễ tân khách sạn'],
                    ['id' => 101, 'name' => 'Bác sĩ đa khoa'],
                    ['id' => 102, 'name' => 'Chuyên viên ngoại trú'],
                    ['id' => 103, 'name' => 'Chuyên viên phẫu thuật'],
                    ['id' => 104, 'name' => 'Nhà tư vấn tâm lý'],
                    ['id' => 105, 'name' => 'Chuyên viên tâm lý học'],
                    ['id' => 106, 'name' => 'Giáo viên dạy nhạc'],
                    ['id' => 107, 'name' => 'Giáo viên dạy thể dục'],
                    ['id' => 108, 'name' => 'Giáo viên dạy nghệ thuật'],
                    ['id' => 109, 'name' => 'Huấn luyện viên cá nhân'],
                    ['id' => 110, 'name' => 'Nhà quản lý cửa hàng'],
                    ['id' => 111, 'name' => 'Nhân viên bán hàng tại cửa hàng'],
                    ['id' => 112, 'name' => 'Chuyên viên logistics'],
                    ['id' => 113, 'name' => 'Quản lý kho'],
                    ['id' => 114, 'name' => 'Nhân viên kho bãi'],
                    ['id' => 115, 'name' => 'Nhân viên giao hàng'],
                    ['id' => 116, 'name' => 'Nhân viên phục vụ nhà hàng'],
                    ['id' => 117, 'name' => 'Bếp phụ'],
                    ['id' => 118, 'name' => 'Chuyên viên dinh dưỡng'],
                    ['id' => 119, 'name' => 'Nhà văn hóa'],
                    ['id' => 120, 'name' => 'Nhân viên bảo vệ sân bay']
                ];
            
                $result = $this->get_success($data, Flang::_e('Get_list_favourite_success'));
                $this->cache->set(json_encode($result));
                
                $this->cache->headers(0);
                echo json_encode($result);
                exit();
            }
        } catch (AppException $e) {
            $this->error($e->getMessage(), [], 500);
        }
    }

    // token device
    public function fcmtoken() {
        $user = $this->_auth();
        if(HAS_POST('fcm_token') && HAS_POST('device_id')) {
            $fcm_token = S_POST('fcm_token') ?? '';
            $device_id = S_POST('device_id') ?? '';
            if(empty($fcm_token) || empty($device_id)) {
                 $this->error(Flang::_e('Invalid'), [], 400);
                exit();
            }
            $optionals = !empty($user['optional']) ? json_decode($user['optional'], true) : [];
            $optionals['fcm_token'][$device_id] = $fcm_token;
            $input = [
                'optional' => json_encode($optionals)
            ];
            $input['updated_at'] = _DateTime();
            $result = $this->usersModel->updateUser($user['id'], $input);
            if(!$result) {
                echo $this->error(Flang::_e('User_updated_error'), [], 404);
                exit();
            }
            $data = [
                'fcm_token' => $fcm_token,
                
            ];
            $result = $this->get_success($data, Flang::_e('update_fcm_token_success'));
           echo json_encode($result);
        } else {
            echo  $this->error(Flang::_e('Method_not_allowed'), [], 405);
        }
        exit();

    }
}
