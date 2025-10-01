<?php
namespace App\Controllers\Api\V2;
use App\Controllers\BaseAuthController;
use System\Libraries\Session;
use System\Libraries\Security;
use System\Libraries\Validate;
use System\Libraries\Events;

/**
 * API v2 Authentication Controller
 * 
 * This controller handles authentication for the API v2 interface.
 * It extends BaseAuthController to inherit common authentication logic
 * and implements API-specific JSON response handling.
 * 
 * @package App\Controllers\Api\V2
 * @author Your Name
 * @version 1.0.0
 */
class AuthController extends BaseAuthController
{
    /**
     * Constructor - Initialize API-specific components
     */
    public function __construct()
    {
        _cors();
        parent::__construct();
        // API-specific initialization
        header('Content-Type: application/json');
    }

    // login() is inherited from BaseAuthController

    // register() is inherited from BaseAuthController

    // forgot() is inherited from BaseAuthController

    // logout() is inherited from BaseAuthController

    // profile() is inherited from BaseAuthController

    // Abstract method implementations for API
    protected function handleInactiveAccount($user)
    {
        return $this->error(__('Account not active'), [
            'user_id' => $user['id'],
            'status' => $user['status']
        ], 403);
    }

    protected function handleSuccessfulLogin($user)
    {
        $me_info = $this->_prepareProfileData($user);
        return $this->success([
            'user' => $me_info,
            'message' => __('Login successful')
        ], __('Login successful'));
    }

    protected function handleSuccessfulRegistration($user_id, $userData)
    {
        return $this->success([
            'user_id' => $user_id,
            'message' => __('Registration successful')
        ], __('Registration successful'));
    }

    protected function handleForgotPasswordSent($user)
    {
        return $this->success([
            'email' => $user['email'],
            'message' => __('Password reset code sent successfully')
        ], __('Password reset code sent successfully'));
    }
    
    // Additional abstract method implementations
    protected function handleAlreadyLoggedIn()
    {
        $user = $this->usersModel->getUserById(Session::get('user_id'));
        $user['access_token'] = isset($_COOKIE['cmsff_token']) ? $_COOKIE['cmsff_token'] : '';
        return $this->success(
            [
                'user' => $this->_prepareProfileData($user),
                'message' => __('Already logged in')
            ], __('Already logged in'));
        }

    protected function handleSessionExpired()
    {
        return $this->error(__('Session expired'), [], 401);
    }

    protected function handleAccountNotFound()
    {
        return $this->error(__('Account not found'), [], 404);
    }

    protected function handleAccountAlreadyActive()
    {
        return $this->success([], __('Account is already active'));
    }

    protected function handleAccountDisabled()
    {
        return $this->error(__('Account is disabled'), [], 403);
    }

    protected function handleInvalidAccountStatus()
    {
        return $this->error(__('Invalid account status'), [], 400);
    }

    protected function handleActivationExpired($activationType, $userOptional)
    {
        return $this->error(__('Activation code has expired'), [], 400);
    }

    protected function handleCsrfFailed()
    {
        return $this->error(__('CSRF verification failed'), [], 400);
    }

    protected function handleMaxAttemptsReached($activationType, $userOptional)
    {
        return $this->error(__('Too many failed attempts. Please wait 30 minutes before trying again.'), [], 429);
    }

    protected function handleCodeVerified($user_id, $activationString)
    {
        return $this->success([
            'user_id' => $user_id,
            'activation_string' => $activationString,
            'message' => __('Code verified successfully')
        ], __('Code verified successfully'));
    }

    protected function handleInvalidCode($remainingAttempts)
    {
        return $this->error(__('Invalid code. %1% attempts remaining.', $remainingAttempts), [], 400);
    }

    protected function displayConfirmForm($activationType, $userOptional, $user)
    {
        $time = 600;
        $confirmData = [
            'csrf_token' => Session::csrf_token($time),
            'expires_in' => $time,
            'expires_at' => time() + $time,

            'activation_type' => $activationType,
            'user' => ['id' => $user['id'],'email' => $user['email'],'username' => $user['username']],
            'message' => __('Confirmation form displayed')
        ];
        return $this->success($confirmData, __('Confirmation form displayed'));
    }

    protected function handleInvalidActivationLink()
    {
        return $this->error(__('Invalid activation link'), [], 400);
    }

    protected function handleActivationLinkExpired()
    {
        return $this->error(__('Activation link has expired'), [], 400);
    }

    protected function handleForgotPasswordConfirmation($user_id)
    {
        return $this->success([
            'user_id' => $user_id,
            'message' => __('Password reset confirmation required')
        ], __('Password reset confirmation required'));
    }

    protected function handleSuccessfulActivation($user)
    {
        $me_info = $this->_prepareProfileData($user);
        $me_info['access_token'] = isset($_COOKIE['cmsff_token']) ? $_COOKIE['cmsff_token'] : '';
            return $this->success([
            'user' => $me_info,
            'message' => __('Account activated successfully')
        ], __('Account activated successfully'));
    }

    protected function handleCooldownPeriod($remainingMinutes)
    {
        return $this->error(__('Please wait %1% minutes before requesting a new code.', $remainingMinutes), [], 429);
    }

    protected function handleCodeResent()
    {
        return $this->success([], __('New code sent successfully'));
    }

    protected function handleInvalidResetRequest()
    {
        return $this->error(__('Invalid reset request'), [], 400);
    }

    protected function handlePasswordResetValidationErrors($errors)
    {
        return $this->error(__('Password reset validation failed'), $errors, 400);
    }

    protected function handlePasswordResetSuccess()
    {
        return $this->success([], __('Password reset successfully'));
    }

    protected function displayPasswordResetForm()
    {
        return $this->csrf_token();
        //return $this->success([], __('Password reset form displayed'));
    }

    protected function handleUserNotFound()
    {
        return $this->error(__('User not found'), [], 404);
    }

    protected function handlePasswordChangeSuccess()
    {
        return $this->success([], __('Password changed successfully'));
    }

    protected function handlePasswordChangeErrors($errors, $user)
    {
        return $this->error(__('Password change failed'), $errors, 400);
    }

    protected function displayPasswordChangeForm($user)
    {
        $user = $this->_prepareProfileData($user);
        $user['access_token'] = isset($_COOKIE['cmsff_token']) ? $_COOKIE['cmsff_token'] : '';

        $time = 600;
        return $this->success([
            'user' => $user,

            'csrf_token' => Session::csrf_token($time),
            'expires_in' => $time,
            'expires_at' => time() + $time,

            'message' => __('Password change form displayed')
        ], __('Password change form displayed'));
    }

    protected function displayProfilePage($me_info)
    {
        $time = 600;
        return $this->success([
            'user' => $me_info,

            'csrf_token' => Session::csrf_token($time),
            'expires_in' => $time,
            'expires_at' => time() + $time,
            
            'message' => __('Profile page displayed')
        ], __('Profile page displayed'));
    }

    protected function handleProfileUpdateSuccess($page_type)
    {
        $messages = [
            'personal_info' => __('Personal information updated successfully'),
            'social_media' => __('Social media updated successfully'),
            'detailed_info' => __('Detailed information updated successfully')
        ];
        
        return $this->success([
            'page_type' => $page_type,
            'message' => $messages[$page_type] ?? __('Profile updated successfully')
        ], $messages[$page_type] ?? __('Profile updated successfully'));
    }

    protected function handleProfileUpdateErrors($errors, $user_id, $page_type)
    {
        return $this->error(__('Profile update failed'), $errors, 400);
    }

    protected function handleGoogleAuthRedirect($auth_url)
    {
        return $this->success([
            'auth_url' => $auth_url,
            'message' => __('Google authentication URL generated')
        ], __('Google authentication URL generated'));
    }

    protected function handleGoogleLoginSuccess($user)
    {
        $me_info = $this->_prepareProfileData($user);
        $me_info['access_token'] = isset($_COOKIE['cmsff_token']) ? $_COOKIE['cmsff_token'] : '';
        return $this->success([
            'user' => $me_info,
            'message' => __('Login with Google successful')
        ], __('Login with Google successful'));
    }

    protected function handleGoogleUserNotFound($fullname, $email_user)
    {
        return $this->success([
            'fullname' => $fullname,
            'email' => $email_user,
            'message' => __('Please complete your registration')
        ], __('Please complete your registration'));
    }

    protected function handleGoogleAuthError()
    {
        return $this->error([
            'message' => __('Google authentication failed. Please try again.')
        ], __('Google authentication failed. Please try again.'), 400);
    }

    // Called by BaseAuthController::logout()
    protected function handleLogoutSuccess()
    {
        return $this->success([], __('Logout successful'));
    }

    // Hooks for shared login()
    protected function displayLoginForm()
    {
        return $this->csrf_token();
        //return $this->error(__('Missing required fields'), [], 400);
    }

    protected function handleLoginErrors($errors)
    {
        return $this->error(__('Login failed'), $errors, 401);
    }

    // Abstract method implementations for register
    protected function handleRegistrationErrors($errors)
    {
        return $this->error(__('Registration failed'), $errors, 400);
    }

    protected function handleMissingRegistrationFields()
    {
        return $this->error(__('Missing required fields'), [], 400);
    }

    protected function displayRegistrationForm()
    {
        return $this->csrf_token();
        //return $this->error(__('Missing required fields'), [], 400);
    }

    // Abstract method implementations for forgot password
    protected function handleForgotPasswordErrors($errors)
    {
        return $this->error(__('Forgot password failed'), $errors, 400);
    }

    protected function handleMissingEmailField()
    {
        return $this->error(__('Missing email field'), [], 400);
    }

    protected function displayForgotPasswordForm()
    {
        return $this->csrf_token();
        //return $this->error(__('Missing email field'), [], 400);
    }

    /**
     * Send success response
     * 
     * @param array $data
     * @param string $message
     * @param int $code
     * @return void
     */
    protected function success($data = [], $message = 'Success', $code = 200)
    {
        http_response_code($code);
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => time()
        ]);
        exit;
    }

    /**
     * Send error response
     * 
     * @param string $message
     * @param array $errors
     * @param int $code
     * @return void
     */
    protected function error($message = 'Error', $errors = [], $code = 400)
    {
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'timestamp' => time()
        ]);
        exit;
    }

    /**
     * Get CSRF token for API requests
     * 
     * This endpoint provides a CSRF token that can be used for subsequent API requests.
     * The token is valid for 1 hour (3600 seconds) by default.
     * 
     * @return void
     */
    public function csrf_token($time = 600)
    {
        if (!is_numeric($time)) {
            $time = 600;
        }
        try {
            // Generate CSRF token with 1 hour expiry
            $csrf_token = Session::csrf_token($time);
            
            return $this->success([
                'csrf_token' => $csrf_token,
                'expires_in' => $time,
                'expires_at' => time() + $time
            ], __('CSRF token generated successfully'));
            
        } catch (\Exception $e) {
            return $this->error([
                'message' => __('Failed to generate CSRF token')
            ], __('Failed to generate CSRF token'), 500);
        }
    }
}