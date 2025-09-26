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
        $this->usersModel = new UsersModel();
        Flang::load('Common/Auth');

        // Render::asset('css', 'css/new_style.css', ['area' => 'backend', 'location' => 'head']);
        // Render::asset('css', 'css/font-inter.css', ['area' => 'backend', 'location' => 'head']);

        Render::asset('js', 'js/jfast.1.2.3.js', ['area' => 'backend', 'location' => 'footer']);
        // Render::asset('js', 'js/feather.min.js', ['area' => 'backend', 'location' => 'footer']);
        // Render::asset('js', 'js/theme.js', ['area' => 'backend', 'location' => 'footer']);
        // Render::asset('js', 'js/script.js', ['area' => 'backend', 'location' => 'footer']);

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
                    'messages' => [Flang::_e('Username can only contain letters, numbers, @, ., and _'), Flang::_e('Username must be between %1% and %2% characters', 5, 30)]
                ],
                'password' => [
                    'rules' => [Validate::length(5, null)],
                    'messages' => [Flang::_e('Password must be at least %1% characters long', 6)]
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
        $this->data('title', Flang::_e('Welcome Back - Sign In'));
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
                Session::flash('error', Flang::_e('Account %1% is not active. Please check your email for activation link.', $input['username']));
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
            Session::flash('error', Flang::_e('Login failed for username: %1%', $input['username']) );
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
                        Flang::_e('Username can only contain letters, numbers, and _'),
                        Flang::_e('Username must be between %1% and %2% characters', 5, 40)
                    ]
                ],
                'fullname' => [
                    'rules' => [
                        Validate::length(5, 60)
                    ],
                    'messages' => [
                        Flang::_e('Full name must be between %1% and %2% characters', 5, 60)
                    ]
                ],
                'email' => [
                    'rules' => [
                        Validate::email(),
                        Validate::length(5, 150)
                    ],
                    'messages' => [
                        Flang::_e('Please enter a valid email address'),
                        Flang::_e('Email must be between %1% and %2% characters', 5, 150)
                    ]
                ],
                'phone' => [
                    'rules' => [
                        Validate::phone(),
                        Validate::length(5, 30)
                    ],
                    'messages' => [
                        Flang::_e('Please enter a valid phone number'),
                        Flang::_e('Phone number must be between %1% and %2% characters', 5, 30)
                    ]
                ],
                'password' => [
                    'rules' => [
                        Validate::length(5, 60),
                    ],
                    'messages' => [
                        Flang::_e('Password must be between %1% and %2% characters', 5, 60),
                    ]
                ],
                'password_repeat' => [
                    'rules' => [
                        Validate::equals($input['password'])
                    ],
                    'messages' => [
                        Flang::_e('Password confirmation does not match')
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
                        Flang::_e('Username %1% is already taken', $input['username'])
                    );
                    $isExists = true;
                }
                if ($this->usersModel->getUserByEmail($input['email'])) {
                    $errors['email'] = array(
                        Flang::_e('Email %1% is already registered', $input['email'])
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
        $this->data('title', Flang::_e('Create New Account'));
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
            $emailContent = Render::component('Common/Email/activation', ['username' => $input['username'], 'activation_link' => $activationLink, 'activation_no' => $activationNo]);
            
            $this->mailer = new Fastmail();
            $this->mailer->send($input['email'], Flang::_e('Account Activation'), $emailContent, ['smtpDebug' => 2]);
            
            Session::flash('success', Flang::_e('Registration successful'));
            $this->data('csrf_token', Session::csrf_token(600));

            Events::run('Backend\\UserRegisterEvent', $user_id);
         
            redirect(auth_url("activation/{$user_id}/"));

        } else {
            Session::flash('error', Flang::_e('Failed to register account'));
            redirect(auth_url('register'));
        }
    }

    public function activation($user_id = '', $activationCode = null)
    {
        // Get user information from ID
        $user = $this->usersModel->getUserById($user_id);
        if (!$user) {
            Session::flash('error', Flang::_e('Account does not exist'));
            redirect(auth_url('login'));
            return;
        }
        if ($user['status'] != 'inactive'){
            Session::flash('success', Flang::_e('Account is already active'));
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
            $this->data('error', Flang::_e('Activation code has expired'));
            return $this->_activation_form($user_id);
        } 

        // Case when user accesses via URL
        if ($activationCode) {
            $user_active_code = $user_optional['activation_code'] ?? '';
            if (!empty($user_active_code) && strtolower($user_active_code) === strtolower($activationCode)) {
                // Activate account
                return $this->_activation($user_id);
            } else {
                $this->data('error', Flang::_e('Invalid activation code'));
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
                $this->data('error', Flang::_e('Invalid activation code'));
                $this->_activation_form($user_id);
            }
        } else {
            // Display activation code input form
            $this->_activation_form($user_id);
        }
    }
    
    //Forgot Password - handles both email input and password reset
    public function forgot($user_id = '', $token = ''){
        // Case 1: User is requesting password reset (has user_id and token)
        if (!empty($user_id) && !empty($token)) {
            $user = $this->usersModel->getUserById($user_id);
            if (!$user) {
                Session::flash('error', Flang::_e('Account does not exist'));
                redirect(auth_url('forgot'));
                return;
            }
            // If token is 'code', show password reset form directly
            if ($token === 'code') {
                return $this->_forgot_password($user, 'code');
            }
            // For regular token, show password reset form with code verification
            return $this->_forgot_password($user, $token);
        }
        
        // Case 1.5: User is requesting password reset with code (has user_id but no token)
        if (!empty($user_id) && empty($token)) {
            $user = $this->usersModel->getUserById($user_id);
            if (!$user) {
                Session::flash('error', Flang::_e('Account does not exist'));
                redirect(auth_url('forgot'));
                return;
            }
            return $this->_forgot_password_with_code($user);
        }
        
        // Case 2: User is submitting email for password reset
        if(HAS_POST('email')) {
            $csrf_token = S_POST('csrf_token') ?? '';
            if (!Session::csrf_verify($csrf_token)){
                Session::flash('error', Flang::_e('csrf_failed') );
                redirect(auth_url('forgot'));
            }
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
                        Flang::_e('Please enter a valid email address'),
                        Flang::_e('Email must be between %1% and %2% characters', 5, 150)
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
                        Flang::_e('User with email %1% not found', $input['email'])
                    );
                    $this->data('errors', $errors);     
                }else {
                    $this->_forgot_send($user);
                }
            }
        }

        // Case 3: Display forgot password form (no POST data, no user_id/token)
        $this->data('csrf_token', Session::csrf_token(600));
        $this->data('title', Flang::_e('Forgot Password'));
        
        echo Render::html('Common/Auth/forgot-password', $this->data);
    }


    private function _forgot_password($user, $token) {
        $user_id = $user['id'];
        $user_optional = @json_decode($user['optional'], true);

        $error = '';
        
        // Validate token if it's not 'code'
        if ($token !== 'code') {
            $token_db = $user_optional['token_reset_password'] ?? '';
            $token_expires = $user_optional['token_reset_password_expires'] ?? 0;
            
            if($token !== $token_db) {
                $error = Flang::_e('Invalid reset token');
            }
            if($token_expires <= time()){
                $error = Flang::_e('Reset token has expired');
            }
        }
        
        // Always require code verification for password reset
        // Always show the password reset form with code input
        
        if (!empty($error)){
            $this->data('error', $error);
            $this->data('title', Flang::_e('Forgot Password'));
            $this->data('csrf_token', Session::csrf_token(600));
            echo Render::html('Common/Auth/forgot-password', $this->data);
        }else{
            // Debug: Check if form is being submitted
            error_log("=== FORGOT PASSWORD DEBUG ===");
            error_log("User ID: " . $user_id);
            error_log("Token: " . $token);
            error_log("Checking for POST password: " . (HAS_POST('password') ? 'YES' : 'NO'));
            error_log("POST data: " . print_r($_POST, true));
            error_log("=============================");
            
            // Force error log to be written
            error_log("FORCE LOG TEST - " . date('Y-m-d H:i:s'));
            
            if(HAS_POST('password')) {
                error_log("*** FORM SUBMITTED - PROCESSING PASSWORD RESET ***");
                $csrf_token = S_POST('csrf_token') ?? '';
                error_log("CSRF token: " . $csrf_token);
                if (!Session::csrf_verify($csrf_token)){
                    error_log("CSRF verification failed");
                    $this->data('error', Flang::_e('csrf_failed'));
                } else {
                    error_log("CSRF verification passed");
                    // Always require code verification for password reset
                    $input_code = S_POST('reset_code') ?? '';
                    $reset_code_db = $user_optional['reset_password_code'] ?? '';
                    $reset_code_expires = $user_optional['reset_password_code_expires'] ?? 0;
                    
                    error_log("=== CODE VERIFICATION DEBUG ===");
                    error_log("Input code: '" . $input_code . "'");
                    error_log("DB code: '" . $reset_code_db . "'");
                    error_log("Codes match: " . ($reset_code_db === $input_code ? 'YES' : 'NO'));
                    
                    // Debug: Log the codes for debugging
                    error_log("Input code: " . $input_code);
                    error_log("DB code: " . $reset_code_db);
                    error_log("Code expires: " . $reset_code_expires);
                    error_log("Current time: " . time());
                    
                    // Debug: Check conditions
                    error_log("Is code expired? " . ($reset_code_expires <= time() ? 'YES' : 'NO'));
                    error_log("Is reset_code_db empty? " . (empty($reset_code_db) ? 'YES' : 'NO'));
                    error_log("Do codes match? " . ($reset_code_db === $input_code ? 'YES' : 'NO'));
                    
                    if($reset_code_expires <= time()){
                        error_log("Code expired - showing error message");
                        $this->data('error', Flang::_e('Reset code has expired'));
                        $this->data('title', Flang::_e('Update Password'));
                        $this->data('csrf_token', Session::csrf_token(600));
                        $this->data('user_id', $user_id);
                        $this->data('token', $token);
                        echo Render::html('Common/Auth/forgot-setpassword', $this->data);
                        return;
                    }elseif (empty($reset_code_db) || $reset_code_db !== $input_code) {
                        error_log("Code mismatch - showing error message");
                        $error_message = Flang::_e('Invalid reset code. Please check your email for the correct code.');
                        error_log("Error message: " . $error_message);
                        $this->data('error', $error_message);
                        $this->data('title', Flang::_e('Update Password'));
                        $this->data('csrf_token', Session::csrf_token(600));
                        $this->data('user_id', $user_id);
                        $this->data('token', $token);
                        error_log("About to render forgot-setpassword template");
                        echo Render::html('Common/Auth/forgot-setpassword', $this->data);
                        return;
                    }
                    $input = [
                        'password' => S_POST('password'),
                    ];
                    $rules = [
                    'password' => [
                        'rules' => [
                            Validate::length(5, 60),
                        ],
                        'messages' => [
                            Flang::_e('Password must be between %1% and %2% characters', 5, 60),
                        ]
                    ]
                    ];
                    $validator = new Validate();
                    if (!$validator->check($input, $rules)) {
                        $errors = $validator->getErrors();
                        error_log("Validation errors: " . print_r($errors, true));
                        $this->data('errors', $errors);
                    }else {
                        error_log("Password validation passed");
                        $input['password'] = Security::hashPassword($input['password']);
                        if (isset($user_optional['token_reset_password'])){
                            unset($user_optional['token_reset_password']);
                        }
                        if (isset($user_optional['token_reset_password_expires'])){
                            unset($user_optional['token_reset_password_expires']);
                        }
                        // Also remove reset code after successful password reset
                        if (isset($user_optional['reset_password_code'])){
                            unset($user_optional['reset_password_code']);
                        }
                        if (isset($user_optional['reset_password_code_expires'])){
                            unset($user_optional['reset_password_code_expires']);
                        }
                        $input['optional'] = json_encode($user_optional); //remove ma reset sau khi set passs.
                        $this->usersModel->updateUser($user_id, $input);                    
                        
                        Session::flash('success', Flang::_e('Password reset successful'));
                        error_log("Password reset successful, redirecting to login");
                        redirect(auth_url('login'));
                        return;
                    }
                }
            } else {
                error_log("No POST password data received");
            }
            // Always show the password reset form with code input
            error_log("Displaying password reset form");
            $this->data('title', Flang::_e('Update Password'));
            $this->data('csrf_token', Session::csrf_token(600));
            $this->data('user_id', $user_id);
            $this->data('token', $token);
            echo Render::html('Common/Auth/forgot-setpassword', $this->data);
        }
    }   

    private function _forgot_password_with_code($user) {
        $user_id = $user['id'];
        $user_optional = @json_decode($user['optional'], true);

        $reset_code_db = $user_optional['reset_password_code'] ?? '';
        $reset_code_expires = $user_optional['reset_password_code_expires'] ?? 0;
        
        $error = '';
        if($reset_code_expires <= time()){
            $error = Flang::_e('Reset code has expired');
        }
        if (!empty($error)){
            $this->data('error', $error);
            $this->data('title', Flang::_e('Forgot Password'));
            $this->data('csrf_token', Session::csrf_token(600));
            echo Render::html('Common/Auth/forgot-password', $this->data);
        }else{
            if(HAS_POST('reset_code')) {
                $csrf_token = S_POST('csrf_token') ?? '';
                if (!Session::csrf_verify($csrf_token)){
                    $this->data('error', Flang::_e('csrf_failed'));
                }else{
                    $input_code = S_POST('reset_code');
                    if (!empty($reset_code_db) && $reset_code_db === $input_code) {
                        // Code is valid, redirect to password reset form
                        redirect(auth_url('forgot/' . $user_id . '/code'));
                        return;
                    } else {
                        $this->data('error', Flang::_e('Invalid reset code'));
                    }
                }
            }
            
            $this->data('title', Flang::_e('Enter Reset Code'));
            $this->data('csrf_token', Session::csrf_token(600));
            $this->data('user_id', $user_id);
            echo Render::html('Common/Auth/forgot-code', $this->data);
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
        $emailContent = Render::component('Common/Email/activation', ['username' => $user['username'], 'activation_link' => $activationLink, 'activation_no' => $activationNo]);
        
        $this->mailer = new Fastmail();
        $this->mailer->send($user['email'], Flang::_e('New Activation Code'), $emailContent);
        Session::flash('success', Flang::_e('Activation code sent to your email'));
        Events::run('Backend\\UserActivationResendEvent', $user);

        redirect(auth_url('activation/' . $user_id));
    }   
    
    // send email forgot password
    private function _forgot_send($user)
    {
        $user_id = $user['id'];
        // tạo token forgot password
        $token = strtolower(random_string(32));
        // Tạo mã kích hoạt 6 số cho người dùng nhập vào
        $resetCode = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT); // Tạo mã gồm 6 số
        
        $user_optional = @json_decode($user['optional'], true);
        if (empty($user_optional)){
            $user_optional = [];
        }
        $user_optional['token_reset_password'] = $token;
        $user_optional['token_reset_password_expires'] = time()+86400;
        // Thêm mã code reset password
        $user_optional['reset_password_code'] = $resetCode;
        $user_optional['reset_password_code_expires'] = time()+86400;
        $this->usersModel->updateUser($user_id, ['optional'=>json_encode($user_optional)]);

        // Construct reset link 
        $reset_link = auth_url('forgot/'.$user_id . '/' . $token) ;
        // Gửi email link reset password và code
        $emailContent = Render::component('Common/Email/reset_password', [
            'username' => $user['username'], 
            'reset_link' => $reset_link,
            'reset_code' => $resetCode,
            'user_id' => $user_id
        ]);
        
        $this->mailer = new Fastmail();
        $this->mailer->send($user['email'], Flang::_e('Password Reset Request'), $emailContent);
        // $this->mailer->send($user['email'], Flang::_e('Password Reset Request'), $emailContent, ['smtpDebug' => 2]);

        Events::run('Backend\\UserForgotSendEvent', $user);

        Session::flash('success', Flang::_e('Password reset link and code sent to your email') . ': ' .$user['email']);
    }   

    /**
     * Hiển thị form nhập mã kích hoạt
     */
    private function _activation_form($user_id)
    {
        $this->data('csrf_token', Session::csrf_token(600)); //token security login chi ton tai 10 phut.
        
        $this->data('title', Flang::_e('Account Activation'));

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
    
        Session::flash('success', Flang::_e('Account activated successfully'));
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
                        Flang::_e('Full name must be between %1% and %2% characters', 3, 50)
                    ]
                ],
                'phone' => [
                    'rules' => [
                        Validate::length(null, 30)
                    ],
                    'messages' => [
                        Flang::_e('Phone number must be between %1% and %2% characters', 0, 30)
                    ]
                ],
                'telegram' => [
                    'rules' => [
                        Validate::length(null, 100)
                    ],
                    'messages' => [
                        Flang::_e('Telegram username must be between %1% and %2% characters', 0, 100)
                    ]
                ],
                'skype' => [
                    'rules' => [
                        Validate::length(null, 100)
                    ],
                    'messages' => [
                        Flang::_e('Skype username must be between %1% and %2% characters', 0, 100)
                    ]
                ],
                'whatsapp' => [
                    'rules' => [
                        Validate::length(null, 30)
                    ],
                    'messages' => [
                        Flang::_e('WhatsApp number must be between %1% and %2% characters', 0, 30)
                    ]
                ]
            ];

            $validator = new Validate();
            if (!$validator->check($input, $rules)) {
                // Lấy các lỗi và hiển thị
                $errors = $validator->getErrors();
                $this->data('errors', $errors);
            }else{
                $this->data('success', Flang::_e('Profile updated successfully'));
                $this->usersModel->updateUser($user_id, $input);
                $user = array_merge($user, $input);
            }
        }
        
        $this->data('me', $user);
        
        // // Hiển thị trang đăng nhập: Nếu ko có request login, or validate that bai
        $this->data('title', Flang::_e('Profile Settings'));
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