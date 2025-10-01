<?php
namespace App\Controllers;
use System\Libraries\Render;
use System\Libraries\Session;
use System\Libraries\Security;
use System\Libraries\Validate;
use System\Libraries\Events;

/**
 * Frontend Authentication Controller
 * 
 * This controller handles authentication for the frontend interface.
 * It extends BaseAuthController to inherit common authentication logic
 * and implements frontend-specific response handling.
 * 
 * @package App\Controllers
 * @author Your Name
 * @version 1.0.0
 */
class AuthController extends BaseAuthController
{
    /**
     * Constructor - Initialize frontend-specific components
     */
    public function __construct()
    {
        parent::__construct();

        // Frontend-specific asset loading
        Render::asset('js', 'js/jfast.1.2.3.js', ['area' => 'backend', 'location' => 'footer']);
    }

    /**
     * Check login status and redirect appropriately
     * 
     * @return void
     */
    public function index()
    {
        if (Session::has('user_id')) {
            // If already logged in, redirect to dashboard
            redirect(auth_url('profile'));
        } else {
            // If not logged in, redirect to login page
            redirect(auth_url('login'));
        }
    }

    // login() is inherited from BaseAuthController

    // register() is inherited from BaseAuthController

    // forgot() is inherited from BaseAuthController

    // logout() is inherited from BaseAuthController

    // Abstract method implementations for frontend
    protected function handleInactiveAccount($user)
    {
        Session::flash('error', __('Account not active. Please confirm your email.'));
        return redirect(auth_url('confirm'));
    }

    protected function handleSuccessfulLogin($user)
    {
        Session::flash('success', __('Login successful'));
        return redirect(auth_url('profile'));
    }

    // Hooks for shared login()
    protected function displayLoginForm()
    {
        $this->data('title', __('Welcome Back - Sign In'));
        $this->data('csrf_token', Session::csrf_token(600));
        echo Render::html('Common/Auth/login', $this->data);
    }

    protected function handleLoginErrors($errors)
    {
        $this->data('errors', $errors);
        return $this->displayLoginForm();
    }

    protected function handleSuccessfulRegistration($user_id, $userData)
    {
        Session::flash('success', __('Registration successful. Please confirm your email.'));
            return redirect(auth_url('confirm'));
        }
        
    protected function handleForgotPasswordSent($user)
    {
        Session::flash('success', __('Password reset code sent to: %1% successfully', $user['email']));
        return redirect(auth_url('confirm'));
    }
    
    // Additional abstract method implementations
    protected function handleAlreadyLoggedIn()
    {
        return redirect(auth_url('profile'));
    }

    protected function handleSessionExpired()
    {
            Session::flash('error', __('Session expired. Please try again.'));
            return redirect(auth_url('login'));
        }
        
    protected function handleAccountNotFound()
    {
        Session::flash('error', __('Account not found.'));
        return redirect(auth_url('login'));
    }

    protected function handleAccountAlreadyActive()
    {
        Session::flash('success', __('Account is already active.'));
        return redirect(auth_url('login'));
    }

    protected function handleAccountDisabled()
    {
        Session::flash('error', __('Account is disabled.'));
                return redirect(auth_url('login'));
    }

    protected function handleInvalidAccountStatus()
    {
        Session::flash('error', __('Invalid account status.'));
                return redirect(auth_url('login'));
    }

    protected function handleActivationExpired($activationType, $userOptional)
    {
        Session::flash('error', __('Activation code has expired. Please request a new one.'));
        return redirect(auth_url('login'));
    }

    protected function handleCsrfFailed()
    {
        Session::flash('error', __('CSRF verification failed.'));
        return redirect(auth_url('login'));
    }

    protected function handleMaxAttemptsReached($activationType, $userOptional)
    {
        Session::flash('error', __('Too many failed attempts. Please wait 30 minutes before trying again.'));
        return redirect(auth_url('login'));
    }

    protected function handleCodeVerified($user_id, $activationString)
    {
        return redirect(auth_url('confirmlink/' . $user_id . '/' . $activationString));
    }

    protected function handleInvalidCode($remainingAttempts)
    {
        $this->data('errors', ['confirmation_code' => [__('Invalid code. %1% attempts remaining.', $remainingAttempts)]]);
        return $this->displayConfirmForm('registration', [], []);
    }

    protected function displayConfirmForm($activationType, $userOptional, $user)
    {
        $this->data('activationType', $activationType);
        $this->data('user', $user);
        $this->data('email', $user['email']);
        $this->data('title', $activationType === 'forgot_password' ? __('Password Reset') : __('Account Activation'));
        $this->data('csrf_token', Session::csrf_token(600));
        echo Render::html('Common/Auth/confirm', $this->data);
    }

    protected function handleInvalidActivationLink()
    {
        Session::flash('error', __('Invalid activation link.'));
                return redirect(auth_url('login'));
    }

    protected function handleActivationLinkExpired()
    {
        Session::flash('error', __('Activation link has expired.'));
            return redirect(auth_url('login'));
        }
        
    protected function handleForgotPasswordConfirmation($user_id)
    {
        return redirect(auth_url('reset-password'));
    }

    protected function handleSuccessfulActivation($user)
    {
        Session::flash('success', __('Account activated successfully.'));
        return redirect(auth_url('profile'));
    }

    protected function handleCooldownPeriod($remainingMinutes)
    {
            Session::flash('error', __('Please wait %1% minutes before requesting a new code.', $remainingMinutes));
            return redirect(auth_url('confirm'));
        }
        
    protected function handleCodeResent()
    {
        Session::flash('success', __('New code sent successfully.'));
        return redirect(auth_url('confirm'));
    }

    protected function handleInvalidResetRequest()
    {
        Session::flash('error', __('Invalid reset request.'));
        return redirect(auth_url('login'));
    }

    protected function handlePasswordResetValidationErrors($errors)
    {
        $this->data('errors', $errors);
        return $this->displayPasswordResetForm();
    }

    protected function handlePasswordResetSuccess()
    {
        Session::flash('success', __('Password reset successfully.'));
        return redirect(auth_url('login'));
    }

    protected function displayPasswordResetForm()
    {
        $this->data('title', __('Reset Password'));
        $this->data('csrf_token', Session::csrf_token(600));
        echo Render::html('Common/Auth/reset-password', $this->data);
    }

    protected function handleUserNotFound()
    {
        Session::flash('error', __('User not found.'));
            return redirect(auth_url('login'));
        }
        
    protected function handlePasswordChangeSuccess()
    {
        Session::flash('success', __('Password changed successfully.'));
        return redirect(auth_url('profile'));
    }

    protected function handlePasswordChangeErrors($errors, $user)
    {
        $this->data('errors', $errors);
        $this->data('me_info', $this->_prepareProfileData($user));
        $this->data('title', __('Profile Settings'));
        $this->data('csrf_token', Session::csrf_token(600));
        echo Render::html('Common/Auth/profile', $this->data);
    }

    protected function displayPasswordChangeForm($user)
    {
        $this->data('me_info', $this->_prepareProfileData($user));
        $this->data('title', __('Change Password'));
        $this->data('csrf_token', Session::csrf_token(600));
        echo Render::html('Common/Auth/profile', $this->data);
    }

    protected function displayProfilePage($me_info)
    {
        $this->data('me_info', $me_info);
        $this->data('title', __('Profile Settings'));
        $this->data('csrf_token', Session::csrf_token(600));
        echo Render::html('Common/Auth/profile', $this->data);
    }

    protected function handleProfileUpdateSuccess($page_type)
    {
        $messages = [
            'personal_info' => __('Personal information updated successfully'),
            'social_media' => __('Social media updated successfully'),
            'detailed_info' => __('Detailed information updated successfully')
        ];
        
        Session::flash('success', $messages[$page_type] ?? __('Profile updated successfully'));
        Session::flash('activetab', $page_type);
        return redirect(auth_url('profile'));
    }

    protected function handleProfileUpdateErrors($errors, $user_id, $page_type)
    {
        $user = $this->usersModel->getUserById($user_id);
                $this->data('errors', $errors);
        $this->data('me_info', $this->_prepareProfileData($user));
        $this->data('title', __('Profile Settings'));
        $this->data('csrf_token', Session::csrf_token(600));
        Session::flash('activetab', $page_type);
        echo Render::html('Common/Auth/profile', $this->data);
    }

    protected function handleGoogleAuthRedirect($auth_url)
    {
        if (!empty($auth_url)) {
            return redirect($auth_url);
        }
        Session::flash('error', __('Google authentication failed. Please try again.'));
        return redirect(auth_url('login'));
    }

    protected function handleGoogleLoginSuccess($user)
    {
        Session::flash('success', __('Login with Google successful'));
        return redirect(auth_url('profile'));
    }

    protected function handleGoogleUserNotFound($fullname, $email_user)
    {
        Session::flash('info', __('Please complete your registration.'));
        return redirect(auth_url('register'));
    }

    protected function handleGoogleAuthError()
    {
        Session::flash('error', __('Google authentication failed. Please try again.'));
        return redirect(auth_url('login'));
    }

    // Called by BaseAuthController::logout()
    protected function handleLogoutSuccess()
    {
        return redirect(base_url());
    }

    // Abstract method implementations for register
    protected function handleRegistrationErrors($errors)
    {
                $this->data('errors', $errors);
        return $this->displayRegistrationForm();
    }

    protected function handleMissingRegistrationFields()
    {
        $this->data('title', __('Create New Account'));
        $this->data('csrf_token', Session::csrf_token(600));
        echo Render::html('Common/Auth/register', $this->data);
    }

    protected function displayRegistrationForm()
    {
        $this->data('title', __('Create New Account'));
        $this->data('csrf_token', Session::csrf_token(600));
        echo Render::html('Common/Auth/register', $this->data);
    }

    // Abstract method implementations for forgot password
    protected function handleForgotPasswordErrors($errors)
    {
        $this->data('errors', $errors);
        return $this->displayForgotPasswordForm();
    }

    protected function handleMissingEmailField()
    {
        $this->data('csrf_token', Session::csrf_token(600));
        $this->data('title', __('Forgot Password'));
        echo Render::html('Common/Auth/forgot', $this->data);
    }

    protected function displayForgotPasswordForm()
    {
        $this->data('csrf_token', Session::csrf_token(600));
        $this->data('title', __('Forgot Password'));
        echo Render::html('Common/Auth/forgot', $this->data);
    }
}