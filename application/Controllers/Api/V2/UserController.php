<?php
namespace App\Controllers\Api\V2;

use App\Controllers\ApiController;
use System\Core\AppException;
use App\Libraries\Fastlang as Flang;
use System\Drivers\Cache\UriCache;
use System\Libraries\Validate;

class UserController extends ApiController {
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
        $user = $this->_authentication();
        unset($user['password']);
        unset($user['permissions']);
        unset($user['optional']);
        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->_profile_post($user);
        } else {
            $this->_profile_get($user);
        }
    }

     private function _profile_get($me)
     {
         try {
            //$me['header'] = getallheaders();
            return $this->success(['me'=>$me], Flang::_e('get_profile'));
         } catch (AppException $e) {
            return $this->error($e->getMessage(), [], 500);
         }
    }

    

    
    private function _profile_post($me) {
        try {
            $avatarPath = '';
            $input = [];
            $fields = ['fullname', 'username', 'password', 'phone', 'about_me', 'birthday', 'gender'];

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
                    $base_dir = ROOT_PATH . DIRECTORY_SEPARATOR . 'writeable/uploads/users/';
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
                    $filePath = ROOT_PATH . DIRECTORY_SEPARATOR . 'writeable/' . $me['avatar'];
                    if (file_exists($filePath)) {
                        if (!unlink($filePath)) {
                            $this->error('Không thể xóa hình ảnh cũ: ' . $me['avatar'], [], 500);
                            return;
                        }
                    }
                }
                $avatarPath = null;
            }
            $input['avatar'] = $avatarPath;
            $input['updated_at'] = DateTime();
                
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
                    // check old password 
                    $old_password = S_POST('old_password') ?? '';
                    if(!empty($old_password) && !\System\Libraries\Security::verifyPassword($old_password, $me['password'])) {
                        $errors['old_password'] = [Flang::_e('old_password_incorrect')];
                    }
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
                                $user = $this->_authentication();
                        unset($user['password']);
                        unset($user['permissions']);
                        unset($user['optional']);
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


    // delete account
    public function delete_account() {
        $user = $this->_authentication();
        if(empty($user)) {
            return $this->error(Flang::_e('authentication_required'), [], 401);
        }
        $password = S_POST('password') ?? '';
        if(empty($password)) {
            return $this->error(Flang::_e('password_required_for_deletion'), [], 400);
        }
        if(!\System\Libraries\Security::verifyPassword($password, $user['password'])) {
            return $this->error(Flang::_e('password_incorrect'), [], 400);
        }
        // change status user to inactive
        $input = [
            'status' => 'inactive'
        ];
        $result = $this->usersModel->updateUser($user['id'], $input);
        if($result) {
            return $this->success([], Flang::_e('account_deleted_successfully'));
        } else {
            return $this->error(Flang::_e('failed_to_delete_account'), [], 500);
        }
    }
    








}
