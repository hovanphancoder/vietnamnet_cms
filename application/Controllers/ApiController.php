<?php
namespace App\Controllers;

use System\Core\BaseController;
use App\Models\UsersModel;
use App\Models\LanguagesModel;
use App\Libraries\Fasttoken;
use App\Libraries\Fastlang as Flang;
use System\Libraries\Session;
use System\Drivers\Cache\UriCache;
use App\Models\FastModel;


class ApiController extends BaseController
{
    protected $usersModel;
    protected $languagesModel;
    protected $posttypeModel;
    protected $posttypeLang = APP_LANG;

    public function __construct()
    {
        // Call BaseController constructor (to maintain common functionality)
        parent::__construct();
        load_helpers(['backend', 'frontend', 'images']);
        $this->usersModel = new UsersModel();
        $this->languagesModel = new LanguagesModel();
    }

    protected function _authentication() {
        $access_token = Fasttoken::getToken();
        if(Session::has('user_id')) {
            $user_id = clean_input(Session::get('user_id'));
        } elseif (!empty($access_token)) {
            $config_security = config('security');
            $me_data = Fasttoken::decodeToken($access_token, $config_security['app_secret']);
            if (!isset($me_data['success'])) {
                return $this->error(Flang::_e('auth_token_invalid'), [$me_data['message']], 401);
            }
            $user_id = $me_data['data']['user_id'] ?? null;
            if (empty($user_id)) {
                return $this->error(Flang::_e('token_invalid'), [], 401);
            }
        } else {
            $this->error(Flang::_e('user_not_found'), [], 403);
        }

        $user = $this->usersModel->getUserById($user_id);
        if (empty($user)) {
            return $this->error(Flang::_e('user_not_found'), [], 404);
        } else {
            return $user;
        }
    }


    protected function _permission($permission) {
        $user = $this->_authentication();
        print_r($user);
        exit;
        if(empty($user)) {
            return $this->error(Flang::_e('user_not_found'), [], 404);
        }
        return $user;
    }


    protected function _caching($functionName = '') {
        $cacheConfig = option('api_cache') ?? [];
        // decode cache config
        $cacheConfig = is_string($cacheConfig) ? json_decode($cacheConfig, true) : $cacheConfig;
        $config = [];
        foreach ($cacheConfig as $cache) {
            if (strtolower($cache['cache_function']) == $functionName) {
                $config = $cache;
                break;
            }
        }

        if(isset($config['cache_caching']) && $config['cache_caching']) {
            if(empty($config['cache_level']) || $config['cache_level'] == 'default') {
                $config['cache_level'] = option('cache_level') ?? 0;
            }
            $cache = new UriCache($config['cache_level'], $config['cache_type']);
            $cache->cacheLogin($config['cache_login'] ?? 0);
            $cache->cacheMobile($config['cache_mobile'] ?? 0);
            return $cache;
        } else {
            return false;
        }
    }

    // format_data
    protected function _formatPosts($posts, $terms = true) {
        if($terms) {
            $formattedItems = [];
            // get list of post IDs
            $ids = array_column($posts, 'id');
            $table_rel = table_posttype_relationship('movie');
            $table_terms = 'fast_terms';

            $termsModel = new FastModel($table_terms);
            // get list of terms for posts
            // join fast_terms table with fast_term_relationships
            $terms = $termsModel->join($table_rel, $table_rel.'.rel_id', '=', $table_terms.'.id_main')
            ->whereIn($table_rel.'.post_id', $ids)
            ->get();
        }

        foreach ($posts as $post) {
            $postTmp = [
                'id' => $post['id'],
                'title' => $post['title'],
                'slug' => $post['slug'],
                'status' => $post['status'],
                'rating_count' => $post['rating_count'],
                'rating_total' => $post['rating_total'],
                'views' => $post['views'],
            ];
            // iterate through terms array
            if($terms) {
                foreach ($terms as $term) {
                    if ($term['post_id'] == $post['id']) {
                        $postTmp[$term['type']][] = [
                            'id' => $term['id_main'],
                            'slug' => $term['slug'],
                            'name' => $term['name'],
                            'type' => $term['type'],
                        ];
                    }
                }
            }
            // description
            if (!empty($post['description'])){
                $postTmp['description'] = $post['description'];
            }

            // original title = seo title minus title part
            if (!empty($post['seo_title'])){
                // "Cung Đường Về Nhà - Long Way Home"
                $title = explode(' - ', $post['seo_title']);
                $postTmp['original_title'] = $title[1] ?? '';
            }

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
                    if(empty($postTmp['feature']->path)) {
                        $postTmp['feature'] = $postTmp['banner'];
                    }
                }else{
                    $postTmp['banner'] = $postTmp['feature'] ?? null;
                }
            } else {
                $postTmp['banner'] = $postTmp['feature'] ?? null;
            }

            // title_image
            if (!empty($post['title_image'])){
                $postTmp['title_image'] = json_decode($post['title_image']);
                if (!empty($postTmp['title_image']->path)){
                    $postTmp['title_image']->path = '/uploads/'.$postTmp['title_image']->path;
                    if (!empty($postTmp['title_image']->resize)){
                        unset($postTmp['title_image']->resize);
                    }
                    if (!empty($postTmp['title_image']->name)){
                        unset($postTmp['title_image']->name);
                    }
                }else{
                    $postTmp['title_image'] = null;
                }
            } else {
                $postTmp['title_image'] = null;
            }

            
            // original title
            if (!empty($post['original_language'])){
                $postTmp['original_language'] = $post['original_language'];
            }

            // rating avg
            if (!empty($post['rating_avg'])){
                // divide by 10 to get 1 decimal place
                $postTmp['rating_avg'] = round($post['rating_avg'] / 10, 1);
            }
            // quality
            if (!empty($post['quality'])){
                $postTmp['quality'] = $post['quality'];
            }

            // imdb_id
            if (!empty($post['imdb_id'])){
                $postTmp['imdb'] = $post['imdb_id'];
            } else {
                $postTmp['imdb'] = null;
            }

            // cinema
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
            if(!empty($post['chapter_total'])) {
                $postTmp['episode_current'] = $post['chapter_total'];
                $postTmp['episode_total'] = $post['chapter_total'];
                $postTmp['chapter_total'] = $post['chapter_total'];
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

            // movie_status
            if (!empty($post['movie_status'])){
                $postTmp['movie_status'] = $post['movie_status'];
            }
            $formattedItems[]  = $postTmp;
        }
        return $formattedItems;
    }


    

    protected function _check_posttype($posttype) {

        if(empty($posttype)) {
            $this->error('Posttype not found');
        }
        $posttype = $this->_get_posttype($posttype);
        $posttype_lang = isset($posttype['languages']) && is_string($posttype['languages']) ? json_decode($posttype['languages'], true) : [];
        if($posttype_lang[0] == 'all') {
            $this->postModel = new FastModel(posttype_name($posttype['slug']));
            return $posttype;
        } elseif(in_array(APP_LANG, $posttype_lang)) {
            $this->postModel = new FastModel(posttype_name($posttype['slug'], APP_LANG));
            return $posttype;
        } else {
            $this->error('Posttype not found');
        }
    }


    protected function _get_posttype($posttype_slug) {
        // put in global to avoid calling repeatedly like $posttype['slug']
        global $posttype;
        if(!isset($posttype['slug'])) {
            $posttype = $this->posttypeModel->where('slug', $posttype_slug)->first();
            if (!$posttype) {
                throw new AppException('Posttype not found');
            }
        }
        return $posttype;
    }

}