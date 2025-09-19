<?php
// Routes cho plugin Reviews



$this->routes->get('api/reactix/(:any)/(:any)/(:num)/paged/(:num)', 'ReactixController::$1:$2:$3:$4', [], 'Reactix');
$this->routes->get('api/reactix/(:any)/(:any)/(:num)', 'ReactixController::$1:$2:$3', [], 'Reactix');
$this->routes->get('api/reactix/(:any)/(:num)', 'ReactixController::$1:$2', [], 'Reactix');


$this->routes->post('api/reactix/(:any)', 'ReactixController::$1', [], 'Reactix');
