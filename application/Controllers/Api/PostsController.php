<?php 
namespace App\Controllers\Api;

use App\Controllers\ApiController;
use App\Libraries\Fasttoken;
use System\Libraries\Session;
use System\Core\AppException;
use App\Models\FastModel;

class PostsController extends ApiController
{
    protected $posttypeModel;
    protected $termsModel;
    protected $postModel;
    protected $limit = 10;
    
    public function __construct() {
        parent::__construct();
        load_helpers(['string']);
        $this->posttypeModel = new FastModel(APP_PREFIX.'posttype');
        $this->termsModel = new FastModel(APP_PREFIX.'terms');
    }


    // list
    public function list($posttype_slug = '', $page = 1) {
        try {
            $page = (int) $page;
            if(empty($posttype_slug)) {
                $this->error('Posttype not empty');
            }
            
            $posttype = $this->_check_posttype($posttype_slug);
            $fields_posttype =  json_decode($posttype['fields'], true);
            $fields_name_allow = array_column($fields_posttype, 'field_name');
            if(empty($posttype)) {
                $this->error('Posttype not found');
            }
            $sortby = S_GET('sortby') ?? 'id__desc';
            $filter = S_GET('filter') ?? '';
            $terms = S_GET('terms') ?? '';
            $search = S_GET('s') ?? '';
            
            $posts = $this->postModel;
            // sortby
            $sortby = explode('__', $sortby);
            if(!empty($sortby) && count($sortby) >= 2) {
                // Only call orderBy once for each column-direction pair
                for($i = 0; $i < count($sortby); $i += 2) {
                    if(isset($sortby[$i + 1])) {
                        if(in_array($sortby[$i], $fields_name_allow)) { 
                            $posts = $posts->orderBy($sortby[$i], $sortby[$i + 1]);
                        }
                    }
                }
            }

            // filter
            $filter = explode('__', $filter);
            if(!empty($filter) && count($filter) >= 2) {
                
                foreach($filter as $key => $value) {
                    if($key % 2 == 0) {
                        // if( inclue _min) thì là >= value ví dụ price_min__1000000 thì là price >= 1000000
                        // nếu chưa _max thì là <= value ví dụ price_max__1000000 thì là price <= 1000000
                        if(strpos($value, '_min') !== false) {
                            $value = str_replace('_min', '', $value);
                            if(in_array($value, $fields_name_allow)) {
                                $posts = $posts->where($value, '>=', $filter[$key + 1]);
                            }
                        } else if(strpos($value, '_max') !== false) {
                            $value = str_replace('_max', '', $value);
                            if(in_array($value, $fields_name_allow)) {
                                $posts = $posts->where($value, '<=', $filter[$key + 1]);
                            }
                        } 
                        if(in_array($value, $fields_name_allow)) {
                            $posts = $posts->where($value, $filter[$key + 1]);
                        }
                    }
                }
            } 
            // terms
            $terms = explode('__', $terms);
            if(!empty($terms) && $terms[0] > 0) {
                $table_relations = table_posttype_relationship($posttype_slug);
                $posts = $posts->join($table_relations, $table_relations.'.post_id', '=', 'id')
                ->where($table_relations.'.rel_type', 'term');

                foreach($terms as $term) {
                    $posts = $posts->where($table_relations.'.rel_id', $term);
                }
            }
            // search
            if(!empty($search)) {
                // search key bỏ dấu đi nè
                $search = keyword_slug($search);
                $posts = $posts->where('search_string', 'like', '%'.$search.'%');
            }

            // status = active
            $posts = $posts->where('status', 'active');
            $posts = $posts->limit($this->limit + 1);
            $posts = $posts->offset(($page - 1) * $this->limit);
            $posts = $posts->get();
            $posts = $this->_formatPosts($posttype_slug, $posts, false);
            
            if(count($posts) > $this->limit) {
                $data = array_slice($posts, 0, $this->limit);
                $is_next = true;
            } else {
                $data = $posts;
                $is_next = false;
            }

            $this->success(['data' => $data, 'is_next' => $is_next, 'page' => $page, 'limit' => $this->limit], 'Posts list');
        } catch (AppException $e) { 
            $this->error($e->getMessage(), [], 500);
        } catch (\Exception $e) {
            $this->error('Internal server error', [], 500);
        }
    }
    // detail
    public function detail($posttype_slug = '', $id = '') {
        try {
            $this->_check_posttype($posttype_slug);
            // check if id is numeric then it's id, if text then it's slug
            if(is_numeric($id)) {
                $post = $this->postModel->where('id', $id)->first();
            } else {
                $post = $this->postModel->where('slug', $id)->first();
            }

            if(empty($post)) {
                $this->error('Post not found');
            }
            $post = $this->_formatPosts($posttype_slug, [$post], true);
            
            $post = $post[0];
            $this->success($post, 'Post detail');
        } catch (AppException $e) {
            $this->error($e->getMessage(), [], 500);
        } catch (\Exception $e) {
            $this->error('Internal server error', [], 500);
        }
    }

    // update view
    public function update_view($posttype_slug = '', $id = '') {
        try {
            $this->_check_posttype($posttype_slug);
            $post = $this->postModel->where('id', $id)->first();
            // check column views, views_day, views_month, views_year, views_total if exist then + 1
            if(isset($post['views'])) {
                $update_data['views'] = $post['views']++;
            }
            if(isset($post['views_day'])) {
                $update_data['views_day'] = $post['views_day']++;
            }
            if(isset($post['views_month'])) {
                $update_data['views_month'] = $post['views_month']++;
            }
            if(isset($post['views_year'])) {
                $update_data['views_year'] = $post['views_year']++;
            }
            if(isset($post['views_total'])) {
                $update_data['views_total'] = $post['views_total']++;
            }

            // update views
            $update_status = $this->postModel->where('id', $id)->update($update_data);
            if($update_status) {
                $this->success(['message' => 'Update view success'], 'Update view success');
            } else {
                $this->error('Update view failed', [], 500);
            }
            } catch (AppException $e) {
                $this->error($e->getMessage(), [], 500);
            } catch (\Exception $e) {
                $this->error('Internal server error', [], 500);
        }
    }


    // terms 
    public function terms($posttype = '', $type = '') {
        try {
            $terms = $this->termsModel;
            if(!empty($posttype)) {
                $terms = $terms->where('posttype', $posttype);
            }
            if(!empty($type)) {
                $terms = $terms->where('type', $type);
            }
            $terms = $terms->whereIn('lang', [APP_LANG, 'all']);
            $terms = $terms->get();
            $terms = $this->_formatTerms($terms);
            $this->success($terms, 'Terms');
        } catch (AppException $e) {
            $this->error($e->getMessage(), [], 500);
        } catch (\Exception $e) {
            $this->error('Internal server error', [], 500);
        }
    }

    // format terms 
    private function _formatTerms($terms) {
        // phân cấp cha con dựa vào parent = id term cha
        // check xem id cha có tồn tại ko đã
        if(!empty($terms)) {
            foreach($terms as $key => $term) {
                if($term['parent'] > 0) {
                    foreach($terms as $term2) {
                        if($term2['id'] == $term['parent']) {
                            $terms[$key]['children'][] = $term2;
                        }
                    }
                }
            }
        }
        return $terms;
    }

    protected function _formatPosts($posttype_slug, $posts, $terms = true) {
        // _check_posttype
        $posttype = $this->_check_posttype($posttype_slug);
        if(empty($posttype)) {
            return $this->error_v2('Posttype not found');
        }

        if($terms) {
            $ids = array_column($posts, 'id');
            $table_rel = table_posttype_relationship($posttype_slug);
            $relModel = new FastModel($table_rel);
            // call all id terms in table_rel
            $terms = $relModel->whereIn('post_id', $ids)->get();
        }
        // Tạo key_type array từ posttype fields
            $key_type = [];
            if(is_string($posttype['fields'])) {
                $posttype['fields'] = json_decode($posttype['fields'], true);
            }
            if (!empty($posttype['fields']) && is_array($posttype['fields'])) {
                foreach ($posttype['fields'] as $field) {
                    if (isset($field['field_name']) && isset($field['type'])) {
                        $key_type[$field['field_name']] = $field['type'];
                    }
                }
            }
            foreach ($posts as &$post) {
            foreach($post as $key => &$value) {
                if(isset($key_type[$key])) {
                    switch($key_type[$key]) {
                        case 'Text':
                            $value = htmlspecialchars($value);
                            break;
                        case 'Textarea':
                            $value = htmlspecialchars($value);
                            break;
                        case 'Image':
                            if (!empty($value)){
                                $value = json_decode($value);
                                if (!empty($value->path)){
                                    $value->path = '/uploads/'.$value->path;
                                    $value->square = img_square($value);
                                    $value->path = img_vertical($value);
                                    if (!empty($value->resize)){
                                        unset($value->resize);
                                    }
                                    if (!empty($value->name)){
                                        unset($value->name);
                                    }
                                }else{
                                    $value = null;
                                }
                            }
                            break;
                        case 'File':
                            $value = json_decode($value);
                            if (!empty($value->path)){
                                $value->path = '/uploads/'.$value->path;
                            }
                            break;
                        case 'Number':
                            $value = (int)$value;
                            break;
                        case 'Date':
                            $value = date('Y-m-d', strtotime($value));
                            break;
                        case 'Datetime':
                            $value = date('Y-m-d H:i:s', strtotime($value));
                            break;
                        case 'Repeater':
                            if(!empty($value) && is_string($value)) {
                                $value = json_decode($value);
                            }
                            break;
                        case 'Flexible':
                            $value = json_decode($value);
                            break;
                    }
                }
            }
            }
        return $posts;
    }


    protected function _check_posttype($posttype) {

        if(empty($posttype)) {
            return false;
        }
        $posttype = $this->_get_posttype($posttype);
        if(empty($posttype)) {
            return false;
        }
        $posttype_lang = isset($posttype['languages']) && is_string($posttype['languages']) ? json_decode($posttype['languages'], true) : [];
        if($posttype_lang[0] == 'all') {
            $this->postModel = new FastModel(posttype_name($posttype['slug']));
            return $posttype;
        } elseif(in_array(APP_LANG, $posttype_lang)) {
            $this->postModel = new FastModel(posttype_name($posttype['slug'], APP_LANG));
            return $posttype;
        } else {
            return false;
        }
    }


    protected function _get_posttype($posttype_slug) {
        // put in global to avoid calling repeatedly like $posttype['slug']
        global $posttype;
        if(!isset($posttype['slug'])) {
            $posttype = $this->posttypeModel->where('slug', $posttype_slug)->first();
            if (!$posttype) {
                return false;
            }
        }
        return $posttype;
    }
    
}