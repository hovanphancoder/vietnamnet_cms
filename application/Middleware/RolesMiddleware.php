<?php
namespace App\Middleware;
class RolesMiddleware {
    /**
     * Handle middleware
     * 
     * @param mixed $request Request information
     * @param callable $next Next middleware
     * @return mixed
     */
    public function handle($request, $next) {
        //return $next($request);
        // Get controller and action names from request (assume they are stored in request)
        $controller = $request['controller'] ?? '';
        $action = $request['action'] ?? '';
        // Get permissions from session
        if (!\System\Libraries\Session::has('user_id')) {
            // If not logged in, redirect to login page
            redirect(base_url('account/login'));
        }
        $user_id = \System\Libraries\Session::get('user_id');
        $usersModel = new \App\Models\UsersModel();
        $me = $usersModel->getUserById($user_id);
        if (!empty($me) && !empty($me['id'])){
            $permissions = json_decode($me['permissions']);
            if ($this->checkPermission($permissions, $controller, $action)) {
                // Allow to continue if has permission
                return $next($request);
            }
        }
        // If no permission, show error message
        throw new \System\Core\AppException('You not have permission access this page!<span style="display:none">'.$controller.'->'.$action.'()</span>', 403, null, 403);
    }

    /**
     * Check if user has permission to access controller and action
     * 
     * @param array $permissions User permissions array
     * @param string $controller Controller name
     * @param string $action Action name
     * @return bool
     */
    protected function checkPermission($permissions, $controller, $action) {
        // Check if permission exists for controller and action
        foreach ($permissions as $account_controller => $account_actions){
            $account_controller = '\\'.$account_controller.'Controller';
            if (strpos($controller, $account_controller) !== FALSE){
                if (in_array($action, $account_actions)){
                    return true;
                }
            }
        }
        return false;
    }
}
