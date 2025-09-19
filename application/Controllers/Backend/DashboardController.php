<?php
//# Overview page for admin. This part is not important, just like the admin homepage

namespace App\Controllers\Backend;

use App\Controllers\BackendController;
use System\Libraries\Session;

class DashboardController extends BackendController {

    public function __construct()
    {
        parent::__construct();
    }
    
    public function index() {
        Session::start();
        print_r($_SESSION);
    }

    public function index2($item = '', $item2 = ''){
        echo $item.'.'.$item2;
    }
}
