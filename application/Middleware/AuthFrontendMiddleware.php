<?php

namespace App\Middleware;

use App\Libraries\Fasttoken;

class AuthFrontendMiddleware
{

    /** 
     * Handle middleware
     * 
     * @param mixed $request Request information
     * @param callable $next Next middleware
     * @return mixed
     */
    public function handle($request, $next)
    {
        $isLogin = false;
        // Assume using session to check if user is logged in
        if (\System\Libraries\Session::has('user_id')) {
            $isLogin = true;
        }
        if (!$isLogin && $access_token = Fasttoken::getToken()) {
            $config_security = config('security');
            $me_data = Fasttoken::decodeToken($access_token, $config_security['app_secret']);
            if (isset($me_data['success']) && isset($me_data['data']['user_id']) && isset($me_data['data']['exp']) && $me_data['data']['exp'] > time()) {
                \System\Libraries\Session::set('user_id', $me_data['data']['user_id']);
                \System\Libraries\Session::set('role', $me_data['data']['role']);
                // Regenerate session ID to prevent session fixation
                \System\Libraries\Session::regenerate();
                $isLogin = true;
                unset($me_data);
                unset($config_security);
            }
        }
        if (!$isLogin) {
            // If not logged in, redirect to login page
            redirect(base_url('user/login'));
        }
        // Call next middleware
        return $next($request);
    }
}
