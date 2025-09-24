<?php
$plugins = option('plugins_active');
$reactix = (object)['name' => 'reactix'];
$plugins = [$reactix];
if (!empty($plugins)){
    foreach ($plugins as $plugin){
        if (file_exists(PATH_ROOT . '/plugins/' . $plugin->name . '/Routes/Web.php')){
            include_once PATH_ROOT . '/plugins/' . $plugin->name . '/Routes/Web.php';
        }
    }
}
//$this->routes->get('admin/files', 'Backend\FilesController::index');


$this->routes->get('admin/files/(:any)/(:any)/(:any)', 'Backend\FilesController::$1:$2:$3', [\App\Middleware\AuthMiddleware::class]);
$this->routes->post('admin/files/(:any)/(:any)/(:any)', 'Backend\FilesController::$1:$2:$3', [\App\Middleware\AuthMiddleware::class]);
$this->routes->get('admin/files/(:any)/(:any)', 'Backend\FilesController::$1:$2', [\App\Middleware\AuthMiddleware::class]);
$this->routes->post('admin/files/(:any)/(:any)', 'Backend\FilesController::$1:$2', [\App\Middleware\AuthMiddleware::class]);
$this->routes->get('admin/files/(:any)', 'Backend\FilesController::$1', [\App\Middleware\AuthMiddleware::class]);
$this->routes->post('admin/files/(:any)', 'Backend\FilesController::$1', [\App\Middleware\AuthMiddleware::class]);

// authen backend
$this->routes->get('account/(:any)/(:any)/(:any)', 'Backend\AuthController::$1:$2:$3', [\App\Middleware\NoauthMiddleware::class]);
$this->routes->post('account/(:any)/(:any)/(:any)', 'Backend\AuthController::$1:$2:$3', [\App\Middleware\NoauthMiddleware::class]);
$this->routes->get('account/(:any)/(:any)', 'Backend\AuthController::$1:$2', [\App\Middleware\NoauthMiddleware::class]);
$this->routes->post('account/(:any)/(:any)', 'Backend\AuthController::$1:$2', [\App\Middleware\NoauthMiddleware::class]);
$this->routes->get('account/logout', 'Backend\AuthController::logout', [\App\Middleware\AuthMiddleware::class]);
$this->routes->get('account/profile', 'Backend\AuthController::profile', [\App\Middleware\AuthMiddleware::class]);
$this->routes->post('account/profile', 'Backend\AuthController::profile', [\App\Middleware\AuthMiddleware::class]);


$this->routes->get('account/login_google/', 'Backend\AuthController::login_google', [\App\Middleware\NoauthMiddleware::class]);
$this->routes->get('account/(:any)/', 'Backend\AuthController::$1', [\App\Middleware\NoauthMiddleware::class]);
$this->routes->post('account/(:any)', 'Backend\AuthController::$1', [\App\Middleware\NoauthMiddleware::class]);

// router admin
$this->routes->get('admin/auth/logout/', 'Backend\AuthController::logout');
$this->routes->get('admin/(:any)/(:any)/(:any)/(:any)/(:any)', 'Backend\$1Controller::$2:$3:$4:$5', [\App\Middleware\AuthMiddleware::class, \App\Middleware\RolesMiddleware::class]);
$this->routes->post('admin/(:any)/(:any)/(:any)/(:any)/(:any)', 'Backend\$1Controller::$2:$3:$4:$5', [\App\Middleware\AuthMiddleware::class, \App\Middleware\RolesMiddleware::class]);
$this->routes->get('admin/(:any)/(:any)/(:any)', 'Backend\$1Controller::$2:$3', [\App\Middleware\AuthMiddleware::class, \App\Middleware\RolesMiddleware::class]);
$this->routes->post('admin/(:any)/(:any)/(:any)', 'Backend\$1Controller::$2:$3', [\App\Middleware\AuthMiddleware::class, \App\Middleware\RolesMiddleware::class]);
$this->routes->get('admin/(:any)/(:any)', 'Backend\$1Controller::$2', [\App\Middleware\AuthMiddleware::class, \App\Middleware\RolesMiddleware::class]);
$this->routes->post('admin/(:any)/(:any)', 'Backend\$1Controller::$2', [\App\Middleware\AuthMiddleware::class, \App\Middleware\RolesMiddleware::class]);
$this->routes->get('admin/(:any)', 'Backend\$1Controller::index', [\App\Middleware\AuthMiddleware::class, \App\Middleware\RolesMiddleware::class]);
$this->routes->post('admin/(:any)', 'Backend\$1Controller::index', [\App\Middleware\AuthMiddleware::class, \App\Middleware\RolesMiddleware::class]);
$this->routes->get('admin', 'Backend\HomeController::index', [\App\Middleware\AuthMiddleware::class, \App\Middleware\RolesMiddleware::class]);

//Use Rewrite from Admin Settings Rewrites
$rewrite = option('url_rewrite', APP_LANG);
if (!empty($rewrite)) {
    // If stored data is JSON string, convert to array
    $rewrite = is_string($rewrite) ? json_decode($rewrite, true) : $rewrite;
    function ensureSlashes($url) {
        if(substr($url, 0, 1) !== '/') {
            $url = '/' . $url;
        }
        if(substr($url, -1) !== '/') {
            $url = $url . '/';
        }
        return $url;
    }
    
    // Update array with URLs that ensure "/" at beginning and end
    foreach ($rewrite as &$item) {
        if(empty($item['url_struct'])) continue;
        $item['url_struct'] = ensureSlashes($item['url_struct']);
    }
    unset($item); // release reference variable
    
    // Function to count URL segments
    function countSegments($url) {
        // Remove "/" from beginning and end
        if (empty($url)) {
            return 0;
        }
        $trimmed = trim($url, '/');
        // Split into parts based on "/"
        $segments = array_filter(explode('/', $trimmed));
        return count($segments);
    }
    
    // Iterate through each item in rewrite array
    foreach ($rewrite as $item) {
        // Check if required keys exist
        if (!isset($item['url_struct'], $item['url_function'])) {
            continue;
        }
        $url = $item['url_struct'];
        // // Replace tokens with regex placeholders
        // $url = str_replace('%slug%', '(:any)', $url);
        // $url = str_replace('%paged%', '(:num)', $url);
        // $url = str_replace('%id%', '(:num)', $url);
        // $url = str_replace('%index%', '(:any)', $url);
        
        // Count number of placeholders in $url
        $pattern = '/\(:any\)|\(:num\)/';
        preg_match_all($pattern, $url, $matches);
        $captureCount = count($matches[0]);
        
        // Build callback based on number of capture groups
        $callback = $item['url_function'];
        if ($captureCount > 0) {
            // Kiểm tra xem url_function đã có placeholder nào rồi
            $existingCaptures = [];
            preg_match_all('/\$(\d+)/', $callback, $existingMatches);
            if (!empty($existingMatches[1])) {
                $existingCaptures = array_map('intval', $existingMatches[1]);
            }
            
            // Chỉ thêm những placeholder còn thiếu
            $captures = [];
            for ($i = 1; $i <= $captureCount; $i++) {
                if (!in_array($i, $existingCaptures)) {
                    $captures[] = '$' . $i;
                }
            }
            
            // Chỉ thêm nếu có placeholder còn thiếu
            if (!empty($captures)) {
                $callback .= ':' . implode(':', $captures);
            }
        }
        $middleware = $item['middleware'] ?? [];
        if($middleware === 'false') {
            $middleware = [];
        }
        $callback = str_replace(' ', '', $callback);
        // echo '<pre>';
        // print_r($url);
        // echo "<br>";
        // print_r($callback);
        // echo '</pre>';
        
        $this->routes->get($url, $callback, $middleware);
    }
}