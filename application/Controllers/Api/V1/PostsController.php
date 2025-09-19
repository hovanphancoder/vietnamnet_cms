<?php

namespace App\Controllers\Api\V1;

use App\Controllers\ApiController;
use App\Models\UsersModel;
use App\Libraries\Fastlang as Flang;
use App\Models\LanguagesModel;
use System\Libraries\Session;
use App\Models\PostsModel;
use App\Models\UtilsModel;
use App\Models\PostrelModel;
use System\Libraries\Validate;
use App\Models\ReviewsModel;
use System\Core\AppException;
use App\Libraries\Fasttoken;
use System\Drivers\Cache\UriCache;

class PostsController extends ApiController {

    protected $postsModel;
    protected $reviewsModel;
    protected $termsModel;
    protected $LanguagesModel;
    protected $usersModel;
    protected $cache;
    protected $current_lang;

    public function __construct() {
        parent::__construct();
        load_helpers(['backend', 'frontend', 'images']);
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Headers: Accept, Authorization, Content-Type, User-Agent, X-Requested-With');
        Flang::load('posts', APP_LANG);
        $this->postsModel = new PostsModel();
        $this->usersModel = new UsersModel();
        $this->reviewsModel = new ReviewsModel();
        $this->LanguagesModel = new LanguagesModel();
        $this->cache = new UriCache(5, 'json');
        $this->cache->cacheLogin(true); 
    }

    public function index($posttype) {
        exit;
    }
    
    public function lists($posttype = '', $category = '', $page = '', $page2 = '') {
        if(!empty($posttype) && !empty($category) && $category !== 'paged' && strlen($category) > 2) {  
            if($category === 'related') {
                $this->list_by_related($posttype, $page, $page2);
            } else {
                $this->list_by_category($posttype, $category, $page, $page2);
            }
        }
        try {
            $cachedata = $this->cache->get();
            if (!empty($cachedata)) {
                $this->cache->headers();
                echo $cachedata;
                exit();
            }else{ 
                if(!empty($posttype) ){  
                    if(empty($lang)){
                        $lang = APP_LANG;
                    }
                    $page = (int) $page;
                    $limit = 10;
                     
                    // kiểm tra posttype có tồn tại hoặc là không
                    $this->postsModel = new PostsModel($posttype, $lang);
                    if (!$this->postsModel->checkPosttypeExists()) {
                        return $this->error("posttype_does_not_exist", [], 404);
                    }
                    // $posttype_data = $this->postsModel->getPostBySlug('fast_posttype', $posttype);
                    
                    $sort = S_GET('sortby') ?? '';
                    $sort = strtolower($sort);
                    
                    switch ($sort) {
                        case 'views_day__desc':
                            $sort = 'views_day__desc';
                            $title = Flang::_e('trending');
                            break;
                        case 'updated_at__desc':
                            $sort = 'updated_at__desc';
                            $title = Flang::_e('last_updated');
                            break;
                        case 'created_at__desc':
                            $sort = 'created_at__desc';
                            $title = Flang::_e('newest');
                            break;
                        case 'like_count__desc':
                            $sort = 'like_count__desc';
                            $title = Flang::_e('likes');
                            break;
                        case 'views_week__desc':
                            $sort = 'views_week__desc';
                            $title = Flang::_e('views');
                            break;
                        case 'rating_total':
                            $sort = 'rating_total';
                            $title = Flang::_e('rating');
                            break;
                        default:
                            $sort = 'updated_at__desc';
                            break;
                    }
                    if(!empty($sort)) {
                        list($key, $value) = explode('__', $sort);
                        $data_filter['sort'] = [$key, $value];
                    } 
                    if(!empty($filter)) {
                        list($key, $value) = explode('__', $filter);
                        $data_filter['filter'] = [$key, $value];
                    }
                    $fields = 'id, title, slug, lang_slug, status, seo_title, seo_desc, rating_total, rating_count, views_day, views, views_week, banner, feature, like_count';
                    $where = '';
                    $params = [];
                    $orderby = [];
                    if(!empty($posttype) && !empty($data_filter)) { 
                        $sort_data = $this->_handle_sort($data_filter);
                    }

                    if(!empty($sort_data['status']) && $sort_data['status'] === 'not_found') {
                        $result = $this->get_error(Flang::_e('post_not_found'), [], 404);
                        echo json_encode($result);
                        // $this->cache->set(json_encode($result));
                        exit();
                    }
                    
                    
                    if(!empty($sort_data['posts_data'])) {
                        $fields = 'id, title, slug, lang_slug, status, seo_title, seo_desc, rating_total, rating_count, views_day, views, views_week, banner, feature, like_count, release_date';
                        
                        $fields = is_string($fields) ? array_map('trim', explode(',', $fields)) : $fields;

                        // Create a proper key => value array for comparison
                        $field_keys = array_fill_keys($fields, '');
                        
                        $posts['data'] = $sort_data['posts_data'];
                        $posts['data'] = array_map(function ($movie) use ($field_keys) {
                            return array_intersect_key($movie, $field_keys);
                        }, $posts['data']);
                    } else {
                        $where = $sort_data['where'] ?? '';
                        $params = $sort_data['params'] ?? [];
                        $orderby = $sort_data['orderby'] ?? [];
                        $posts = $this->postsModel->getPostsFieldsPagination($fields,  $where, $params, $orderby, $page, $limit);
                    }
                    
                    
                   

                    if (!empty($posts['data'])) {
                        foreach ($posts['data'] as $key => $post) {
                            $postTmp = $post;
                            $terms = $this->postsModel->getPostTermsByPostId($posttype, $postTmp['id'], APP_LANG);
                            $termsNew = [];
                            if(!empty($terms)) { 
                                foreach ($terms as $term) {
                                    unset($term['id_main']);
                                    unset($term['updated_at']);
                                    unset($term['created_at']);
                                    $termsNew[$term['type']][] = $term;
                                }
                            }

                            if (!empty($postTmp['feature'])){
                                $postTmp['feature'] = json_decode($postTmp['feature']);
                                if (!empty($postTmp['feature']->path)){
                                    $postTmp['feature']->path = '/uploads/'.$postTmp['feature']->path;
                                    $postTmp['feature']->square = img_square($postTmp['feature']);
                                    $postTmp['feature']->path = img_vertical($postTmp['feature']);
                                    if (!empty($postTmp['feature']->resize)){
                                        unset($postTmp['feature']->resize);
                                    }
                                    if (!empty($postTmp['feature']->name)){
                                        unset($postTmp['feature']->name);
                                    }
                                }else{
                                    $postTmp['feature'] = null;
                                }
                            }
                            if (!empty($postTmp['banner'])){
                                $postTmp['banner'] = json_decode($postTmp['banner']);
                                if (!empty($postTmp['banner']->path)){
                                    $postTmp['banner']->path = '/uploads/'.$postTmp['banner']->path;
                                    $postTmp['banner']->square = img_square($postTmp['banner']);
                                    $postTmp['banner']->path = img_vertical($postTmp['banner']);
                                    if (!empty($postTmp['banner']->resize)){
                                        unset($postTmp['banner']->resize);
                                    }
                                    if (!empty($postTmp['banner']->name)){
                                        unset($postTmp['banner']->name);
                                    }
                                }else{
                                    $postTmp['banner'] = null;
                                }
                            }

                            if(!empty($termsNew)) {
                                $postTmp['terms'] = $termsNew;
                            }

                            $posts['data'][$key] = $postTmp;
                        }
                    } else {
                        $result = $this->get_error(Flang::_e('post_not_found'), [], 404);
                        echo json_encode($result);
                        $this->cache->set(json_encode($result));
                        exit();
                    }
                    $posts['title'] = $title ?? '';
                    $posts['sort'] = [
                        [
                            'id' => 1,
                            'name' => Flang::_e('trending'),
                            'slug' => 'views_day__desc' 
                        ],
                        [
                            'id' => 2,
                            'name' => Flang::_e('last_updated'),
                            'slug' => 'updated_at__desc'
                        ],
                        [
                            'id' => 3,
                            'name' => Flang::_e('newest'),
                            'slug' => 'created_at__desc'
                        ],
                        [
                            'id' => 4,
                            'name' => Flang::_e('likes'),
                            'slug' => 'like_count__desc'
                        ],
                        [
                            'id' => 5,
                            'name' => Flang::_e('views'),
                            'slug' => 'views_week__desc'
                        ],
                        [
                            'id' => 6,
                            'name' => Flang::_e('rating'),
                            'slug' => 'rating_total'
                        ]
                    ];
                    $result = $this->get_success($posts, Flang::_e('Get_list_posttype_success'));
                    $this->cache->set(json_encode($result));
                } else {
                    $result = $this->get_error(Flang::_e('posttype_termtype_empty'), [], 404);
                   //  $this->cache->set(json_encode($result));
                }

                $this->cache->headers(0);
                echo json_encode($result);
                exit();
            }
        } catch (AppException $e) {
            return $this->error($e->getMessage(), [], 500);
        }
    }

    public function search($page = null , $page_number = null) {
        try {
            if(empty($lang)){
                $lang = APP_LANG;
            }
            if(!empty($page) && !empty($page_number) && is_numeric($page_number) && $page == 'paged') {
                $page = (int) $page_number;
            } else {
                $page = 1;
            }
            $limit = 10;
            $fields = 'id, title, slug, lang_slug, status, seo_title, seo_desc, rating_total, rating_count, views_day, views, views_week, banner, feature, like_count';
            $where = '';
            $params = [];
            $orderby = [];


            $data_filter = [];
            $filter = S_GET('filter') ?? '';
            $sort = S_GET('sortby') ?? '';
            $keysearch = S_GET('q') ?? '';

            if(!empty($keysearch)) {
                $data_filter['keysearch'] = $keysearch;
            }

            
            if(!empty($filter)) { 
                $parts = explode('__', $filter);
                $filter_array = [];
                for ($i = 0; $i < count($parts); $i += 2) {
                    // Kiểm tra nếu còn value tương ứng
                    if (isset($parts[$i + 1])) {
                        $filter_array[$parts[$i]] = $parts[$i + 1];
                    }
                }
            }

            if(!empty($sort)) {
                list($key, $value) = explode('__', $sort);
                $data_filter['sort'] = [$key, $value];
            }


            if(!empty($filter_array['posttype']) ) {
                if(!empty($filter_array['posttype'])) {
                    $posttype = $filter_array['posttype'];
                } else {
                    return $this->error("posttype_does_not_exist", [], 404);
                }
                if(!empty($filter_array['catid'])) {
                    $data_filter['catid'] = $filter_array['catid'];
                }

                $this->postsModel = new PostsModel($posttype, $lang);
                if (!$this->postsModel->checkPosttypeExists()) {
                    return $this->error("posttype_does_not_exist", [], 404);
                }
                $posttype_data = $this->postsModel->getPostBySlug('fast_posttype', $posttype);
                $posttype_fields = json_decode($posttype_data['fields'], true);
                        $fields_ralationship = [];
                        foreach($posttype_fields as $field) {
                            switch($posttype_data['slug']) {
                                case 'movie':
                                    if($field['field_name'] == 'directors') {$fields_ralationship[] = $field; };
                                case 'comic':
                                    if($field['field_name'] == 'creators') {$fields_ralationship[] = $field; };
                                case 'novel':
                                    if($field['field_name'] == 'creators') {$fields_ralationship[] = $field; };
                            }
                        }
                if(!empty($posttype_data) && !empty($data_filter)) { 
                    $sort_data = $this->_handle_sort($data_filter);
                }
                if(!empty($sort_data['status']) && $sort_data['status'] === 'not_found') {
                    $result = $this->get_error(Flang::_e('post_not_found'), [], 404);
                    echo json_encode($result);
                    exit();
                }
                if(!empty($sort_data['posts_data'])) {
                    $fields = '*';
                    
                    $fields = is_string($fields) ? array_map('trim', explode(',', $fields)) : $fields;
    
                    // Create a proper key => value array for comparison
                    $field_keys = array_fill_keys($fields, '');
                    
                    $posts['data'] = $sort_data['posts_data'];

                    $posts['data'] = array_map(function ($movie) use ($field_keys) {
                        return array_intersect_key($movie, $field_keys);
                    }, $posts['data']);
                } else {
                    $where = $sort_data['where'] ?? '';
                    $params = $sort_data['params'] ?? [];
                    $orderby = $sort_data['orderby'] ?? [];
                    $posts = $this->postsModel->getPostsFieldsPagination($fields, $where, $params, $orderby, $page, $limit);

                    $posts['data'] = $this->_formatPosts($posts['data'], false ,$posttype_data['slug'], $fields_ralationship) ;

                }
            } else {
                $ultilsModel = new UtilsModel();
                $posttype_list = $ultilsModel->getDatasByTable('fast_posttype');
                $all_posts = [];
                if(!empty($posttype_list)) {
                    $is_next = false;
                    foreach($posttype_list as $posttype) {
                        $is_sortby = false;
                        
                        if(!in_array($posttype['slug'],['comic', 'movie', 'novel' ,'game'])) continue;
                                                
                        $posttype_fields = json_decode($posttype['fields'], true);
                        $fields_ralationship = [];
                        foreach($posttype_fields as $field) {
                            switch($posttype['slug']) {
                                case 'movie':
                                    if($field['field_name'] == 'directors') {$fields_ralationship[] = $field; };
                                case 'comic':
                                    if($field['field_name'] == 'creators') {$fields_ralationship[] = $field; };
                                case 'novel':
                                    if($field['field_name'] == 'creators') {$fields_ralationship[] = $field; };
                            }
                        }

                        
                        $this->postsModel = new PostsModel($posttype['slug'], $lang);
                        if (!$this->postsModel->checkPosttypeExists()) {
                            return $this->error("posttype_does_not_exist", [], 404);
                        }
                        if(!empty($posttype) && !empty($data_filter)) { 
                            $sort_data = $this->_handle_sort($data_filter);
                        }
                        if(!empty($sort_data['status']) && $sort_data['status'] === 'not_found') {
                            continue;
                        }
                        if(!empty($sort_data['posts_data'])) {
                            $fields = '*';
                            
                            $fields = is_string($fields) ? array_map('trim', explode(',', $fields)) : $fields;
            
                            // Create a proper key => value array for comparison
                            $field_keys = array_fill_keys($fields, '');
                            
                            $posts['data'] = $sort_data['posts_data'];
                            $posts['data'] = array_map(function ($movie) use ($field_keys) {
                                return array_intersect_key($movie, $field_keys);
                            }, $posts['data']);
                            if(!empty($posts['data'])) $posts = $posts['data'];
                        } else {
                            $where = $sort_data['where'] ?? '';
                            $params = $sort_data['params'] ?? [];
                            $orderby = $sort_data['orderby'] ?? [];
                            $fields = '*';
                            $posts = $this->postsModel->getPostsFieldsPagination($fields, $where, $params, $orderby, $page, $limit);
                            if($posts['is_next']) $is_next = true;
                            $postssub = $this->_formatPosts($posts['data'], false ,$posttype['slug'], $fields_ralationship) ;
                        }
                        if(!empty($postssub)) {
                            $all_posts = array_merge($all_posts, $postssub);
                        }
                    }
                }
                
            }

            if(!empty($all_posts)) {
                if (!empty($data_filter['sort'])) {
                    $sortKey = $data_filter['sort'][0] ?? 'id'; // Key cần sắp xếp (mặc định là 'id')
                    $sortOrder = $data_filter['sort'][1] ?? 'desc'; // Thứ tự (mặc định là 'desc')
            
                    // Sắp xếp mảng $all_posts
                    usort($all_posts, function ($a, $b) use ($sortKey, $sortOrder) {
                        if (!isset($a[$sortKey]) || !isset($b[$sortKey])) {
                            return 0; // Không có key để so sánh
                        }
                        if (strtoupper($sortOrder) === 'desc') {
                            return $b[$sortKey] <=> $a[$sortKey];
                        } else {
                            return $a[$sortKey] <=> $b[$sortKey];
                        }
                    });
                }
                $posts = [
                    'data' => $all_posts,
                    'is_next' => $is_next,
                    'pade' => $page
                ];
            } 
            // kiểm tra posttype có tồn tại hoặc là không
            // if (!empty($posts['data'])) {
            //     foreach ($posts['data'] as $key => $post) {
            //         $postTmp = $post;
            //         unset($postTmp['content']);
            //         unset($postTmp['created_at']);
            //         unset($postTmp['updated_at']);
            //         unset($postTmp['author']);
            //         if (!empty($postTmp['feature'])){
            //             $postTmp['feature'] = json_decode($postTmp['feature']);
            //             if (!empty($postTmp['feature']->path)){
            //                 $postTmp['feature']->path = '/uploads/'.$postTmp['feature']->path;
            //                 $postTmp['feature']->square = img_square($postTmp['feature']);
            //                 $postTmp['feature']->path = img_vertical($postTmp['feature']);
            //                 if (!empty($postTmp['feature']->resize)){
            //                     unset($postTmp['feature']->resize);
            //                 }
            //                 if (!empty($postTmp['feature']->name)){
            //                     unset($postTmp['feature']->name);
            //                 }
            //             }else{
            //                 $postTmp['feature'] = null;
            //             }
            //         }
            //         if (!empty($postTmp['banner'])){
            //             $postTmp['banner'] = json_decode($postTmp['banner']);
            //             if (!empty($postTmp['banner']->path)){
            //                 $postTmp['banner']->path = '/uploads/'.$postTmp['banner']->path;
            //                 $postTmp['banner']->square = img_square($postTmp['banner']);
            //                 $postTmp['banner']->path = img_vertical($postTmp['banner']);
            //                 if (!empty($postTmp['banner']->resize)){
            //                     unset($postTmp['banner']->resize);
            //                 }
            //                 if (!empty($postTmp['banner']->name)){
            //                     unset($postTmp['banner']->name);
            //                 }
            //             }else{
            //                 $postTmp['banner'] = null;
            //             }
            //         }

            //         $posts['data'][$key] = $postTmp;
            //     }
            // } else {
            //     $result = $this->get_error(Flang::_e('post_not_found'), [], 404);
            //     echo json_encode($result);
            //     exit();
            // }
            $result = $this->get_success($posts, Flang::_e('Get_list_posttype_success'));
            if(empty($posts['data']))  $result = $this->get_error(Flang::_e('post_not_found'), [], 404);
            echo json_encode($result);
            exit();
        } catch (AppException $e) {
            return $this->error($e->getMessage(), [], 500);
        }
    }

    public function detail($posttype_slug = '', $id = null, $lang = '') {
        try {
            $cachedata = $this->cache->get();
            if (!empty($cachedata)) {
                $this->cache->render($cachedata);
                //echo $cachedata;
                exit();
            }else{ 
                if(!empty($posttype_slug)){
                    if(empty($lang)){
                        $lang = APP_LANG;
                    }
                    $idCheck = (int)$id;
                    
                    $posttype = $this->postsModel->getPostBySlug('fast_posttype', $posttype_slug);
                    if (!empty($posttype) || !empty($posttype['fields'])){
                        $posttype_fields = json_decode($posttype['fields']);
                    }else{
                        return $this->error(Flang::_e('posttype_404'), ["posttype"=>["posttype_404"]], 404);
                    }
                    $postrelModel = new PostrelModel();
                    $this->postsModel = new PostsModel($posttype_slug, $lang);
                    if (!$this->postsModel->checkPosttypeExists()) {
                        return $this->error(Flang::_e('posttype_404'), ["posttype"=>["posttype_404"]], 404);
                    }

                    if($idCheck == $id) {
                        $detail = $this->postsModel->getPostByIdTable($id);
                    }else{
                        $detail = $this->postsModel->getPostBySlug('fast_posts_' . $posttype_slug . '_' . $lang, $id);
                    }

                    if (empty($detail) || empty($detail['id'])){
                        return $this->error(Flang::_e('post_404'), ["posttype"=>["post_404"]], 404);
                    }

                    $post_terms = $this->postsModel->getPostTermsByPostId($posttype_slug, $detail['id'], $lang);
                    foreach ($post_terms as $term){
                        if (empty($detail[$term['type']])){
                            $detail[$term['type']] = array();
                        }
                        $detail[$term['type']][] = [
                            'id' => $term['id'],
                            'name' => $term['name'],
                            'slug' => $term['slug'],
                            'posttype' => $term['posttype'],
                            'id_main' => $term['id_main'],
                        ];
                    }

                    $main_term_type = 'categories';
                    $main_terms =  $detail[$main_term_type];
                    if(!empty($main_terms)) {
                        $post_ids = array();
                        foreach($main_terms as $main_term) {
                            $ids = $this->postsModel->getPostIdByTerm($posttype_slug, $main_term['id_main'], APP_LANG);
                            if(empty($ids)) continue;
                            $post_ids[] = array_merge($ids);
                        }
                        $new_post_ids = array();
                        if(!empty($post_ids)) {
                            foreach ($post_ids as $subArray) {
                                foreach ($subArray as $item) {
                                    $new_post_ids[] = $item['post_id'];
                                }
                            }
                            
                            // Bước 2: Loại bỏ trùng lặp
                            $post_ids = array_unique($new_post_ids);
                            $post_ids = array_diff($post_ids, array($id));
                            $post_ids = array_slice($post_ids, 0, 10);

                        }
                        if(!empty($post_ids)) {
                            $postIdsString = implode(',', array_fill(0, count($post_ids), '?'));
                            $fields = 'id, title, slug, lang_slug, status, seo_title, seo_desc, rating_total, rating_count, views_day, views, views_week, banner, feature, like_count';
                            $page = 1;
                            $limit = 10;
                            $where = 'status = ? AND id IN (' . $postIdsString . ')';
                            $params = array_merge(['active'], $post_ids);
                            $related_post = $this->postsModel->getPostsFieldsPagination($fields, $where, $params, 'views desc', $page, $limit);
                            if(!empty($related_post['data'])) {
                                $related_post = [
                                    'type' => 'itemshorizontal',
                                    'posttype' => $posttype_slug,
                                    'label' => ucfirst(Flang::_e($posttype_slug.'_related_posts')),
                                    'name' => $posttype_slug,
                                    'button' => $this->_formatButton('load_more', 'linkpage', $posttype_slug,  $posttype_slug.'_related_posts', $posttype_slug.'_related_posts', '/posts/lists/'.$posttype_slug. '/related/'),
                                    'items' => $this->_formatPosts($related_post['data'], true, $posttype_slug ),
                                ];
                            }
                        }
                    } else {
                        $related_post = [];
                    }
                    
                    foreach ($posttype_fields as $field){
                        if (empty($field->type)) continue;
                        switch ($field->type){
                            case 'WYSIWYG':
                                $detail[$field->field_name] = json_decode($detail[$field->field_name]);
                                break;
                            case 'Image':
                                $detail[$field->field_name] = json_decode($detail[$field->field_name]);
                                break;
                        }
                        if(ucfirst($field->type) == 'Reference' && !empty($field->post_type_reference) && isset($field->table_save_data_reference)) {
                            // if($field->field_name === 'chapters') continue;
                            $save_rel = (int)$field->table_save_data_reference == 1 ? true : false;
                            $posts = $postrelModel->getPosts($id, $posttype_slug, $field->post_type_reference, $save_rel, $lang);
                            if ($posts) {
                                $posts = array_filter($posts, function ($p) {
                                    return $p['status'] !== 'inactive';
                                });
                                $detail[$field->field_name] = array_map(function ($postTmp) {
                                    if(!empty($postTmp['social_media'])) {
                                        $postTmp['social_media'] = json_decode($postTmp['social_media']);
                                    }
                                    if(!empty($postTmp['content'])) {
                                        $postTmp['content'] = json_decode($postTmp['content']);
                                        if(!empty($postTmp['content'])) {
                                            foreach($postTmp['content'] as $i =>  $item) {
                                                if(!empty($item->path)) {
                                                    $postTmp['content'][$i]->path= '/uploads/'.$item->path;
                                                }
                                            }
                                        }
                                    }
                                    if (!empty($postTmp['feature'])) {
                                        $postTmp['feature'] = is_string($postTmp['feature']) ? json_decode($postTmp['feature']) : $postTmp['feature'];
                                        if (!empty($postTmp['feature']->path)) {
                                            $postTmp['feature']->path   = '/uploads/'.$postTmp['feature']->path;
                                            $postTmp['feature']->square = img_square($postTmp['feature']);
                                            $postTmp['feature']->path   = img_vertical($postTmp['feature']);
                                            if (!empty($postTmp['feature']->resize)) {
                                                unset($postTmp['feature']->resize);
                                            }
                                            if (!empty($postTmp['feature']->name)) {
                                                unset($postTmp['feature']->name);
                                            }
                                        } elseif (!empty($postTmp['feature']['path'])) {
                                            $postTmp['feature']['path']   = '/uploads/' . $postTmp['feature']['path'];
                                            $postTmp['feature']['square'] = img_square($postTmp['feature']);
                                            $postTmp['feature']['path']   = img_vertical($postTmp['feature']);
                                            if (!empty($postTmp['feature']['resize'])) {
                                                unset($postTmp['feature']['resize']);
                                            }
                                            if (!empty($postTmp['feature']['name'])) {
                                                unset($postTmp['feature']['name']);
                                            }
                                        } else {
                                            $postTmp['feature'] = null;
                                        }
                                    }
                                    if (!empty($postTmp['index'])) {
                                        $postTmp['index'] = (int)$postTmp['index'];
                                    }

                                    if(!empty($postTmp['source'])) {
                                        $links = array_filter(explode("\n", $postTmp['source'])); // Tách các URL thành mảng và loại bỏ các phần tử rỗng
                                        $sources = [];
                                        foreach($links as $index => $link) {
                                            $server_number = $index + 1;
                                            $sources[] = [
                                                'server' => "Server {$server_number}",
                                                'link' => trim($link) // Loại bỏ khoảng trắng và ký tự xuống dòng
                                            ];
                                        }
                                        $postTmp['source'] = $sources;
                                    }
                                    if (!empty($postTmp['banner'])) {
                                        if (!empty($postTmp['banner']->path)) {
                                            $postTmp['banner']->path   = '/uploads/'.$postTmp['banner']->path;
                                            $postTmp['banner']->square = img_square($postTmp['banner']);
                                            $postTmp['banner']->path   = img_vertical($postTmp['banner']);
                                            if (!empty($postTmp['banner']->resize)) {
                                                unset($postTmp['banner']->resize);
                                            }
                                            if (!empty($postTmp['banner']->name)) {
                                                unset($postTmp['banner']->name);
                                            }
                                        } else {
                                            $postTmp['banner'] = null;
                                        }
                                    }
                                    return $postTmp;
                                },$posts);
                            } else {
                                $detail[$field->field_name] = [];
                            }
                        }
                    }
                    if(!empty($detail)) {
                        $postTmp = $detail;
                        if (!empty($postTmp['feature'])) {
                            if (!empty($postTmp['feature']->path)) {
                                $postTmp['feature']->path   = '/uploads/'.$postTmp['feature']->path;
                                $postTmp['feature']->square = img_square($postTmp['feature']);
                                $postTmp['feature']->path   = img_vertical($postTmp['feature']);
                                if (!empty($postTmp['feature']->resize)) {
                                    unset($postTmp['feature']->resize);
                                }
                                if (!empty($postTmp['feature']->name)) {
                                    unset($postTmp['feature']->name);
                                }
                            } else {
                                $postTmp['feature'] = null;
                            }
                        }
                        if (!empty($postTmp['banner'])) {
                            if (!empty($postTmp['banner']->path)) {
                                $postTmp['banner']->path   = '/uploads/'.$postTmp['banner']->path;
                                $postTmp['banner']->square = img_square($postTmp['banner']);
                                $postTmp['banner']->path   = img_vertical($postTmp['banner']);
                                if (!empty($postTmp['banner']->resize)) {
                                    unset($postTmp['banner']->resize);
                                }
                                if (!empty($postTmp['banner']->name)) {
                                    unset($postTmp['banner']->name);
                                }
                            } else {
                                $postTmp['banner'] = null;
                            }
                        }

                        if(!empty($related_post)) {
                            $postTmp['related_post'] = $related_post;
                        }
                        $postTmp['url'] = single_url($postTmp['slug'], $posttype_slug);
                        $postTmp['posttype'] = $posttype_slug;
                        $detail = $postTmp;
                    }
                    $result = $this->get_success($detail, Flang::_e('Get_detail_post_success'));
                    $checkGzip_content = $this->cache->set(json_encode($result)); 
                } else {    
                    $result = $this->get_error(Flang::_e('terms_empty'), [], 403);
                    $gzip_content = $this->cache->set(json_encode($result));
                }
                $this->cache->render($checkGzip_content);
                //echo json_encode($result);
                exit();    
            }
        } catch (AppException $e) {
            return $this->error($e->getMessage(), [], 500);
        }
    } 

    public function listchapter($posttype_slug = '', $id = null, $paged = 'paged' , $page = 1) {
        
        
        $postrelModel = new PostrelModel();
        $posts = $postrelModel->getListChapterPanigation($posttype_slug, $id, APP_LANG, $page, 50);
        if(!empty($posts['data'])) {
            foreach($posts['data'] as &$chapter) {
                $chapter = $this->_formatChapter($chapter);
            }
        }
        $result = $this->get_success($posts, Flang::_e('Get_listchapter_success'));
        echo json_encode($result);
        exit();
    }
    public function chapter($posttype_slug = '', $id = null, $chapter_index = null) {
        $chapter_index = (int) $chapter_index;
        $id = (int) $id;
        if(!is_numeric($chapter_index) && !is_numeric($id)) {
            $result = $this->get_error(Flang::_e('id_or_index_erro'), [], 404);
            echo json_encode($result);
            exit();
        }
        $postrelModel = new PostrelModel();
        $chapter = $postrelModel->getChapter($posttype_slug, $id, $chapter_index, APP_LANG);
        $chapter = $this->_formatChapter($chapter);
        if(empty($chapter)) {
            $result = $this->get_success([], Flang::_e('Get_chapter_success'));
        } else {
            $result = $this->get_success($chapter, Flang::_e('Get_chapter_success'));
        }
        echo json_encode($result);
        exit();
    }
    private function _formatChapter($chapter) {
        if(!empty($chapter['source'])) {
            $links = array_filter(explode("\n", $chapter['source'])); // Tách các URL thành mảng và loại bỏ các phần tử rỗng
            $sources = [];
            foreach($links as $index => $link) {
                $server_number = $index + 1;
                $sources[] = [
                    'server' => "Server {$server_number}",
                    'link' => trim($link) // Loại bỏ khoảng trắng và ký tự xuống dòng
                ];
            }
            $chapter['source'] = $sources;
        }
    
        if (!empty($chapter['feature'])) {
            if (!empty($chapter['feature']->path)) {
                $chapter['feature']->path   = '/uploads/'.$chapter['feature']->path;
                $chapter['feature']->square = img_square($chapter['feature']);
                $chapter['feature']->path   = img_vertical($chapter['feature']);
                if (!empty($chapter['feature']->resize)) {
                    unset($chapter['feature']->resize);
                }
                if (!empty($chapter['feature']->name)) {
                    unset($chapter['feature']->name);
                }
            } else {
                $chapter['feature'] = null;
            }
        }
        if (!empty($chapter['banner'])) {
            if (!empty($chapter['banner']->path)) {
                $chapter['banner']->path   = '/uploads/'.$chapter['banner']->path;
                $chapter['banner']->square = img_square($chapter['banner']);
                $chapter['banner']->path   = img_vertical($chapter['banner']);
                if (!empty($chapter['banner']->resize)) {
                    unset($chapter['banner']->resize);
                }
                if (!empty($chapter['banner']->name)) {
                    unset($chapter['banner']->name);
                }
            } else {
                $chapter['banner'] = null;
            }
        }

        if(!empty($chapter['data'])) {
            $chapter['data'] = json_decode($chapter['data'], true);
            $content = format_data_comic_otruyen($chapter['data']);
            $chapter['content'] = $content;
        } else {
            if(!empty($chapter['content'])) {
                $chapter['content'] = json_decode($chapter['content']);
                if(!empty($chapter['content'])) {
                    foreach($chapter['content'] as $i =>  $item) {
                        if(!empty($item->path)) {
                            $chapter['content'][$i]->path= '/uploads/'.$item->path;
                        }
                    }
                }
            }
            
        }
        return $chapter;
        
    }


    public function action($action = null, $posttype = '', $id = null) {
        if(!empty($action) && !empty($posttype)) {
            switch($action) {
                case 'like':
                    $this->_like_action($posttype, $id);
                case 'views':
                    $this->_views_action($posttype, $id);
                case 'save':
                    $this->_save_action($posttype);
                case 'rating':
                    $this->_rating_action($posttype, $id);
                default:
                    $errors = [
                        'invalid_action'
                    ];
                    return $this->error(Flang::_e('invalid_action'), $errors, 400);
            }
        }
    }

    private function _like_action($posttype = '',  $id = null) {
        
        try {
            if(!empty($posttype) && !empty($id)){
                
                $csrf_token = S_POST('csrf_token') ?? '';
                // if (!Session::csrf_verify($csrf_token)) {
                //     return $this->error(Flang::_e('csrf_failed'), [], 400);
                // }
                if(empty($lang)){
                    $lang = APP_LANG;
                }
                if (!is_numeric($id)) {
                    $errors = [
                        'invalid_post_id'
                    ];
                    return $this->error(Flang::_e('invalid_post_id'), $errors, 400);  // Trả lỗi nếu id không phải là số
                }
                $id = (int) $id;
                $this->postsModel = new PostsModel($posttype, $lang);
                if (!$this->postsModel->checkPosttypeExists()) {
                    throw new AppException("posttype_does_not_exist");
                }
                $post = $this->postsModel->getPostByIdTable($id);

                if (empty($post)) {
                    $errors = [
                        'post not found'
                    ];
                    return $this->error(Flang::_e('post_not_found'), $errors, 404);
                }
                $like_message = '';
                $like_action = S_POST('like'); 
                if ((int)$like_action === 1) {
                    $new_like_count = $post['like_count'] + 1;
                    $like_message = 'like success';
                } elseif((int)$like_action === 2) {
                    $new_like_count = max(0, $post['like_count'] - 1);
                    $like_message = 'unlike success';
                } else {
                    $like_message = 'like action not found';
                }
                $data = ['like_count' => $new_like_count];
                $this->postsModel->editPostTable( $id, $data);
                return $this->success($data, Flang::_e($like_message)); 
            
            } else {
                $result = $this->get_error(Flang::_e('terms_empty'), [], 403);
                $this->cache->set(json_encode($result));
            }

        } catch (AppException $e) {
            return $this->error($e->getMessage(), [], 500);
        }
    }

    private function _views_action($posttype = '', $id = null) {
        try {
            if (!empty($posttype) && !empty($id)) {
                // Verify CSRF token first
                // $csrf_token = S_POST('csrf_token') ?? '';
                // if (!Session::csrf_verify($csrf_token)) {
                //     return $this->error(Flang::_e('csrf_failed'), [], 400);
                // }
    
                // Set default language if empty
                if (empty($lang)) {
                    $lang = APP_LANG;
                }
    
                // Validate post ID
                if (!is_numeric($id)) {
                    return $this->error(Flang::_e('invalid_post_id'), ['invalid_post_id'], 400);
                }
                $id = (int) $id;
    
                // Initialize PostsModel and check if posttype exists
                $this->postsModel = new PostsModel($posttype, $lang);
                if (!$this->postsModel->checkPosttypeExists()) {
                    throw new AppException("posttype_does_not_exist");
                }
    
                // Get post data
                $post = $this->postsModel->getPostByIdTable($id);
                if (empty($post)) {
                    return $this->error(Flang::_e('post_not_found'), ['post not found'], 404);
                }

                if(isset($post['views'])) {
                    $data ['views'] = $post['views'] + 1;
                }
                if(isset($post['views_day'])) {
                    $data ['views_day'] = $post['views_day'] + 1;
                }
                if(isset($post['views_week'])) {
                    $data ['views_week'] = $post['views_week'] + 1;
                }
    
                // Update post with new view counts
                if(empty($data)) return $this->error(Flang::_e('view_update_failed'), [], 500);
                $result = $this->postsModel->editPostTable($id, $data);
                if ($result) {
                    return $this->success($data, Flang::_e('view_updated_successfully'));
                } else {
                    return $this->error(Flang::_e('view_update_failed'), [], 500);
                }
    
            } else {
                return $this->error(Flang::_e('terms_empty'), [], 403);
            }
    
        } catch (AppException $e) {
            return $this->error($e->getMessage(), [], 500);
        }
    }

    private function _save_action($posttype = '')
    {
        try {
            $access_token = Fasttoken::getToken();
            if (Session::has('user_id')) {
                $user_id = clean_input(Session::get('user_id'));
            } elseif (!empty($access_token)) {
                $config_security = config('security');
                $me_data = Fasttoken::decodeToken($access_token, $config_security['app_secret']);
                if (!$me_data['success']) {
                    return $this->error(Flang::_e('auth_token_invalid'), [$me_data['message']], 401);
                }
                $user_id = $me_data['data']['user_id'] ?? null;
                if (empty($user_id)) {
                    return $this->error(Flang::_e('Tô kèn sai'), [], 401);
                }
            } else {
                return $this->error(Flang::_e('Không nhận được token'), [], 403);
            }
            // Check if user exists
            $user = $this->usersModel->getUserById($user_id);
            if (empty($user)) {
                return $this->error(Flang::_e('Có token mà không có user'), [], 403);
            }

            // Lấy dữ liệu lưu hiện tại của người dùng
            $save = !empty($user['save']) ? json_decode($user['save'], true) : [];
            if (!isset($save[$posttype]) || !is_array($save[$posttype])) {
                $save[$posttype] = [];
            }

            // if POST 
            if(HAS_POST('post_ids')) {
                // Check CSRF token
                $csrf_token = S_POST('csrf_token') ?? '';
                if (!Session::csrf_verify($csrf_token)) {
                    return $this->error(Flang::_e('csrf_failed'), [], 400);
                }
        
                
                
                // Lấy array ids từ post_ids, nếu không tồn tại hoặc không phải mảng thì báo lỗi
                $ids = S_POST('post_ids') ?? [];
                $ids = is_array($ids) ? $ids : json_decode($ids, true);
                if (empty($ids) || !is_array($ids)) {
                    return $this->error(Flang::_e('ids_invalid'), [], 400);
                }
        
                
                
                
        
                // Xử lý toggle: nếu id đã tồn tại trong save thì remove, nếu không có thì add
                $action = []; // Mảng lưu trạng thái thao tác cho từng id
                foreach ($ids as $id) {
                    if (in_array($id, $save[$posttype])) {
                        // Nếu tồn tại, remove
                        $index = array_search($id, $save[$posttype]);
                        if ($index !== false) {
                            unset($save[$posttype][$index]);
                            $save[$posttype] = array_values($save[$posttype]); // Reindex array
                            $action[$id] = 'remove';
                        }
                    } else {
                        // Nếu không tồn tại, add
                        $save[$posttype][] = $id;
                        $action[$id] = 'add';
                    }
                }
        
                // Update dữ liệu save của người dùng
                $result = $this->usersModel->updateUser($user_id, [
                    'save' => json_encode($save)
                ]);
        
                if ($result) {
                    return $this->success([
                        'action' => $action,
                        'save' => $save
                    ], Flang::_e('favorite_update_successfully'));
                } else {
                    return $this->error(Flang::_e('favorite_update_failed'), [], 500);
                }
            } else {
                if(!empty($save[$posttype])) {
                    $this->_get_post_by_ids($posttype, $save[$posttype], 'paged', 1, 100);
                } else {
                    return $this->success([], Flang::_e('save_post_empty'));
                }
            }
            
        } catch (AppException $e) {
            return $this->error($e->getMessage(), [], 500);
        }
    }
    

    private function _rating_action($posttype = '', $id = null) {
        try {
            // $csrf_token = S_POST('csrf_token') ?? '';
            // if (!Session::csrf_verify($csrf_token)) {
            //         return $this->error(Flang::_e('csrf_failed'), [], 400);
            // }
            $access_token = Fasttoken::getToken();
            if(!empty($access_token)) {
                $config_security = config('security');

                $me_data = Fasttoken::decodeToken($access_token, $config_security['app_secret']);
                if (!$me_data['success']) {
                    return $this->error(Flang::_e('auth_token_invalid'), [$me_data['message']], 401);
                }

                $user_id = $me_data['data']['user_id'] ?? null;
                if (empty($user_id)) {
                    return $this->error(Flang::_e('token_invalid'), [], 401);
                }
                $user = $this->usersModel->getUserById($user_id);
                if (empty($user)) {
                    return $this->error(Flang::_e('user_not_found'), [], 404);
                }
            } elseif (!Session::has('user_id')) {
               if(HAS_POST('fullname') && HAS_POST('email')) {
                    $user = [
                        'email' => S_POST('email'),
                        'fullname' => S_POST('fullname'),
                    ];

                    $rules = [
                        'fullname' => [
                            'rules' => [Validate::length(6, 50)],
                            'messages' => [Flang::_e('fullname_invalid'), Flang::_e('fullname_length', 6, 50)]
                        ],
                        'email' => [
                            'rules' => [Validate::email(), Validate::length(6, 150)],
                            'messages' => [Flang::_e('email_invalid'), Flang::_e('email_length', 6, 150)]
                        ],
                    ];
                    $validator = new Validate();
                    if (!$validator->check($user, $rules)) {
                        // Trả về lỗi nếu validate thất bại
                        $errors = $validator->getErrors();
                        return $this->error(Flang::_e('rating_failed'), $errors, 400);
                    }

               } else {
                    return $this->error(Flang::_e('user_not_found'), [], 404);
               }
            } else {
                $user_id = clean_input(Session::get('user_id'));
                // Check if user exists
                $user = $this->usersModel->getUserById($user_id);
                if (empty($user)) {
                    return $this->error(Flang::_e('user_not_found'), [], 404);
                }
            }

            if (empty($posttype) || empty($id)) {
                return $this->error(Flang::_e('terms_empty'), [], 403);
            }
            $lang = APP_LANG;
            $this->postsModel = new PostsModel($posttype, $lang);      
            if (!$this->postsModel->checkPosttypeExists()) {
                return $this->error(Flang::_e("posttype_does_not_exist"), [], 404);
            }

            if (!is_numeric($id)) return $this->error(Flang::_e('invalid_post_id'), ['post phải có id là số'], 400);  // Trả lỗi nếu id không phải là số
            $id = (int) $id;
            $posts = $this->postsModel->getPostByIdTable($id);
            if (empty($posts)) {
                return $this->error(Flang::_e('post_empty'), [], 404);
            }

            $ratingSuccess = $this->handleRating($posts, $id);
            $commentSuccess = $this->handleComment($id, $posttype, $lang, $user);

            if ($ratingSuccess || $commentSuccess) {
                $responseData = [
                    'post_id' => $posts['id'],
                    'rating_count' => $posts['rating_count'],
                    'rating_total' => $posts['rating_total']
                ];
                return $this->success($responseData, Flang::_e('rating_review_success'));
            }

            return $this->error(Flang::_e('nothing_done'), [], 400);
        } catch (AppException $e) {
            return $this->error($e->getMessage(), [], 500);
        }
    }

    private function handleRating($posts, $id) {
        if (HAS_POST('rating')) {
            $rating = S_POST('rating') ?? 1;
            if ($rating < 1 || $rating > 10) {
                return $this->error(Flang::_e('invalid_rating'), [], 400);
            }

            $new_rating_total = $posts['rating_total'] + $rating;
            $new_rating_count = $posts['rating_count'] + 1;
            $data = [ 
                'rating_count' => $new_rating_count,
                'rating_total' => $new_rating_total,
            ];

            return $this->postsModel->editPostTable($id, $data);
        }
        return false;
    }

    private function handleComment($id, $posttype, $lang, $user) {

        if (HAS_POST('content')) {
            $content = S_POST('content') ?? '';
            $parent = (int)(S_POST('parent') ?? null); 
            $user_id = !empty($user['id']) ? $user['id'] : null;
            $user_info = [
                'fullname' => $user['fullname'] ?? '',
                'email' => $user['email'] ?? '',
            ];
            $user_info = is_string($user_info) ? $user_info : json_encode($user_info);
            $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';

            if (!empty($parent)) {
                $parentComment = $this->reviewsModel->getCommentById($parent);
                if (empty($parentComment)) {
                    return $this->error(Flang::_e('invalid_parent_comment'), [], 400);
                }
            }

            $data = [
                'user_id' => $user_id,
                'user_info' => $user_info,
                'post_id' => $id,
                'posttype' => $posttype,
                'lang' => $lang,
                'content' => $content,
                'parent' => $parent,
                'ip_address' => $ip_address
            ];

            return $this->reviewsModel->addReviews($data);
        }
        return false;
    }

    private function list_by_category($posttype, $category, $paged = '', $page = 1) {
        if($paged == 'paged' && is_numeric($page)) {
            $page = $page;
        } else {
            $page = 1;
        }
        $limit = 10;
        $where = "status = ?";
        $params = ['active'];
        $lang = APP_LANG;
        $this->postsModel = new PostsModel($posttype, $lang);
        if (!$this->postsModel->checkPosttypeExists()) {
            return $this->error("posttype_does_not_exist", [], 404);
        }


        if(($category === 'movie' || $category === 'tvshow' || $category === 'tvseries') && $posttype === 'movie') {
            $where = $where . ' AND movie_type = ?';
            $params[] = $category;
        } elseif($category === 'cinema' && $posttype === 'movie') {
            $where = $where . ' AND cinema = ?';
            $params[] = 'yes';
        } else {
            $sql = 'WHERE posttype = "' . $posttype . '" AND slug = "' . $category . '" AND lang = "' . $lang . '"';
            $terms = $this->postsModel->getPostByQuery('fast_terms', $sql );
            if(!empty($terms)) {
                $term_id = $terms[0]['id_main'];
                $post_ids = $this->postsModel->getPostIdByTerm($posttype, $term_id, $lang);
                $postIds = array_unique(array_column($post_ids, 'post_id')); 
                if (!empty($postIds)) {

                    $sort = S_GET('sortby') ?? '';
                    $sort = strtolower($sort);
                    
                    switch ($sort) {
                        case 'views_day__desc':
                            $sort = 'views_day__desc';
                            $title = Flang::_e('trending');
                            break;
                        case 'updated_at__desc':
                            $sort = 'updated_at__desc';
                            $title = Flang::_e('last_updated');
                            break;
                        case 'created_at__desc':
                            $sort = 'created_at__desc';
                            $title = Flang::_e('newest');
                            break;
                        case 'like_count__desc':
                            $sort = 'like_count__desc';
                            $title = Flang::_e('likes');
                            break;
                        case 'views_week__desc':
                            $sort = 'views_week__desc';
                            $title = Flang::_e('views');
                            break;
                        case 'rating_total__desc':
                            $sort = 'rating_total__desc';
                            $title = Flang::_e('rating');
                            break;
                        default:
                            $sort = 'updated_at__desc';
                            break;
                    }
                    if(!empty($sort)) {
                        list($key, $value) = explode('__', $sort);
                        $sort = [$key, $value];
                    }
                    $postIdsString = implode(',', array_fill(0, count($postIds), '?'));
                    $where .= " AND id IN ($postIdsString)";
                    $params = array_merge($params, $postIds);
                }
                // Add sort if provided
                    $orderby = 'created_at desc'; // Default sort
                    if (!empty($sort) && count($sort) == 2) {
                        if($sort[0] == 'trending') $sort[0] = 'views_day';
                        $orderby = $sort[0] . ' ' . $sort[1];
                    }
                    // get post 
                    $fields = 'id, title, slug, lang_slug, status, seo_title, seo_desc, rating_total, rating_count, views_day, views, views_week, banner, feature, like_count';
    
                    $posts = $this->postsModel->getPostsFieldsPagination($fields, $where, $params, $orderby, $page, $limit);
                    if(!empty($posts)) {
                        foreach ($posts['data'] as $key => $post) {
                            $postTmp = $post;
                            unset($postTmp['content']);
                            unset($postTmp['created_at']);
                            unset($postTmp['updated_at']);
                            unset($postTmp['author']);
                            if (!empty($postTmp['feature'])){
                                $postTmp['feature'] = json_decode($postTmp['feature']);
                                if (!empty($postTmp['feature']->path)){
                                    $postTmp['feature']->path = '/uploads/'.$postTmp['feature']->path;
                                    $postTmp['feature']->square = img_square($postTmp['feature']);
                                    $postTmp['feature']->path = img_vertical($postTmp['feature']);
                                    if (!empty($postTmp['feature']->resize)){
                                        unset($postTmp['feature']->resize);
                                    }
                                    if (!empty($postTmp['feature']->name)){
                                        unset($postTmp['feature']->name);
                                    }
                                }else{
                                    $postTmp['feature'] = null;
                                }
                            }
                            if (!empty($postTmp['banner'])){
                                $postTmp['banner'] = json_decode($postTmp['banner']);
                                if (!empty($postTmp['banner']->path)){
                                    $postTmp['banner']->path = '/uploads/'.$postTmp['banner']->path;
                                    $postTmp['banner']->square = img_square($postTmp['banner']);
                                    $postTmp['banner']->path = img_vertical($postTmp['banner']);
                                    if (!empty($postTmp['banner']->resize)){
                                        unset($postTmp['banner']->resize);
                                    }
                                    if (!empty($postTmp['banner']->name)){
                                        unset($postTmp['banner']->name);
                                    }
                                }else{
                                    $postTmp['banner'] = null;
                                }
                            }
                            
                            $posts['data'][$key] = $postTmp;
                        }
                        if(!empty( $posts['data'])) {
                            $posts['title'] = $title ?? '';
                            $posts['sort'] = [
                                [
                                    'id' => 1,
                                    'name' => Flang::_e('trending'),
                                    'slug' => 'views_day__desc' 
                                ],
                                [
                                    'id' => 2,
                                    'name' => Flang::_e('last_updated'),
                                    'slug' => 'updated_at__desc' 
                                ],
                                [
                                    'id' => 3,
                                    'name' => Flang::_e('newest'),
                                    'slug' => 'created_at__desc' 
                                ],
                                [
                                    'id' => 4,
                                    'name' => Flang::_e('likes'),
                                    'slug' => 'like_count__desc' 
                                ],
                                [
                                    'id' => 5,
                                    'name' => Flang::_e('views'),
                                    'slug' => 'views_week__desc'
                                ],
                                [
                                    'id' => 6,
                                    'name' => Flang::_e('rating'),
                                    'slug' => 'rating_total__desc' 
                                ]
                            ];
                            $result = $this->get_success($posts, Flang::_e('Get_list_posttype_success'));
                        } else {
                            $result = $this->get_error(Flang::_e('post_not_found'), [], 404);
                        }
                        echo json_encode($result);
                        exit();
                    } else {
                        $result = $this->get_error(Flang::_e('post_not_found'), [], 404);
                        echo json_encode($result);
                        exit();
                    }
            } else {
                return $this->error("category_not_found", [], 404);
            }
        }

        // Add sort if provided
        $orderby = 'created_at desc'; // Default sort
        if (!empty($sort) && count($sort) == 2) {
            if($sort[0] == 'trending') $sort[0] = 'views_day';
            $orderby = $sort[0] . ' ' . $sort[1];
        }
        // get post 
        $fields = 'id, title, slug, lang_slug, status, seo_title, seo_desc, rating_total, rating_count, views_day, views, views_week, banner, feature, like_count';

        $posts = $this->postsModel->getPostsFieldsPagination($fields, $where, $params, $orderby, $page, $limit);
        if(!empty($posts)) {
            foreach ($posts['data'] as $key => $post) {
                $postTmp = $post;
                unset($postTmp['content']);
                unset($postTmp['created_at']);
                unset($postTmp['updated_at']);
                unset($postTmp['author']);
                if (!empty($postTmp['feature'])){
                    $postTmp['feature'] = json_decode($postTmp['feature']);
                    if (!empty($postTmp['feature']->path)){
                        $postTmp['feature']->path = '/uploads/'.$postTmp['feature']->path;
                        $postTmp['feature']->square = img_square($postTmp['feature']);
                        $postTmp['feature']->path = img_vertical($postTmp['feature']);
                        if (!empty($postTmp['feature']->resize)){
                            unset($postTmp['feature']->resize);
                        }
                        if (!empty($postTmp['feature']->name)){
                            unset($postTmp['feature']->name);
                        }
                    }else{
                        $postTmp['feature'] = null;
                    }
                }
                if (!empty($postTmp['banner'])){
                    $postTmp['banner'] = json_decode($postTmp['banner']);
                    if (!empty($postTmp['banner']->path)){
                        $postTmp['banner']->path = '/uploads/'.$postTmp['banner']->path;
                        $postTmp['banner']->square = img_square($postTmp['banner']);
                        $postTmp['banner']->path = img_vertical($postTmp['banner']);
                        if (!empty($postTmp['banner']->resize)){
                            unset($postTmp['banner']->resize);
                        }
                        if (!empty($postTmp['banner']->name)){
                            unset($postTmp['banner']->name);
                        }
                    }else{
                        $postTmp['banner'] = null;
                    }
                }

                $posts['data'][$key] = $postTmp;
            }
            $result = $this->get_success($posts, Flang::_e('Get_list_posttype_success'));
            echo json_encode($result);
            exit();
        } else {
            $result = $this->get_error(Flang::_e('post_not_found'), [], 404);
            echo json_encode($result);
            exit();
        }
    }

    public function list_by_ids($posttype, $paged = '', $page = 1) {
        if($paged == 'paged' && is_numeric($page)) {
            $page = $page;
        } else {
            $page = 1;
        }
       if(HAS_POST('post_ids')) {
            $post_ids = S_POST('post_ids') ?? '';
            $sort = S_GET('sortby') ?? '';
            $this->_get_post_by_ids($posttype, $post_ids, $paged, $page, 10);
       } else {
        return $this->error("post_ids_required", [], 400);
       }

    }


    private function _get_post_by_ids($posttype = '', $post_ids = [], $paged = '', $page = 1, $limit = 10) {
        if(!empty($post_ids)) {
            $post_ids = is_string($post_ids) ? json_decode($post_ids) : $post_ids;  
            $fields = '*';
            $where = "status = ?";
            $params = ['active'];
            $lang = APP_LANG;
            $this->postsModel = new PostsModel($posttype, $lang);
            if (!$this->postsModel->checkPosttypeExists()) {
                return $this->error("posttype_does_not_exist", [], 404);
            }
            $postIds = array_unique($post_ids);
            if (!empty($postIds)) {
                if(!empty($sort)) {
                    list($key, $value) = explode('__', $sort);
                    $sort = [$key, $value];
                }
                $postIdsString = implode(',', array_fill(0, count($postIds), '?'));
                $where .= " AND id IN ($postIdsString)";
                $params = array_merge($params, $postIds);
            }
            $orderby = ''; 
                // get post 
            $fields = 'id, title, slug, lang_slug, status, seo_title, seo_desc, rating_total, rating_count, views_day, views, views_week, banner, feature, like_count';
            $posts = $this->postsModel->getPostsFieldsPagination($fields, $where, $params, $orderby, $page, $limit);
            $posts['data'] = $this->_formatPosts($posts['data'], true, $posttype);
            $result = $this->get_success($posts, Flang::_e('Get_list_related_success'));
            if(empty($posts['data']))  $result = $this->get_error(Flang::_e('post_not_found'), [], 404);
            echo json_encode($result);
            exit();
        } else {
            return $this->error("post_ids_empty", [], 400);
        }
    }
    private function list_by_related($posttype, $paged = '', $page = 1) {
        if($paged == 'paged' && is_numeric($page)) {
            $page = $page;
        } else {
            $page = 1;
        }
        $limit = 10;
        $where = "status = ?";
        $params = ['active'];
        $lang = APP_LANG;
        $this->postsModel = new PostsModel($posttype, $lang);
        if (!$this->postsModel->checkPosttypeExists()) {
            return $this->error("posttype_does_not_exist", [], 404);
        }
        $sort = S_GET('sortby') ?? '';
        $filter = S_GET('filter') ?? '';
        if(!empty($sort)) {
            list($key, $value) = explode('__', $sort);
            $data_filter['sort'] = [$key, $value];
        } 
        if(!empty($filter)) {
            list($key, $value) = explode('__', $filter);
            $data_filter['filter'] = [$key, $value];
        }

        if(!empty( $data_filter['filter']) &&  $data_filter['filter'][0] === 'post_id') {
            $post_id = $data_filter['filter'][1];
        }
        
        if(!empty($post_id) && is_numeric($post_id)) {
            
            $orderby = 'views_day desc'; // Default sort
            if (!empty($sort) && count($sort) == 2) {
                if($sort[0] == 'trending') $sort[0] = 'views_day';
                $orderby = $sort[0] . ' ' . $sort[1];
            }
            $post_terms = $this->postsModel->getPostTermsByPostId($posttype, $post_id, $lang);
            $main_terms = [];
            
            foreach ($post_terms as $term){
               if($term['type'] === 'categories') {
                 $main_terms[] = $term;
               }
            }

            if(!empty($main_terms)) {
                $post_ids = array();
                foreach($main_terms as $main_term) {
                    $ids = $this->postsModel->getPostIdByTerm($posttype, $main_term['id_main'], APP_LANG);
                    if(empty($ids)) continue;
                    $post_ids[] = array_merge($ids);
                }
                $new_post_ids = array();
                if(!empty($post_ids)) {
                    foreach ($post_ids as $subArray) {
                        foreach ($subArray as $item) {
                            $new_post_ids[] = $item['post_id'];
                        }
                    }
                    
                    // Bước 2: Loại bỏ trùng lặp
                    $post_ids = array_unique($new_post_ids);
                    $post_ids = array_diff($post_ids, array($post_id));
                    $post_ids = array_slice($post_ids, 0, 10);

                }

                if(!empty($post_ids)) {
                    $postIdsString = implode(',', array_fill(0, count($post_ids), '?'));
                    $fields = 'id, title, slug, lang_slug, status, seo_title, seo_desc, rating_total, rating_count, views_day, views, views_week, banner, feature, like_count';
                    $page = 1;
                    $limit = 10;
                    $where = 'status = ? AND id IN (' . $postIdsString . ')';
                    $params = array_merge(['active'], $post_ids);
                    $related_post = $this->postsModel->getPostsFieldsPagination($fields, $where, $params, $orderby, $page, $limit);
                    if(!empty($related_post)) {
                        $related_post['data'] = $this->_formatPosts($related_post['data'], true, $posttype);
                    }
                }
            } else {
                $related_post = [];
            }
        }
        
        if(!empty($related_post)) {
            $result = $this->get_success($related_post, Flang::_e('Get_list_related_success'));
            if(empty($related_post['data']))  $result = $this->get_error(Flang::_e('post_not_found'), [], 404);
            echo json_encode($result);
            exit();
        } 
    }

    private function _handle_sort($data_filter = []) {
        // Base conditions
        $where = "status = ?";
        $params = ['active'];
        
        // Get filter data
        $keysearch = $data_filter['keysearch'] ?? '';
        $sort = $data_filter['sort'] ?? [];
        // Add search condition if keysearch is provided
        if (!empty($keysearch)) {
            $where .= " AND (title LIKE ?)";
            $params[] = "%{$keysearch}%";
        }
    
        // Default return if no specific filtering is applied
        // Apply sort if provided
        $orderby = 'created_at desc'; // Default sort
        if (!empty($sort) && count($sort) === 2) {
            if($sort[0] == 'trending') $sort[0] = 'views_day';
            $orderby = $sort[0] . ' ' . $sort[1];
        }
    
        return [
            'where' => $where,
            'params' => $params,
            'orderby' => $orderby,
        ];
    }
    private function _formatPosts($posts, $termshow = false, $posttype = '',  $relationship = []) {
        $formattedItems = [];
        foreach ($posts as $post) {
            if($termshow && !empty($posttype)) {
                $terms = $this->postsModel->getPostTermsByPostId($posttype, $post['id'], APP_LANG);
                $termsNew = [];
                if(!empty($terms)) { 
                    foreach ($terms as $term) {
                        unset($term['id_main']);
                        unset($term['updated_at']);
                        unset($term['created_at']);
                        $termsNew[$term['type']][] = $term;
                    }
                }
            }
            // if($posttype == 'movie') {
            //     $postrelModel = new PostrelModel();
            //     $count = $postrelModel->countPosts(
            //         $post['id'],      // ID của post cần đếm relationship
            //         'movie',    // Loại post
            //         'movie_chapter', // Loại post relationship
            //         false,         // save_rel flag
            //         APP_LANG           // ngôn ngữ
            //     );
            // }
            // if($posttype == 'comic') {
            //     $postrelModel = new PostrelModel();
            //     $chapter_current = $postrelModel->countPosts(
            //         $post['id'],      // ID của post cần đếm relationship
            //         'comic',    // Loại post
            //         'comic_chapter', // Loại post relationship
            //         false,         // save_rel flag
            //         APP_LANG           // ngôn ngữ
            //     );
            // }
            // if($posttype == 'novel') {
            //     $postrelModel = new PostrelModel();
            //     $chapter_current = $postrelModel->countPosts(
            //         $post['id'],      // ID của post cần đếm relationship
            //         'novel',    // Loại post
            //         'novel_chapter', // Loại post relationship
            //         false,         // save_rel flag
            //         APP_LANG           // ngôn ngữ
            //     );
            // }
            $postTmp = [
                'id' => $post['id'],
                'title' => $post['title'],
                'slug' => $post['slug'],
                'status' => $post['status'],
                'rating_count' => $post['rating_count'],
                'rating_total' => $post['rating_total'],
                'views' => $post['views'],
            ];
            if (!empty($post['feature'])){
                $postTmp['feature'] = json_decode($post['feature']);
                if (!empty($postTmp['feature']->path)){
                    $postTmp['feature']->path = '/uploads/'.$postTmp['feature']->path;
                    $postTmp['feature']->square = img_square($postTmp['feature']);
                    $postTmp['feature']->path = img_vertical($postTmp['feature']);
                    if (!empty($postTmp['feature']->resize)){
                        unset($postTmp['feature']->resize);
                    }
                    if (!empty($postTmp['feature']->name)){
                        unset($postTmp['feature']->name);
                    }
                }else{
                    $postTmp['feature'] = null;
                }
            }
            if (!empty($post['banner'])){
                $postTmp['banner'] = json_decode($post['banner']);
                if (!empty($postTmp['banner']->path)){
                    $postTmp['banner']->path = '/uploads/'.$postTmp['banner']->path;
                    if (!empty($postTmp['banner']->resize)){
                        unset($postTmp['banner']->resize);
                    }
                    if (!empty($postTmp['banner']->name)){
                        unset($postTmp['banner']->name);
                    }
                    if(!empty($postTmp['feature']->path)) {
                        $postTmp['feature'] = $postTmp['banner'];
                    }
                }else{
                    $postTmp['banner'] = $postTmp['feature'] ?? null;
                }
            } else {
                $postTmp['banner'] = $postTmp['feature'] ?? null;
            }
            
            if (!empty($post['cinema'])){
                $postTmp['cinema'] = $post['cinema'];
            }
            if (!empty($post['movie_type'])){
                $postTmp['movie_type'] = $post['movie_type'];
            }
            if (!empty($post['duration'])){
                $postTmp['duration'] = $post['duration'];
            }
            if (!empty($post['trailer'])){
                $postTmp['trailer'] = $post['trailer'];
            }
            if (!empty($post['episode_total'])){
                $postTmp['episode_total'] = $post['episode_total'];
            }
            if (!empty($post['like_count'])){
                $postTmp['like_count'] = $post['like_count'];
            }
            if (!empty($post['release_date'])){
                $postTmp['release_date'] = $post['release_date'];
            }
            if(!empty($termsNew)) {
                $postTmp['terms'] = $termsNew;
            }
            if(!empty($count)) {
                $postTmp['episode_current'] = $count;
            }
            if(!empty($chapter_current)) {
                $postTmp['episode_current'] = $chapter_current;
            }
            if(!empty($posttype)) {
                $postTmp['posttype'] = $posttype;
            }
            if( !empty($relationship) && !empty($posttype)) { 
                foreach ($relationship as $field) {
                    $key = $field['field_name'];
                    // if($key === 'chapters') continue;
                    $postrelModel = new PostrelModel();
                    $postsRelationship = $postrelModel->getPosts(
                        $post['id'],      // ID của post cần đếm relationship
                        $posttype, // Loại post relationship
                        $field['post_type_reference'],    // Loại post
                        false,         // save_rel flag
                        APP_LANG           // ngôn ngữ
                    );
                    // Lọc mảng giữ lại chỉ các key cần thiết
                    $filteredPosts = array_map(function($item) {
                        return [
                            'id' => $item['id'],
                            'title' => $item['title'],
                            'slug' => $item['slug']
                        ];
                    }, $postsRelationship);
                    $postsRelationship = $filteredPosts;
                    if(!empty($postsRelationship)) {
                        
                        $postTmp[$key] = $postsRelationship;
                    }
                }
            }
            $formattedItems[]  = $postTmp;
        }
        return $formattedItems;
    }

    private function _formatButton($label, $type, $posttype , $heading, $dataTitle, $api, $sort_by = '', $paged = 1, $page_type ="lists" ) {
        $button = [];
    
        if (!empty($label)) {
            $button["label"] = Flang::_e($label);
        }
    
        if (!empty($type)) {
            $button["type"] = $type;
        }
    
        if (!empty($heading)) {
            $button["heading"] = Flang::_e($heading);
        }
    
        $data = [];
        if (!empty($dataTitle)) { 
            $data["title"] = Flang::_e($dataTitle);
        }
        if (!empty($page_type)) {
            $data["type"] = $page_type;
        }
        if (!empty($posttype)) {
            $data["posttype"] = $posttype;
        }
        if (!empty($api)) {
            $data["api"] = $api;
        }
        if (!empty($data)) {
            $button["data"] = $data;
        }
    
        if (!empty($paged)) {
            $button["paged"] = $paged;
        }
    
        if (!empty($sort_by)) {
            $button["sortby"] = $sort_by;
        }
    
        return $button;
    }
}

 