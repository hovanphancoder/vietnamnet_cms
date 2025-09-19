<?php
namespace App\Models;
use System\Core\BaseModel;

class UtilsModel extends BaseModel {
    // Columns that are fillable (can be added or modified)
    protected $fillable = ['name'];

    // Columns that are guarded (cannot be modified)
    protected $guarded = ['id', 'created_at'];

    /**
     * Define the table schema
     * 
     * @return array Table schema
     */
    public function _schema() {
        return [
            'id' => [
                'type' => 'int unsigned',
                'auto_increment' => true,
                'key' => 'primary',
                'null' => false
            ],
            'name' => [
                'type' => 'varchar(150)',
                'null' => false,
                'default' => ''
            ]
        ];
    }

    /**
     * Get Table Name
     */

    public function table($name = ''){
        if (empty($name)) return null;
        return 'fast_'.$name;
    }

    
    public function getDatasByTable($tableName)
    {
        return $this->list($tableName);
    }

    /**
     * Get all records
     */
    public function getUntis($where = '', $params = [], $orderBy = 'id desc', $limit = null, $offset = null) {
        return $this->list($this->table, $where, $params, $orderBy, $limit, $offset);
    }

    /**
     * Add a new record
     */
    public function addUnti($data) {
        $data = $this->fill($data);
        return $this->add($this->table, $data);
    }

    /**
     * Update an existing record
     */
    public function setUnti($id, $data) {
        $data = $this->fill($data);
        return $this->set($this->table, $data, 'id = ?', [$id]);
    }

    /**
     * Delete a record
     */
    public function delUnti($id) {
        return $this->del($this->table, 'id = ?', [$id]);
    }

    public function getAllPostTypes() {
        return $this->list($this->table('posttype'));
    }

    public function getMenus() {
        $menus = [
            [
                'type'  =>  'block',
                'label' =>  'search',
                'name'  =>  'search',
                'icon'  =>  '/uploads/assets/menus/search.png'
            ],
            [
                'type'  =>  'block',
                'label' =>  '',
                'name'  =>  'dash',
                'icon'  =>  '/uploads/assets/menus/dash.png'
            ],
            [
                'type'  =>  'block',
                'name'  =>  'login',
                'label' =>  'Get more benefits by becoming a member',
                'items' =>  [
                    [
                        'type'  =>  'button',
                        'color' =>  'red',
                        'name'  =>  'login',
                        'label' =>  'login'
                    ],
                    [
                        'type'  =>  'button',
                        'color' =>  'primary',
                        'name'  =>  'register',
                        'label' =>  'register'
                    ]
                ]
            ],
            [
                'type'  =>  'block',
                'name'  =>  'languages',
                'label' =>  'languages',
                'icon'  =>  '/uploads/assets/menus/languages.png'
            ],
            'nav' => [
                'home'    =>  [
                    'label' =>  'home',
                    'icon'  =>  '/uploads/assets/menus/home.png'
                ],
                'movie'    =>  [
                    'label' =>  'movie',
                    'icon'  =>  '/uploads/assets/menus/movie.png'
                ],
                'comic'    =>  [
                    'label' =>  'comic',
                    'icon'  =>  '/uploads/assets/menus/comic.png'
                ],
                'game'    =>  [
                    'label' =>  'game',
                    'icon'  =>  '/uploads/assets/menus/game.png'
                ],
                'novel'    =>  [
                    'label' =>  'novel',
                    'icon'  =>  '/uploads/assets/menus/novel.png'
                ],
                'chat'    =>  [
                    'label' =>  'chat',
                    'icon'  =>  '/uploads/assets/menus/chat.png'
                ],
            ],
            'languages' => [
                'name'  =>  'languages',
                'label' =>  'languages',
                'icon'  =>  '/uploads/assets/menus/languages.png'
            ]
        ];

        return $menus;
    }
    public function getNavbar() {
        $navbar = [
            'home'    =>  [
                'label' =>  'home',
                'active' =>  true,
                'icon'  =>  '/uploads/assets/menus/home.png'
            ],
            'movie'    =>  [
                'label' =>  'movie',
                'icon'  =>  '/uploads/assets/menus/movie.png'
            ],
            'game'    =>  [
                'label' =>  'game',
                'type'  =>  'bigsize',
                'icon'  =>  '/uploads/assets/menus/game.png'
            ],
            'comic'    =>  [
                'label' =>  'comic',
                'icon'  =>  '/uploads/assets/menus/comic.png'
            ],
            'chat'    =>  [
                'label' =>  'chat',
                'icon'  =>  '/uploads/assets/menus/chat.png'
            ]
        ];
        return $navbar;
    }
}