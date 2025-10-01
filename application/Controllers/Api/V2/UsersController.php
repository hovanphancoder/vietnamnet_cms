<?php
namespace App\Controllers\Api\V2;

use App\Controllers\ApiController;
use System\Core\AppException;
use App\Libraries\Fastlang as Flang;
use System\Drivers\Cache\UriCache;
use System\Libraries\Validate;
use System\Libraries\Files;

class UsersController extends ApiController {
    protected $cache;

    public function __construct(){ 
        parent::__construct();
        $this->cache = new UriCache(5, 'json');
        $this->cache->cacheLogin(true);
    }
// This function was written by me, it's below in the controller
     // Get user details
    public function info(){
        // get header all data 
        $user = $this->_auth();
        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Giữ nguyên thông tin đầy đủ (bao gồm password hash) để xác thực mật khẩu cũ
            $this->_profile_post($user);
        } else {
            // Ẩn các trường nhạy cảm khi trả về thông tin
            unset($user['password']);
            unset($user['permissions']);
            unset($user['optional']);
            $this->_profile_get($user);
        }
    }

     private function _profile_get($me)
     {
         try {
            //$me['header'] = getallheaders();
            return $this->success_v2(['me'=>$me], Flang::_e('get_profile'));
         } catch (AppException $e) {
            return $this->error_v2($e->getMessage(), [], 500);
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
            $avatarPath = $this->_process_avatar($me);
            $input['avatar'] = $avatarPath;
            $input['updated_at'] = _DateTime();
                
            $validator = new Validate();
            if (!$validator->check($input, $rules)) {
                $errors = $validator->getErrors();
            $this->error_v2('errors', $errors);
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
                    $password_repeat = S_POST('password_repeat') ?? '';
                    // check old password 
                    $old_password = S_POST('old_password') ?? '';
                    if(!empty($old_password) && !\System\Libraries\Security::verifyPassword($old_password, $me['password'])) {
                        $errors['old_password'] = [Flang::_e('old_password_incorrect')];
                    }
                    if (!empty($password) && strlen($password) >= 6){ //user nhap change password
                        if ($password_repeat == $password){
                            $input['password'] = \System\Libraries\Security::hashPassword($password);
                        }else{
                            $errors['password'] = [Flang::_e('password_length', 6)];
                        }
                    }else{
                        $errors['password'] = [Flang::_e('password_repeat_invalid', $password_repeat )];
                    }
                }
                if (empty($errors)) {
                    $result = $this->usersModel->updateUser($me['id'], $input);
                    if ($result) {
                                $user = $this->_auth();
                        unset($user['password']);
                        unset($user['permissions']);
                        unset($user['optional']);
                        return $this->success_v2($user, Flang::_e('User_updated_successfully'));
                    } else {
                        return $this->error_v2(Flang::_e('User_updated_error'), [], 404);
                    }
                } else {
                    return $this->error_v2($errors, [], 404);
                }
            } 
        } catch (AppException $e) {
            return  $this->error_v2($e->getMessage(), [], 500);
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


    // Handle avatar upload via Files library. Only accept file uploads.
    private function _process_avatar($me) {
        try {
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $bucket = ceil($me['id'] / 1000);
                $targetFolder = 'users/' . $bucket . '/' . $me['id'];
                // Ép tên file về avatar.{ext} để subfolder là avatar và output chuẩn
                $origExt = strtolower(pathinfo($_FILES['avatar']['name'] ?? '', PATHINFO_EXTENSION));
                if (empty($origExt)) {
                    $this->error_v2(Flang::_e('Invalid_avatar_file'), [], 400);
                    exit;
                }
                $_FILES['avatar']['name'] = 'avatar.' . $origExt;
                // Xóa TẤT CẢ thư mục avatar* trước khi upload để tránh sinh avatar_1, avatar_2...
                $absBase = rtrim(PATH_ROOT, '/').'/'.trim(PATH_WRITE, '/').'uploads/users/'.$bucket.'/'.$me['id'].'/';
                if (is_dir($absBase)) {
                    $candidates = @glob($absBase . 'avatar*');
                    if ($candidates && is_array($candidates)) {
                        foreach ($candidates as $candDir) {
                            if (is_dir($candDir)) {
                                $relCand = 'uploads/users/'.$bucket.'/'.$me['id'].'/'.basename($candDir);
                                Files::deleteFolderRecursive($relCand);
                            }
                        }
                    }
                }

                $options = [
                    'folder' => $targetFolder,
                    'allowed_types' => ['jpg','jpeg','png','gif','webp'],
                    'allowed_mimes' => ['image/jpeg','image/png','image/gif','image/webp'],
                    'webp' => ['q' => 80],
                    'overwrite' => true,
                ];
                $result = Files::upload($_FILES['avatar'], $options, false);
                if (empty($result) || (isset($result['success']) && $result['success'] === false)) {
                    $errorMsg = is_array($result) ? ($result['error'] ?? Flang::_e('Invalid_avatar_file')) : Flang::_e('Invalid_avatar_file');
                    $this->error_v2($errorMsg, [], 400);
                    exit;
                }

                $fileInfo = $result['data'] ?? $result;
                if (!(isset($fileInfo['folder']) && !empty($fileInfo['folder']))) {
                    $this->error_v2(Flang::_e('Invalid_avatar_file'), [], 400);
                    exit;
                }
                // TRẢ VỀ RESPON ĐƠN GIẢN: chọn file tồn tại và trả về URL tương ứng, không rename/di chuyển
                $absUploads = rtrim(PATH_ROOT, '/').'/'.trim(PATH_WRITE, '/').'uploads/';
                $relFolder  = trim($fileInfo['folder'], '/');
                $absFolder  = $absUploads . $relFolder;

                // Ưu tiên webp bất kỳ dạng avatar*.webp, nếu không có thì trả về avatar.{ext}
                $chosen = '';
                $webpCandidates = @glob($absFolder . '/avatar*.webp');
                if (is_array($webpCandidates) && !empty($webpCandidates)) {
                    $chosen = basename($webpCandidates[0]);
                } else {
                    foreach (['png','jpg','jpeg'] as $extTry) {
                        $try = $absFolder . '/avatar.' . $extTry;
                        if (is_file($try)) { $chosen = 'avatar.' . $extTry; break; }
                    }
                }
                if ($chosen === '') {
                    // Fallback: dùng tên do uploader trả về
                    $chosen = 'avatar.' . $origExt;
                }

                // Trả về URL theo file thực tế
                $newPath = '/uploads/' . $relFolder . '/' . $chosen;

                if (!empty($me['avatar'])) {
                    Files::deleteWithParentFolder($me['avatar']);
                }

                return $newPath;
            }

            // Reject link-based updates: only file uploads are allowed
            if (!empty(S_POST('avatar'))) {
                $this->error_v2(Flang::_e('Only_file_uploads_are_allowed'), [], 400);
                exit;
            }

            // No change
            return $me['avatar'];
        } catch (AppException $e) {
            $this->error_v2($e->getMessage(), [], 500);
            exit;
        }
    }


    // delete account
    public function delete_account() {
        $user = $this->_auth();
        if(empty($user)) {
            return $this->error_v2(Flang::_e('authentication_required'), [], 401);
        }
        $password = S_POST('password') ?? '';
        if(empty($password)) {
            return $this->error_v2(Flang::_e('password_required_for_deletion'), [], 400);
        }
        if(!\System\Libraries\Security::verifyPassword($password, $user['password'])) {
            return $this->error_v2(Flang::_e('password_incorrect'), [], 400);
        }
        // change status user to inactive
        $input = [
            'status' => 'deleted'
        ];
        $result = $this->usersModel->updateUser($user['id'], $input);
        if($result) {
            return $this->success_v2([], Flang::_e('account_deleted_successfully'));
        } else {
            return $this->error_v2(Flang::_e('failed_to_delete_account'), [], 500);
        }
    }
    
}
