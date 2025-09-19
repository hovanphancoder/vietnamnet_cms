<?php

define('APP_DEBUGBAR', true); //set to true if you want DEBUG
if (APP_DEBUGBAR){
    // Start measurement from when framework starts running
    define('APP_START_TIME', microtime(true));
    define('APP_START_MEMORY', memory_get_usage());
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    $GLOBALS['debug_sql'] = [];
    error_reporting(E_ALL);
}

// Read public/define.md to get more information about the path
define('APP_VER', '2.1.0');
// Path to application root directory
define('PATH_ROOT', realpath(__DIR__ . '/../'));
define('PATH_APP', realpath(PATH_ROOT . '/application/').'/');
define('PATH_SYS', realpath(PATH_ROOT . '/system/').'/');
define('PATH_WRITE', realpath(PATH_ROOT . '/writeable/').'/');
define('PATH_PLUGINS', realpath(PATH_ROOT . '/plugins/').'/');
define('PATH_THEMES', realpath(PATH_ROOT . '/themes/').'/');
// Auto parse JSON input into $_POST and $_REQUEST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && 
    isset($_SERVER['CONTENT_TYPE']) && 
    stripos($_SERVER['CONTENT_TYPE'], 'application/json') === 0) {

    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (is_array($data)) {
        $_POST = $data;
        $_REQUEST = array_merge($_REQUEST, $data);
    }
}
 // Load Core_helper.php to be able to use load_helpers function
require_once PATH_SYS . 'Helpers/Core_helper.php';
load_helpers(['uri', 'security']); // Load helpers like Uri_helper, Security_helper or other helpers you want to autoload.
// Load plugins
autoload_plugins();

$themeName = config('theme');
$themeName = $themeName['theme_name'] ?? 'default';
define('APP_THEME_NAME', $themeName);
define('APP_THEME_PATH', realpath(PATH_ROOT . '/themes/')."/$themeName/"); unset($themeName);

// Load init Languages
require_once PATH_ROOT . '/application/Config/Languages.php';

// Load list Posttype active for languages APP_LANG
$objectPosttypes = require PATH_ROOT . '/application/Config/Posttype.php';
$listPosttypes = [];
if (!empty($objectPosttypes) && is_array($objectPosttypes)) {
    foreach ($objectPosttypes as $key => $item) {
        if (isset($item['languages']) && (in_array(APP_LANG, $item['languages']) || in_array('all', $item['languages']))) {
            $listPosttypes[] = $key;
        }
    }
    $listPosttypes[] = 'pages';
    define('APP_POSTTYPES', $listPosttypes);
    unset($listPosttypes, $objectPosttypes);
}

// Load autoload from Composer
if (file_exists(PATH_ROOT . '/vendor/autoload.php')){
    require_once PATH_ROOT . '/vendor/autoload.php';
}else{
    echo '<h1>Please download version have vendor, or run command: composer install (or run: php composer.phar install) (<a href="https://getcomposer.org/download/" rel="nofollow">Download composer.phar here!</a>)</h1>';
    exit();
}

// load shortcode
load_helpers(['shortcode']);
\System\Libraries\Shortcode::init();
// Load Bootstrap file to start the system
require_once PATH_SYS . 'Core/Bootstrap.php';
// Start Bootstrap of the framework
$application = new \System\Core\Bootstrap();
$application->run();
