<?php
namespace App\Controllers;

use System\Core\BaseController;
use App\Libraries\Fastlang as Flang;
use System\Libraries\Session;

/**
 * AppController
 * 
 * Base controller for other "backend" controllers.
 * Automatically loads helpers, initializes sidebar, header, footer, etc.
 */
class BackendController extends BaseController
{
    protected $post_lang;

    /**
     * Constructor
     * - Load helpers
     * - Initialize assets with default CSS/JS
     * - Pre-render layout parts (header, footer, sidebar)
     */
    public function __construct()
    {
        // Call parent BaseController constructor (to maintain common functionality)
        parent::__construct();
        // User ID Global variable
        global $me_info;
        if (empty($me_info)){
            $me_id = Session::get('user_id');
            $usersModel = new \App\Models\UsersModel();
            $me_info = $usersModel->getUserById($me_id);
            if (empty($me_info)) {
                redirect(auth_url('logout'));
            }
        }
        // Load 'backend' helper
        load_helpers(['backend', 'database', 'languages']);
        Flang::load('Backend/Global');
        //Flang::load('general', APP_LANG);
        $this->post_lang = S_GET('post_lang') ?? APP_LANG;
        
    }
}