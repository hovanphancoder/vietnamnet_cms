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
        $this->posttypeModel = new FastModel('fast_posttype');
        $this->termsModel = new FastModel('fast_terms');
    }


    // list
    public function list($posttype = '', $page = 1) {
        try {
            if(empty($posttype)) {
                $this->error('Posttype not empty');
            }
            $posttype = $this->_check_posttype($posttype);
            if(empty($posttype)) {
                $this->error('Posttype not found');
            }
            $sortby = S_GET('sortby') ?? 'id__desc';
            $filter = S_GET('filter') ?? '';
            $terms = S_GET('terms') ?? '';
            
            $posts = $this->postModel;
            // sortby
            $sortby = explode('__', $sortby);
            if(!empty($sortby) && count($sortby) >= 2) {
                // Only call orderBy once for each column-direction pair
                for($i = 0; $i < count($sortby); $i += 2) {
                    if(isset($sortby[$i + 1])) {
                        $posts = $posts->orderBy($sortby[$i], $sortby[$i + 1]);
                    }
                }
            }

            // filter
            $filter = explode('__', $filter);
            if(!empty($filter) && count($filter) >= 2) {
                foreach($filter as $key => $value) {
                    if($key % 2 == 0) {
                        $posts = $posts->where($value, $filter[$key + 1]);
                    }
                }
            } 

            // terms
            $terms = explode('__', $terms);
            if(!empty($terms) && $terms[0] > 0) {
                $table_relations = table_posttype_relationship($posttype);
                $posts = $posts->join($table_relations, $table_relations.'.post_id', '=', 'id')
                ->where($table_relations.'.rel_type', 'term');

                foreach($terms as $term) {
                    $posts = $posts->where($table_relations.'.rel_id', $term);
                }
            }

            // status = active
            $posts = $posts->where('status', 'active');
            $posts = $posts->limit($this->limit + 1);
            $posts = $posts->offset(($page - 1) * $this->limit);
            $posts = $posts->get();
            
            $posts = $this->_formatPosts($posts, false);
            
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
    public function detail($posttype = '', $id = '') {
        try {
            $this->_check_posttype($posttype);
            // check if id is numeric then it's id, if text then it's slug
            if(is_numeric($id)) {
                $post = $this->postModel->where('id', $id)->first();
            } else {
                $post = $this->postModel->where('slug', $id)->first();
            }

            if(empty($post)) {
                $this->error('Post not found');
            }
            
            $post = $this->_formatPosts([$post], true);
            $post = $post[0];
            $this->success($post, 'Post detail');
        } catch (AppException $e) {
            $this->error($e->getMessage(), [], 500);
        } catch (\Exception $e) {
            $this->error('Internal server error', [], 500);
        }
    }


    // // update
    // public function update($posttype = '', $id = '') {
    //     try {
    //         // check authentication
    //         $this->_permission('update_post');
    //         $this->_check_posttype($posttype);
    //         echo 'update';
    //         exit;
    //     }
    // }


    // update view
    public function update_view($posttype = '', $id = '') {
        try {
            $this->_check_posttype($posttype);
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

    public function search($posttype = '', $page = 1) {
        try {
            $this->_check_posttype($posttype);
            $keyword = $_GET['s'] ?? '';
            if(empty($keyword)) {
                $this->error('Keyword not empty');
            } 
            //remove special characters string string helper
            $keyword = keyword_slug($keyword);
            $posts = $this->postModel->where('search_string', 'like', '%'.$keyword.'%')->paginate(10, $page);
            // $posts = $this->_formatPosts($posts, false);
            $this->success($posts, 'Posts search');
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
    
}