<?php
namespace System\Core;
use System\Core\Middleware;
// Check if PATH_ROOT is not defined, prevent direct access
if (!defined('PATH_ROOT')) {
    exit('No direct access allowed.');
}

class Router {

    private $routes = [];

    /**
     * Register GET route
     */
    public function get($uri, $controller, $middleware = [], $namespace = 'application') {
        $this->addRoute('GET', $uri, $controller, $middleware, $namespace);
    }

    /**
     * Register POST route
     */
    public function post($uri, $controller, $middleware = [], $namespace = 'application') {
        $this->addRoute('POST', $uri, $controller, $middleware, $namespace);
    }

    /**
     * Register PUT route
     */
    public function put($uri, $controller, $middleware = [], $namespace = 'application') {
        $this->addRoute('PUT', $uri, $controller, $middleware, $namespace);
    }

    /**
     * Register DELETE route
     */
    public function delete($uri, $controller, $middleware = [], $namespace = 'application') {
        $this->addRoute('DELETE', $uri, $controller, $middleware, $namespace);
    }

    /**
     * Add route to routes list
     */
    private function addRoute($method, $uri, $controller, $middleware = [], $namespace = 'application') {
        $this->routes[$method][parse_uri($uri)] = [
            'controller' => $controller,
            'middleware' => $middleware,
            'namespace' => $namespace
        ];
    }

    /**
     * Match URI with route and return controller, action, params, and middleware information
     */
    public function match($uri, $method) {
        $uri = parse_uri($uri);

        // Check each registered route to find match
        foreach ($this->routes[$method] as $routeUri => $route) {
            if (preg_match($this->convertToRegex($routeUri), $uri, $matches)) {
                array_shift($matches); // Remove full regex match
                $controllerAction = $this->getControllerAction($route['controller'], $matches, $route['namespace']); 
                $controllerAction['middleware'] = $route['middleware'];
                return [
                    'controller' => $controllerAction[0],
                    'action' => $controllerAction[1],
                    'params' => $controllerAction[2],
                    'middleware' => $route['middleware'] // Return middleware if exists
                ];
            }
        }

        // Check if only controller matches (e.g. /admin or /admin/index)
        $controller = explode('::', $route['controller'])[0];
        if ($this->isControllerRoute($routeUri, $uri, $controller)) {
            $controllerAction = $this->getControllerAction($route['controller'], [], $route['namespace']);
           
            return [
                'controller' => $controllerAction[0],
                'action' => $controllerAction[1] ?? 'index', // Default action is 'index'
                'params' => [],
                'middleware' => $route['middleware'] // Return middleware if exists
            ];
        }
        // If no route matches, check automatic structure "/Controller/Function"
        return $this->autoRoute($uri);
    }

    /**
     * Check if URL matches controller and method (wildcard)
     */
    private function isControllerRoute($routeUri, $uri, $controller) {
        $controller = str_replace('\\', '/', strtolower($controller));
        $routeUri = str_replace('\\', '/', trim(parse_uri($routeUri), '/'));
        $uri = str_replace('\\', '/', $uri);
        // Check if URI starts with routeUri (controller) and has no specific action
        return strpos($uri, $routeUri) === 0 && strpos($uri, $controller) !== false;
    }
    
    private function getControllerAction($controllerString, $params, $namespace = 'application') {
        // Step 1: Replace all $n in controllerString with corresponding values from $params
        preg_match_all('/\$(\d+)/', $controllerString, $matches);
        if (!empty($matches[1])) {
            foreach ($matches[1] as $key => $paramIndex) {
                $index = intval($paramIndex) - 1;
                if (isset($params[$index])) {
                    $value = $params[$index];
                    // If value contains "/" then only take first part
                    if (strpos($value, '/') !== false) {
                        $value = explode('/', $value)[0];
                    }
                    if ($key < 2){
                        $value = str_replace('.', '', $value);
                    }
                    // If replacing in controller part then uppercase first character
                    if (strpos($controllerString, '::') > strpos($controllerString, '$' . $paramIndex)) {
                        $value = ucfirst($value);
                    }
                    $controllerString = str_replace('$' . $paramIndex, $value, $controllerString);
                }
            }
        }
    
        // Step 2: Parse replaced controllerString to get controller and action
        list($controller, $actionString) = explode('::', $controllerString);
        
        // Split action and action parameters (if any)
        $actionParts = explode(':', $actionString);
        $action = array_shift($actionParts); // Take first element as action name
        
        // Remaining parts in $actionParts are action parameters
        $controllerClass = "App\\Controllers\\{$controller}";
        if ($namespace !== 'application') $controllerClass = "Plugins\\{$namespace}\\Controllers\\{$controller}";
        return [
            $controllerClass, 
            $action,
            $actionParts
        ];
    }

    /**
     * Convert route to regex to match URI
     */
    private function convertToRegex($routeUri) {
        // Replace special patterns like CodeIgniter
        $routeUri = str_replace('(:any)', '(.+)', $routeUri);
        $routeUri = str_replace('(:segment)', '([^/]+)', $routeUri);
        $routeUri = str_replace('(:num)', '(\d+)', $routeUri);
        $routeUri = str_replace('(:alpha)', '([a-zA-Z]+)', $routeUri);
        $routeUri = str_replace('(:alphadash)', '([a-zA-Z\-]+)', $routeUri); // Match letters and hyphens (-)
        $routeUri = str_replace('(:alphanum)', '([a-zA-Z0-9]+)', $routeUri);
        $routeUri = str_replace('(:alphanumdash)', '([a-zA-Z0-9\-]+)', $routeUri); // Match letters, numbers and hyphens (-)
    
        // Support for custom regular expressions
        $routeUri = preg_replace('#\(([a-zA-Z0-9_\-\.\[\]\+\*]+)\)#', '($1)', $routeUri);
    
        // Put route in complete regular expression form
        return "#^" . $routeUri . "$#";
    }    

    /**
     * Auto route from URI to Controller and Function
     * URI format /ControllerName/FunctionName/params...
     */
    private function autoRoute($uri) {
        $segments = explode('/', trim($uri, '/'));

        $controller = !empty($segments[0]) ? ucfirst($segments[0]) . 'Controller' : 'HomeController';
        $action = isset($segments[1]) ? $segments[1] : 'index';
        $params = array_slice($segments, 2);

        $controllerClass = "App\\Controllers\\{$controller}";
        if (class_exists($controllerClass) && method_exists($controllerClass, $action)) {
            return [
                'controller' => $controllerClass,
                'action' => $action,
                'params' => $params,
                'middleware' => [] // Auto route has no middleware
            ];
        }
        return false; // If controller/action not found
    }
}
