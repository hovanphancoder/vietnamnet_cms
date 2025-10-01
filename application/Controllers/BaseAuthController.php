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

/**
 * Base Authentication Controller
 * 
 * This class contains all the core authentication logic that can be shared
 * between Frontend and API controllers. It provides common methods for
 * login, registration, password management, and profile handling.
 * 
 * @package App\Controllers
 * @author Your Name
 * @version 1.0.0
 */
abstract class BaseAuthController extends BaseController
{
    /**
     * Users model instance
     * @var UsersModel
     */
    protected $usersModel;
    
    /**
     * Mailer instance
     * @var Fastmail
     */
    protected $mailer;

    /**
     * Constructor - Initialize common components
     */
    public function __construct()
    {
        parent::__construct();
        $this->usersModel = new UsersModel();
        Fastlang::load('Common/Auth');
        load_helpers(['languages','images']);
    }

    /**
     * Login - shared flow
     * Child classes decide how to render/return the response via hooks.
     * @return mixed
     */
    public function login()
    {
        // Already logged in
        if (Session::has('user_id')) {
            return $this->handleAlreadyLoggedIn();
        }

        // Handle submit
        if (HAS_POST('csrf_token')) {
            // CSRF verify
            $csrf_token = S_POST('csrf_token') ?? '';
            if (!Session::csrf_verify($csrf_token)) {
                return $this->handleCsrfFailed();
            }

            $input = [
                'username' => trim(S_POST('username') ?? ''),
                'password' => S_POST('password') ?? '',
                'remember' => S_POST('remember') ?? ''
            ];

            // Normalize
            $input['username'] = strtolower($input['username']);

            // Validate
            $validationErrors = $this->_validateLogin($input);
            if (!empty($validationErrors)) {
                return $this->handleLoginErrors($validationErrors);
            }

            // Attempt login
            $result = $this->_login($input);
            if (is_array($result) && !empty($result)) {
                // _login returned errors
                return $this->handleLoginErrors($result);
            }
            // If _login() already produced a response (redirect/json), just return it
            return $result;
        }

        // Initial display
        return $this->displayLoginForm();
    }

    /**
     * Register - shared flow
     * Child classes decide how to render/return the response via hooks.
     * @return mixed
     */
    public function register()
    {
        // Check if already logged in
        if (Session::has('user_id')) {
            return $this->handleAlreadyLoggedIn();
        }

        // Handle registration request
        if (HAS_POST('csrf_token')) {
            if (!HAS_POST('username') || !HAS_POST('fullname') || !HAS_POST('email') || !HAS_POST('password') || !HAS_POST('password_repeat') || !HAS_POST('terms')) {
                return $this->handleMissingRegistrationFields();
            }
            $errors = [];
            $csrf_token = S_POST('csrf_token') ?? '';
            if (!Session::csrf_verify($csrf_token)) {
                $errors['csrf_token'] = [__('csrf_failed')];
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
            
            // Validate input
            $validationErrors = $this->_validateRegistration($input);
            if (!empty($validationErrors)) {
                $errors = array_merge($errors, $validationErrors);
            }
            
            if (empty($errors)) {
                $userData = [
                    'username' => $input['username'],
                    'email' => $input['email'],
                    'password' => Security::hashPassword($input['password']),
                    'fullname' => $input['fullname'],
                    'avatar' => '',
                    'phone' => $input['phone'],
                    'coin' => 0,
                    'role' => 'member',
                    'permissions' => json_encode(config('member', 'Roles')['permissions'] ?? []),
                    'country' => lang_country(),
                    'address' => '',
                    'package_name' => 'membership',
                    'online' => 0,
                    'display' => 1,
                    'status' => 'inactive',
                    'password_at' => _DateTime(),
                    'activity_at' => _DateTime(),
                    'updated_at' => _DateTime(),
                    'created_at' => _DateTime()
                ];
                
                $registerErrors = $this->_register($userData);
                if (!empty($registerErrors)) {
                    return $this->handleRegistrationErrors($registerErrors);
                }
            } else {
                return $this->handleRegistrationErrors($errors);
            }
        }
        
        // Display registration form
        return $this->displayRegistrationForm();
    }

    /**
     * Forgot Password - shared flow
     * Child classes decide how to render/return the response via hooks.
     * @return mixed
     */
    public function forgot()
    {
        // Check if already logged in
        if (Session::has('user_id')) {
            return $this->handleAlreadyLoggedIn();
        }
        
        // Handle email submission for password reset
        if (HAS_POST('csrf_token')) {
            if (!HAS_POST('email')) {
                return $this->handleMissingEmailField();
            }
            $errors = [];
            $csrf_token = S_POST('csrf_token') ?? '';
            if (!Session::csrf_verify($csrf_token)) {
                $errors['csrf_token'] = [__('csrf_failed')];
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
                $errors = array_merge($errors, $validator->getErrors());
            }
            if (empty($errors)) {
                $user = $this->usersModel->getUserByEmail($input['email']);
                if (!$user) {
                    $errors['email'] = [__('User with email %1% not found', $input['email'])];
                }
            }
            if (empty($errors)) {
                $forgotErrors = $this->_forgot_send($user);
                if (!empty($forgotErrors)) {
                    return $this->handleForgotPasswordErrors($forgotErrors);
                }
            } else {
                return $this->handleForgotPasswordErrors($errors);
            }
        }
        // Display forgot password form
        return $this->displayForgotPasswordForm();
    }

    /**
     * Display login (shared flow)
     *
     * Child classes decide how to render/return the response via hooks.
     * @param array $input Login input data (username, password, remember)
     * @return array|void Returns errors array if validation fails, void if successful
     */
    protected function _login($input)
    {
        // Find user by username or email
        if (filter_var($input['username'], FILTER_VALIDATE_EMAIL)) {
            $user = $this->usersModel->getUserByEmail($input['username']);
        } else {
            $user = $this->usersModel->getUserByUsername($input['username']);
        }
        
        // Check if user exists
        if (!$user) {
            $errors['username'] = [__('Login failed for username: %1%', $input['username'])];
            return $errors;
        }
        
        // Check if user is locked from login attempts
        $userOptional = _json_decode($user['optional'] ?? []);
        if (empty($userOptional)) {
            $userOptional = [];
        }
        
        $loginLockUntil = $userOptional['login_lock_until'] ?? 0;
        if ($loginLockUntil > time()) {
            $remainingMinutes = ceil(($loginLockUntil - time()) / 60);
            $errors['login_lock_until'] = [__('Account is temporarily locked. Please wait %1% minutes before trying again.', $remainingMinutes)];
            return $errors;
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
                
                $errors['login_attempts'] = [__('Too many failed login attempts. Account locked for 5 minutes.')];
                return $errors;
            } else {
                // Update failed attempts
                $userOptional['login_attempts'] = $loginAttempts;
                $this->usersModel->updateUser($user['id'], ['optional' => json_encode($userOptional)]);
                
                $remainingAttempts = 5 - $loginAttempts;
                $errors['login_attempts'] = [__('Login failed for username: %1%. %2% attempts remaining.', $input['username'], $remainingAttempts)];
                return $errors;
            }
        }
        
        // if account is not active, set session confirm_user_id and confirm_expires and redirect to confirm page
        if ($user['status'] !== 'active') {
            Session::set('confirm_user_id', $user['id']);
            Session::set('confirm_expires', time() + 1800); // 30 minutes
            return $this->handleInactiveAccount($user);
        }
        
        // Reset login attempts on successful login
        if (isset($userOptional['login_attempts']) || isset($userOptional['login_lock_until'])) {
            unset($userOptional['login_attempts']);
            unset($userOptional['login_lock_until']);
            $this->usersModel->updateUser($user['id'], ['optional' => json_encode($userOptional)]);
        }
        
        // Set login session
        $access_token = $this->_set_login_session($user, $input);
        if (!empty($access_token)) {
            $user['access_token'] = $access_token;
        }
        
        // Update last login time
        $this->usersModel->updateUser($user['id'], ['activity_at' => _DateTime()]);
        
        // Trigger login event success
        Events::run('Backend\\UserLoginEvent', $user);
        
        return $this->handleSuccessfulLogin($user);
    }

    /**
     * Set login session and cookies
     * 
     * @param array $user User data
     * @param array $input Login input data
     * @return void
     */
    protected function _set_login_session($user, $input = [])
    {
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
        return $access_token;
    }

    /**
     * Handle user registration process
     * 
     * @param array $userData User registration data
     * @return array|void Returns errors array if registration fails, void if successful
     */
    protected function _register($userData)
    {
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
         
            return $this->handleSuccessfulRegistration($user_id, $userData);
        } else {
            $errors['register_failed'] = [__('Failed to register account')];
            return $errors;
        }
    }

    /**
     * Send forgot password email
     * 
     * @param array $user User data
     * @return array|void Returns errors array if sending fails, void if successful
     */
    protected function _forgot_send($user)
    {
        if (empty($user['id'])) {
            $errors['email'] = [__('User with email %1% not found', '')];
            return $errors;
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

        return $this->handleForgotPasswordSent($user);
    }

    /**
     * Validate user input for registration
     * 
     * @param array $input Input data
     * @return array Validation errors
     */
    protected function _validateRegistration($input)
    {
        $errors = [];
        
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
                'messages' => [__('Min %1% & Max %2% char, and only letters (Spaces, hyphens, dots, and middle dots cannot be doubled consecutively)', 2, 150)]
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
            $errors = array_merge($errors, $validator->getErrors());
        } else {
            // Check for existing username/email
            if ($this->usersModel->getUserByUsername($input['username'])) {
                $errors['username'] = [__('Username %1% is already taken', $input['username'])];
            }
            if ($this->usersModel->getUserByEmail($input['email'])) {
                $errors['email'] = [__('Email %1% is already registered', $input['email'])];
            }
        }
        
        return $errors;
    }

    /**
     * Validate user input for login
     * 
     * @param array $input Input data
     * @return array Validation errors
     */
    protected function _validateLogin($input)
    {
        $errors = [];
        
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
            $errors = array_merge($errors, $validator->getErrors());
        }
        
        return $errors;
    }

    /**
     * Prepare profile data for display
     * 
     * @param array $user User data
     * @return array Processed profile data
     */
    protected function _prepareProfileData($user)
    {
        // Parse personal data
        $personal = _json_decode($user['personal'] ?? []);
        
        // Parse address data
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
            'password_at' => $user['password_at'] ?? 0,
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
        if (isset($user['access_token'])) {
            $me_info['access_token'] = $user['access_token'];
        }
        
        return $me_info;
    }

    /**
     * Set profile - Handle profile update request
     * 
     * @param int $user_id
     * @param array $user
     * @return array
     */
    protected function _setProfile($user_id, $user)
    {
        $errors = [];
        $input = [];
        $rules = [];
        
        // Check each field individually using isset strategy
        if (HAS_POST('username')){
            $input['username'] = trim(S_POST('username') ?? '');
            $rules['username'] = [
                'rules' => [Validate::length(5, 40), Validate::alnum('_')],
                'messages' => [
                    __('Username must be between %1% and %2% characters', 3, 40),
                    __('Username can only contain letters, numbers, and underscores')
                ]
            ];
        }
        if (HAS_POST('fullname')){
            $input['fullname'] = trim(S_POST('fullname') ?? '');
            $rules['fullname'] = [
                'rules' => [Validate::name(2, 150)],
                'messages' => [__('Min %1% & Max %2% char, and only letters (Spaces, hyphens, dots, and middle dots cannot be doubled consecutively)', 2, 150)]
            ];
        }
        if (HAS_POST('birthday')){
            $input['birthday'] = S_POST('birthday') ?? '';
            $rules['birthday'] = [
                'rules' => [Validate::date('Y-m-d')],
                'messages' => [__('Birthday must be a valid date in YYYY-MM-DD format')]
            ];
        }
        if (HAS_POST('gender')){
            $input['gender'] = S_POST('gender') ?? '';
            $rules['gender'] = [
                'rules' => [Validate::optional(Validate::in(['male', 'female', 'other']))],
                'messages' => [__('Gender must be male, female, or other')]
            ];
        }
        if (HAS_POST('phone')){
            $input['phone'] = trim(S_POST('phone') ?? '');
            $rules['phone'] = [
                'rules' => [Validate::optional(Validate::phone()), Validate::optional(Validate::length(1, 30))],
                'messages' => [
                    __('Please enter a valid phone number'),
                    __('Phone number must be between %1% and %2% characters', 1, 30)
                ]
            ];
        }
        if (HAS_POST('display')){
            $input['display'] = S_POST('display') ? 1 : 0;
        }
        if (HAS_POST('about_me')){
            $input['about_me'] = trim(S_POST('about_me') ?? '');
            $rules['about_me'] = [
                'rules' => [Validate::optional(Validate::length(10, 300))],
                'messages' => [__('Personal description must be between %1% and %2% characters', 10, 300)]
            ];
        }
        if (HAS_POST('country')){
            $input['country'] = S_POST('country') ?? '';
            $rules['country'] = [
                'rules' => [Validate::optional(Validate::length(2, 2)), Validate::optional(Validate::alpha())],
                'messages' => [
                    __('Country code must be exactly 2 characters'),
                    __('Country code can only contain letters')
                ]
            ];
        }
        if (HAS_POST('address1')){
            $input['address1'] = trim(S_POST('address1') ?? '');
            $rules['address1'] = [
                'rules' => [Validate::optional(Validate::address(3, 200))],
                'messages' => [__('Address line 1 must be between %1% and %2% characters and contain only valid address characters', 3, 200)]
            ];
        }
        if (HAS_POST('address2')){
            $input['address2'] = trim(S_POST('address2') ?? '');
            $rules['address2'] = [
                'rules' => [Validate::optional(Validate::address(3, 200))],
                'messages' => [__('Address line 2 must be between %1% and %2% characters and contain only valid address characters', 3, 200)]
            ];
        }
        if (HAS_POST('city')){
            $input['city'] = trim(S_POST('city') ?? '');
            $rules['city'] = [
                'rules' => [Validate::optional(Validate::name(2, 100))],
                'messages' => [__('Min %1% & Max %2% char, and only letters (Spaces, hyphens, dots, and middle dots cannot be doubled consecutively)', 2, 100)]
            ];
        }
        if (HAS_POST('state')){
            $input['state'] = trim(S_POST('state') ?? '');
            $rules['state'] = [
                'rules' => [Validate::optional(Validate::name(2, 100))],
                'messages' => [__('Min %1% & Max %2% char, and only letters (Spaces, hyphens, dots, and middle dots cannot be doubled consecutively)', 2, 100)]
            ];
        }
        if (HAS_POST('zipcode')){
            $input['zipcode'] = trim(S_POST('zipcode') ?? '');
            $rules['zipcode'] = [
                'rules' => [Validate::optional(Validate::alnum('-')), Validate::optional(Validate::length(3, 20))],
                'messages' => [
                    __('ZIP code can only contain letters, numbers, and hyphens'),
                    __('ZIP code must be between %1% and %2% characters', 3, 20)
                ]
            ];
        }
        if (HAS_POST('facebook')){
            $input['facebook'] = trim(S_POST('facebook') ?? '');
            $rules['facebook'] = [
                'rules' => [Validate::optional(Validate::url()), Validate::optional(Validate::length(5, 200))],
                'messages' => [
                    __('Facebook must be a valid URL'),
                    __('Facebook URL must be between %1% and %2% characters', 5, 200)
                ]
            ];
        }
        if (HAS_POST('linkedin')){
            $input['linkedin'] = trim(S_POST('linkedin') ?? '');
            $rules['linkedin'] = [
                'rules' => [Validate::optional(Validate::url()), Validate::optional(Validate::length(5, 200))],
                'messages' => [
                    __('LinkedIn must be a valid URL'),
                    __('LinkedIn URL must be between %1% and %2% characters', 5, 200)
                ]
            ];
        }
        if (HAS_POST('telegram')){
            $input['telegram'] = trim(S_POST('telegram') ?? '');
            $rules['telegram'] = [
                'rules' => [Validate::optional(Validate::alnum('@_')), Validate::optional(Validate::length(3, 100))],
                'messages' => [
                    __('Telegram username can only contain letters, numbers, @, and _'),
                    __('Telegram username must be between %1% and %2% characters', 3, 100)
                ]
            ];
        }
        if (HAS_POST('whatsapp')){
            $input['whatsapp'] = trim(S_POST('whatsapp') ?? '');
            $rules['whatsapp'] = [
                'rules' => [Validate::optional(Validate::phone()), Validate::optional(Validate::length(5, 30))],
                'messages' => [
                    __('WhatsApp must be a valid phone number'),
                    __('WhatsApp number must be between %1% and %2% characters', 5, 30)
                ]
            ];
        }
        if (HAS_POST('custom_social_name') && HAS_POST('custom_social_value')){
            $input['custom_social_name'] = S_POST('custom_social_name') ?? [];
            $input['custom_social_value'] = S_POST('custom_social_value') ?? [];
            $customSocialErrors = $this->_validate_custom_social_media($input['custom_social_name'], $input['custom_social_value']);
            $errors = array_merge($errors, $customSocialErrors);
        }

        if (HAS_POST('work_experiences')){
            $input['work_experiences'] = S_POST('work_experiences') ?? [];
        }
        if (HAS_POST('educations')){
            $input['educations'] = S_POST('educations') ?? [];
        }
        if (HAS_POST('skills')){
            $input['skills'] = S_POST('skills') ?? [];
        }
        if (HAS_POST('languages')){
            $input['languages'] = S_POST('languages') ?? [];
        }
        if (HAS_POST('hobbies')){
            $input['hobbies'] = S_POST('hobbies') ?? [];
        }
        if (HAS_POST('certifications')){
            $input['certifications'] = S_POST('certifications') ?? [];
        }
        
        // Validate detailed information
        $detailedInfoErrors = $this->_validate_detailed_information($input);
        if (!empty($detailedInfoErrors)) {
            $errors = array_merge($errors, $detailedInfoErrors);
        }
        
        if (!empty($rules)) {
            $validator = new Validate();
            if (!$validator->check($input, $rules)) {
                $errors = array_merge($errors, $validator->getErrors());
            }
        }
        
        // Check username uniqueness if changed
        if (isset($input['username']) && !hash_equals($input['username'], $user['username'])) {
            $existingUser = $this->usersModel->where('username', $input['username'])->first();
            if ($existingUser) {
                if (!empty($errors['username'])){
                    $errors['username'][] = __('Username already exists');
                }else{
                    $errors['username'] = [__('Username already exists')];
                }
            }else{
                //Update Optional để tạo 1 field lưu thời gian đổi username
                $optionalData = _json_decode($user['optional']);
                if (isset($optionalData['username_changed_at']) && $optionalData['username_changed_at'] > _DateTime('-3 months')) {
                    $errors['username'] = [__('Username can change 1 times per 3 months')];
                }else{
                    $optionalData['username_changed_at'] = _DateTime();
                    $input['optional'] = json_encode($optionalData);
                }
            }
        }
        
        if (empty($errors)) {
            // Process and save personal information
            $this->_updateDBProfile($user, $input);
        }
        
        return $errors;
    }

    /**
     * Process profile data and save to database
     * @param int $user_id
     * @param array $input
     * @return void
     */
    protected function _updateDBProfile($user, $input) {
        // Get current user data
        $user_id = $user['id'];
        $userData = ['updated_at' => _DateTime(),'activity_at' => _DateTime()];
        //Decode Json Data to Array Data
        $personalData = _json_decode($user['personal']);
        $addressData = _json_decode($user['address']);
        //Update Optional Data (maybe change username need to update optional)
        if (isset($input['optional'])){
            $userData['optional'] = $input['optional'];
        }
        if (isset($input['username'])){
            $userData['username'] = $input['username'];
        }
        if (isset($input['fullname'])){
            $userData['fullname'] = $input['fullname'];
        }
        if (isset($input['birthday'])){
            $userData['birthday'] = $input['birthday'];
        }
        if (isset($input['gender'])){
            $userData['gender'] = $input['gender'];
        }
        if (isset($input['phone'])){
            $userData['phone'] = $input['phone'];
        }
        if (isset($input['country'])){
            $userData['country'] = $input['country'];
        }
        if (isset($input['display'])){
            $userData['display'] = $input['display'];
        }
        //Update Address Data
        if (isset($input['address1'])){
            $addressData['address1'] = $input['address1'];
        }
        if (isset($input['address2'])){
            $addressData['address2'] = $input['address2'];
        }
        if (isset($input['city'])){
            $addressData['city'] = $input['city'];
        }
        if (isset($input['state'])){
            $addressData['state'] = $input['state'];
        }
        if (isset($input['zipcode'])){
            $addressData['zipcode'] = $input['zipcode'];
        }
        //Update Personal About Me Data
        if (isset($input['about_me'])){
            $personalData['about_me'] = $input['about_me'];
        }

        //Update Personal Socials Data
        $socialsData = [];
        if (isset($input['facebook'])){
            $socialsData['facebook'] = $input['facebook'];
        }
        if (isset($input['linkedin'])){
            $socialsData['linkedin'] = $input['linkedin'];
        }
        if (isset($input['telegram'])){
            $socialsData['telegram'] = $input['telegram'];
        }
        if (isset($input['whatsapp'])){
            $socialsData['whatsapp'] = $input['whatsapp'];
        }
        if (!empty($input['custom_social_name']) && !empty($input['custom_social_value'])) {
            foreach ($input['custom_social_name'] as $index => $name) {
                if (!empty($name) && !empty($input['custom_social_value'][$index])) {
                    $key = strtolower(trim($name));
                    $value = trim($input['custom_social_value'][$index]);
                    $socialsData[$key] = $value;
                }
            }
        }
        $socialsData = array_merge($socialsData, _json_decode($personalData['socials'], []));
        //Add field Social to user field personal
        $personalData['socials'] = $socialsData;

        //Update Personal Detailed Information Data
        if (isset($input['work_experiences'])){
            $personalData['work_experiences'] = $input['work_experiences'];
        }
        if (isset($input['educations'])){
            $personalData['educations'] = $input['educations'];
        }
        if (isset($input['skills'])){
            $personalData['skills'] = $input['skills'];
        }
        if (isset($input['languages'])){
            $personalData['languages'] = $input['languages'];
        }
        if (isset($input['hobbies'])){
            $personalData['hobbies'] = $input['hobbies'];
        }
        if (isset($input['certifications'])){
            $personalData['certifications'] = $input['certifications'];
        }

        //Encode Json Data from Array Data
        $userData['personal'] = json_encode($personalData);
        $userData['address'] = json_encode($addressData);
        
        // Update user in database
        $this->usersModel->updateUser($user_id, $userData);
    }

    /**
     * Validate custom social media data
     * @param array $input
     * @return array
     */
    protected function _validate_custom_social_media($inputName, $inputValue) {
        $errors = [];
        
        if (!empty($inputName) && !empty($inputValue)) {
            foreach ($inputName as $index => $name) {
                $value = $inputValue[$index] ?? '';
                
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
     * Validate custom social media
     * 
     * @param array $customSocials
     * @return array
     */

    /**
     * Validate detailed information
     * 
     * @param array $input
     * @return array
     */
    protected function _validate_detailed_information($input)
    {
        $errors = [];
        
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
                        'messages' => [__('Min %1% & Max %2% char, and only letters (Spaces, hyphens, dots, and middle dots cannot be doubled consecutively)', 2, 100)]
                    ];
                }
                
                if (!empty($work['position'])) {
                    $validationData["work_position_{$index}"] = $work['position'];
                    $validationRules["work_position_{$index}"] = [
                        'rules' => [Validate::name(2, 100)],
                        'messages' => [__('Min %1% & Max %2% char, and only letters (Spaces, hyphens, dots, and middle dots cannot be doubled consecutively)', 2, 100)]
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
                        'messages' => [__('Min %1% & Max %2% char, and only letters (Spaces, hyphens, dots, and middle dots cannot be doubled consecutively)', 2, 200)]
                    ];
                }
                
                if (!empty($edu['degree'])) {
                    $validationData["edu_degree_{$index}"] = $edu['degree'];
                    $validationRules["edu_degree_{$index}"] = [
                        'rules' => [Validate::name(2, 100)],
                        'messages' => [__('Min %1% & Max %2% char, and only letters (Spaces, hyphens, dots, and middle dots cannot be doubled consecutively)', 2, 100)]
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
                        'messages' => [__('Min %1% & Max %2% char, and only letters (Spaces, hyphens, dots, and middle dots cannot be doubled consecutively)', 2, 50)]
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
                        'messages' => [__('Min %1% & Max %2% char, and only letters (Spaces, hyphens, dots, and middle dots cannot be doubled consecutively)', 2, 200)]
                    ];
                }
                
                if (!empty($cert['issuer'])) {
                    $validationData["cert_issuer_{$index}"] = $cert['issuer'];
                    $validationRules["cert_issuer_{$index}"] = [
                        'rules' => [Validate::name(2, 200)],
                        'messages' => [__('Min %1% & Max %2% char, and only letters (Spaces, hyphens, dots, and middle dots cannot be doubled consecutively)', 2, 200)]
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
            $validator = new Validate();
            if (!$validator->check($validationData, $validationRules)) {
                $validationErrors = $validator->getErrors();
                $errors = array_merge($errors, $validationErrors);
            }
        }
        
        return $errors;
    }

    /**
     * Confirm screen - handles activation/Forgot password link after code validation
     * 
     * @return void
     */
    public function confirm()
    {
        // Check if user is already logged in
        if (Session::has('user_id')) {
            return $this->handleAlreadyLoggedIn();
        }
        
        // Get user_id from secure session
        $user_id = Session::get('confirm_user_id');
        $confirm_expires = Session::get('confirm_expires');
        
        // Check if session is valid
        if (empty($user_id) || $confirm_expires < time()) {
            Session::del('confirm_user_id');
            Session::del('confirm_expires');
            return $this->handleSessionExpired();
        }
        
        // Get user information
        $user = $this->usersModel->getUserById($user_id);
        if (!$user) {
            Session::del('confirm_user_id');
            Session::del('confirm_expires');
            return $this->handleAccountNotFound();
        }
        
        $userOptional = _json_decode($user['optional'] ?? []);
        $activationType = $userOptional['activation_type'] ?? 'registration';
        
        // For registration activation, check if account is already active or disabled
        if ($activationType === 'registration') {
            if ($user['status'] === 'active') {
                return $this->handleAccountAlreadyActive();
            } elseif ($user['status'] === 'disabled') {
                return $this->handleAccountDisabled();
            } elseif ($user['status'] !== 'inactive') {
                return $this->handleInvalidAccountStatus();
            }
        }
        
        // Check if activation has expired
        $activationExpires = $userOptional['activation_expires'] ?? 0;
        if ($activationExpires < time()) {
            return $this->handleActivationExpired($activationType, $userOptional);
        }
        
        // Handle code submission
        if (HAS_POST('csrf_token') && HAS_POST('confirmation_code')) {
            $csrf_token = S_POST('csrf_token') ?? '';
            if (!Session::csrf_verify($csrf_token)) {
                return $this->handleCsrfFailed();
            } else {
                $inputCode = S_POST('confirmation_code');
                $storedCode = $userOptional['activation_code'] ?? '';
                $attempts = $userOptional['activation_attempts'] ?? 0;
                
                // Check if max attempts reached
                if ($attempts >= 5) {
                    // Set cooldown period (30 minutes)
                    $userOptional['cooldown_until'] = time() + 1800; // 30 minutes
                    $this->usersModel->updateUser($user_id, ['optional' => json_encode($userOptional)]);
                    
                    return $this->handleMaxAttemptsReached($activationType, $userOptional);
                }
                
                // Verify code
                if ($inputCode === $storedCode) {
                    // Code is correct, redirect to confirmlink
                    $activationString = $userOptional['activation_string'] ?? '';
                    return $this->handleCodeVerified($user_id, $activationString);
                } else {
                    // Wrong code, increment attempts
                    $userOptional['activation_attempts'] = $attempts + 1;
                    $this->usersModel->updateUser($user_id, ['optional' => json_encode($userOptional)]);
                    
                    $remainingAttempts = 5 - ($attempts + 1);
                    return $this->handleInvalidCode($remainingAttempts);
                }
            }
        }
        
        // Display confirmation form
        return $this->displayConfirmForm($activationType, $userOptional, $user);
    }

    /**
     * Confirmlink - handles activation/Forgot password link after code validation
     * 
     * @param string $user_id
     * @param string $activationString
     * @return void
     */
    public function confirmlink($user_id = null, $activationString = '')
    {
        if (!$user_id || !$user = $this->usersModel->getUserById($user_id)) {
            return $this->handleAccountNotFound();
        }
        
        // Check account status for registration activation
        $userOptional = _json_decode($user['optional'] ?? []);
        $activationType = $userOptional['activation_type'] ?? 'registration';
        
        // For registration activation, check if account is already active or disabled
        if ($activationType === 'registration') {
            if ($user['status'] === 'active') {
                return $this->handleAccountAlreadyActive();
            } elseif ($user['status'] === 'disabled') {
                return $this->handleAccountDisabled();
            } elseif ($user['status'] !== 'inactive') {
                return $this->handleInvalidAccountStatus();
            }
        }
        $storedActivationString = $userOptional['activation_string'] ?? '';
        
        // Validate activation string
        if (empty($storedActivationString) || !hash_equals(strtolower($storedActivationString), strtolower($activationString))) {
            return $this->handleInvalidActivationLink();
        }

        // Check if activation has expired
        $activationExpires = $userOptional['activation_expires'] ?? 0;
        if ($activationExpires < time()) {
            return $this->handleActivationLinkExpired();
        }
        
        // Process based on activation type
        if ($activationType === 'forgot_password') {
            // Set secure session for password reset
            Session::set('confirm_user_id', $user_id);
            Session::set('confirm_expires', time() + 1800); // 30 minutes
            // SECURITY FIX: Set verification flag only after successful link verification
            Session::set('confirm_verified', true);
            
            // For forgot password, redirect to password reset form
            return $this->handleForgotPasswordConfirmation($user_id);
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
            $access_token = $this->_set_login_session($user);
            if (!empty($access_token)) {
                $user['access_token'] = $access_token;
            }
            return $this->handleSuccessfulActivation($user);
        }
    }

    /**
     * Resend activation/forgot password code
     * 
     * @return void
     */
    public function resend_code()
    {
        // Get user_id from secure session
        $user_id = Session::get('confirm_user_id');
        $confirm_expires = Session::get('confirm_expires');
        
        // Check if session is valid
        if (empty($user_id) || $confirm_expires < time()) {
            Session::del('confirm_user_id');
            Session::del('confirm_expires');
            return $this->handleSessionExpired();
        }
        
        $user = $this->usersModel->getUserById($user_id);
        if (!$user) {
            Session::del('confirm_user_id');
            Session::del('confirm_expires');
            return $this->handleAccountNotFound();
        }
        
        $userOptional = _json_decode($user['optional'] ?? []);
        $activationType = $userOptional['activation_type'] ?? 'registration';
        
        // For registration activation, check if account is already active or disabled
        if ($activationType === 'registration') {
            if ($user['status'] === 'active') {
                return $this->handleAccountAlreadyActive();
            } elseif ($user['status'] === 'disabled') {
                return $this->handleAccountDisabled();
            } elseif ($user['status'] !== 'inactive') {
                return $this->handleInvalidAccountStatus();
            }
        }
        
        // Check if user is in cooldown period
        $cooldownUntil = $userOptional['cooldown_until'] ?? 0;
        if ($cooldownUntil > time()) {
            $remainingMinutes = ceil(($cooldownUntil - time()) / 60);
            return $this->handleCooldownPeriod($remainingMinutes);
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
        
        return $this->handleCodeResent();
    }

    /**
     * Reset Password - handles password reset after code validation
     * 
     * @return void
     */
    public function reset_password()
    {
        // Check if user is already logged in
        if (Session::has('user_id')) {
            return $this->handleAlreadyLoggedIn();
        }
        
        // Get user_id from secure session
        $user_id = Session::get('confirm_user_id');
        $confirm_expires = Session::get('confirm_expires');
        
        // Check if session is valid
        if (empty($user_id) || $confirm_expires < time()) {
            Session::del('confirm_user_id');
            Session::del('confirm_expires');
            return $this->handleSessionExpired();
        }
        
        $user = $this->usersModel->getUserById($user_id);
        if (!$user) {
            Session::del('confirm_user_id');
            Session::del('confirm_expires');
            return $this->handleAccountNotFound();
        }
        
        $userOptional = _json_decode($user['optional'] ?? []);
        $activationType = $userOptional['activation_type'] ?? '';
        
        // Verify this is a forgot password request
        if ($activationType !== 'forgot_password') {
            Session::del('confirm_user_id');
            Session::del('confirm_expires');
            return $this->handleInvalidResetRequest();
        }
        
        // SECURITY FIX: Check if user has actually verified the code
        // This session should only be set after successful code verification in confirmlink()
        if (!Session::has('confirm_verified')) {
            //Session::del('confirm_user_id');
            //Session::del('confirm_expires');
            return $this->handleForgotPasswordSent($user);
        }
        
        // Handle password reset form submission
        if (HAS_POST('password')) {
            $csrf_token = S_POST('csrf_token') ?? '';
            if (!Session::csrf_verify($csrf_token)) {
                return $this->handleCsrfFailed();
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
                    return $this->handlePasswordResetValidationErrors($errors);
                } else {
                    $this->usersModel->updateUser($user_id, [
                        'password' => Security::hashPassword($input['password']),
                        'password_at' => _DateTime(),
                        'optional' => null
                    ]);

                    // Clear confirmation session
                    Session::del('confirm_user_id');
                    Session::del('confirm_expires');
                    Session::del('confirm_verified');
    
                    Events::run('Backend\\UserPasswordResetEvent', $user_id);
                    
                    return $this->handlePasswordResetSuccess();
                }
            }
        }
        
        // Display password reset form
        return $this->displayPasswordResetForm();
    }

    /**
     * Change password
     * 
     * @return void
     */
    public function change_password()
    {
        $user_id = Session::get('user_id');
        $user = $this->usersModel->getUserById($user_id);
        if (!$user) {
            return $this->handleUserNotFound();
        }
        Session::flash('activetab', 'security');
        
        // Handle password change request
        if (HAS_POST('csrf_token') && HAS_POST('current_password')) {
            $errors = [];
            $csrf_token = S_POST('csrf_token') ?? '';
            if (!Session::csrf_verify($csrf_token)) {
                $errors['csrf_token'] = [__('csrf_failed')];
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
                $errors = array_merge($errors, $validator->getErrors());
            }
            if (empty($errors)) {
                if (!Security::verifyPassword($input['current_password'], $user['password'])) {
                    $errors['current_password'] = [__('Current password is incorrect')];
                }
            }
            if (empty($errors)) {
                // Update password
                $this->usersModel->updateUser($user_id, [
                    'password' => Security::hashPassword($input['new_password']),
                    'password_at' => _DateTime()
                ]);
                return $this->handlePasswordChangeSuccess();
            } else {
                return $this->handlePasswordChangeErrors($errors, $user);
            }
        }
        
        return $this->displayPasswordChangeForm($user);
    }

    /**
     * Logout - shared implementation
     * 
     * @return void
     */
    public function logout()
    {
        setcookie('cmsff_logged', '', time()-1, '/');
        Session::del('user_id');
        Session::del('role');
        Session::del('permissions');
        if (isset($_COOKIE['cmsff_token'])) {
            setcookie('cmsff_token', '', time()-1, '/');
        }
        Events::run('Backend\\UserLogoutEvent');
        return $this->handleLogoutSuccess();
    }

    /**
     * Profile - Show profile page
     * 
     * @return void
     */
    public function profile()
    {
        $user_id = Session::get('user_id');
        if (!$user_id) {
            return $this->handleUserNotFound();
        }
        $user = $this->usersModel->getUserById($user_id);
        if (!$user) {
            return $this->handleUserNotFound();
        }
        
        // Prepare data for display
        $me_info = $this->_prepareProfileData($user);
        return $this->displayProfilePage($me_info);
    }

    /**
     * Set profile - Handle profile update request
     * 
     * @return void
     */
    public function set_profile()
    {
        // Check Not POST REQUEST, Display profile page
        if (!HAS_POST('csrf_token')) {
            // Prepare data for display profile
            $me_info = $this->_prepareProfileData($user);
            return $this->displayProfilePage($me_info);
        }
        
        // Handle profile update request
        $user_id = Session::get('user_id');
        $user = $this->usersModel->getUserById($user_id);
        if (!$user) {
            return $this->handleUserNotFound();
        }
        
        $errors = [];

        $csrf_token = S_POST('csrf_token') ?? '';
        if (!Session::csrf_verify($csrf_token)) {
            $errors['csrf_token'] = [__('csrf_failed')];
        }
        
        // Get page type to determine which handler to use
        $page_type = S_POST('page_type') ?? '';
        switch($page_type) {
            case 'personal_info':
                Session::flash('activetab', 'personal_info');
                break;
            case 'social_media':
                Session::flash('activetab', 'social_media');
                break;
            case 'detailed_info':
                Session::flash('activetab', 'detailed_info');
                break;
        }
        $errors = array_merge($errors, $this->_setProfile($user_id, $user));
        if (empty($errors)) {
            Session::flash('success', __('Profile updated successfully'));
            return $this->handleProfileUpdateSuccess($page_type);
        }
        
        return $this->handleProfileUpdateErrors($errors, $user_id, $page_type);
    }

    /**
     * Login with Google
     * 
     * @return void
     */
    public function login_google()
    {
        if (!HAS_GET('code')) {
            // Generate Google OAuth URL and redirect
            $auth_url = $this->_getGoogleAuthUrl();
            return $this->handleGoogleAuthRedirect($auth_url);
        } else {
            // Handle Google OAuth callback
            $code = $_GET['code'];
            $googleUserInfo = $this->_getGoogleUserInfo($code);
            
            if ($googleUserInfo) {
                $user = $this->usersModel->getUserByEmail($googleUserInfo['email']);
                
                if ($user) {
                    // User exists, log them in
                    $access_token = $this->_set_login_session($user);
                    if (!empty($access_token)) {
                        $user['access_token'] = $access_token;
                    }
                    $this->usersModel->updateUser($user['id'], ['activity_at' => _DateTime()]);
                    Events::run('Backend\\UserLoginGoogleEvent', $user);
                    
                    return $this->handleGoogleLoginSuccess($user);
                } else {
                    // User doesn't exist, redirect to registration
                    Session::set('fullname', $googleUserInfo['name']);
                    Session::set('email', $googleUserInfo['email']);
                    
                    return $this->handleGoogleUserNotFound($googleUserInfo['name'], $googleUserInfo['email']);
                }
            } else {
                // Failed to get user info from Google
                return $this->handleGoogleAuthError();
            }
        }
    }

    /**
     * Get Google OAuth authorization URL
     * 
     * @return string
     */
    protected function _getGoogleAuthUrl()
    {
        // Check if Google_Client class exists
        if (!class_exists('Google_Client')) {
            return '';
        }

        $option_google = option('google');
        $option_google = _json_decode($option_google);
        if (!empty($option_google)) {
            $option_google = array_column($option_google, 'google_value', 'google_key');
            $client_id = $option_google['GOOGLE_CLIENT_ID'] ?? '';
            $client_secret = $option_google['GOOGLE_CLIENT_SECRET'] ?? '';
            $client_url = $option_google['GOOGLE_REDIRECT_URL'] ?? '';

            // Check if required Google config is available
            if (empty($client_id) || empty($client_secret) || empty($client_url)) {
                return '';
            }

            try{
                $client = new \Google_Client();
                $client->setClientId($client_id); 
                $client->setClientSecret($client_secret);
                $client->setRedirectUri($client_url);
                $client->addScope('email');
                $client->addScope('profile');

                return $client->createAuthUrl();
            }catch(\Exception $e){
                return '';
            }
        }
        return '';
    }

    /**
     * Get user information from Google OAuth
     * 
     * @param string $code
     * @return array|null
     */
    protected function _getGoogleUserInfo($code)
    {
        try {
            $option_google = option('google');
            $option_google = array_column($option_google, 'google_value', 'google_key');
            $client_id = $option_google['GOOGLE_CLIENT_ID'] ?? '';
            $client_secret = $option_google['GOOGLE_CLIENT_SECRET'] ?? '';
            $client_url = $option_google['GOOGLE_REDIRECT_URL'] ?? '';

            $client = new \Google_Client();
            $client->setClientId($client_id); 
            $client->setClientSecret($client_secret);
            $client->setRedirectUri($client_url);
            
            // Exchange code for access token
            $token = $client->fetchAccessTokenWithAuthCode($code);
            $client->setAccessToken($token);
            
            // Get user info from Google
            $oauth2 = new \Google_Service_Oauth2($client);
            $userInfo = $oauth2->userinfo->get();
            
            return [
                'email' => $userInfo->email ?? '',
                'name' => $userInfo->name ?? '',
                'id' => $userInfo->id ?? '',
                'picture' => $userInfo->picture ?? ''
            ];
        } catch (\Exception $e) {
            // Log error if needed
            return null;
        }
    }

    // Abstract methods that must be implemented by child classes
    abstract protected function handleInactiveAccount($user);
    abstract protected function handleSuccessfulLogin($user);
    abstract protected function handleSuccessfulRegistration($user_id, $userData);
    abstract protected function handleForgotPasswordSent($user);
    
    // Additional abstract methods for common handlers
    abstract protected function handleAlreadyLoggedIn();
    abstract protected function handleSessionExpired();
    abstract protected function handleAccountNotFound();
    abstract protected function handleAccountAlreadyActive();
    abstract protected function handleAccountDisabled();
    abstract protected function handleInvalidAccountStatus();
    abstract protected function handleActivationExpired($activationType, $userOptional);
    abstract protected function handleCsrfFailed();
    abstract protected function handleMaxAttemptsReached($activationType, $userOptional);
    abstract protected function handleInvalidResetRequest();
    abstract protected function handleUserNotFound();
    
    // Abstract methods for register
    abstract protected function handleRegistrationErrors($errors);
    abstract protected function handleMissingRegistrationFields();
    abstract protected function displayRegistrationForm();
    
    // Abstract methods for forgot password
    abstract protected function handleForgotPasswordErrors($errors);
    abstract protected function handleMissingEmailField();
    abstract protected function displayForgotPasswordForm();
    
    // Abstract methods for login
    abstract protected function handleLoginErrors($errors);
    abstract protected function displayLoginForm();
    
    // Abstract methods for confirm
    abstract protected function handleCodeVerified($user_id, $activationString);
    abstract protected function handleInvalidCode($remainingAttempts);
    abstract protected function displayConfirmForm($activationType, $userOptional, $user);
    abstract protected function handleCooldownPeriod($remainingMinutes);
    abstract protected function handleCodeResent();
    
    // Abstract methods for confirmlink
    abstract protected function handleInvalidActivationLink();
    abstract protected function handleActivationLinkExpired();
    abstract protected function handleForgotPasswordConfirmation($user_id);
    abstract protected function handleSuccessfulActivation($user);
    
    // Abstract methods for reset password
    abstract protected function handlePasswordResetValidationErrors($errors);
    abstract protected function handlePasswordResetSuccess();
    abstract protected function displayPasswordResetForm();
    
    // Abstract methods for change password
    abstract protected function handlePasswordChangeSuccess();
    abstract protected function handlePasswordChangeErrors($errors, $user);
    abstract protected function displayPasswordChangeForm($user);
    
    // Abstract methods for logout
    abstract protected function handleLogoutSuccess();
    
    // Abstract methods for profile
    abstract protected function displayProfilePage($me_info);
    abstract protected function handleProfileUpdateSuccess($page_type);
    abstract protected function handleProfileUpdateErrors($errors, $user_id, $page_type);
    
    // Abstract methods for login_google
    abstract protected function handleGoogleAuthRedirect($auth_url);
    abstract protected function handleGoogleLoginSuccess($user);
    abstract protected function handleGoogleUserNotFound($fullname, $email_user);
    abstract protected function handleGoogleAuthError();
}