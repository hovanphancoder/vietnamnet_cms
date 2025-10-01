<?php 
namespace App\Controllers\Api;

use App\Controllers\ApiController;
use App\Models\FastModel;
use App\Models\UsersModel;
use App\Libraries\Fasttoken;
use System\Libraries\Session;
use System\Core\AppException;

class PostController extends ApiController
{
    protected $usersModel;

    public function __construct() {
        parent::__construct();
        load_helpers(['string']);
        $this->usersModel = new UsersModel();
    }

    // update views
    public function update_view($posttype = '', $id = '') {
        try {
            $table = posttype_name($posttype);
            $postModel = new FastModel($table);
            $post = $postModel->where('id', $id)->first();
            if (!$post) {
                throw new AppException('Post not found');
            }

            // Check which view columns the post has
            $viewsColumns = ['views', 'views_day', 'views_week', 'views_month'];
            $updatedColumns = [];
            foreach ($viewsColumns as $column) {
                if (isset($post[$column])) {
                    $updatedColumns[$column] = $post[$column] + 1;
                }
            }

            // Update view columns without affecting updated_at
            // add updated_at column = old time
            $updatedColumns['updated_at'] = $post['updated_at'];
            $postModel->where('id', $id)->update($updatedColumns);
            
            $this->success($updatedColumns, 'View updated successfully');
            
        } catch (AppException $e) {
            $this->error($e->getMessage(), [], 500);
        } catch (\Exception $e) {
            $this->error('Internal server error', [], 500);
        }
    }

    // comment 
    public function comment() {
        try {
            // user_id, rating, par_comment, content, posttype, post_id
            $rating = S_POST('rating') ?? 0;
            $par_comment = S_POST('par_comment') ?? null;
            $content = S_POST('content') ?? '';
            $posttype = S_POST('posttype') ?? '';
            $post_id = S_POST('post_id') ?? 0;

            if (empty($post_id)) {
                $this->error('Invalid post id', [], 400);
            }
            if(empty($posttype)) {
                $this->error('Invalid post type', [], 400);
            }

            // check rating must be integer and from 0 to 5
            if (!is_numeric($rating) || $rating < 0 || $rating > 5) {
                $this->error('Invalid rating', [], 400);
            }

            // check if post exists
            $table = posttype_name($posttype);
            $postModel = new FastModel($table);
            $post = $postModel->where('id', $post_id)->first();

            if (!$post) {
                $this->error('Post not found', [], 404);
            }

            // check if user exists by checking token or session
            $user = $this->_auth();
            if (!$user) {
               $user_id = 0;
            } else {
                $user_id = $user['id'];
            }

            // if rating > 0 then update both APP_PREFIX.posts_{posttype} $post rating_count, rating_total, rating_avg
            if ($rating > 0) {
                // rating_avg = (rating_total + rating) / (rating_count + 1) round to first decimal and multiply by 10 to integer
                $rating_avg = round(($post['rating_total'] + $rating) / ($post['rating_count'] + 1) * 10, 0);
                $postModel->where('id', $post_id)->update([
                    'rating_count' => $post['rating_count'] + 1, 
                    'rating_total' => $post['rating_total'] + $rating, 
                    'rating_avg' => $rating_avg,
                    'updated_at' => $post['updated_at'],
                ]);
            }
            
            // add comment
            $title = 'Comment on ' . $post['title'];
            $slug = url_slug($title);
            $table = posttype_name('comment');
            $commentModel = new FastModel($table);
            // only insert if content is not empty
            if (!empty($content)) {
                $comment = $commentModel->insert([
                    'title' => $title,
                    'slug' => $slug,
                    'user_id' => $user_id,
                    'rating' => $rating,
                    'par_comment' => $par_comment,
                    'content' => $content,
                    'posttype' => $posttype,
                    'post_id' => $post_id,
                ]);
                if($comment) {
                    $this->success($comment, 'Comment added successfully');
                } else {
                    $this->error('Comment not added', [], 400);
                }
            } else {
                $this->success([], 'Rating added successfully');
            }

            
        } catch (AppException $e) {
            $this->error($e->getMessage(), [], 500);
        }
    }

    // Process story (save/favorite/read_chapter)
    public function process_story() {
        try {
            $user = $this->_auth();
            if (!$user) {
                $this->error('User not found', [], 404);
            }

            // Get action from request
            $action = S_POST('action');
            $posttype = S_POST('posttype');
            $id = S_POST('id');
            $chapter_number = S_POST('chapter_number') ?? 0;
            if (empty($action) || empty($posttype) || empty($id)) {
                $this->error('Invalid request', [], 400);
            }
            if (!in_array($action, ['save', 'favorite', 'read_chapter'])) {
                $this->error('Invalid action', [], 400);
            } 

            // Check if story exists
            $table = posttype_name($posttype);
            $postModel = new FastModel($table);
            $post = $postModel->where('id', $id)->first();
            if (!$post) {
                $this->error('Post not found', [], 404);
            }

            // Process in process_user_stories table
            $posttype_process_user_stories = 'user_chapter_progress';
            $table_process_user_stories = posttype_name($posttype_process_user_stories);
            $process_user_storiesModel = new FastModel($table_process_user_stories);
            $process_user_stories = $process_user_storiesModel->where('user_id', $user['id'])->where('post_id', $id)->first();
            switch ($action) {
                case 'save':
                    $column = 'saved';
                    $value = '1';
                    break;
                case 'favorite':
                    $column = 'favorite';
                    $value = '1';
                    break;
                case 'read_chapter':
                    $column = 'chapter_number';
                    $value = $chapter_number;
                    break;
            }
            $current_value = $process_user_stories[$column] ?? '0';
            if ($process_user_stories) {
                // If exists then process by column type
                if ($column === 'chapter_number') {
                    // Only update if new chapter is greater than current chapter
                    $current_chapter = $process_user_stories[$column] ?? '0';
                    if ($value > $current_chapter) {
                        $process_user_storiesModel->where('id', $process_user_stories['id'])->update([$column => $value]);
                    }
                } else {
                    // Toggle processing for saved and favorite
                    $new_value = $current_value === '1' ? '0' : '1';
                    $process_user_storiesModel->where('id', $process_user_stories['id'])->update([$column => $new_value]);
                }
            } else {
                // If not exists then create new
                $process_user_storiesModel->insert([
                    'title' => $user['username'] . ' saved ' . $post['title'],
                    'slug' => url_slug($user['username'] . ' saved ' . $post['title']),
                    'user_id' => $user['id'],
                    'post_id' => $id,
                    'posttype' => $posttype,
                    'chapter_number' => 0,
                    $column => $value
                ]);
            }

            // if $column = favorite then update like_count column, if favorite = 1 then increase by 1, otherwise decrease by 1
            if ($column === 'favorite') {
                
                if ($current_value === '1') {
                    $postModel->where('id', $id)->update([
                        'like_count' => $post['like_count'] - 1,
                        'updated_at' => $post['updated_at']
                    ]);
                } else {
                    $postModel->where('id', $id)->update([
                        'like_count' => $post['like_count'] + 1,
                        'updated_at' => $post['updated_at']
                    ]);
                }
            }

            $this->success([], 'Story processed successfully');
            
        } catch (AppException $e) {
            $this->error($e->getMessage(), [], 500);
        }
    }

    // get process to update UI layouts
    public function process($posttype = '', $id = 0) {
        try {
            $user = $this->_auth();
            if (!$user) {
                $this->error('User not found', [], 404);
            }

            // get saved and liked stories
            $table = posttype_name('user_chapter_progress');
            $processModel = new FastModel($table);
            $process = $processModel->where('user_id', $user['id'])->where('post_id', $id)->where('posttype', $posttype)->first();
            if (!$process) {
                $this->error('Process not found', [], 404);
            }

            $this->success($process, 'Process retrieved successfully');
        } catch (AppException $e) {
            $this->error($e->getMessage(), [], 500);
        }
    }

    // like comment
    public function like_comment($id = 0) {
        try {
            // check if comment exists
            $table = posttype_name('comment');
            $commentModel = new FastModel($table);
            $comment = $commentModel->where('id', $id)->first();
            if (!$comment) {
                $this->error('Comment not found', [], 404);
            }
            // remember to convert to number
            $like_count = max(0, (int)($comment['like_count'] ?? 0)) + 1;
            $commentModel->where('id', $id)->update([
                'like_count' => $like_count
            ], ['timestamps' => false]);
            $this->success(['like_count' => $like_count], 'Comment liked successfully');
        } catch (AppException $e) {
            $this->error($e->getMessage(), [], 500);
        }
    }

    protected function _auth() {
        $access_token = Fasttoken::headerToken();
        if(Session::has('user_id')) {
            $user_id = clean_input(Session::get('user_id'));
        } elseif (!empty($access_token)) {
            $me_data = Fasttoken::checkToken($access_token);
            if (empty($me_data)) {
                return false;
            }
            $user_id = $me_data['user_id'] ?? null;
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