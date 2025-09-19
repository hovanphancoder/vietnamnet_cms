<?php
// TODO: update version is unavaliable, need to update
//Controller draft, don't worry about it, ignore
namespace App\Controllers\Backend;

use App\Libraries\Fastlang as Flang;

class PluginsController extends LibraryController
{
    public function __construct()
    {
        parent::__construct();

        // Initialize manager with plugin settings
        $this->initializeManager(
            'plugins',
            PATH_ROOT . '/plugins',
            'plugins_active',
            'Backend/plugins_index'
        );

        Flang::load('general', APP_LANG);
        Flang::load('plugins', APP_LANG);
    }
}
