<?php 

add_shortcode('feature-rating', function (
    $posttype = '', $post_id = ''
) {
    $fillters = [];
    if($posttype != ''){
        $fillters['posttype'] = $posttype;
    }
    if($post_id != ''){
        $fillters['post_id'] = $post_id;
    }
    $array = [
        'posttype' => 'comment',
        'sort' => ['like_count', 'DESC'],
        'filters' => $fillters,
        'perPage' => 10,
    ];
   
    $comments = get_posts($array);
    $comments = $comments['data'];
    ?>
<!-- Đánh giá cộng đồng  -->
<section class="bg-white rounded-lg shadow-md p-6">
    <div class="flex justify-between items-center mb-6">
        <div class="flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round"
                class="lucide lucide-heart h-5 w-5 text-purple-600 mr-2">
                <path
                    d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z">
                </path>
            </svg>
            <h2 class="text-xl font-bold text-gray-800">
                Đánh Giá Từ Cộng Đồng
            </h2>
        </div>
        <!-- <a class="flex items-center text-purple-600 hover:text-purple-700 font-medium text-sm"
            href="/danh-gia">Xem tất cả
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round"
                class="lucide lucide-chevron-right h-4 w-4 ml-1">
                <path d="m9 18 6-6-6-6"></path>
            </svg>
        </a> -->
    </div>
    <div class="space-y-4">
        <?php if(!empty($comments) && is_array($comments)) : ?>
            <div class="comments-container" style="height: 600px; overflow-y: auto; scroll-behavior: smooth;">
                <div class="space-y-4">
                    <?php foreach ($comments as $comment) : ?>
                        <?php $user = get_user($comment['user_id']); ?>
                        <?php $post = get_post(['id' => $comment['post_id']]); ?>
                        <div class="bg-gray-50 rounded-lg p-4 border border-gray-100">
                            <div class="flex items-center mb-3">
                                <span class="relative flex shrink-0 overflow-hidden rounded-full h-8 w-8 mr-3">
                                    <img class="aspect-square h-full w-full" alt="<?= $user['fullname'] ?>" src="<?= get_avatar($user['id']) ?>" />
                                </span>
                                <div>
                                    <div class="flex items-center">
                                        <span class="font-medium text-sm"><?= $user['fullname'] ?></span>
                                        <span class="mx-2 text-gray-400">•</span>
                                        <span class="text-xs text-gray-500"><?= time_ago($comment['created_at']) ?></span>
                                    </div>
                                    <div class="flex items-center">
                                        <span class="text-sm text-gray-600 mr-2">Đánh giá:</span>
                                        <span class="text-sm font-medium"><?= $post['title'] ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex mb-2">
                                <?php 
                                    $rating = isset($comment['rating']) ? (int)$comment['rating'] : 0;
                                    for ($i = 1; $i <= 5; $i++):
                                        $isFilled = $i <= $rating;
                                ?>
                                   <img src="<?= theme_assets($isFilled ? 'images/star.svg' : 'images/star-empty.svg'); ?>" alt="star" class="star">
                                <?php endfor; ?>
                            </div>
                            <p class="text-sm text-gray-700 mb-3">
                                <?= $comment['content'] ?>
                            </p>
                            <div class="flex items-center text-sm text-gray-500">
                                <button data-like-id="<?= $comment['id'] ?>" onclick="likeComment(<?= $comment['id'] ?>)" class="like-btn inline-flex items-center justify-center gap-2 whitespace-nowrap text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 hover:bg-accent rounded-md h-8 px-2 text-gray-500 hover:text-purple-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-thumbs-up h-4 w-4 mr-1">
                                        <path d="M7 10v12"></path>
                                        <path d="M15 5.88 14 10h5.83a2 2 0 0 1 1.92 2.56l-2.33 8A2 2 0 0 1 17.5 22H4a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2h2.76a2 2 0 0 0 1.79-1.11L12 2a3.13 3.13 0 0 1 3 3.88Z"></path>
                                    </svg>
                                    <span><?= $comment['like_count'] ?></span>
                                </button>
                                <button class="inline-flex items-center justify-center gap-2 whitespace-nowrap text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 hover:bg-accent rounded-md h-8 px-2 text-gray-500 hover:text-purple-600">
                                    Reply
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else : ?>
            <p class="text-gray-500">Chưa có đánh giá nào.</p>
        <?php endif; ?>
    </div>
    <!-- <div class="flex justify-center mt-6">
        <button
            class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 [&amp;_svg]:pointer-events-none [&amp;_svg]:size-4 [&amp;_svg]:shrink-0 border bg-background h-10 px-4 py-2 text-purple-600 border-purple-200 hover:bg-purple-50 hover:text-purple-700">
            Viết đánh giá
        </button>
    </div> -->
</section>
    <script>
        
        async function likeComment(id) {
        // Find the correct button by data-like-id
        const btn = document.querySelector(`button[data-like-id="${id}"]`);
        if (!btn || btn.disabled) return;          // already liked or not found

        try {
            const res = await fetch(`/api/reactix/like_comment/${id}`, {
            method : 'GET',
            headers: { 'Content-Type': 'application/json' },
            });

            // Assume API returns { success: true, like_count: 99 }
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'API Error');

            // Update like count (if API returns), or increment by 1
            const counter = btn.querySelector('span');
            if (counter) {
            let current = parseInt(counter.textContent, 10);
            if (isNaN(current)) current = 0;
            counter.textContent = typeof data.like_count === 'number' ? data.like_count : (current + 1);
            }

            // Color and disable button
            btn.classList.remove('text-gray-500', 'hover:text-purple-600');
            btn.classList.add('text-purple-600', 'pointer-events-none');

            // If there's SVG inside the button, replace like SVG (thumbs up) with purple fill
            const svg = btn.querySelector('svg');
            if (svg) {
            btn.querySelector('svg').outerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                viewBox="0 0 24 24" fill="#a21caf" stroke="#a21caf"
                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                class="lucide lucide-thumbs-up h-4 w-4 mr-1 text-purple-600">
                <path d="M7 10v12"></path>
                <path d="M15 5.88 14 10h5.83a2 2 0 0 1 1.92 2.56l-2.33 8A2 2 0 0 1 17.5 22H4a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2h2.76a2 2 0 0 0 1.79-1.11L12 2a3.13 3.13 0 0 1 3 3.88Z"></path>
                </svg>
            `;
            }
            // Tailwind already has disabled:opacity-50 so we just need to set disabled
        } catch (err) {
            console.error(err);
            btn.disabled = false;                    // allow clicking again on error
            alert('Cannot like, please try again!');
        }
        }
    </script>
    <?php
    return ob_get_clean();
});
