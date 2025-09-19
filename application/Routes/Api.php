<?php
$plugins = option('plugins_active');
if (!empty($plugins)){
    if (is_string($plugins)) {
        $plugins = json_decode($plugins, true);
    }    
    if (is_array($plugins) && !empty($plugins)){
        foreach ($plugins as $plugin){
            if (file_exists(PATH_APP . 'Plugins/' . $plugin['name'] . '/Routes/Api.php')){
                include_once PATH_APP . 'Plugins/' . $plugin['name'] . '/Routes/Api.php';
                // $this->routes->get('/api/fastshop/(:any)/', 'Api\CartController::$1', [], 'fastshop');
            }
        }
    }
}

include_once PATH_ROOT . '/plugins/reactix/Routes/Api.php';

$this->routes->get('/api/v1/auth/(:any)/', 'Api\V1\AuthController::$1');
$this->routes->post('/api/v1/auth/(:any)', 'Api\V1\AuthController::$1');


$this->routes->get('/api/v1/posts/(:any)/(:any)/(:any)/(:any)/paged/(:num)/', 'Api\V1\PostsController::$1:$2:$3:$4:$5');
$this->routes->get('/api/v1/posts/(:any)/(:any)/(:any)/(:any)/(:num)/', 'Api\V1\PostsController::$1:$2:$3:$4:$5');
$this->routes->post('/api/v1/posts/(:any)/(:any)/(:any)/(:any)/(:num)/', 'Api\V1\PostsController::$1:$2:$3:$4:$5');
$this->routes->get('/api/v1/posts/(:any)/(:any)/(:any)/(:num)/', 'Api\V1\PostsController::$1:$2:$3:$4');
$this->routes->post('/api/v1/posts/(:any)/(:any)/(:any)/(:num)/', 'Api\V1\PostsController::$1:$2:$3:$4');
$this->routes->get('/api/v1/(:any)/(:any)/(:any)/(:any)/(:num)', 'Api\V1\$1Controller::$2:$3:$4');
$this->routes->get('/api/v1/(:any)/(:any)/(:any)/(:any)', 'Api\V1\$1Controller::$2:$3:$4');
$this->routes->post('/api/v1/(:any)/(:any)/(:any)/(:any)', 'Api\V1\$1Controller::$2:$3:$4');
$this->routes->get('/api/v1/(:any)/(:any)/(:any)', 'Api\V1\$1Controller::$2:$3');
$this->routes->post('/api/v1/(:any)/(:any)/(:any)', 'Api\V1\$1Controller::$2:$3');
$this->routes->get('/api/v1/(:any)/(:any)', 'Api\V1\$1Controller::$2');
$this->routes->post('/api/v1/(:any)/(:any)', 'Api\V1\$1Controller::$2');
$this->routes->delete('/api/v1/(:any)/(:any)', 'Api\V1\$1Controller::$2');

$this->routes->get('api/(:any)/(:any)/(:any)/(:any)', 'Api\$1Controller::$2:$3:$4');
$this->routes->get('api/(:any)/(:any)/(:any)/', 'Api\$1Controller::$2:$3');


// For file and auth controller not through /v1/
$this->routes->get('api/(:any)/(:any)', 'Api\$1Controller::$2');
$this->routes->post('api/(:any)/(:any)', 'Api\$1Controller::$2');
$this->routes->put('api/(:any)/(:any)', 'Api\$1Controller::$2');
$this->routes->delete('api/(:any)/(:any)', 'Api\$1Controller::$2');

// Register routes for API
// API Auth 
// $this->routes->get('/api/v1/auth/(:any)/', 'Api\V1\AuthController::$2');
// $this->routes->post('/api/v1/auth/(:any)', 'Api\V1\AuthController::$2');

// $this->routes->get('/api/v1/posts/(:any)/(:any)/(:num)/(:num)/', 'Api\V1\PostsController::$2:$3:$4:$5');

// $this->routes->get('/api/v1/(:any)/(:any)/(:any)/(:any)', 'Api\V1\$2Controller::$3:$4:$5:$1');
// $this->routes->post('/api/v1/(:any)/(:any)/(:any)/(:any)', 'Api\V1\$2Controller::$3:$4:$5:$1');
// $this->routes->get('/api/v1/(:any)/(:any)/(:any)', 'Api\V1\$2Controller::$3:$4:$1');
// $this->routes->post('/api/v1/(:any)/(:any)/(:any)', 'Api\V1\$2Controller::$3:$4:$1');
// $this->routes->get('/api/v1/(:any)/(:any)', 'Api\V1\$2Controller::$3:$1');
// $this->routes->post('/api/v1/(:any)/(:any)', 'Api\V1\$2Controller::$3:$1');

// $this->routes->get('api/v1/(:any)/(:any)', 'Api\V1\$1Controller::$2');
// $this->routes->post('api/v1/(:any)/(:any)', 'Api\V1\$1Controller::$2');
// $this->routes->put('api/v1/(:any)/(:any)', 'Api\V1\$1Controller::$2');
// $this->routes->delete('api/v1/(:any)/(:any)', 'Api\V1\$1Controller::$2');

