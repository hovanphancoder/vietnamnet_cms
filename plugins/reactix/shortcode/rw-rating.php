<?php 
// load database_helper
use App\Models\FastModel;
add_shortcode('rw-rating', function ($posttype, $post_id) {
    $table = posttype_name('comment');
    $commentModel = new FastModel($table);

    // Get 10 parent comments (par_comment = 0)
    $comments = $commentModel->where('posttype', $posttype)
                            ->where('post_id', $post_id)
                            ->where('par_comment', 0)
                            ->orderBy('created_at', 'DESC')
                            ->paginate(5, 1);

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
        $groupedChildren[$child['par_comment']][] = $child;
    }    
    ob_start();
    ?>
    <div class="wp-rating-container bg-white rounded-lg shadow-md p-6"
    data-posttype="<?= $posttype; ?>"
    data-post_id="<?= $post_id; ?>"
    >
        <h2 class="text-xl font-bold text-gray-800 mb-6">
            Bình Luận & Đánh Giá
        </h2>
        <form class="mb-8">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Đánh giá của bạn:</label>
                <div class="flex items-center star-rating">
                    <div class="flex">
                        <?php for($i = 1; $i <= 5; $i++): ?>
                            <img data-src-start="<?= assets_url('images/star.svg'); ?>" src="<?= assets_url('images/star-empty.svg'); ?>" alt="star" class="star">
                        <?php endfor; ?>
                    </div>
                    <span id="rating-display" class="ml-2 text-sm text-gray-600">Chưa đánh giá</span>
                </div>
            </div>
            <div class="mb-4">
                <label for="comment" class="block text-sm font-medium text-gray-700 mb-2">Comment của bạn: </label>
                <textarea
                    class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-base ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm min-h-[100px]"
                    id="comment"
                    placeholder="Chia sẻ suy nghĩ của bạn về truyện này..."></textarea>
            </div>
            <button
                id="submit-comment"
                class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 h-10 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white"
                type="button" disabled>
                Gửi bình luận
            </button>
        </form>

        <!-- Comments container -->
        <div id="comments-container" class="space-y-6 comment-container collapsed">
            <?php foreach ($comments['data'] as $index => $comment): 
                $user = get_user($comment['user_id']);
                $rating = (int)($comment['rating'] ?? 0);
                $likeCount = (int)($comment['like_count'] ?? 0);
                $date = date('d/m/Y', strtotime($comment['created_at']));
                $content = nl2br(htmlspecialchars($comment['content']));
                $index_comment = $index + 1;
            ?>
                <div class="comment-item item-<?php echo $index_comment; ?> border-b border-gray-100 pb-6 last:border-0 last:pb-0">
                    <div class="flex items-start">
                        <span class="relative flex shrink-0 overflow-hidden rounded-full h-10 w-10 mr-4">
                            <img class="aspect-square h-full w-full"
                                alt="<?= $user['fullname'] ?? '' ?>"
                                src="<?= get_avatar($user['id']); ?>" />
                        </span>

                        <div class="flex-grow">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-2"
                                data-comment-id="<?= $comment['id']; ?>"
                            >
                                <div>
                                    <h4 class="font-medium text-gray-800">
                                        <?= $user['fullname'] ?? 'Anonymous User'; ?>
                                    </h4>

                                    <div class="flex items-center mt-1">
                                        <?php if ($rating > 0): ?>
                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                class="h-4 w-4 text-yellow-400 fill-current"
                                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none">
                                                <path d="M12 17.3l6.18 3.73-1.64-7.03L21 9.24l-7.19-.61L12 2 10.19 8.63 3 9.24l5.46 4.76L6.82 21z"/>
                                            </svg>
                                            <span class="ml-1 text-sm text-gray-600"><?= $rating; ?>/5</span>
                                        <?php else: ?>
                                            <span class="ml-1 text-sm text-gray-600">Comment</span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <span class="text-xs text-gray-500 mt-1 sm:mt-0"><?= $date; ?></span>
                            </div>
                            <p class="text-gray-700 mb-3"><?= $content; ?></p>

                            <div class="flex items-center gap-4">
                                <button onclick="likeComment(<?= $comment['id'] ?>)" class="like-btn inline-flex items-center gap-1 text-sm text-gray-500 hover:text-purple-600
                                            rounded-md px-3 h-9 hover:bg-accent transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M7 10v12"></path>
                                        <path d="M15 5.88 14 10h5.83A2 2 0 0 1 21.75 12.56l-2.33 8A2 2 0 0 1 17.5 22H4a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2h2.76
                                                a2 2 0 0 0 1.79-1.11L12 2a3.13 3.13 0 0 1 3 3.88Z"/>
                                    </svg>
                                    <span><?= $likeCount; ?></span>
                                </button>

                                <button onclick="toggleReplyInput(<?= $comment['id'] ?>)" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-purple-600
                                            rounded-md px-3 h-9 hover:bg-accent transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                                    </svg>
                                    <span>Reply</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Reply input container -->
                    <div id="reply-input-<?= $comment['id'] ?>" class="hidden mt-4 ml-12">
                        <div class="flex gap-2">
                            <input type="text" 
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                placeholder="Write your reply..."
                                onkeypress="handleReplyKeyPress(event, <?= $comment['id'] ?>)"
                            />
                            <button onclick="sendReply(<?= $comment['id'] ?>)" 
                                class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-purple-600 text-white hover:bg-purple-700 h-10 px-4">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                    <path d="m22 2-7 20-4-9-9-4Z"/>
                                    <path d="M22 2 11 13"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Replies container -->
                    <div id="replies-<?= $comment['id'] ?>" class="mt-4 space-y-4">
                        <?php if (!empty($groupedChildren[$comment['id']])): ?>
                            <?php foreach ($groupedChildren[$comment['id']] as $reply): 
                                $reply_user = get_user($reply['user_id']);
                                $reply_date = date('d/m/Y', strtotime($reply['created_at']));
                                $reply_content = nl2br(htmlspecialchars($reply['content']));
                            ?>
                                <div class="flex items-start ml-12">
                                    <span class="relative flex shrink-0 overflow-hidden rounded-full h-8 w-8 mr-3">
                                        <img class="aspect-square h-full w-full"
                                            alt="<?= $reply_user['fullname'] ?? '' ?>"
                                            src="<?= get_avatar($reply_user['id']); ?>" />
                                    </span>
                                    <div class="flex-grow">
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="font-medium text-sm"><?= $reply_user['fullname'] ?? 'Anonymous User'; ?></span>
                                            <span class="text-xs text-gray-500"><?= $reply_date; ?></span>
                                        </div>
                                        <p class="text-sm text-gray-700"><?= $reply_content; ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if(!empty($comments['is_next'])): ?>
        <!-- Show more/less button -->
            <button id="toggle-comments"
            class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 border bg-background hover:text-accent-foreground h-10 px-4 py-2 w-full mt-4 text-purple-600 border-purple-200 hover:bg-purple-50">
                Show More
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round" class="lucide lucide-chevron-down ml-2 h-4 w-4">
                    <path d="m6 9 6 6 6-6"></path>
                </svg>
            </button>
        <?php endif; ?>
    </div>

    <!-- Load JavaScript -->
    <script src="<?= assets_url('js/wp-rating.js', 'reactix'); ?>"></script>
    <?php
    return ob_get_clean();
});
