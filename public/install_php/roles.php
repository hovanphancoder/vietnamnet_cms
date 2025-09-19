<?php
//Setting Default Roles
return [
    'admin' => [
        'Backend\Home' => ["index"],
        'Backend\Files' => ["index", "add", "edit", "delete", 'manage'],
        'Backend\Posttype' => ["index", 'edit', 'add', 'delete', 'copy', 'changestatus'],
        'Backend\Posts' => ["index", 'edit', 'add', 'delete', 'clone', 'copy', 'import'],
        'Backend\Terms' => ["index", 'edit', 'add', 'delete', 'gettermsbylang'],
        'Backend\Me' => ["index", 'profile'],
        'Backend\Users' => ["index", 'edit', 'add', 'delete', 'changestatus', 'update_status'],
        'Backend\Options' => ["index", 'add', 'edit','delete'],
        'Backend\Languages' => ["index", 'add', 'edit', 'setdefault', 'changestatus', 'delete'],
        'Backend\Crawl' => ["index", 'add', 'edit', 'setdefault', 'changestatus', 'getlist', 'comic', 'addchapters'],
        'Api\Files' => ["index", 'add', 'edit', 'delete', 'move', 'copy', 'rename'],
        'Backend\Plugins' => ["index", "action", "upload", "uploadWithOverwrite"],
        'Backend\Themes' => ["index", "action", "upload", "uploadWithOverwrite"],
        
    ],
    'moderator' => [
        'Backend\Home' => ["index"],
        'Backend\Files' => ["index", "add", "edit", "delete", 'manage'],
        'Backend\Posts' => ["index", 'edit', 'add', 'clone', 'delete'],
        'Backend\Terms' => ["index", 'edit', 'add'],
        'Backend\Users' => [],
        'Backend\Me' => ["index", 'profile'],
    ],
    'author' => [
        'Backend\Home' => ["index"],
        'Backend\Files' => ["index", "add", "edit", "delete", 'manage'],
        'Backend\Posts' => ["index", 'edit', 'add', 'clone', 'delete'],
        'Backend\Terms' => ["index"],
        'Backend\Users' => [],
        'Backend\Me' => ["index", 'profile'],
    ],
    'member' => [
        'Backend\Home' => ["index"],
        'Backend\Users' => [],
        'Backend\Me' => ["index", 'profile'],
    ],
    // Add other roles as needed
];
