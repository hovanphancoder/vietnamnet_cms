<?php

namespace App\Blocks\Backend\Sidebar;

use App\Models\UtilsModel;
use System\Core\BaseBlock;
use App\Libraries\Fastlang as Flang;

class SidebarBlock extends BaseBlock
{
    private $listLang = ['en', 'vi', 'fr', 'de', 'es'];
    public function __construct()
    {
        $this->setLabel('Backend\Sidebar Block');
        $this->setName('Backend\Sidebar');
        $this->setProps([
            'layout'      => 'default',
            'url'         => $_SERVER['REQUEST_URI']
        ]);
    }

    // đây là function xử lý data bắt buộc phải có
    public function handleData()
    {
        Flang::load('general', APP_LANG);
        $UtilsModel = new UtilsModel();
        $postTypes = $UtilsModel->getAllPostTypes();
        
        // Xác định posttype hiện tại từ URL parameters
        $currentPostType = $this->getCurrentPostType();

        // Overview Group
        $overviewGroup = [
            "id" => "overview",
            "label" => Flang::_e("overview"),
            "items" => [
                [
                    "id" => "dashboard",
                    "label" => Flang::_e("dashboard"),
                    "href" => admin_url('home'),
                    "icon" => 'home',
                    "children" => []
                ]
            ]
        ];

        // Media Management Group
        $mediaGroup = [
            "id" => "media",
            "label" => Flang::_e("media management"),
            "items" => [
                [
                    "id" => "files",
                    "label" => Flang::_e("files manager"),
                    "href" => "#",
                    "icon" => 'folder',
                    "children" => [
                        [
                            "id" => "files-manager",
                            "label" => Flang::_e("files timeline"),
                            "href" => admin_url('files'),
                            "icon" => 'sliders'
                        ]
                    ]
                ]
            ]
        ];

        // Posts Management Group
        $postsGroup = [
            "id" => "posts",
            "label" => Flang::_e("posts management"),
            "items" => []
        ];

        // Thêm Post Types vào Posts Management
        // THEME_NAME/Backend/posttype_index.php
        if (file_exists(APP_THEME_PATH . '/Backend/posttype_index.php')) {
            $postsGroup["items"][] = [
                "id" => "posttypes",
                "label" => Flang::_e("post types"),
                "href" => "#",
                "icon" => 'file-text',
                "children" => [
                    [
                        "id" => "list-posttypes",
                        "label" => Flang::_e("list") . " " . Flang::_e("post types"),
                        "href" => admin_url('posttype/index'),
                        "icon" => 'list'
                    ],
                    [
                        "id" => "add-posttype",
                        "label" => Flang::_e("add") . " " . Flang::_e("post type"),
                        "href" => admin_url('posttype/add'),
                        "icon" => 'plus'
                    ]
                ]
            ];
        }

        // Thêm các Post Types động vào Posts Management
        foreach ($postTypes as $postType) {
            if (isset($postType['status']) && strtolower($postType['status']) === 'active') {
                if (empty($postType['slug']) || empty($postType['menu']) || $postType['menu'] != $postType['slug']) {
                    continue;
                }

                $postTypeName = htmlspecialchars($postType['name']);
                $postTypeSlug = htmlspecialchars($postType['slug']);

                $children = [
                    [
                        "id" => "list-$postTypeSlug",
                        "label" => Flang::_e("list") . " " . Flang::_e($postTypeName),
                        "href" => admin_url("posts/index") . '?type=' . $postTypeSlug,
                        "icon" => 'list'
                    ],
                    [
                        "id" => "add-$postTypeSlug",
                        "label" => Flang::_e("add") . " " . Flang::_e($postTypeName),
                        "href" => admin_url("posts/add") . '?type=' . $postTypeSlug,
                        "icon" => 'plus'
                    ]
                ];

                // Thêm subtypes
                foreach ($postTypes as $subtype) {
                    if (empty($subtype['name']) || $subtype['menu'] == $subtype['slug']) {
                        continue;
                    }
                    if ($subtype['menu'] == $postType['slug']) {
                        $children[] = [
                            "id" => "list-{$subtype['slug']}",
                            "label" => Flang::_e("list") . " " . Flang::_e($subtype['name']),
                            "href" => admin_url("posts/index") . '?type=' . $subtype['slug'],
                            "icon" => 'database'
                        ];
                    }
                }

                // Thêm terms
                $postType['terms'] = is_string($postType['terms']) ? json_decode($postType['terms'], true) : $postType['terms'];
                if (isset($postType['terms']) && is_array($postType['terms'])) {
                    foreach ($postType['terms'] as $term) {
                        if (empty($term['name']) || empty($term['type'])) {
                            continue;
                        }
                        $termType = htmlspecialchars($term['type']);
                        $children[] = [
                            "id" => "list-{$postTypeSlug}-{$termType}",
                            "label" => Flang::_e("list") . " " . Flang::_e($termType) . " " . Flang::_e($postTypeName),
                            "href" => admin_url("terms/index") . '?posttype=' . $postTypeSlug . '&type=' . $termType,
                            "icon" => 'grid'
                        ];
                    }
                }

                $isExpanded = ($currentPostType && trim($currentPostType) === trim($postTypeSlug));
                
                $postsGroup["items"][] = [
                    "id" => $postTypeSlug,
                    "label" => Flang::_e("$postTypeSlug"),
                    "href" => "#",
                    "icon" => 'edit',
                    "children" => $children,
                    "expanded" => $isExpanded // Tự động mở rộng nếu là posttype hiện tại
                ];
            }
        }

        // Users & Permissions Group
        $usersGroup = [
            "id" => "users",
            "label" => Flang::_e("users permissions"),
            "items" => [
                [
                    "id" => "users",
                    "label" => Flang::_e("users manager"),
                    "href" => "#",
                    "icon" => 'users-2',
                    "children" => [
                        [
                            "id" => "list-users",
                            "label" => Flang::_e("list users"),
                            "href" => admin_url('users/index'),
                            "icon" => 'users-2'
                        ],
                        [
                            "id" => "add-user",
                            "label" => Flang::_e("add user"),
                            "href" => admin_url('users/add'),
                            "icon" => 'user-plus'
                        ]
                    ]
                ]
            ]
        ];

        // Site Management Group
        $siteGroup = [
            "id" => "site",
            "label" => Flang::_e("site management"),
            "items" => [
                [
                    "id" => "plugins",
                    "label" => Flang::_e("plugins"),
                    "href" => admin_url('plugins'),
                    "icon" => 'puzzle',
                    "children" => []
                ],
                [
                    "id" => "themes",
                    "label" => Flang::_e("themes"),
                    "href" => admin_url('themes'),
                    "icon" => 'monitor',
                    "children" => []
                ],
                [
                    "id" => "settings",
                    "label" => Flang::_e("site settings"),
                    "href" => "#",
                    "icon" => 'settings',
                    "children" => [
                        [
                            "id" => "list-settings",
                            "label" => Flang::_e("site settings"),
                            "href" => admin_url('options/index'),
                            "icon" => 'settings'
                        ],
                        [
                            "id" => "add-setting",
                            "label" => Flang::_e("add option"),
                            "href" => admin_url('options/add'),
                            "icon" => 'plus'
                        ]
                    ]
                ],
                [
                    "id" => "languages",
                    "label" => Flang::_e("languages"),
                    "href" => admin_url('languages'),
                    "icon" => 'globe',
                    "children" => [
                        [
                            "id" => "list-settings",
                            "label" => Flang::_e("list languages"),
                            "href" => admin_url('languages/index'),
                            "icon" => 'settings'
                        ],
                        [
                            "id" => "add-setting",
                            "label" => Flang::_e("add language"),
                            "href" => admin_url('languages/?showform=true'),
                            "icon" => 'plus'
                        ]
                    ]
                ]
            ]
        ];

        // Development Group - Lấy menu từ plugins active
		$childs_development = [
			[
				"id" => "database",
				"label" => Flang::_e("backup & restore"),
				"href" => admin_url('database/index'),
				"icon" => 'database',
				"children" => [
					[
						"id" => "database-settings",
						"label" => Flang::_e("setting backup"),
						"href" => admin_url('database/settings'),
						"icon" => 'settings'
					],
					[
						"id" => "database-index",
						"label" => Flang::_e("list backups"),
						"href" => admin_url('database/index'),
						"icon" => 'database'
					]
				]
			],
			[
				"id" => "restful",
				"label" => Flang::_e("api restful"),
				"href" => admin_url('restful/index'),
				"icon" => 'code',
				"children" => [
					[
						"id" => "cms-documentation",
						"label" => Flang::_e("documentation"),
						"href" => 'https://docs.cmsfullform.com',
						"icon" => 'file-text'
					],
					[
						"id" => "restful-index",
						"label" => Flang::_e("API Keys"),
						"href" => admin_url('restful/index'),
						"icon" => 'key'
					]
				]
			]	
		];
		$childs_development = array_merge($childs_development, $this->loadPluginMenus() );
        $developmentGroup = [
            "id" => "development",
            "label" => Flang::_e("development"),
            "items" => $childs_development
        ];

        // Tổng hợp menu data
        $menuData = [
            $overviewGroup,
            $mediaGroup,
            $postsGroup,
            $usersGroup,
            $siteGroup,
            $developmentGroup
        ];

        $data['menuData'] = $menuData;
        // $data['user_info'] = $props['user_info'];
        return $data;
    }

    /**
     * Load menu items from active plugins
     */
    private function loadPluginMenus()
    {
        $menuItems = [];

        // Lấy danh sách plugins active từ Options
        $activePlugins = option('plugins_active');
        if ($activePlugins && is_string($activePlugins)) {
            $activePlugins = json_decode($activePlugins, true);
        }

        if (is_array($activePlugins)) {
            foreach ($activePlugins as $plugin) {
                $pluginName = $plugin['name'] ?? '';
                if (empty($pluginName)) continue;

                $pluginConfigPath = PATH_ROOT . '/plugins/' . $pluginName . '/Config/Config.php';

                if (file_exists($pluginConfigPath)) {
                    $pluginConfig = include $pluginConfigPath;

                    // Kiểm tra xem plugin có menu không
                    if (isset($pluginConfig['menu']) && is_array($pluginConfig['menu'])) {
                        foreach ($pluginConfig['menu'] as $menuItem) {
                            // Đảm bảo menu item có đủ thông tin cần thiết
                            if (isset($menuItem['id']) && isset($menuItem['label']) && isset($menuItem['href'])) {
                                $menuItems[] = [
                                    "id" => $menuItem['id'],
                                    "label" => $menuItem['label'],
                                    "href" => $menuItem['href'],
                                    "icon" => $menuItem['icon'] ?? 'puzzle',
                                    "children" => $menuItem['children'] ?? []
                                ];
                            }
                        }
                    }
                }
            }
        }

        return $menuItems;
    }

    function getLangSlugFromUrl($url, $supportedLangs)
    {
        $parsedUrl = parse_url($url);
        $path = isset($parsedUrl['path']) ? $parsedUrl['path'] : '';
        $segments = explode('/', trim($path, '/'));

        if (!empty($segments) && in_array($segments[0], $supportedLangs)) {
            return '/' . $segments[0];
        }
        return '';
    }

    /**
     * Xác định posttype hiện tại từ URL parameters
     */
    private function getCurrentPostType()
    {
        // Kiểm tra URL path để xác định context
        $currentUrl = $_SERVER['REQUEST_URI'] ?? '';
        $parsedUrl = parse_url($currentUrl);
        $path = $parsedUrl['path'] ?? '';
        
        // Nếu đang ở trang posts, check $_GET['type']
        if (preg_match('/\/admin\/posts\//', $path)) {
            if (isset($_GET['type']) && !empty($_GET['type'])) {
                return $_GET['type'];
            }
        }
        
        // Nếu đang ở trang terms, check $_GET['posttype'] (KHÔNG check $_GET['type'])
        if (preg_match('/\/admin\/terms\//', $path)) {
            if (isset($_GET['posttype']) && !empty($_GET['posttype'])) {
                return $_GET['posttype'];
            }
        }
        
        return null;
    }
}
