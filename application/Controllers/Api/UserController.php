<?php 
namespace App\Controllers\Api;

use App\Controllers\ApiController;
use App\Models\FastModel;
use App\Models\UsersModel;
use App\Libraries\Fasttoken;
use System\Libraries\Session;
use System\Core\AppException;

class UserController extends ApiController
{
    protected $usersModel;
    protected $FastModel;
    public function __construct() {
        parent::__construct();
        load_helpers(['string', 'frontend']);
        $this->usersModel = new UsersModel();
    }

    public function me() {
        $user = $this->_authen_check();
        // unset some sensitive information
        unset($user['password']);
        unset($user['email']);
        unset($user['phone']);
        unset($user['address']);
        unset($user['created_at']);
        unset($user['updated_at']);
        unset($user['location']);
        unset($user['permissions']);
        unset($user['status']);
        
        if(empty($user)) {
            return $this->error('Unauthorized', [], 401);
        }
        $user['avatar'] = get_avatar($user['id']);
        $this->success($user, 'User info successfully');
    }

    public function count_reading_challenge() {
        $user = $this->_authen_check();
        if(!$user) {
            return $this->error('Unauthorized', [], 401);
        }
        $posttype = 'user_chapter_progress';
        $table = table_posttype($posttype);
        $this->FastModel = new FastModel($table);
        $qb = $this->FastModel->newQuery();
        // count where user_id = $user['id']
        $qb->where('user_id', $user['id']);
        // in current year
        $qb->where('created_at', '>=', date('Y-m-d H:i:s', strtotime('-1 year')));
        $qb->where('created_at', '<=', date('Y-m-d H:i:s'));
        $qb->where('chapter_number', '>', 0);
        $count = $qb->count();
        $this->success($count, 'Count reading challenge successfully');
    }


    // get reading history
    public function reading_history() {
        $user = $this->_authen_check();
        if(!$user) {
            return $this->error('Unauthorized', [], 401);
        }

        $posttype = 'user_chapter_progress';
        $posttype_stories = 'stories';
        $user_chapter_progress_table = table_posttype($posttype);
        $stories_table = table_posttype($posttype_stories);
        
        // 1. Get story_id list from user_chapter_progress
        $progressModel = new FastModel($user_chapter_progress_table);
        $progressQuery = $progressModel->newQuery();
        $progressQuery->select([
            'post_id',
            'chapter_number',
            'saved',
            'favorite',
            'created_at as last_read_at'
        ]);
        $progressQuery->where('user_id', $user['id']);
        $progressQuery->orderBy('created_at', 'desc');
        $progressQuery->limit(10);
        $progressList = $progressQuery->get();

        if(empty($progressList)) {
            return $this->success([], 'Reading history successfully');
        }

        // 2. Get post_id list
        $storyIds = array_column($progressList, 'post_id');

        // 3. Query stories with post_id list
        $storiesModel = new FastModel($stories_table);
        $storiesQuery = $storiesModel->newQuery();
        $storiesQuery->whereIn('id', $storyIds);
        $stories = $storiesQuery->get();

        // 4. Merge information
        $result = [];
        foreach($stories as $story) {
            $progress = array_filter($progressList, function($item) use ($story) {
                return $item['post_id'] == $story['id'];
            });
            $progress = reset($progress);
            
            $result[] = array_merge($story, [
                'chapter_number' => $progress['chapter_number'],
                'saved' => $progress['saved'],
                'favorite' => $progress['favorite'],
                'last_read_at' => $progress['last_read_at']
            ]);
        }

        $this->success($result, 'Reading history successfully');
    }


    protected function _authen_check() {
        $access_token = Fasttoken::getToken();
        if(Session::has('user_id')) {
            $user_id = clean_input(Session::get('user_id'));
        } elseif (!empty($access_token)) {
            $config_security = config('security');
            $me_data = Fasttoken::decodeToken($access_token, $config_security['app_secret']);
            if (!isset($me_data['success'])) {
                return false;
            }
            $user_id = $me_data['data']['user_id'] ?? null;
            if (empty($user_id)) {
                return false;
            }
        } else {
            return false;
        }

        $user = $this->usersModel->getUserById($user_id);
        if (empty($user)) {
            return false;
        } else {
            return $user;
        }
    }


    
}