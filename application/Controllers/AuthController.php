<?php
namespace App\Controllers;
use System\Core\BaseController;
use App\Models\UsersModel;
use App\Libraries\Fastlang;
use System\Libraries\Security;
use System\Libraries\Session;
use System\Libraries\Render;
use App\Libraries\Fastmail;
use System\Libraries\Validate;
use System\Libraries\Events;

class AuthController extends BaseController
{
    protected $usersModel;
    protected $mailer;

    public function __construct()
    {
        parent::__construct();
        $this->usersModel = new UsersModel();
        Fastlang::load('Common/Auth');
        load_helpers(['languages','images']);

        // Render::asset('css', 'css/new_style.css', ['area' => 'backend', 'location' => 'head']);
        // Render::asset('css', 'css/font-inter.css', ['area' => 'backend', 'location' => 'head']);

        Render::asset('js', 'js/jfast.1.2.3.js', ['area' => 'backend', 'location' => 'footer']);
        // Render::asset('js', 'js/feather.min.js', ['area' => 'backend', 'location' => 'footer']);
        // Render::asset('js', 'js/theme.js', ['area' => 'backend', 'location' => 'footer']);
        // Render::asset('js', 'js/script.js', ['area' => 'backend', 'location' => 'footer']);

    }
    /**
     * Check login status
     * @return void
     */
    public function index(){
        if (Session::has('user_id')) {
            // If already logged in, redirect to dashboard
            redirect(auth_url('profile'));
        } else {
            // If not logged in, redirect to login page
            redirect(auth_url('login'));
        }
    }
    /**
     * Display login form
     * @return void
     */
    public function login(){
        // Check if already logged in
        if (Session::has('user_id')) {
            return redirect(auth_url('profile'));
        }

        // Handle login request
        if (HAS_POST('username')){
            $csrf_token = S_POST('csrf_token') ?? '';
            if (!Session::csrf_verify($csrf_token)){
                Session::flash('error', __('csrf_failed'));
                return redirect(auth_url('login'));
            }
            
            $input = [
                'username' => trim(S_POST('username') ?? ''),
                'password' => S_POST('password') ?? '',
                'remember' => S_POST('remember') ?? ''
            ];
            
            // Convert username to lowercase
            $input['username'] = strtolower($input['username']);
            
            // Validation rules
            $rules = [
                'username' => [
                    'rules' => [Validate::alnum("@._"), Validate::length(5, 150)],
                    'messages' => [
                        __('Username can only contain letters, numbers, @, ., and _'), 
                        __('Username must be between %1% and %2% characters', 5, 150)
                    ]
                ],
                'password' => [
                    'rules' => [Validate::length(6, null)],
                    'messages' => [__('Password must be at least %1% characters long', 6)]
                ]
            ];
            
            $validator = new Validate();
            if (!$validator->check($input, $rules)) {
                $errors = $validator->getErrors();
                $strErrors = '';
                foreach ($errors as $key => $error) {
                    $strErrors .= $key . ': ' . implode('. ', $error) . '.<br />';
                }
                Session::flash('error', $strErrors);
                return redirect(auth_url('login'));
            } else {
                return $this->_login($input);
            }
        }

        // Display login page
        $this->data('title', __('Welcome Back - Sign In'));
        $this->data('csrf_token', Session::csrf_token(600));

        echo Render::html('Common/Auth/login', $this->data);
    }
    /**
     * Handle login
     * @param array $input
     * @return void
     */
    public function _login($input){
        // Find user by username or email
        if (filter_var($input['username'], FILTER_VALIDATE_EMAIL)) {
            $user = $this->usersModel->getUserByEmail($input['username']);
        } else {
            $user = $this->usersModel->getUserByUsername($input['username']);
        }
        
        // Check if user exists
        if (!$user) {
            Session::flash('error', __('Login failed for username: %1%', $input['username']));
            return redirect(auth_url('login'));
        }
        
        // Check if user is locked from login attempts
        $userOptional = _json_decode($user['optional'] ?? []);
        if (empty($userOptional)) {
            $userOptional = [];
        }
        
        $loginLockUntil = $userOptional['login_lock_until'] ?? 0;
        if ($loginLockUntil > time()) {
            $remainingMinutes = ceil(($loginLockUntil - time()) / 60);
            Session::flash('error', __('Account is temporarily locked. Please wait %1% minutes before trying again.', $remainingMinutes));
            return redirect(auth_url('login'));
        }
        
        // Check if password is correct
        if (!Security::verifyPassword($input['password'], $user['password'])) {
            // Increment failed login attempts
            $loginAttempts = $userOptional['login_attempts'] ?? 0;
            $loginAttempts++;
            
            // Check if max attempts reached (5 attempts)
            if ($loginAttempts >= 5) {
                // Lock account for 5 minutes
                $userOptional['login_lock_until'] = time() + 300; // 5 minutes
                $userOptional['login_attempts'] = 0; // Reset attempts
                $this->usersModel->updateUser($user['id'], ['optional' => json_encode($userOptional)]);
                
                Session::flash('error', __('Too many failed login attempts. Account locked for 5 minutes.'));
                return redirect(auth_url('login'));
            } else {
                // Update failed attempts
                $userOptional['login_attempts'] = $loginAttempts;
                $this->usersModel->updateUser($user['id'], ['optional' => json_encode($userOptional)]);
                
                $remainingAttempts = 5 - $loginAttempts;
                Session::flash('error', __('Login failed for username: %1%. %2% attempts remaining.', $input['username'], $remainingAttempts));
                return redirect(auth_url('login'));
            }
        }
        
        // if account is not active, set session confirm_user_id and confirm_expires and redirect to confirm page
        if ($user['status'] !== 'active') {
            Session::set('confirm_user_id', $user['id']);
            Session::set('confirm_expires', time() + 1800); // 30 minutes
            return redirect(auth_url('confirm'));
        }
        
        // Reset login attempts on successful login
        if (isset($userOptional['login_attempts']) || isset($userOptional['login_lock_until'])) {
            unset($userOptional['login_attempts']);
            unset($userOptional['login_lock_until']);
            $this->usersModel->updateUser($user['id'], ['optional' => json_encode($userOptional)]);
        }
        
        // Set login session
        $this->_set_login_session($user, $input);
        
        // Update last login time
        $this->usersModel->updateUser($user['id'], ['activity_at' => DateTime()]);
        
        // Trigger login event
            Events::run('Backend\\UserLoginEvent', $user);

        // Success message and redirect
        Session::flash('success', __('Login successful'));
        return redirect(admin_url('/'));
    }
    /**
     * Set login session and cookies
     * @param array $user
     * @param array $input
     * @return void
     */
    private function _set_login_session($user, $input = []){
        // Set session data
        Session::set('user_id', $user['id']);
        Session::set('role', $user['role']);
        Session::set('permissions', _json_decode($user['permissions'] ?? []));
        Session::regenerate(); // Prevent session fixation
        
        // Set cookies
        setcookie('cmsff_logged', $user['id'], time() + 86400, '/');

        // Create JWT token
        $config_security = config('security');
        $me_data = [
            'id' => $user['id'],
            'role' => $user['role'],
            'username' => $user['username'],
            'email' => $user['email']
        ];
        $access_token = \App\Libraries\Fasttoken::createToken($me_data, $config_security['app_secret'], $config_security['app_id']);
        
        // Set remember me cookie
        $cookie_expiry = (isset($input['remember']) && $input['remember'] === 'on') ? time() + (86400 * 365) : time() + 86400;
        setcookie('cmsff_token', $access_token, $cookie_expiry, '/');
    }
    /**
     * Logout
     * @return void
     */
    public function logout(){
        setcookie('cmsff_logged', '', time()-1, '/');
        Session::del('user_id');
        Session::del('role');
        Session::del('permissions');
        if (isset($_COOKIE['cmsff_token'])){
            setcookie('cmsff_token', '', time()-1, '/');
        }
        Events::run('Backend\\UserLogoutEvent');
        return redirect(base_url());
    }
    /**
     * Register new account
     * @return void
     */
    public function register(){
        // Check if already logged in
        if (Session::has('user_id')) {
            return redirect(admin_url('/'));
        }
       
        // Handle registration request
        if (HAS_POST('username')){
            $csrf_token = S_POST('csrf_token') ?? '';
            if (!Session::csrf_verify($csrf_token)){
                Session::flash('error', __('csrf_failed'));
                return redirect(auth_url('register'));
            }
            
            $input = [
                'username' => trim(S_POST('username') ?? ''),
                'fullname' => trim(S_POST('fullname') ?? ''),
                'email' => trim(S_POST('email') ?? ''),
                'password' => S_POST('password') ?? '',
                'password_repeat' => S_POST('password_repeat') ?? '',
                'phone' => trim(S_POST('phone') ?? ''),
                'terms' => S_POST('terms') ?? ''
            ];
            
            // Convert username to lowercase
            $input['username'] = strtolower($input['username']);
            
            // Validation rules
            $rules = [
                'username' => [
                    'rules' => [Validate::alnum('_'), Validate::length(5, 40)],
                    'messages' => [
                        __('Username can only contain letters, numbers, and _'),
                        __('Username must be between %1% and %2% characters', 5, 40)
                    ]
                ],
                'fullname' => [
                    'rules' => [Validate::name(2, 150)],
                    'messages' => [__('Full name must be between %1% and %2% characters and contain only letters, spaces, hyphens, dots, and middle dots', 2, 150)]
                ],
                'email' => [
                    'rules' => [Validate::email(), Validate::length(5, 150)],
                    'messages' => [
                        __('Please enter a valid email address'),
                        __('Email must be between %1% and %2% characters', 5, 150)
                    ]
                ],
                'phone' => [
                    'rules' => [Validate::phone(), Validate::length(5, 30)],
                    'messages' => [
                        __('Please enter a valid phone number'),
                        __('Phone number must be between %1% and %2% characters', 5, 30)
                    ]
                ],
                'password' => [
                    'rules' => [Validate::length(6, 60)],
                    'messages' => [__('Password must be between %1% and %2% characters', 6, 60)]
                ],
                'password_repeat' => [
                    'rules' => [Validate::equals($input['password'])],
                    'messages' => [__('Password confirmation does not match')]
                    ],
                'terms' => [
                    'rules' => [Validate::notEmpty()],
                    'messages' => [__('Please accept the terms and conditions')]
                    ]
            ];
            
            $validator = new Validate();
            if (!$validator->check($input, $rules)) {
                $errors = $validator->getErrors();
                $this->data('errors', $errors);
            } else {
                // Check for existing username/email
                $errors = [];
                if ($this->usersModel->getUserByUsername($input['username'])) {
                    $errors['username'] = [__('Username %1% is already taken', $input['username'])];
                }
                if ($this->usersModel->getUserByEmail($input['email'])) {
                    $errors['email'] = [__('Email %1% is already registered', $input['email'])];
                }
                
                if (empty($errors)) {
                    // Prepare user data
                    $userData = [
                        'username' => $input['username'],
                        'fullname' => $input['fullname'],
                        'email' => $input['email'],
                        'password' => Security::hashPassword($input['password']),
                        'phone' => $input['phone'],
                        'avatar' => '',
                        'role' => 'member',
                        'permissions' => json_encode(config('member', 'Roles')['permissions'] ?? []),
                        'status' => 'inactive',
                        'created_at' => DateTime(),
                        'updated_at' => DateTime()
                    ];
                    
                    return $this->_register($userData);
                } else {
                    $this->data('errors', $errors);
                }
            }
        }
        
        // Display registration page
        $this->data('title', __('Create New Account'));
        $this->data('csrf_token', Session::csrf_token(600));

        echo Render::html('Common/Auth/register', $this->data);
    }
    /**
     * Handle account registration
     * @param array $userData
     * @return void
     */
    private function _register($userData){
        // Generate activation codes
        $activationCode = str_pad(rand(10000000, 99999999), 8, '0', STR_PAD_LEFT); // 8-digit code
        $activationString = strtolower(random_string(32)); // 32-character string for URL
        
        // Prepare optional data
        $optionalData = [
            'activation_code' => $activationCode,
            'activation_string' => $activationString,
            'activation_expires' => time() + 86400, // 24 hours
            'activation_attempts' => 0, // Track failed attempts
            'activation_type' => 'registration', // Distinguish from forgot password
            'cooldown_until' => 0 // Cooldown period to prevent spam
        ];
        
        $userData['optional'] = json_encode($optionalData);
        
        // Add user to database
        $user_id = $this->usersModel->addUser($userData);

        if ($user_id) {
            // Set secure session for confirmation
            Session::set('confirm_user_id', $user_id);
            Session::set('confirm_expires', time() + 1800); // 30 minutes
            
            // Send activation email
            $activationLink = auth_url('confirmlink/' . $user_id . '/' . $activationString);
            $emailContent = Render::component('Common/Email/auth_register', [
                'username' => $userData['username'], 
                'activation_link' => $activationLink, 
                'activation_code' => $activationCode
            ]);
            
            $this->mailer = new Fastmail();
            $this->mailer->send($userData['email'], option('site_brand') . ' - ' . __('Account Registration Activation'), $emailContent);
            
            // Trigger registration event
            Events::run('Backend\\UserRegisterEvent', $user_id);
         
            // Redirect to confirm screen
            Session::flash('success', __('Registration successful'));
            redirect(auth_url("confirm"));
        } else {
            Session::flash('error', __('Failed to register account'));
            redirect(auth_url('register'));
        }
    }
    /**
     * Forgot Password - handles email input
     * @return void
     */
    public function forgot(){
        // Check if already logged in
        if (Session::has('user_id')) {
            return redirect(admin_url('/'));
        }
        
        // Handle email submission for password reset
        if (HAS_POST('email')) {
            $csrf_token = S_POST('csrf_token') ?? '';
            if (!Session::csrf_verify($csrf_token)) {
                Session::flash('error', __('csrf_failed'));
                redirect(auth_url('forgot'));
                return;
            }
            
            $input = ['email' => trim(S_POST('email'))];
            
            $rules = [
                'email' => [
                    'rules' => [Validate::email(), Validate::length(5, 150)],
                    'messages' => [
                        __('Please enter a valid email address'),
                        __('Email must be between %1% and %2% characters', 5, 150)
                    ]
                ]
            ];
            
            $validator = new Validate();
            if (!$validator->check($input, $rules)) {
                $errors = $validator->getErrors();
                $this->data('errors', $errors);     
            } else {
                $user = $this->usersModel->getUserByEmail($input['email']);
                if (!$user) {
                    $errors['email'] = [__('User with email %1% not found', $input['email'])];
                    $this->data('errors', $errors);     
                } else {
                    $this->_forgot_send($user);
                }
            }
        }

        // Display forgot password form
        $this->data('csrf_token', Session::csrf_token(600));
        $this->data('title', __('Forgot Password'));
        
        echo Render::html('Common/Auth/forgot', $this->data);
    }
    /**
     * Send forgot password email
     * @param array $user
     * @return void
     */
    private function _forgot_send($user){
        if (empty($user['id'])) {
            return;
        }
        $user_id = $user['id'];
        
        // Generate reset codes
        $resetCode = str_pad(rand(10000000, 99999999), 8, '0', STR_PAD_LEFT); // 8-digit code
        $resetToken = strtolower(random_string(32)); // 32-character token
        
        $userOptional = _json_decode($user['optional'] ?? []);
        if (empty($userOptional)) {
            $userOptional = [];
        }
        
        // Prepare optional data for forgot password
        $userOptional['activation_code'] = $resetCode;
        $userOptional['activation_string'] = $resetToken;
        $userOptional['activation_expires'] = time() + 86400; // 24 hours
        $userOptional['activation_attempts'] = 0; // Track failed attempts
        $userOptional['activation_type'] = 'forgot_password'; // Distinguish from registration
        $userOptional['cooldown_until'] = 0; // Reset cooldown
        
        $this->usersModel->updateUser($user_id, ['optional' => json_encode($userOptional)]);

        // Set secure session for confirmation
        Session::set('confirm_user_id', $user_id);
        Session::set('confirm_expires', time() + 1800); // 30 minutes

        // Send reset email
        $resetLink = auth_url('confirmlink/' . $user_id . '/' . $resetToken);
        $emailContent = Render::component('Common/Email/auth_reset_password', [
            'username' => $user['username'], 
            'reset_link' => $resetLink,
            'reset_code' => $resetCode,
            'user_id' => $user_id
        ]);
        
        $this->mailer = new Fastmail();
        $this->mailer->send($user['email'], option('site_brand') . ' - ' . __('Password Reset Request'), $emailContent);

        Events::run('Backend\\UserForgotSendEvent', $user);

        Session::flash('success', __('Password reset code sent to: %1% successfully', $user['email']) );
        redirect(auth_url("confirm"));
    }
    /**
     * Confirm screen - handles activation/Forgot password link after code validation
     * @return void
     */
    public function confirm(){
        // Check if user is already logged in
        if (Session::has('user_id')) {
            return redirect(admin_url('/'));
        }
        
        // Get user_id from secure session
        $user_id = Session::get('confirm_user_id');
        $confirm_expires = Session::get('confirm_expires');
        
        // Check if session is valid
        if (empty($user_id) || $confirm_expires < time()) {
            Session::del('confirm_user_id');
            Session::del('confirm_expires');
            Session::flash('error', __('Session expired. Please try again.'));
            return redirect(auth_url('login'));
        }
        
        // Get user information
        $user = $this->usersModel->getUserById($user_id);
        if (!$user) {
            Session::del('confirm_user_id');
            Session::del('confirm_expires');
            Session::flash('error', __('Account does not exist'));
            redirect(auth_url('login'));
            return;
        }
        $this->data('email', $user['email']);
        
        $userOptional = _json_decode($user['optional'] ?? []);
        $activationType = $userOptional['activation_type'] ?? 'registration';
        
        // For registration activation, check if account is already active or disabled
        if ($activationType === 'registration') {
            if ($user['status'] === 'active') {
                Session::flash('success', __('Account has already been activated'));
                return redirect(auth_url('login'));
            } elseif ($user['status'] === 'disabled') {
                Session::flash('error', __('Account has been disabled. Please contact support.'));
                return redirect(auth_url('login'));
            } elseif ($user['status'] !== 'inactive') {
                Session::flash('error', __('Invalid account status'));
                redirect(auth_url('login'));
            }
        }
        
        // Check if activation has expired
        $activationExpires = $userOptional['activation_expires'] ?? 0;
        if ($activationExpires < time()) {
            Session::flash('error', __('Activation code has expired'));
            $this->data('activation_type', $activationType);
            $this->data('cooldown_until', $userOptional['cooldown_until'] ?? 0);
            $this->data('title', __('Enter Confirmation Code'));
            $this->data('csrf_token', Session::csrf_token(600));
            echo Render::html('Common/Auth/confirm', $this->data);
            return;
        }
        
        // Handle code submission
        if (HAS_POST('confirmation_code')) {
                $csrf_token = S_POST('csrf_token') ?? '';
            if (!Session::csrf_verify($csrf_token)) {
                    Session::flash('error', __('csrf_failed'));
                } else {
                $inputCode = S_POST('confirmation_code');
                $storedCode = $userOptional['activation_code'] ?? '';
                $attempts = $userOptional['activation_attempts'] ?? 0;
                
                // Check if max attempts reached
                if ($attempts >= 5) {
                    // Set cooldown period (30 minutes)
                    $userOptional['cooldown_until'] = time() + 1800; // 30 minutes
                    $this->usersModel->updateUser($user_id, ['optional' => json_encode($userOptional)]);
                    
                    Session::flash('error', __('Maximum attempts reached. Please wait 30 minutes before requesting a new code.'));
                    $this->data('activation_type', $activationType);
                    $this->data('cooldown_until', $userOptional['cooldown_until']);
                    $this->data('title', __('Enter Confirmation Code'));
                        $this->data('csrf_token', Session::csrf_token(600));
                    echo Render::html('Common/Auth/confirm', $this->data);
                        return;
                    }
                
                // Verify code
                if ($inputCode === $storedCode) {
                    // Code is correct, redirect to confirmlink
                    $activationString = $userOptional['activation_string'] ?? '';
                    redirect(auth_url("confirmlink/{$user_id}/{$activationString}"));
                } else {
                    // Wrong code, increment attempts
                    $userOptional['activation_attempts'] = $attempts + 1;
                    $this->usersModel->updateUser($user_id, ['optional' => json_encode($userOptional)]);
                    
                    $remainingAttempts = 5 - ($attempts + 1);
                    Session::flash('error', __('Invalid confirmation code. %1% attempts remaining.', $remainingAttempts));
                }
            }
        }
        
        // Display confirmation form
        $this->data('activation_type', $activationType);
        $this->data('cooldown_until', $userOptional['cooldown_until'] ?? 0);
        $this->data('title', __('Enter Confirmation Code'));
        $this->data('csrf_token', Session::csrf_token(600));
        
        echo Render::html('Common/Auth/confirm', $this->data);
    }
    /**
     * Confirmlink - handles activation/Forgot password link after code validation
     * @param string $user_id
     * @param string $activationString
     * @return void
     */
    public function confirmlink($user_id = null, $activationString = ''){
        if (!$user_id || !$user = $this->usersModel->getUserById($user_id)) {
            Session::flash('error', __('Account does not exist'));
            redirect(auth_url('login'));
            return;
        }
        
        // Check account status for registration activation
        $userOptional = _json_decode($user['optional'] ?? []);
        $activationType = $userOptional['activation_type'] ?? 'registration';
        
        // For registration activation, check if account is already active or disabled
        if ($activationType === 'registration') {
            if ($user['status'] === 'active') {
                Session::flash('success', __('Account has already been activated'));
                return redirect(auth_url('login'));
            } elseif ($user['status'] === 'disabled') {
                Session::flash('error', __('Account has been disabled. Please contact support.'));
                return redirect(auth_url('login'));
            } elseif ($user['status'] !== 'inactive') {
                Session::flash('error', __('Invalid account status'));
                return redirect(auth_url('login'));
            }
        }
        $storedActivationString = $userOptional['activation_string'] ?? '';
        
        // Validate activation string
        if (empty($storedActivationString) || strtolower($storedActivationString) !== strtolower($activationString)) {
            Session::flash('error', __('Invalid activation link'));
            redirect(auth_url('login'));
            return;
        }

        // Check if activation has expired
        $activationExpires = $userOptional['activation_expires'] ?? 0;
        if ($activationExpires < time()) {
            Session::flash('error', __('Activation link has expired'));
            redirect(auth_url('login'));
            return;
        }
        
        // Process based on activation type
        if ($activationType === 'forgot_password') {
            // Set secure session for password reset
            Session::set('confirm_user_id', $user_id);
            Session::set('confirm_expires', time() + 1800); // 30 minutes
            
            // For forgot password, redirect to password reset form
            redirect(auth_url("reset-password"));
        } else {
            // For registration, activate account
            $this->usersModel->updateUser($user_id, [
                'status' => 'active',
                'optional' => null
            ]);
            
            // Send welcome email
            $welcomeContent = Render::component('Common/Email/auth_welcome', [
                'username' => $user['username']
            ]);
            
            $this->mailer = new Fastmail();
            $this->mailer->send($user['email'], option('site_brand') . ' - ' . __('Welcome to %1%', option('site_brand')), $welcomeContent);
            
            Events::run('Backend\\UserActivationEvent', $user_id);
            
            // Set login session and redirect to dashboard
            $this->_set_login_session($user);
            redirect(base_url());
            //redirect(auth_url('login'));
        }
    }
    /**
     * Resend activation/forgot password code
     * @return void
     */
    public function resend_code(){
        // Get user_id from secure session
        $user_id = Session::get('confirm_user_id');
        $confirm_expires = Session::get('confirm_expires');
        
        // Check if session is valid
        if (empty($user_id) || $confirm_expires < time()) {
            Session::del('confirm_user_id');
            Session::del('confirm_expires');
            Session::flash('error', __('Session expired. Please try again.'));
            return redirect(auth_url('login'));
        }
        
        $user = $this->usersModel->getUserById($user_id);
        if (!$user) {
            Session::del('confirm_user_id');
            Session::del('confirm_expires');
            Session::flash('error', __('Account does not exist'));
            redirect(auth_url('login'));
                        return;
        }
        
        $userOptional = _json_decode($user['optional'] ?? []);
        $activationType = $userOptional['activation_type'] ?? 'registration';
        
        // For registration activation, check if account is already active or disabled
        if ($activationType === 'registration') {
            if ($user['status'] === 'active') {
                Session::flash('success', __('Account has already been activated'));
                return redirect(auth_url('login'));
            } elseif ($user['status'] === 'disabled') {
                Session::flash('error', __('Account has been disabled. Please contact support.'));
                return redirect(auth_url('login'));
            } elseif ($user['status'] !== 'inactive') {
                Session::flash('error', __('Invalid account status'));
                return redirect(auth_url('login'));
            }
        }
        
        // Check if user is in cooldown period
        $cooldownUntil = $userOptional['cooldown_until'] ?? 0;
        if ($cooldownUntil > time()) {
            $remainingMinutes = ceil(($cooldownUntil - time()) / 60);
            Session::flash('error', __('Please wait %1% minutes before requesting a new code.', $remainingMinutes));
            return redirect(auth_url('confirm'));
        }
        
        // Generate new codes
        $activationCode = str_pad(rand(10000000, 99999999), 8, '0', STR_PAD_LEFT); // 8-digit code
        $activationString = strtolower(random_string(32));
        
        // Update optional data
        $userOptional['activation_code'] = $activationCode;
        $userOptional['activation_string'] = $activationString;
        $userOptional['activation_expires'] = time() + 86400;
        $userOptional['activation_attempts'] = 0; // Reset attempts
        $userOptional['cooldown_until'] = 0; // Reset cooldown
        $userOptional['activation_type'] = $activationType;
        
        $this->usersModel->updateUser($user_id, ['optional' => json_encode($userOptional)]);
        
        // Send new activation email based on type
        if ($activationType === 'forgot_password') {
            $resetLink = auth_url('confirmlink/' . $user_id . '/' . $activationString);
            $emailContent = Render::component('Common/Email/auth_reset_password', [
                'username' => $user['username'], 
                'reset_link' => $resetLink,
                'reset_code' => $activationCode,
                'user_id' => $user_id
            ]);
            $subject = __('New Password Reset Code');
        } else {
            $activationLink = auth_url('confirmlink/' . $user_id . '/' . $activationString);
            $emailContent = Render::component('Common/Email/auth_register', [
                'username' => $user['username'], 
                'activation_link' => $activationLink, 
                'activation_code' => $activationCode
            ]);
            $subject = __('New Register Activation Code');
        }
        
        $this->mailer = new Fastmail();
        $this->mailer->send($user['email'], option('site_brand') . ' - ' . $subject, $emailContent);
        
        Session::flash('success', __('Confirmation code sent to your email'));
        redirect(auth_url("confirm"));
    }
    /**
     * Login with Google
     * @return void
     */
    public function login_google(){
        
        $option_google = option('google');
        $option_google = array_column($option_google, 'google_value', 'google_key');
        $client_id = $option_google['GOOGLE_CLIENT_ID'] ?? '';
        $client_secret = $option_google['GOOGLE_CLIENT_SECRET'] ?? '';
        $client_url = $option_google['GOOGLE_REDIRECT_URL'] ?? '';

        $client = new \Google_Client();
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
            $oauth2 = new \Google_Service_Oauth2($client);
            $userInfo = $oauth2->userinfo->get();
            $email_user = $userInfo->email ?? '';
            $fullname = $userInfo->name ?? ''; 
            $user = $this->usersModel->getUserByEmail($email_user);

            if ($user) {
                // Set login session
                $this->_set_login_session($user);
                
                // Update last login time
                $this->usersModel->updateUser($user['id'], ['activity_at' => DateTime()]);

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
    /**
     * Reset Password - handles password reset after code validation
     * @return void
     */
    public function reset_password(){
        // Check if user is already logged in
        if (Session::has('user_id')) {
            return redirect(admin_url('/'));
        }
        
        // Get user_id from secure session
        $user_id = Session::get('confirm_user_id');
        $confirm_expires = Session::get('confirm_expires');
        
        // Check if session is valid
        if (empty($user_id) || $confirm_expires < time()) {
            Session::del('confirm_user_id');
            Session::del('confirm_expires');
            Session::flash('error', __('Session expired. Please try again.'));
            return redirect(auth_url('login'));
        }
        
        $user = $this->usersModel->getUserById($user_id);
        if (!$user) {
            Session::del('confirm_user_id');
            Session::del('confirm_expires');
            Session::flash('error', __('Account does not exist'));
            redirect(auth_url('login'));
            return;
        }
        
        $userOptional = _json_decode($user['optional'] ?? []);
        $activationType = $userOptional['activation_type'] ?? '';
        
        // Verify this is a forgot password request
        if ($activationType !== 'forgot_password') {
            Session::del('confirm_user_id');
            Session::del('confirm_expires');
            Session::flash('error', __('Invalid reset request'));
            redirect(auth_url('login'));
            return;
        }
        
        // Handle password reset form submission
        if (HAS_POST('password')) {
            $csrf_token = S_POST('csrf_token') ?? '';
            if (!Session::csrf_verify($csrf_token)) {
                Session::flash('error', __('csrf_failed'));
            } else {
                $input = [
                    'password' => S_POST('password'),
                    'password_confirm' => S_POST('password_confirm')
                ];
                
                $rules = [
                    'password' => [
                        'rules' => [Validate::length(6, 60)],
                        'messages' => [__('Password must be between %1% and %2% characters', 6, 60)]
                    ],
                    'password_confirm' => [
                        'rules' => [Validate::equals($input['password'])],
                        'messages' => [__('Password confirmation does not match')]
                    ]
                ];
                
                $validator = new Validate();
                if (!$validator->check($input, $rules)) {
                    $errors = $validator->getErrors();
                    $this->data('errors', $errors);
                } else {
                    $this->usersModel->updateUser($user_id, [
                        'password' => Security::hashPassword($input['password']),
                        'optional' => null
                    ]);

                    // Clear confirmation session
                    Session::del('confirm_user_id');
                    Session::del('confirm_expires');
    
                    Events::run('Backend\\UserPasswordResetEvent', $user_id);
                    
                    Session::flash('success', __('Password reset successful'));
                    return redirect(auth_url('login'));
                }
            }
        }
        
        // Display password reset form
        $this->data('title', __('Reset Your Password'));
        $this->data('csrf_token', Session::csrf_token(600));
        
        echo Render::html('Common/Auth/reset_password', $this->data);
    }   

    // update profile - main function for displaying profile page
    public function profile(){
        $user_id = Session::get('user_id');
        $user = $this->usersModel->getUserById($user_id);
        if (!$user){
            return $this->logout();
        }
        
        // Handle profile update request
        if (HAS_POST('csrf_token')){
            $csrf_token = S_POST('csrf_token') ?? '';
            if (!Session::csrf_verify($csrf_token)){
                Session::flash('error', __('csrf_failed'));
                return redirect(auth_url('profile'));
            }
            
            // Get page type to determine which handler to use
            $page = S_POST('page') ?? '';
            
            switch($page) {
                case 'personal_info':
                    $this->_handle_personal_info($user_id, $user);
                    break;
                case 'social_media':
                    $this->_handle_social_media($user_id, $user);
                    break;
                case 'detailed_info':
                    $this->_handle_detailed_info($user_id, $user);
                    break;
                default:
                    Session::flash('error', __('Invalid request'));
                    return redirect(auth_url('profile'));
            }
            $user = $this->usersModel->getUserById($user_id);
        }
        
        // Prepare data for display
        $this->_prepare_profile_data($user);
        
        $this->data('title', __('Profile Settings'));
        $this->data('csrf_token', Session::csrf_token(600));
        
        echo Render::html('Common/Auth/profile', $this->data);
    }
    
    /**
     * Handle personal information form submission
     * @param int $user_id
     * @param array $user
     * @return void
     */
    private function _handle_personal_info($user_id, $user) {
        // Get personal information data
            $input = [
            'username' => trim(S_POST('username') ?? ''),
            'fullname' => trim(S_POST('fullname') ?? ''),
            'birthday' => S_POST('birthday') ?? '',
            'gender' => S_POST('gender') ?? '',
            'phone' => trim(S_POST('phone') ?? ''),
            'country' => S_POST('country') ?? '',
            'display' => S_POST('display') ? 1 : 0,
            'about_me' => trim(S_POST('about_me') ?? ''),
            'address1' => trim(S_POST('address1') ?? ''),
            'address2' => trim(S_POST('address2') ?? ''),
            'city' => trim(S_POST('city') ?? ''),
            'state' => trim(S_POST('state') ?? ''),
            'zipcode' => trim(S_POST('zipcode') ?? '')
        ];
        
        // Validation rules for personal information
            $rules = [
            'username' => [
                'rules' => [Validate::length(3, 40), Validate::alnum('_')],
                'messages' => [
                    __('Username must be between %1% and %2% characters', 3, 40),
                    __('Username can only contain letters, numbers, and underscores')
                ]
            ],
                'fullname' => [
                'rules' => [Validate::name(2, 150)],
                'messages' => [__('Full name must be between %1% and %2% characters and contain only letters, spaces, hyphens, dots, and middle dots', 2, 150)]
            ],
            'birthday' => [
                'rules' => [Validate::optional(Validate::date('Y-m-d'))],
                'messages' => [__('Birthday must be a valid date in YYYY-MM-DD format')]
            ],
            'gender' => [
                'rules' => [Validate::optional(Validate::in(['male', 'female', 'other']))],
                'messages' => [__('Gender must be male, female, or other')]
            ],
            'phone' => [
                'rules' => [Validate::optional(Validate::phone()), Validate::optional(Validate::length(1, 30))],
                    'messages' => [
                    __('Please enter a valid phone number'),
                    __('Phone number must be between %1% and %2% characters', 1, 30)
                ]
            ],
            'country' => [
                'rules' => [Validate::optional(Validate::length(2, 2)), Validate::optional(Validate::alpha())],
                    'messages' => [
                    __('Country code must be exactly 2 characters'),
                    __('Country code can only contain letters')
                ]
            ],
            'about_me' => [
                'rules' => [Validate::optional(Validate::length(null, 1000))],
                'messages' => [__('Personal description must be less than %1% characters', 1000)]
            ],
            'address1' => [
                'rules' => [Validate::optional(Validate::address(3, 200))],
                'messages' => [__('Address line 1 must be between %1% and %2% characters and contain only valid address characters', 3, 200)]
            ],
            'address2' => [
                'rules' => [Validate::optional(Validate::address(3, 200))],
                'messages' => [__('Address line 2 must be between %1% and %2% characters and contain only valid address characters', 3, 200)]
            ],
            'city' => [
                'rules' => [Validate::optional(Validate::name(2, 100))],
                'messages' => [__('City must be between %1% and %2% characters and contain only letters, spaces, hyphens, dots, and middle dots', 2, 100)]
            ],
            'state' => [
                'rules' => [Validate::optional(Validate::name(2, 100))],
                'messages' => [__('State must be between %1% and %2% characters and contain only letters, spaces, hyphens, dots, and middle dots', 2, 100)]
            ],
            'zipcode' => [
                'rules' => [Validate::optional(Validate::alnum('-')), Validate::optional(Validate::length(3, 20))],
                    'messages' => [
                    __('ZIP code can only contain letters, numbers, and hyphens'),
                    __('ZIP code must be between %1% and %2% characters', 3, 20)
                ]
            ]
        ];

        // Check username uniqueness if changed
        if ($input['username'] !== $user['username']) {
            $existingUser = $this->usersModel->where('username', $input['username'])->first();
            if ($existingUser) {
                $this->data('errors', ['username' => [__('Username already exists')]]);
                Session::flash('activetab', 'personal-info');
                return;
            }
        }

        $validator = new Validate();
        if (!$validator->check($input, $rules)) {
            $errors = $validator->getErrors();
            $this->data('errors', $errors);
            Session::flash('activetab', 'personal-info');
        } else {
            // Process and save personal information
            $this->_process_profile_data($user_id, $input, 'personal_info');
            Session::flash('success', __('Personal information updated successfully'));
            Session::flash('activetab', 'personal-info');
        }
    }
    
    /**
     * Handle social media form submission
     * @param int $user_id
     * @param array $user
     * @return void
     */
    private function _handle_social_media($user_id, $user) {
        // Get social media data
        $input = [
            'facebook' => trim(S_POST('facebook') ?? ''),
            'linkedin' => trim(S_POST('linkedin') ?? ''),
            'telegram' => trim(S_POST('telegram') ?? ''),
            'whatsapp' => trim(S_POST('whatsapp') ?? ''),
            'custom_social_name' => S_POST('custom_social_name') ?? [],
            'custom_social_value' => S_POST('custom_social_value') ?? []
        ];
        
        // Validation rules for social media
        $rules = [
            'facebook' => [
                'rules' => [Validate::optional(Validate::url()), Validate::optional(Validate::length(5, 200))],
                'messages' => [
                    __('Facebook must be a valid URL'),
                    __('Facebook URL must be between %1% and %2% characters', 5, 200)
                ]
            ],
            'linkedin' => [
                'rules' => [Validate::optional(Validate::url()), Validate::optional(Validate::length(5, 200))],
                'messages' => [
                    __('LinkedIn must be a valid URL'),
                    __('LinkedIn URL must be between %1% and %2% characters', 5, 200)
                ]
            ],
            'telegram' => [
                'rules' => [Validate::optional(Validate::alnum('@_')), Validate::optional(Validate::length(3, 100))],
                    'messages' => [
                    __('Telegram username can only contain letters, numbers, @, and _'),
                    __('Telegram username must be between %1% and %2% characters', 3, 100)
                    ]
                ],
                'whatsapp' => [
                'rules' => [Validate::optional(Validate::phone()), Validate::optional(Validate::length(5, 30))],
                    'messages' => [
                    __('WhatsApp must be a valid phone number'),
                    __('WhatsApp number must be between %1% and %2% characters', 5, 30)
                ]
            ]
        ];

        // Validate custom social media
        $customSocialErrors = $this->_validate_custom_social_media($input);
        if (!empty($customSocialErrors)) {
            $this->data('errors', $customSocialErrors);
            Session::flash('activetab', 'social-media');
            return;
        }

            $validator = new Validate();
            if (!$validator->check($input, $rules)) {
                $errors = $validator->getErrors();
                $this->data('errors', $errors);
            Session::flash('activetab', 'social-media');
        } else {
            // Process and save social media
            $this->_process_profile_data($user_id, $input, 'social_media');
            Session::flash('success', __('Social media updated successfully'));
            Session::flash('activetab', 'social-media');
        }
    }
    
    /**
     * Handle detailed information form submission
     * @param int $user_id
     * @param array $user
     * @return void
     */
    private function _handle_detailed_info($user_id, $user) {
        // Get detailed information data
        $input = [
            'work_experiences' => S_POST('work_experiences') ?? [],
            'educations' => S_POST('educations') ?? [],
            'skills' => S_POST('skills') ?? [],
            'languages' => S_POST('languages') ?? [],
            'hobbies' => S_POST('hobbies') ?? [],
            'certifications' => S_POST('certifications') ?? []
        ];
        
        // Validate detailed information
        $detailedInfoErrors = $this->_validate_detailed_information($input);
        if (!empty($detailedInfoErrors)) {
            $this->data('errors', $detailedInfoErrors);
            Session::flash('activetab', 'detailed-info');
        } else {
            // Process and save detailed information
            $this->_process_profile_data($user_id, $input, 'detailed_info');
            Session::flash('success', __('Detailed information updated successfully'));
            Session::flash('activetab', 'detailed-info');
        }
    }
    
    /**
     * Process profile data and save to database
     * @param int $user_id
     * @param array $input
     * @param string $page_type
     * @return void
     */
    private function _process_profile_data($user_id, $input, $page_type = '') {
        // Get current user data
        $currentUser = $this->usersModel->getUserById($user_id);
        $userData = ['updated_at' => DateTime()];
        
        switch($page_type) {
            case 'personal_info':
                // Update basic user fields
                $userData = array_merge($userData, [
                    'username' => $input['username'],
                    'fullname' => $input['fullname'],
                    'birthday' => $input['birthday'],
                    'gender' => $input['gender'],
                    'phone' => $input['phone'],
                    'country' => $input['country'],
                    'display' => $input['display']
                ]);
                
                // Update address
                $addressData = [
                    'address1' => $input['address1'],
                    'address2' => $input['address2'],
                    'city' => $input['city'],
                    'state' => $input['state'],
                    'zipcode' => $input['zipcode']
                ];
                $userData['address'] = json_encode($addressData);
                
                // Update about_me in personal data
                $existingPersonal = _json_decode($currentUser['personal'] ?? []);
                $personalData = array_merge($existingPersonal, [
                    'about_me' => $input['about_me']
                ]);
                $userData['personal'] = json_encode($personalData);
                break;
                
            case 'social_media':
                // Update social media in personal data
                $existingPersonal = _json_decode($currentUser['personal'] ?? []);
                $socials = [
                    'facebook' => $input['facebook'],
                    'linkedin' => $input['linkedin'],
                    'telegram' => $input['telegram'],
                    'whatsapp' => $input['whatsapp']
                ];
                
                // Process custom social media - add directly to socials array
                if (!empty($input['custom_social_name']) && !empty($input['custom_social_value'])) {
                    foreach ($input['custom_social_name'] as $index => $name) {
                        if (!empty($name) && !empty($input['custom_social_value'][$index])) {
                            $key = strtolower(trim($name));
                            $value = trim($input['custom_social_value'][$index]);
                            $socials[$key] = $value;
                        }
                    }
                }
                
                $personalData = array_merge($existingPersonal, [
                    'socials' => $socials
                ]);
                $userData['personal'] = json_encode($personalData);
                break;
                
            case 'detailed_info':
                // Update detailed information in personal data
                $existingPersonal = _json_decode($currentUser['personal'] ?? []);
                $detailedData = [
                    'work_experiences' => $input['work_experiences'],
                    'educations' => $input['educations'],
                    'skills' => _json_decode($input['skills']),
                    'languages' => $input['languages'],
                    'hobbies' => _json_decode($input['hobbies']),
                    'certifications' => $input['certifications']
                ];
                
                $personalData = array_merge($existingPersonal, $detailedData);
                $userData['personal'] = json_encode($personalData);
                break;
        }
        
        // Update user in database
        $this->usersModel->updateUser($user_id, $userData);
    }
    
    
    /**
     * Prepare profile data for display
     * @param array $user
     * @return void
     */
    private function _prepare_profile_data($user) {
        // Parse personal data
        // Handle personal data - could be array or JSON string
        $personal = _json_decode($user['personal'] ?? []);
        
        // Parse address data - could be array or JSON string
        $address = _json_decode($user['address'] ?? []);
        
        // Prepare detailed information data
        $me_info = [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'fullname' => $user['fullname'],
            'birthday' => $user['birthday'],
            'gender' => $user['gender'],
            'phone' => $user['phone'],
            'country' => $user['country'],
            'display' => $user['display'],
            'about_me' => $personal['about_me'] ?? '',
            'work_experiences' => $personal['work_experiences'] ?? [],
            'educations' => $personal['educations'] ?? [],
            'skills' => $personal['skills'] ?? [],
            'languages' => $personal['languages'] ?? [],
            'hobbies' => $personal['hobbies'] ?? [],
            'certifications' => $personal['certifications'] ?? [],
            'address' => $address,
            'socials' => $personal['socials'] ?? []
        ];
        
        $this->data('me_info', $me_info);
    }
    
    /**
     * Validate custom social media data
     * @param array $input
     * @return array
     */
    private function _validate_custom_social_media($input) {
        $errors = [];
        
        if (!empty($input['custom_social_name']) && !empty($input['custom_social_value'])) {
            foreach ($input['custom_social_name'] as $index => $name) {
                $value = $input['custom_social_value'][$index] ?? '';
                
                // Validate social media name
                if (!empty($name)) {
                    if (!preg_match('/^[a-zA-Z0-9\s\-_]+$/', $name)) {
                        $errors["custom_social_name_{$index}"] = [__('Social platform name can only contain letters, numbers, spaces, hyphens, and underscores')];
                    }
                    if (strlen($name) > 50) {
                        $errors["custom_social_name_{$index}"] = [__('Social platform name must be less than %1% characters', 50)];
                    }
                }
                
                // Validate social media value
                if (!empty($value)) {
                    if (strlen($value) > 200) {
                        $errors["custom_social_value_{$index}"] = [__('Social media value must be less than %1% characters', 200)];
                    }
                    // Check for XSS patterns
                    if (preg_match('/<script|javascript:|on\w+\s*=/i', $value)) {
                        $errors["custom_social_value_{$index}"] = [__('Invalid characters detected in social media value')];
                    }
                }
            }
        }
        
        return $errors;
    }
    
    /**
     * Validate detailed information data
     * @param array $input
     * @return array
     */
    private function _validate_detailed_information($input) {
        $errors = [];
        $validator = new Validate();
        
        // Prepare all data and rules for batch validation
        $validationData = [];
        $validationRules = [];
        
        // Validate work experiences
        if (!empty($input['work_experiences'])) {
            foreach ($input['work_experiences'] as $index => $work) {
                if (!empty($work['company'])) {
                    $validationData["work_company_{$index}"] = $work['company'];
                    $validationRules["work_company_{$index}"] = [
                        'rules' => [Validate::name(2, 100)],
                        'messages' => [__('Company name must be between %1% and %2% characters and contain only letters, spaces, hyphens, dots, and middle dots', 2, 100)]
                    ];
                }
                
                if (!empty($work['position'])) {
                    $validationData["work_position_{$index}"] = $work['position'];
                    $validationRules["work_position_{$index}"] = [
                        'rules' => [Validate::name(2, 100)],
                        'messages' => [__('Position must be between %1% and %2% characters and contain only letters, spaces, hyphens, dots, and middle dots', 2, 100)]
                    ];
                }
                
                // Manual validation for description (XSS check)
                if (!empty($work['description'])) {
                    if (strlen($work['description']) > 1000) {
                        $errors["work_description_{$index}"] = [__('Work description must be less than %1% characters', 1000)];
                    }
                    if (preg_match('/<script|javascript:|on\w+\s*=/i', $work['description'])) {
                        $errors["work_description_{$index}"] = [__('Work description contains invalid characters')];
                    }
                }
            }
        }
        
        // Validate educations
        if (!empty($input['educations'])) {
            foreach ($input['educations'] as $index => $edu) {
                if (!empty($edu['institution'])) {
                    $validationData["edu_institution_{$index}"] = $edu['institution'];
                    $validationRules["edu_institution_{$index}"] = [
                        'rules' => [Validate::name(2, 200)],
                        'messages' => [__('Institution name must be between %1% and %2% characters and contain only letters, spaces, hyphens, dots, and middle dots', 2, 200)]
                    ];
                }
                
                if (!empty($edu['degree'])) {
                    $validationData["edu_degree_{$index}"] = $edu['degree'];
                    $validationRules["edu_degree_{$index}"] = [
                        'rules' => [Validate::name(2, 100)],
                        'messages' => [__('Degree must be between %1% and %2% characters and contain only letters, spaces, hyphens, dots, and middle dots', 2, 100)]
                    ];
                }
            }
        }
        
        // Validate languages
        if (!empty($input['languages'])) {
            foreach ($input['languages'] as $index => $lang) {
                if (!empty($lang['language'])) {
                    $validationData["lang_language_{$index}"] = $lang['language'];
                    $validationRules["lang_language_{$index}"] = [
                        'rules' => [Validate::name(2, 50)],
                        'messages' => [__('Language name must be between %1% and %2% characters and contain only letters, spaces, hyphens, dots, and middle dots', 2, 50)]
                    ];
                }
                
                // Manual validation for proficiency
                if (!empty($lang['proficiency'])) {
                    if (!in_array($lang['proficiency'], ['beginner', 'intermediate', 'advanced', 'native'])) {
                        $errors["lang_proficiency_{$index}"] = [__('Invalid proficiency level')];
                    }
                }
            }
        }
        
        // Validate certifications
        if (!empty($input['certifications'])) {
            foreach ($input['certifications'] as $index => $cert) {
                if (!empty($cert['name'])) {
                    $validationData["cert_name_{$index}"] = $cert['name'];
                    $validationRules["cert_name_{$index}"] = [
                        'rules' => [Validate::name(2, 200)],
                        'messages' => [__('Certification name must be between %1% and %2% characters and contain only letters, spaces, hyphens, dots, and middle dots', 2, 200)]
                    ];
                }
                
                if (!empty($cert['issuer'])) {
                    $validationData["cert_issuer_{$index}"] = $cert['issuer'];
                    $validationRules["cert_issuer_{$index}"] = [
                        'rules' => [Validate::name(2, 200)],
                        'messages' => [__('Issuing organization must be between %1% and %2% characters and contain only letters, spaces, hyphens, dots, and middle dots', 2, 200)]
                    ];
                }
            }
        }
        
        // Validate skills
        if (!empty($input['skills']) && is_array($input['skills'])) {
            foreach ($input['skills'] as $index => $skill) {
                if (!empty($skill)) {
                    // XSS check for skills
                    if (preg_match('/<script|javascript:|on\w+\s*=/i', $skill)) {
                        $errors["skill_{$index}"] = [__('Skill contains invalid characters')];
                    }
                    if (strlen($skill) > 100) {
                        $errors["skill_{$index}"] = [__('Skill must be less than %1% characters', 100)];
                    }
                }
            }
        }
        
        // Validate hobbies
        if (!empty($input['hobbies']) && is_array($input['hobbies'])) {
            foreach ($input['hobbies'] as $index => $hobby) {
                if (!empty($hobby)) {
                    // XSS check for hobbies
                    if (preg_match('/<script|javascript:|on\w+\s*=/i', $hobby)) {
                        $errors["hobby_{$index}"] = [__('Hobby contains invalid characters')];
                    }
                    if (strlen($hobby) > 100) {
                        $errors["hobby_{$index}"] = [__('Hobby must be less than %1% characters', 100)];
                    }
                }
            }
        }
        
        // Batch validate all fields at once
        if (!empty($validationData) && !empty($validationRules)) {
            if (!$validator->check($validationData, $validationRules)) {
                $validationErrors = $validator->getErrors();
                $errors = array_merge($errors, $validationErrors);
            }
        }
        
        return $errors;
    }

    /**
     * Change password
     * @return void
     */
    public function change_password(){
        $user_id = Session::get('user_id');
        $user = $this->usersModel->getUserById($user_id);
        if (!$user){
            return $this->logout();
        }
        
        // Handle password change request
        if (HAS_POST('current_password')){
            $csrf_token = S_POST('csrf_token') ?? '';
            if (!Session::csrf_verify($csrf_token)){
                Session::flash('error', __('csrf_failed'));
                return redirect(auth_url('profile'));
            }
            
            $input = [
                'current_password' => S_POST('current_password') ?? '',
                'new_password' => S_POST('new_password') ?? '',
                'confirm_password' => S_POST('confirm_password') ?? ''
            ];
            
            $rules = [
                'current_password' => [
                    'rules' => [Validate::length(6, null)],
                    'messages' => [__('Current password is required')]
                ],
                'new_password' => [
                    'rules' => [Validate::length(6, 60)],
                    'messages' => [__('New password must be between %1% and %2% characters', 6, 60)]
                ],
                'confirm_password' => [
                    'rules' => [Validate::equals($input['new_password'])],
                    'messages' => [__('Password confirmation does not match')]
                ]
            ];
            
            $validator = new Validate();
            if (!$validator->check($input, $rules)) {
                $errors = $validator->getErrors();
                $this->data('errors', $errors);
            } else {
                // Verify current password
                if (!Security::verifyPassword($input['current_password'], $user['password'])) {
                    Session::flash('error', __('Current password is incorrect'));
                    return redirect(auth_url('profile'));
                }
                
                // Update password
                $this->usersModel->updateUser($user_id, [
                    'password' => Security::hashPassword($input['new_password'])
                ]);
                
                Session::flash('success', __('Password changed successfully'));
                return redirect(auth_url('profile'));
            }
        }
        
        // Redirect to profile if not POST request
        return redirect(auth_url('profile'));
    }
}