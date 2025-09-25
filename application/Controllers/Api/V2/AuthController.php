<?php
namespace App\Controllers\Api\V2;
    

use App\Controllers\ApiController;
use App\Models\UsersModel;
use System\Core\AppException;
use App\Libraries\Fastmail;
use App\Libraries\Fastlang as Flang;
use System\Libraries\Validate;
use System\Libraries\Security;
use System\Libraries\Session;
use App\Libraries\Fasttoken;

class AuthController extends ApiController
{
    protected $usersModel;

    public function __construct()
    {
        parent::__construct();
        // Flang::load('auth', LANG);
        Flang::load('auth', APP_LANG);
        $this->usersModel = new UsersModel();
    }

    public function login()
    {
        try {
            // Kiểm tra nếu có yêu cầu POST với dữ liệu 'username' và 'password'
            if (HAS_POST('username') && HAS_POST('password')) {
                $input = [
                    'username' => S_POST('username'),
                    'password' => S_POST('password')
                ];
                // check csrf token
                $csrf_token = S_POST('csrf_token') ?? '';
                if (!Session::csrf_verify($csrf_token)) {
                    // return $this->error(Flang::_e('csrf_failed'), [], 400);
                }

                $rules = [
                    'username' => [
                        'rules' => [Validate::alnum("@._"), Validate::length(6, 150)],
                        'messages' => [Flang::_e('loginname_invalid'), Flang::_e('username_length', 6, 150)]
                    ],
                    'password' => [
                        'rules' => [Validate::length(6, null)],
                        'messages' => [Flang::_e('password_length', 6)]
                    ]
                ];

                $validator = new Validate();
                if (!$validator->check($input, $rules)) {
                    // Trả về lỗi nếu validate thất bại
                    $errors = $validator->getErrors();
                    return $this->error(Flang::_e('login_failed', $input['username']), $errors, 400);
                }

                // Kiểm tra thông tin đăng nhập
                if (!filter_var($input['username'], FILTER_VALIDATE_EMAIL)) {
                    $me = $this->usersModel->getUserByUsername($input['username']);
                } else {
                    $me = $this->usersModel->getUserByEmail($input['username']);
                }

                if ($me && Security::verifyPassword($input['password'], $me['password'])) {
                    if ($me['status'] !== 'active') {
                        return $this->error(Flang::_e('users_noactive', $input['username']), [], 403);
                    }
                    
                    // Set thông tin đăng nhập vào session
                    Session::set('user_id', $me['id']);
                    Session::set('role', $me['role']);
                    Session::set('permissions', json_decode($me['permissions'], true));
                    // Tái tạo session ID để tránh session fixation
                    Session::regenerate();

                    // Tạo JWT token
                    $config_security = config('security');
                    $me_data = [
                        'id' => $me['id'],
                        'role' => $me['role'],
                        'username' => $me['username'],
                        'email' => $me['email']
                    ];
                    $access_token = Fasttoken::createToken($me_data, $config_security['app_secret'], $config_security['app_id']);
                    return $this->success([
                        'me' => [
                            'id' => $me['id'],
                            'username' => $me['username'],
                            'email' => $me['email'],
                            'fullname' => $me['fullname'],
                            'avatar' => $me['avatar'],
                            'role' => $me['role'],
                            'status' => $me['status'],
                            'created_at' => $me['created_at'],
                            'updated_at' => $me['updated_at'],
                            'phone' => $me['phone'],
                            'birthday' => $me['birthday'],
                            'gender' => $me['gender'],
                            'about_me' => $me['about_me'],
                            'coin' => $me['coin']
                        ],
                        'access_token' => $access_token
                    ], Flang::_e('login_success'));
                } else {
                    return $this->error(Flang::_e('login_failed', $input['username']), [], 401);
                }
            }

            return $this->error(Flang::_e('username_invalid'), [], 400);
        } catch (AppException $e) {
            return $this->error(    $e->getMessage(), [], 500);
        }
    }

    public function logout()
    {
        $user = $this->_authentication();
        if(empty($user)) {
            return $this->error(Flang::_e('logout_failed'), [], 400);
        }
        try {
            // Xóa session nếu tồn tại
            if (Session::has('user_id')) {
                Session::del('user_id');
                Session::del('role');
                Session::del('permissions');
            }

            return $this->success([], Flang::_e('logout_success'));
        } catch (AppException $e) {
            return $this->error($e->getMessage(), [], 500);
        }
    }

    public function csrf_create(){
        return $this->success(['csrf_token' => Session::csrf_token(600)], 'CSRF Token Created');
    }

    public function register()
    {
        try {
            // Kiểm tra nếu có yêu cầu POST với các trường cần thiết
            if (HAS_POST('username')) {
                $csrf_token = S_POST('csrf_token') ?? '';
                if (!Session::csrf_verify($csrf_token)) {
                    // return $this->error(Flang::_e('csrf_failed'), [], 400);
                }   

                $input = [
                    'username' => S_POST('username'),
                    'fullname' => S_POST('fullname'),
                    'email' => S_POST('email'),
                    'password' => S_POST('password'),
                    'password_repeat' => S_POST('password_repeat'),
                    // 'phone' => S_POST('phone'),
                ];
        
                // Quy tắc kiểm tra
                $rules = [
                    'username' => [
                        'rules' => [Validate::alnum("@._"), Validate::length(6, 30)],
                        'messages' => [Flang::_e('username_invalid'), Flang::_e('username_length', 6, 30)]
                    ],
                    'fullname' => [
                        'rules' => [ Validate::regex('/^[\p{L}\p{M}\s]+$/u') , Validate::length(6, 60)],
                        'messages' => [Flang::_e('fullname_invalid'), Flang::_e('fullname_length', 6, 60)]
                    ],
                    'email' => [
                        'rules' => [Validate::email(), Validate::length(6, 150)],
                        'messages' => [Flang::_e('email_invalid'), Flang::_e('email_length', 6, 150)]
                    ],
                    // 'phone' => [
                    //     'rules' => [Validate::phone(), Validate::length(6, 30)],
                    //     'messages' => [Flang::_e('phone_invalid'), Flang::_e('phone_length', 6, 30)]
                    // ],
                    'password' => [
                        'rules' => [Validate::length(6, 60)],
                        'messages' => [Flang::_e('password_length', 6, 60)]
                    ],
                    'password_repeat' => [
                        'rules' => [
                            Validate::equals($input['password'])
                        ],
                        'messages' => [
                            Flang::_e('password_repeat_invalid')
                        ]
                    ],
                ];

                $validator = new Validate();
                if (!$validator->check($input, $rules)) {
                    $errors = $validator->getErrors();
                    return $this->error(Flang::_e('register_failed'), $errors, 400);
                }

                // Kiểm tra xem username hoặc email đã tồn tại hay chưa
                if ($this->usersModel->getUserByUsername($input['username'])) {
                    $errors = [
                        'username' => 'The username address is already registered in the system.'
                    ];
                    return $this->error(Flang::_e('Username_already_exists'), $errors, 400);
                }
                if ($this->usersModel->getUserByEmail($input['email'])) {
                    $errors = [
                        'email' => 'The email address is already registered in the system.'
                    ];
                    return $this->error(Flang::_e('Email_already_exists'), $errors, 400);
                }

                // Hash mật khẩu và thêm dữ liệu người dùng
                $input['password'] = Security::hashPassword($input['password']);
                $input['avatar'] = '';
                $input['role'] = 'member';
                $input['permissions'] = json_encode(config('member', 'Roles'));
                $input['status'] = 'active';
                $input['created_at'] = date('Y-m-d H:i:s');
                $input['updated_at'] = date('Y-m-d H:i:s');
                unset($input['password_repeat']);
                // Tạo mã kích hoạt
                $activationNo = strtoupper(random_string(6));
                $activationCode = strtolower(random_string(20));
                $optionalData = [
                    'activation_no' => $activationNo,
                    'activation_code' => $activationCode,
                    'activation_expires' => time() + 86400,
                ];
                $input['optional'] = json_encode($optionalData);

                $user_id = $this->usersModel->addUser($input);
                
                if ($user_id) {
                    // Gửi email kích hoạt
                    $activationLink = auth_url('activation/' . $user_id . '/' . $activationCode . '/');
                    $mailer = new Fastmail();
                    $mailer->send($input['email'], Flang::_e('active_account'), 'activation', [
                        'username' => $input['username'],
                        'activation_link' => $activationLink,
                        'activation_no' => $activationNo,
                        'smtpDebug' => 2
                    ]);

                    $input['id'] = $user_id;

                    $config_security = config('security');
                    $me_data = [
                        'id' => $input['id'],
                        'role' => $input['role'],
                        'username' => $input['username'],
                        'email' => $input['email']
                    ];
                    $access_token = Fasttoken::createToken($me_data, $config_security['app_secret'], $config_security['app_id']);
                    $input['personal'] = $input['personal'] ?? [];
                    $data = [
                        'me' => [
                            'id' => $input['id'],
                            'username' => $input['username'] ?? '',
                            'email' => $input['email'] ?? '',
                            'fullname' => $input['fullname']?? '',
                            'avatar' => $input['avatar'] ?? '',
                            'role' => $input['role'] ?? '',
                            'status' => $input['status'] ?? 'inactive',
                            'created_at' => $input['created_at'] ?? '',
                            'updated_at' => $input['updated_at'] ?? '',
                            'phone' => $input['phone'] ?? '',
                            'birthday' => $input['birthday'] ?? '',
                            'gender' => $input['gender'] ?? '',
                            'about_me' => $input['about_me'] ?? '',
                            'coin' => $input['coin'] ?? 0
                        ],
                        'access_token'=> $access_token
                    ];
                    return $this->success($data, Flang::_e('register_success'));
                } else {
                    return $this->error(Flang::_e('register_error'), [], 500);
                }
            }  else {
                $csrf_token = Session::csrf_token(600);
                return $this->success(['csrf_token' => $csrf_token], Flang::_e('register_success'));
            }

            
        } catch (AppException $e) {
            return $this->error($e->getMessage(), [], 500);
        }
    }


    public function google() {
        $this->success([], Flang::_e('coming_soon'));
        $data = [
            'idToken' => S_POST('idToken') ?? '',
            'user' => $_POST['user'] ?? ''
        ];
        if(empty($data['idToken']) || empty($data['user'])) {
            return $this->error(Flang::_e('google_login_failed'), [], 400);
        }
        $config_security = config('security');
        
        $data['user'] = json_decode($data['user'], true);
        // check email exist
        $me = $this->usersModel->getUserByEmail($data['user']['email']);
        if($me) {
            // check google token
            $optional = json_decode($me['optional'], true);
            $google_token = $optional['id_google'] ?? '';
            if($google_token != $data['user']['id']) {
                $this->error(Flang::_e('google_login_failed'), [], 400);
            } else {
                $me_data = [
                    'id' => $me['id'],
                    'role' => $me['role'],
                    'username' => $me['username'],
                    'email' => $me['email']
                ];

                $access_token = Fasttoken::createToken($me_data, $config_security['app_secret'], $config_security['app_id']);

                return $this->success([
                    'me' => [
                        'id' => $me['id'],
                        'username' => $me['username'],
                        'email' => $me['email'],
                        'fullname' => $me['fullname'],
                        'avatar' => $me['avatar'],
                        'role' => $me['role'],
                        'status' => $me['status'],
                        'created_at' => $me['created_at'],
                        'updated_at' => $me['updated_at'],
                        'phone' => $me['phone'],
                        'birthday' => $me['birthday'],
                        'gender' => $me['gender'],
                        'about_me' => $me['about_me'],
                        'coin' => $me['coin']
                    ],
                    'access_token' => $access_token
                ], Flang::_e('login_success'));

            }
        } else {
            $input['fullname'] = $data['user']['name'];
            $input['username'] = $data['user']['email'];
            $input['email'] = $data['user']['email'];
            $input['password'] = 'null';
            $input['avatar'] = $data['user']['photo'];
            $input['role'] = 'member';
            $input['permissions'] = json_encode(config('member', 'Roles'));
            $input['status'] = 'active';
            $input['coin'] = 0;
            $input['created_at'] = date('Y-m-d H:i:s');
            $input['updated_at'] = date('Y-m-d H:i:s');
            $optionNal = [
                'google_token' => $data['idToken'],
                'id_google' => $data['user']['id']
            ]; 
            $input['optional'] = json_encode($optionNal);
            $user_id = $this->usersModel->addUser($input);
    
            if ($user_id) {
                $me_data = [
                    'id' => $user_id,
                    'role' => $input['role'],
                    'username' => $input['username'],
                    'email' => $input['email']
                ];
                $access_token = Fasttoken::createToken($me_data, $config_security['app_secret'], $config_security['app_id']);
                return $this->success([
                    'me' => [
                        'id' => $user_id,
                        'username' => $input['username'],
                        'email' => $input['email'],
                        'fullname' => $input['fullname'],
                        'avatar' => $input['avatar'],
                        'role' => $input['role'],
                        'status' => $input['status'],
                        'created_at' => $input['created_at'],
                        'updated_at' => $input['updated_at']
                    ],
                    'access_token' => $access_token
                ], Flang::_e('login_success'));
            } else {
                return $this->error(Flang::_e('register_error'), [], 500);
            }
        }
        exit;        
    }

    /**
     * Forgot Password - Step 1: Send reset email
     */
    public function forgot_password()
    {
        try {
            if (HAS_POST('email')) {
                $input = [
                    'email' => S_POST('email')
                ];
                
                $rules = [
                    'email' => [
                        'rules' => [Validate::email(), Validate::length(5, 150)],
                        'messages' => [Flang::_e('email_invalid'), Flang::_e('email_length', 5, 150)]
                    ]
                ];

                $validator = new Validate();
                if (!$validator->check($input, $rules)) {
                    $errors = $validator->getErrors();
                    return $this->error(Flang::_e('forgot_password_failed'), $errors, 400);
                }

                $user = $this->usersModel->getUserByEmail($input['email']);
                if (!$user) {
                    return $this->error(Flang::_e('email_not_found', $input['email']), [], 404);
                }

                // Send reset email
                $this->_forgot_send($user);
                
                return $this->success([
                    'message' => Flang::_e('reset_password_email_sent'),
                    'email' => $input['email']
                ], Flang::_e('forgot_password_success'));
            }

            return $this->error(Flang::_e('email_required'), [], 400);
        } catch (AppException $e) {
            return $this->error($e->getMessage(), [], 500);
        }
    }

    /**
     * Reset Password - Step 2: Reset password with token
     */
    public function reset_password()
    {
        try {
            if (HAS_POST('user_id') && HAS_POST('token') && HAS_POST('password')) {
                $user_id = S_POST('user_id');
                $token = S_POST('token');
                $password = S_POST('password');

                $user = $this->usersModel->getUserById($user_id);
                if (!$user) {
                    return $this->error(Flang::_e('user_not_found'), [], 404);
                }

                $user_optional = json_decode($user['optional'], true);
                $token_db = $user_optional['token_reset_password'] ?? '';
                $token_expires = $user_optional['token_reset_password_expires'] ?? 0;

                if ($token !== $token_db) {
                    return $this->error(Flang::_e('token_invalid'), [], 400);
                }

                if ($token_expires <= time()) {
                    return $this->error(Flang::_e('token_expired'), [], 400);
                }

                // Validate password
                $rules = [
                    'password' => [
                        'rules' => [Validate::length(6, 60)],
                        'messages' => [Flang::_e('password_length', 6, 60)]
                    ]
                ];

                $validator = new Validate();
                if (!$validator->check(['password' => $password], $rules)) {
                    $errors = $validator->getErrors();
                    return $this->error(Flang::_e('password_invalid'), $errors, 400);
                }

                // Update password and remove token
                $update_data = [
                    'password' => Security::hashPassword($password),
                    'updated_at' => date('Y-m-d H:i:s')
                ];

                // Remove reset token from optional
                if (isset($user_optional['token_reset_password'])) {
                    unset($user_optional['token_reset_password']);
                }
                if (isset($user_optional['token_reset_password_expires'])) {
                    unset($user_optional['token_reset_password_expires']);
                }
                $update_data['optional'] = json_encode($user_optional);

                $result = $this->usersModel->updateUser($user_id, $update_data);
                if ($result) {
                    return $this->success([
                        'message' => Flang::_e('password_reset_success')
                    ], Flang::_e('reset_password_success'));
                } else {
                    return $this->error(Flang::_e('password_reset_failed'), [], 500);
                }
            }

            return $this->error(Flang::_e('missing_required_fields'), [], 400);
        } catch (AppException $e) {
            return $this->error($e->getMessage(), [], 500);
        }
    }

    /**
     * Send forgot password email
     */
    private function _forgot_send($user)
    {
        $user_id = $user['id'];
        // Tạo token forgot password
        $token = strtolower(random_string(32));
        
        $user_optional = json_decode($user['optional'], true);
        if (empty($user_optional)) {
            $user_optional = [];
        }
        
        $user_optional['token_reset_password'] = $token;
        $user_optional['token_reset_password_expires'] = time() + 86400;
        
        $this->usersModel->updateUser($user_id, ['optional' => json_encode($user_optional)]);

        // Construct reset link 
        $reset_link = auth_url('forgot_password/' . $user_id . '/' . $token . '/');
        
        // Gửi email link reset password
        $mailer = new Fastmail();
        $mailer->send($user['email'], Flang::_e('title_email_link_reset'), 'reset_password', [
            'username' => $user['username'], 
            'reset_link' => $reset_link
        ]);
    }
}