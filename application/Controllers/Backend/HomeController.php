<?php
//Controller draft, don't worry about it, ignore
namespace App\Controllers\Backend;

use App\Controllers\BackendController;
use System\Libraries\Render;
use App\Libraries\Fastlang as Flang;
use App\Models\FastModel;

class HomeController extends BackendController {
    protected $assets;

    public function __construct()
    {
        parent::__construct();
        Flang::load('general', APP_LANG);
    }

    /**
     * Display list of movies
     */
    public function index() {
        // tự động lấy tên của các file trong thư mục themes/cmsfullform/Backend/HomeComponent
        $component = [];
        $files = glob(APP_THEME_PATH . 'Backend/HomeComponent/*.php');
        foreach ($files as $file) {
            $component[] = basename($file, '.php');
        }
        $this->data('component', $component);
        $this->data('title', Flang::_e('tile_languages'));
        $result = Render::html('Backend/home_index', $this->data);
        echo $result;
    }
}
