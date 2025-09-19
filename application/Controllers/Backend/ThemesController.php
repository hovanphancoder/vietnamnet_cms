<?php
// TODO: update version is unavaliable, need to update
//Controller draft, don't worry about it, ignore
namespace App\Controllers\Backend;

use App\Libraries\Fastlang as Flang;

class ThemesController extends LibraryController
{
    public function __construct()
    {
        parent::__construct();

        // Initialize manager with theme settings
        $this->initializeManager(
            'themes',
            PATH_ROOT . '/themes',
            'themes_active',
            'Backend/themes_index'
        );

        Flang::load('general', APP_LANG);
        Flang::load('themes', APP_LANG);
    }
}
