<?php
namespace System\Core;
use System\Libraries\Logger;
use Exception;

// Check if PATH_ROOT is not defined, prevent direct access
if (!defined('PATH_ROOT')) {
    exit('No direct access allowed.');
}

class Bootstrap {

    protected $routes;
    protected $uri;

    public function __construct() {
        if (APP_DEBUGBAR){
            \System\Libraries\Monitor::mark('Application_Init');
        }
        $appConfig = config('app');
        if (!empty($appConfig['app_timezone'])) {
            date_default_timezone_set($appConfig['app_timezone']);
        }
        if (!empty($appConfig['debug']) && $appConfig['debug']) {
            ini_set('display_startup_errors', 1);
            ini_set('display_errors', 1);
            error_reporting(-1);
        }else{
            ini_set('display_startup_errors', 0);
            ini_set('display_errors',0);
            error_reporting(E_ALL & ~E_NOTICE);
        }
        $this->init_uri();
        $this->routes = new Router(); // Create Router instance
        $this->loadRoutes();          // Load routes
        if (APP_DEBUGBAR){
            \System\Libraries\Monitor::stop('Application_Init');
        }
    }


    /**
     * Canonicalise the current request URI and build $this->uri.
     *
     * @return array{uri:string, split:string[]} Sanitised path + segments
    */
    private function init_uri(){
        /* -----------------------------------------------------------------
        * 1) Grab raw path + raw query from super-globals
        * -----------------------------------------------------------------*/
        $rawPath  = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');  // path only
        $rawQuery = $_SERVER['QUERY_STRING']    ?? '';
        /* -----------------------------------------------------------------
        * 2) Sanitise the path part (custom security filter + collapse slash)
        * -----------------------------------------------------------------*/
        $path = preg_replace('#/+#', '/', uri_security(trim($rawPath, '/')));
	    $path = trim($path, '/');
        $segments = $path === '' ? [] : explode('/', $path);
        /* -----------------------------------------------------------------
        * 3) Sanitise the query string via your GET-filter helper
        * -----------------------------------------------------------------*/
        $safeQuery = '';
        if ($rawQuery !== '') {
            $safeQuery = http_build_query(sget_security());  // returns cleaned $_GET
        }
        /* -----------------------------------------------------------------
        * 4) Assemble the canonical URI (to compare / redirect)
        * -----------------------------------------------------------------*/
        if ($path == ''){
            $path = '/';
            $canonical = '/';
        }else{
            $canonical = '/' . $path.'/';
        }
        if ($safeQuery !== '') {
            $canonical .= '?' . $safeQuery;
        }
        /* -----------------------------------------------------------------
        * 5) If canonical ≠ original → 301 redirect to canonical form
        *    (trailing-slash tolerant)
        * -----------------------------------------------------------------*/
        $original = $_SERVER['REQUEST_URI'] ?? '/';
        if ($canonical !== $original && $canonical !== $original.'/') {
            if ($segments && defined('APP_LANGUAGES') && isset(APP_LANGUAGES[$segments[0]])) {
                $canonical = '/'.substr($canonical, 3);
                redirect(base_url($canonical));
            }else{
                redirect(base_url($canonical));
            }
        }
        /* -----------------------------------------------------------------
        * 6) Build segments array + strip language prefix if needed
        * -----------------------------------------------------------------*/
        $segments = ($path === '' || $path === '/') ? [] : explode('/', $path);
        if ($segments && defined('APP_LANGUAGES') && isset(APP_LANGUAGES[$segments[0]])) {
            array_shift($segments);                 // remove language code
            $path = implode('/', $segments);
        }
        define('APP_URI', [
            'uri'   => $path,                       // e.g. "api/v1/auth"
            'split' => $segments,                    // e.g. ['api','v1','auth']
            'query' => $safeQuery
        ]);

        return $this->uri = APP_URI;
    }
    /**
     * Start framework
     */
    public function run() {
        try {
            if (APP_DEBUGBAR){
                \System\Libraries\Monitor::mark('Application_Dispatch');
            }
            if (!isset($_SERVER['REQUEST_METHOD'])) $_SERVER['REQUEST_METHOD'] = 'GET';
            $method = $_SERVER['REQUEST_METHOD'];
            $this->dispatch($this->uri['uri'], $method);
        } catch (AppException $e) {
            $e->handle();
        } catch (\Throwable $e) { // Catch all exceptions and errors
            Logger::error($e->getMessage(), $e->getFile(), $e->getLine());
            http_response_code(500);
            if (!empty(config('app')['debug'])) {
                echo $e->getMessage(), $e->getFile(), $e->getLine();
            }else{
                echo "An unknown error has occurred. Lets check file logger.log! ";
            }
        }
    }

    /**
     * Load routes from routes/web.php and routes/api.php files
     */
    private function loadRoutes() {
        // Load all Routes config files.
        if (!empty($this->uri) && !empty($this->uri['split']) && $this->uri['split'][0] == 'api' &&  file_exists(PATH_APP . 'Routes/Api.php')  ) {
            // Load API routes plugins if exists
            require_once PATH_APP . 'Routes/Api.php';
        }else{
            // Load Web routes plugins if exists
            if (file_exists(PATH_APP . 'Routes/Web.php')) {
                require_once PATH_APP . 'Routes/Web.php';
                require_once PATH_APP . 'Config/Events.php';
            }
        }
    }

    /**
     * Route URI to corresponding controller and action
     */
    private function dispatch($uri, $method) {
        $route = $this->routes->match($uri, $method);
        if (isset($route['action']) && $route['action'][0] == '_'){
            throw new AppException("404 - Router: /{$uri} ({$method}) can not access!", 404, null, 404);
        }
        
        if (!$route) {
            throw new AppException("404 - Router: /{$uri} ({$method}) not found!", 404, null, 404);
        }
        if (APP_DEBUGBAR){
            \System\Libraries\Monitor::stop('Application_Dispatch');
        }
        if (APP_DEBUGBAR){
            \System\Libraries\Monitor::mark('Application_Middleware_Init');
        }
        //Process Middleware before calling Controller.
        $middleware = new Middleware();
        if (!empty($route['middleware'])) {
            if (is_string($route['middleware'])){
                $route['middleware'] = ['App\\Middleware\\'.$route['middleware']];
            }
            // Add middlewares to list if Middleware exists
            foreach ($route['middleware'] as $mw) {
                $middleware->add($mw);
            }
        }
        if (APP_DEBUGBAR){
            \System\Libraries\Monitor::stop('Application_Middleware_Init');
        }
        // Execute middleware before calling controller
        unset($route['middleware']);//can skip this function if need to use middleware below
        $route['uri'] = $uri;
        if (APP_DEBUGBAR){
            \System\Libraries\Monitor::mark('Application_Controller');
        }
        $middleware->handle($route, function () use ($route) {
            // Get controller and method information from matched route
            $controllerClass = $route['controller'];
            $action = str_replace('-', '_', $route['action']);
            $params = $route['params'];
            define('APP_ROUTE', $route);
            
            try{
                // Check if controller exists
                if (!class_exists($controllerClass)) {
                    throw new AppException("Controller {$controllerClass} not found.", 404, null, 404);
                }
                // Initialize controller object
                $controller = new $controllerClass();
                // Check if action exists
                if (!method_exists($controller, $action)) {
                    throw new AppException("Action {$action} not found in {$controllerClass} Controller.", 404, null, 404);
                }
                call_user_func_array([$controller, $action], $params);
            }catch(\Exception $e){
                throw new AppException($e->getMessage(), 500, null, 500);
            }
        });
        if (APP_DEBUGBAR){
            \System\Libraries\Monitor::stop('Application_Controller');
        }
    }
}
