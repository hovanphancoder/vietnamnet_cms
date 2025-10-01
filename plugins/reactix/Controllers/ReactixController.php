<?php 
namespace Plugins\Reactix\Controllers;

use App\Controllers\ApiController;
use App\Models\FastModel;
use App\Models\UsersModel;
use App\Libraries\Fasttoken;
use System\Libraries\Session;
use System\Core\AppException;

class ReactixController extends ApiController
{
    protected $usersModel;

    public function __construct() {
        parent::__construct();
        load_helpers(['string', 'database']);
        $this->usersModel = new UsersModel();
    }

    // get comment
    public function get_comment($posttype = '', $post_id = 0, $paged = 1) {
        try {
            // get comment
            $table = posttype_name('comment');
            $commentModel = new FastModel($table);
            
            // Get parent comments (par_comment = 0)
            $comments = $commentModel->where('posttype', $posttype)
                                    ->where('post_id', $post_id)
                                    ->where('par_comment', 0)
                                    ->orderBy('created_at', 'DESC')
                                    ->paginate(5, $paged);
            $parentIds = array_column($comments['data'], 'id');
            // If there are parent comments, get corresponding child comments
            $childComments = [];
            if (!empty($parentIds)) {
                $childComments = $commentModel->newQuery()
                                            ->whereIn('par_comment', $parentIds)
                                            ->get();
            }

            

            // Group children by parent id
            $groupedChildren = [];
            foreach ($childComments as $child) {
                // Add user for child comment
                $child['user'] = get_user($child['user_id']);
                $groupedChildren[$child['par_comment']][] = $child;
            }

            // Add children and user to data of each parent comment
            foreach ($comments['data'] as &$comment) {
                $comment['user'] = get_user($comment['user_id']);
                $comment['children'] = $groupedChildren[$comment['id']] ?? [];
            }
            $this->success($comments, 'Comment retrieved successfully');
        } catch (AppException $e) {
            $this->error($e->getMessage(), [], 500);
        }
    }

    // comment 
    public function comment() {
        try {
            // user_id, rating, par_comment, content, posttype, post_id
            $rating = S_POST('rating') ?? 0;
            $par_comment = S_POST('par_comment') ?? 0;
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
                // rating_avg = (rating_total + rating) / (rating_count + 1) round to first decimal and multiply by 10 to become integer
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