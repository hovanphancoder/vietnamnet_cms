<?php
namespace App\Controllers\Backend;

use App\Controllers\BackendController;
use App\Models\FilesModel;
use App\Libraries\Fastlang as Flang;
use System\Libraries\Render;

class FilesController extends BackendController {

    protected $filesModel;

    public function __construct()
    {
        parent::__construct();
        load_helpers(['backend']);
        $this->filesModel = new FilesModel();
        $config_files = config('files');
        unset($config_files['storage_path']);
        $this->data('config_files', $config_files);
        Flang::load('files', APP_LANG);
    }

    public function index(){
        $this->data('title', 'Files List - Timeline');
        Render::asset('js', 'js/jfast.1.2.3.js', ['area' => 'backend', 'location' => 'head']);
        Render::asset('js', 'js/iMagify.1.1.js', ['area' => 'backend', 'location' => 'head']);
        echo Render::html('Backend/files_index', $this->data);
    }

    public function timeline() {
        // try {
        //     $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        //     $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
        //     $search = isset($_GET['q']) ? $_GET['q'] : '';

        //     $where = '';
        //     $params = [];

        //     if (!empty($search)) {
        //         $where = 'name LIKE ?';
        //         $params[] = '%' . $search . '%';
        //     }
        //     $files = $this->filesModel->getFiles($where, $params, 'created_at DESC', $page, $limit);
        // } catch (\Exception $e) {
        //     $this->error($e->getMessage(), [], 500);
        // }
        // $this->data('files', $files);
        $this->data('title', 'Files List - Timeline');
        Render::asset('js', 'js/jfast.1.2.3.js', ['area' => 'backend', 'location' => 'head']);
        Render::asset('js', 'js/iMagify.1.1.js', ['area' => 'backend', 'location' => 'head']);
        echo Render::html('Backend/files_timeline', $this->data);
    }

    public function manage() {
        echo Render::html('Backend/Files/manage', $this->data);        
    }

  

  
   
}


