<?php
namespace App\Controllers\Backend;

use App\Controllers\BackendController;
use App\Models\UsersModel;
use App\Libraries\Fasttoken;
use System\Libraries\Security;
use System\Libraries\Session;
use System\Libraries\Render;
use App\Libraries\Fastmail;
use App\Libraries\Fastlang as Flang;
use System\Libraries\Validate;
use Google_Client;
use Google_Service_Oauth2;
use System\Libraries\Events;

class AuthController extends BackendController
{
    protected $usersModel;
    protected $mailer;

    public function __construct()
    {
        parent::__construct();
        load_helpers(['backend']);
        $this->usersModel = new UsersModel();
        Flang::load('auth', APP_LANG);

        Render::asset('css', 'css/new_style.css', ['area' => 'backend', 'location' => 'head']);
        Render::asset('css', 'css/font-inter.css', ['area' => 'backend', 'location' => 'head']);

        Render::asset('js', 'js/jfast.1.2.3.js', ['area' => 'backend', 'location' => 'footer']);
        Render::asset('js', 'js/feather.min.js', ['area' => 'backend', 'location' => 'footer']);
        Render::asset('js', 'js/theme.js', ['area' => 'backend', 'location' => 'footer']);
        Render::asset('js', 'js/script.js', ['area' => 'backend', 'location' => 'footer']);

    }

    // Check login status
    public function index()
    {
        if (Session::has('user_id')) {
            // If already logged in, redirect to dashboard
            redirect(admin_url('/'));
        } else {
            // If not logged in, redirect to login page
            redirect(auth_url('login'));
        }
    }

    // Display login form
    public function login()
    {
        //Validate step if there's a login request.
        if (HAS_POST('username')){
            $csrf_token = S_POST('csrf_token') ?? '';
            if (!Session::csrf_verify($csrf_token)){
                Session::flash('error', Flang::_e('csrf_failed') );
                redirect(auth_url('login'));
            }
            $input = [
                'username'  =>  S_POST('username') ?? '',
                'password'  =>  S_POST('password') ?? ''
            ];
            // lowercase username
            $input['username'] = strtolower($input['username']);
            $rules = [
                'username' => [
                    'rules' => [Validate::alnum("@._"), Validate::length(5, 150)],
                    'messages' => [Flang::_e('username_invalid'), Flang::_e('username_length', 5, 30)]
                ],
                'password' => [
                    'rules' => [Validate::length(5, null)],
                    'messages' => [Flang::_e('password_length', 6)]
                ]
            ];
            $validator = new Validate();
            if (!$validator->check($input, $rules)) {
                // Get errors and display
                $errors = $validator->getErrors();
                $this->data('errors', $errors);     
            }else{
                return $this->_login($input);
            }
        }

        // Display login page: If no login request, or validation failed
        $this->data('title', Flang::_e('login_welcome'));
        $this->data('csrf_token', Session::csrf_token(600)); //security token for login only exists for 10 minutes.

        echo Render::html('Common/Auth/login', $this->data);
    }
    
    // Handle login
    public function _login($input)
    {
        if (!filter_var($input['username'], FILTER_VALIDATE_EMAIL)) {
            $user = $this->usersModel->getUserByUsername($input['username']);
        }else{
            $user = $this->usersModel->getUserByEmail($input['username']);
        }
        
        // echo Security::hashPassword($input['password']);die;
        if ($user && Security::verifyPassword($input['password'], $user['password'])) {
            if ($user['status'] !== 'active') {
                Session::flash('error', Flang::_e('users_noactive', $input['username']));
                redirect(auth_url('login'));
                exit();
            }
            // Set login information to session
            setcookie('cmsff_logged', $user['id'], time()+86400, '/');
            Session::set('user_id', $user['id']);
            Session::set('role', $user['role']);
            Session::set('permissions', json_decode($user['permissions'], true));
            // Regenerate session ID to avoid session fixation
            Session::regenerate();

            // Create JWT token
            $config_security = config('security');
            $me_data = [
                'id' => $user['id'],
                'role' => $user['role'],
                'username' => $user['username'],
                'email' => $user['email']
            ];
            $access_token = Fasttoken::createToken($me_data, $config_security['app_secret'], $config_security['app_id']);
            setcookie('cmsff_logged', $user['id'], time()+86400, '/');
            if (S_POST('remember') == 'on') {
                setcookie('cmsff_token', $access_token, time()+86400*365, '/');
            } else {
                setcookie('cmsff_token', $access_token, time()+86400, '/');
            }

            // Write Events that a user has successfully logged in
            Events::run('Backend\\UserLoginEvent', $user);

            redirect(admin_url('/'));
        } else {
            Session::flash('error', Flang::_e('login_failed', $input['username']) );
            redirect(auth_url('login'));
        }
    }

    // Logout
    public function logout()
    {
        setcookie('cmsff_logged', '', time()-1, '/');
        Session::del('user_id');
        Session::del('role');
        Session::del('permissions');
        if (isset($_COOKIE['cmsff_token'])){
            setcookie('cmsff_token', '', time()-1, '/');
        }
        Events::run('Backend\\UserLogoutEvent');
        redirect(auth_url('login'));
        exit();
    }

    // Register new account
    public function register()
    {
        //Validate step if there's a register request.
        if (HAS_POST('username')){
            $csrf_token = S_POST('csrf_token') ?? '';
            if (!Session::csrf_verify($csrf_token)){
                Session::flash('error', Flang::_e('csrf_failed') );
                redirect(auth_url('register'));
            }
            $input = [
                'username' => S_POST('username'),
                'fullname' => S_POST('fullname'),
                'email' => S_POST('email'),
                'password' => S_POST('password'),
                'password_repeat' => S_POST('password_repeat'),
                'phone' => S_POST('phone'),
            ];
            //Lowercase username
            $input['username'] = strtolower($input['username']);
            $rules = [
                'username' => [
                    'rules' => [
                        Validate::alnum('_'),
                        Validate::length(5, 40)
                    ],
                    'messages' => [
                        Flang::_e('username_invalid'),
                        Flang::_e('username_length', 5, 40)
                    ]
                ],
                'fullname' => [
                    'rules' => [
                        Validate::length(5, 60)
                    ],
                    'messages' => [
                        Flang::_e('fullname_length', 5, 60)
                    ]
                ],
                'email' => [
                    'rules' => [
                        Validate::email(),
                        Validate::length(5, 150)
                    ],
                    'messages' => [
                        Flang::_e('email_invalid'),
                        Flang::_e('email_length', 5, 150)
                    ]
                ],
                'phone' => [
                    'rules' => [
                        Validate::phone(),
                        Validate::length(5, 30)
                    ],
                    'messages' => [
                        Flang::_e('phone_invalid'),
                        Flang::_e('phone_length', 5, 30)
                    ]
                ],
                'password' => [
                    'rules' => [
                        Validate::length(5, 60),
                    ],
                    'messages' => [
                        Flang::_e('password_length', 5, 60),
                    ]
                ],
                'password_repeat' => [
                    'rules' => [
                        Validate::equals($input['password'])
                    ],
                    'messages' => [
                        Flang::_e('password_repeat_invalid', $input['password_repeat'])
                    ]
                ],
            ];
            $validator = new Validate();
            if (!$validator->check($input, $rules)) {
                // Lấy các lỗi và hiển thị
                $errors = $validator->getErrors();
                $this->data('errors', $errors);
            }else{
                $errors = [];
                if ($this->usersModel->getUserByUsername($input['username'])) {
                    $errors['username'] = array(
                        Flang::_e('username_double', $input['username'])
                    );
                    $isExists = true;
                }
                if ($this->usersModel->getUserByEmail($input['email'])) {
                    $errors['email'] = array(
                        Flang::_e('email_double', $input['email'])
                    );
                    $isExists = true;
                }
                if (!isset($isExists) && empty($errors)){
                    $input['password'] = Security::hashPassword($input['password']);
                    $input['avatar'] = '';
                    $input['role'] = 'member';
                    $input['permissions'] = json_encode(config('member', 'Roles')['permissions'] ?? []);
                    $input['status'] = 'inactive';
                    $input['created_at'] = DateTime();
                    $input['updated_at'] = DateTime();
                    return $this->_register($input);
                }else{
                    $this->data('errors', $errors);
                }
            }
        }
        
        // Display login page: If no login request, or validation failed
        $this->data('title', Flang::_e('register_welcome'));
        $this->data('csrf_token', Session::csrf_token(600)); //security token for login only exists for 10 minutes.

        echo Render::html('Common/Auth/register', $this->data);
    }
    
    // Handle account registration
    private function _register($input)
    {
        // Create 6-character activation code for user input
        $activationNo = strtoupper(random_string(6)); // Create 6-character code
        // Create separate activation code for URL
        $activationCode = strtolower(random_string(20)); // Create 20-character code
        $optionalData = [
            'activation_no' => $activationNo,
            'activation_code' => $activationCode,
            'activation_expires' => time()+86400,
        ];
        $input['optional'] = json_encode($optionalData);
        //Them Data Nguoi Dung Vao Du Lieu
        $user_id = $this->usersModel->addUser($input);

        if ($user_id) {
            // Send activation email
            $activationLink = auth_url('activation/' . $user_id . '/' . $activationCode.'/');
            //$emailContent = Render::component('Common/Email/activation', ['username' => $input['username'], 'activation_link' => $activationLink]);
            //echo $emailContent;die;
            $this->mailer = new Fastmail();
            $this->mailer->send($input['email'], Flang::_e('active_account'), 'activation', ['username' => $input['username'], 'activation_link' => $activationLink, 'activation_no' => $activationNo]);
            
            Session::flash('success', Flang::_e('regsiter_success'));
            $this->data('csrf_token', Session::csrf_token(600));

            Events::run('Backend\\UserRegisterEvent', $user_id);
         
            redirect(auth_url("activation/{$user_id}/"));

        } else {
            Session::flash('error', Flang::_e('register_error'));
            redirect(auth_url('register'));
        }
    }

    public function activation($user_id = '', $activationCode = null)
    {
        // Get user information from ID
        $user = $this->usersModel->getUserById($user_id);
        if (!$user) {
            Session::flash('error', Flang::_e('acccount_does_exist'));
            redirect(auth_url('login'));
            return;
        }
        if ($user['status'] != 'inactive'){
            Session::flash('success', Flang::_e('account_active'));
            redirect(auth_url('login'));
            return;
        }

        $user_optional = @json_decode($user['optional'], true);

        $user_active_expires = $user_optional['activation_expires'] ?? 0;

        // If user requests to resend code
        if (HAS_POST('activation_resend')) {
            return $this->_activation_resend($user_id, $user_optional, $user);
        }

        if ($user_active_expires < time()){
            $this->data('error', Flang::_e('token_out_time'));
            return $this->_activation_form($user_id);
        } 

        // Case when user accesses via URL
        if ($activationCode) {
            $user_active_code = $user_optional['activation_code'] ?? '';
            if (!empty($user_active_code) && strtolower($user_active_code) === strtolower($activationCode)) {
                // Activate account
                return $this->_activation($user_id);
            } else {
                $this->data('error', Flang::_e('token_invalid'));
                return $this->_activation_form($user_id);
            }
        }

        // Case when user enters code in form
        if (HAS_POST('activation_no')) {
            $activationNo = S_POST('activation_no');
            $user_active_no = $user_optional['activation_no'] ?? '';
            if (!empty($user_active_no) && strtoupper($user_active_no) === strtoupper($activationNo)) {
                // Activate account
                $this->_activation($user_id);
            } else {
                $this->data('error', Flang::_e('token_invalid'));
                $this->_activation_form($user_id);
            }
        } else {
            // Display activation code input form
            $this->_activation_form($user_id);
        }
    }
    
    //Forgot Password
    public function forgot($user_id = '', $token = ''){
        if (empty($user_id) || empty($token)){
            if(HAS_POST('email')) {
                $input = [ 
                    'email' => S_POST('email')
                ];
                $rules = [
                    'email' => [
                        'rules' => [
                            Validate::email(),
                            Validate::length(5, 150)
                        ],
                        'messages' => [
                            Flang::_e('email_invalid'),
                            Flang::_e('email_length', 5, 150)
                        ]
                    ],
                ];
                $validator = new Validate();
                if (!$validator->check($input, $rules)) {
                    $errors = $validator->getErrors();
                    $this->data('errors', $errors);     
                }else{
                    $user = $this->usersModel->getUserByEmail($input['email']);
                    if (!$user) {
                        $errors['email'] = array(
                            Flang::_e('email_exist', $input['email'])
                        );
                        $this->data('errors', $errors);     
                    }else {
                        $user_optional = @json_decode($user['optional'], true);
                        $this->_forgot_send($user);
                    }
                }
            }

            $this->data('csrf_token', Session::csrf_token(600));
            $this->data('title', Flang::_e('forgotpassw_welcome'));
            
            echo Render::html('Common/Auth/forgot-password', $this->data);
        }else{
            $user_id = clean_input($user_id);
            $user = $this->usersModel->getUserById($user_id);
            if (!$user){
                $errors['email'] = array(
                    Flang::_e('user_exist')
                );
                $this->data('errors', $errors);
                $this->data('title', Flang::_e('forgotpassw_welcome'));

                echo Render::html('Backend/Auth/forgot_password', $this->data);
            }else{
                return $this->_forgot_password($user, $token);
            }
        }
    }

    private function _forgot_password($user, $token) {
        $user_id = $user['id'];
        $user_optional = @json_decode($user['optional'], true);

        $token_db = $user_optional['token_reset_password'] ?? '';
        $token_expires = $user_optional['token_reset_password_expires'] ?? 0;
        
        if($token !== $token_db) {
            $error = Flang::_e('token_fotgot_invalid');
        }
        if($token_expires <= time()){
            $error = Flang::_e('token_fotgot_out_time');
        }
        if (!empty($error)){
            $this->data('error', $error);
            $this->data('title', Flang::_e('forgotpassw_welcome'));
            $this->render('auth', 'Backend/Auth/forgot_password');
        }else{
            if(HAS_POST('password')) {
                $input = [
                    'password' => S_POST('password'),
                ];
                $rules = [
                    'password' => [
                        'rules' => [
                            Validate::length(5, 60),
                        ],
                        'messages' => [
                            Flang::_e('password_length', 5, 60),
                        ]
                    ]
                ];
                $validator = new Validate();
                if (!$validator->check($input, $rules)) {
                    $errors = $validator->getErrors();
                    $this->data('errors', $errors);
                }else {
                    $input['password'] = Security::hashPassword($input['password']);
                    if (isset($user_optional['token_reset_password'])){
                        unset($user_optional['token_reset_password']);
                    }
                    if (isset($user_optional['token_reset_password_expires'])){
                        unset($user_optional['token_reset_password_expires']);
                    }
                    $input['optional'] = json_encode($user_optional); //remove ma reset sau khi set passs.
                    $this->usersModel->updateUser($user_id, $input);                    
                    
                    $success = Flang::_e('reset_password_success');
                    $this->data('success', $success);
                    $this->data('csrf_token', Session::csrf_token(600));
                    $this->data('title', Flang::_e('login_welcome'));

                    Events::run('Backend\\UserForgotPasswordEvent', $user_id);
                    
                    $this->render('auth', 'Backend/Auth/login');
                }
            }
            $this->data('title', Flang::_e('update_password_welcome'));
            $this->render('auth', 'Backend/Auth/forgot_setpassword');
        }
    }   

    public function login_google(){
        
        $option_google = option('google');
        $option_google = array_column($option_google, 'google_value', 'google_key');
        $client_id = $option_google['GOOGLE_CLIENT_ID'] ?? '';
        $client_secret = $option_google['GOOGLE_CLIENT_SECRET'] ?? '';
        $client_url = $option_google['GOOGLE_REDIRECT_URL'] ?? '';

        $client = new Google_Client();
        $client->setClientId($client_id); 
        $client->setClientSecret($client_secret);
        $client->setRedirectUri($client_url);
        // Thêm các phạm vi truy cập
        $client->addScope('email');
        $client->addScope('profile');

        if (!HAS_GET('code')) {
            // Tạo URL để người dùng đăng nhập qua Google
            $auth_url = $client->createAuthUrl();
            
            redirect(filter_var($auth_url, FILTER_SANITIZE_URL));
        }else{
            // Lấy mã code từ URL khi người dùng quay lại từ Google
            $code = $_GET['code'];
            // Trao đổi mã lấy token truy cập
            $token = $client->fetchAccessTokenWithAuthCode($code);
            // Đặt token truy cập cho client
            $client->setAccessToken($token);
            // Lấy thông tin người dùng từ Google
            $oauth2 = new Google_Service_Oauth2($client);
            $userInfo = $oauth2->userinfo->get();
            $email_user = $userInfo->email ?? '';
            $fullname = $userInfo->name ?? ''; 
            $user = $this->usersModel->getUserByEmail($email_user);

            if ($user) {
                // Set thông tin đăng nhập vào session
                Session::set('user_id', $user['id']);
                Session::set('role', $user['role']);
                Session::set('permissions', json_decode($user['permissions'], true));
                // Tái tạo session ID để tránh session fixation
                Session::regenerate();

                Events::run('Backend\\UserLoginGoogleEvent', $user);

                redirect(admin_url('/'));
            } else {
                Session::set('fullname', $fullname);
                Session::set('email', $email_user);
                // Chuyển hướng đến trang đăng ký để nhập các trường còn lại
                redirect(auth_url('register'));
            }
        
        }
    }

    private function _activation_resend($user_id, $user_optional, $user)
    {
        // Tạo mã kích hoạt 6 ký tự cho người dùng nhập vào
        $activationNo = strtoupper(random_string(6)); // Tạo mã gồm 6 ký tự
        // Tạo mã kích hoạt riêng cho URL
        $activationCode = strtolower(random_string(32)); // Tạo mã gồm 32 ký tự
        if (empty($user_optional)){
            $user_optional = [];
        }/*  */
        $user_optional['activation_no'] = $activationNo;
        $user_optional['activation_code'] = $activationCode;
        $user_optional['activation_expires'] = time()+86400;
        $this->usersModel->updateUser($user_id, ['optional'=>json_encode($user_optional)]);

        // Gửi email mã kích hoạt mới
        $activationLink = auth_url('activation/' . $user_id . '/' . $activationCode.'/');
        $this->mailer = new Fastmail();
        $this->mailer->send($user['email'], Flang::_e('code_active_account'), 'activation', ['username' => $user['username'], 'activation_link' => $activationLink, 'activation_no' => $activationNo]);
        Session::flash('success', Flang::_e('active_send_email'));
        Events::run('Backend\\UserActivationResendEvent', $user);

        redirect(auth_url('activation/' . $user_id));
    }   
    // send email forgot password
    private function _forgot_send($user)
    {
        $user_id = $user['id'];
        // tạo token forgot password
        $token = strtolower(random_string(32));
        // Tạo mã kích hoạt 6 ký tự cho người dùng nhập vào
        $user_optional = @json_decode($user['optional'], true);
        if (empty($user_optional)){
            $user_optional = [];
        }
        $user_optional['token_reset_password'] = $token;
        $user_optional['token_reset_password_expires'] = time()+86400;
        $this->usersModel->updateUser($user_id, ['optional'=>json_encode($user_optional)]);

        // Construct reset link 
        $reset_link = base_url('account/forgot/' . $user_id . '/' . $token);
        // Gửi email link reset password
        $this->mailer = new Fastmail();
        $this->mailer->send($user['email'], Flang::_e('title_email_link_reset'), 'reset_password', ['username' => $user['username'], 'reset_link' => $reset_link]);

        Events::run('Backend\\UserForgotSendEvent', $user);

        Session::flash('success', Flang::_e('link_reset_password') .$user['email']);
    }   

    /**
     * Hiển thị form nhập mã kích hoạt
     */
    private function _activation_form($user_id)
    {
        $this->data('csrf_token', Session::csrf_token(600)); //token security login chi ton tai 10 phut.
        
        $this->data('title', Flang::_e('active_welcome'));

        $this->data('user_id', $user_id);
        $this->render('auth', 'Backend/Auth/activation');
    }

    private function _activation($user_id)
    {
        $this->usersModel->updateUser($user_id, [
            'status' => 'active',
            'optional' => null
        ]);

        Events::run('Backend\\UserActivationEvent', $user_id);
    
        Session::flash('success', Flang::_e('active_email_success'));
        redirect(auth_url('login'));
    }

    // update profile
    public function profile()
    {
        $user_id = Session::get('user_id');
        $user = $this->usersModel->getUserById($user_id);
        if (!$user){
            return $this->logout();
        }
        
        //Buoc validate neu co request register.
        if (HAS_POST('fullname')){
            $csrf_token = S_POST('csrf_token') ?? '';
            if (!Session::csrf_verify($csrf_token)){
                $this->data('error', Flang::_e('csrf_failed'));
                unset($_POST['username']);
            }
        }
        if (HAS_POST('fullname')){
            $input = [
                'fullname' => S_POST('fullname') ?? '',
                'phone' => S_POST('phone') ?? '',
                'telegram' => S_POST('telegram') ?? '',
                'skype' => S_POST('skype') ?? '',
                'whatsapp' => S_POST('whatsapp') ?? '',
            ];
            $rules = [
                'fullname' => [
                    'rules' => [
                        Validate::length(3, 30)
                    ],
                    'messages' => [
                        Flang::_e('fullname_length', 3, 50)
                    ]
                ],
                'phone' => [
                    'rules' => [
                        Validate::length(null, 30)
                    ],
                    'messages' => [
                        Flang::_e('phone_length', 0, 30)
                    ]
                ],
                'telegram' => [
                    'rules' => [
                        Validate::length(null, 100)
                    ],
                    'messages' => [
                        Flang::_e('telegram_length', 0, 100)
                    ]
                ],
                'skype' => [
                    'rules' => [
                        Validate::length(null, 100)
                    ],
                    'messages' => [
                        Flang::_e('skype_length', 0, 100)
                    ]
                ],
                'whatsapp' => [
                    'rules' => [
                        Validate::length(null, 30)
                    ],
                    'messages' => [
                        Flang::_e('whatsapp_length', 0, 30)
                    ]
                ]
            ];

            $validator = new Validate();
            if (!$validator->check($input, $rules)) {
                // Lấy các lỗi và hiển thị
                $errors = $validator->getErrors();
                $this->data('errors', $errors);
            }else{
                $this->data('success', Flang::_e('profile_updated'));
                $this->usersModel->updateUser($user_id, $input);
                $user = array_merge($user, $input);
            }
        }
        
        $this->data('me', $user);
        
        // // Hiển thị trang đăng nhập: Nếu ko có request login, or validate that bai
        $this->data('title', Flang::_e('profile_welcome'));
        $this->data('csrf_token', Session::csrf_token(600)); //token security login chi ton tai 10 phut.
        
        
        $this->render('auth', 'Backend/Auth/profile');
    }

    // Kiểm tra quyền truy cập (middleware)
    // public function _checkPermission($controller, $action)
    // {
    //     $permissions = Session::get('permissions');
    //     if (!$permissions) {
    //         return false;
    //     }

    //     if (isset($permissions[$controller]) && in_array($action, $permissions[$controller])) {
    //         return true;
    //     }

    //     return false;
    // }
}