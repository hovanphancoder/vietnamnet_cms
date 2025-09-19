<?php
namespace App\Middleware;

class NoauthFrontendMiddleware {

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
            // If already logged in, redirect to admin page
            redirect(base_url());
        }
        // Call next middleware
        return $next($request);
    }
}