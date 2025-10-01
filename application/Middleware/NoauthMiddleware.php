<?php
namespace App\Middleware;

class NoauthMiddleware {

    /**
     * Handle middleware
     * 
     * @param mixed $request Request information
     * @param callable $next Next middleware
     * @return mixed
     */
    public function handle($request, $next) {
        // Assume using session to check if user is logged in
        if (\System\Libraries\Session::has('user_id')) {
            //Echo Json Noauth Required if XHR request
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                echo json_encode(['success' => false, 'message' => 'No Login Required', 'errors' => ['auth_middleware'=>['No Login Required']]]);
                exit();
            }
            // If already logged in, redirect to profile page
            redirect(auth_url('profile'));
        }
        // Call next middleware
        return $next($request);
    }
}