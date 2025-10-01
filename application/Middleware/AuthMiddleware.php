<?php
namespace App\Middleware;
use App\Libraries\Fasttoken;
class AuthMiddleware {

    /** 
     * Handle middleware
     * 
     * @param mixed $request Request information
     * @param callable $next Next middleware
     * @return mixed
     */
    public function handle($request, $next) {
        $isLogin = false;
        // Assume using session to check if user is logged in
        if (\System\Libraries\Session::has('user_id')) {
            $isLogin = true;
        }
        if (!$isLogin && $access_token = Fasttoken::headerToken()){
            $me_data = Fasttoken::checkToken($access_token);
            if (!empty($me_data)){
                \System\Libraries\Session::set('user_id', $me_data['user_id']);
                \System\Libraries\Session::set('role', $me_data['role']);
                // Regenerate session ID to prevent session fixation
                \System\Libraries\Session::regenerate();
                $isLogin = true;
                unset($me_data);
                unset($config_security);
            }
        }
        if (!$isLogin){
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                echo json_encode(['success' => false, 'message' => 'Login Required', 'errors' => ['auth_middleware'=>['Login Required']]]);
                exit();
            }
            // If not logged in, redirect to login page
            redirect(base_url('account/login'));
        }
        // Call next middleware
        return $next($request);
    }
}