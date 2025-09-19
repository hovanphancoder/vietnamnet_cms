<?php
App\Libraries\Fastlang::load('Homepage');
die;

// ===== LẤY DỮ LIỆU BLOG POST =====
// Lấy slug từ URL
$slug = get_current_slug();

// Lấy dữ liệu bài viết từ database
$post = get_post([
    'slug' => $slug,
    'posttype' => 'news',
    'withCategories' => true,
    'active' => true
]);

// var_dump($post);
// die;
// Nếu không tìm thấy bài viết, redirect về trang blog
// if (!$post) {
//     header('Location: /blog');
//     exit;
// }

// Lấy nội dung bài viết
$post_content = $post['content'] ?? $post['description'] ?? '';
$post_title = $post['title'] ?? 'Untitled';
$post_excerpt = $post['excerpt'] ?? '';

// Lấy hình ảnh featured
$featured_image = '';
if (!empty($post['feature'])) {
    $image_data = is_string($post['feature']) ? json_decode($post['feature'], true) : $post['feature'];
    if (isset($image_data['path'])) {
        $featured_image = rtrim(base_url(), '/') . '/uploads/' . $image_data['path'];
    }
}

// Lấy thông tin tác giả
$author_name = $post['author_name'] ?? 'Admin';
$author_avatar = '/themes/apkcms/Frontend/images/default-user.png';

// Lấy categories
$categories = $post['categories'] ?? [];

// Lấy bài viết liên quan
$related_posts = get_posts([
    'posttype' => 'news',
    'perPage' => 4,
    'withCategories' => true,
    'sort' => ['created_at', 'DESC'],
    'active' => true
]);
$related_posts = $related_posts['data'] ?? [];


// var_dump($related_posts);
//Get Object Data for this Pages
$locale = APP_LANG.'_'.strtoupper(lang_country(APP_LANG));
get_template('_metas/meta_single', ['locale' => $locale]);

?>

        <section>
            <div class="container">

                <!-- breadcrumb -->
                <div class="entry-content">
                    <div id="breadcrumb" class="margin-bottom-15 font-size__small color__gray truncate">
                        <span>
                            <span><a class="color__gray" href="/" aria-label="Home">Home</a></span> / 
                            <span><a class="color__gray" href="/news/" aria-label="Blog">News</a></span> / 
                            <span class="color__gray" aria-current="page"><?php echo htmlspecialchars($post_title, ENT_QUOTES, 'UTF-8'); ?></span>
                        </span>
                    </div>
                </div>
                
                <div class="app-name">
                        <h1 class="" id="title-post">
                            <strong><?php echo htmlspecialchars($post_title, ENT_QUOTES, 'UTF-8'); ?></strong> 
                        </h1>
                        
                    </div>
                <!-- primary image -->
                <?php if (!empty($featured_image)): ?>
                <div id="primaryimage">
                    <figure>
                        <img width="540" height="360" 
                             src="<?php echo htmlspecialchars($featured_image, ENT_QUOTES, 'UTF-8'); ?>" 
                             alt="<?php echo htmlspecialchars($post_title, ENT_QUOTES, 'UTF-8'); ?>" 
                             decoding="async" fetchpriority="high">
                    </figure>
                </div>
                <?php endif; ?>

                <!-- content -->
                <div class="entry-block entry-content main-entry-content" style="height: auto !important;">

                    <div class="entry-author" href="" aria-label="Author profile">
                        <a class="entry-author" href="#" aria-label="Author profile">
                            <img decoding="async" loading="lazy" 
                                 src="<?php echo htmlspecialchars($author_avatar, ENT_QUOTES, 'UTF-8'); ?>" 
                                 alt="Author avatar" width="36" height="36" class="avatar circle loaded">
                            <div class="font-size__small">
                                <span>Written by</span>
                                <strong><?php echo htmlspecialchars($author_name, ENT_QUOTES, 'UTF-8'); ?></strong>
                            </div>
                        </a>
                        <div class="font-size__small"><button id="toc-trigger" aria-label="Toggle table of contents">Show Contents</button></div>
                    </div>
                    <details id="table-of-content" class="table-of-contents">
                        <summary class="pointer"></summary>
                        <ul></ul>
                    </details>
                    
                    <!-- Post Content -->
                    <div class="post-content">
                        <?php if (!empty($post_content)): ?>
                            <?php echo $post_content; ?>
                        <?php else: ?>
                            <p><?php echo htmlspecialchars($post_excerpt, ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php endif; ?>
                    </div>
                    <!-- <div class="wp-container-flex-center font-size__small">
                        <a class="button button__small no-border no-border-radius color__blue" href="#comments" aria-label="View comments">
                            <span class="svg-icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 56 56">
                                    <path d="M42.1950364,4.70590037 C50.2325622,7.05798578 56,13.4885137 56,21.0515337 C56,26.1995677 53.3256367,30.8267622 49.0827722,33.9966405 L48.7137694,34.2597431 L48.1260756,34.6661104 L48.0663422,34.7054653 C46.5765241,35.8053078 47.498083,38.1200638 48.8783303,39.9705116 L49.1339041,40.3014664 C49.2204904,40.4098301 49.3083268,40.5161145 49.3969786,40.6199458 L49.6649456,40.9237066 L49.9022318,41.1800346 L50.0803516,41.3846967 C50.6153298,42.0299765 50.562871,42.3068231 49.926109,42.2403769 L49.7175544,42.2101694 C49.6045157,42.1899414 49.4783372,42.1620459 49.3390296,42.1265677 C47.7257449,41.7172882 46.4472546,41.2110785 45.3916999,40.6949439 L44.9199432,40.45634 C44.767696,40.3767817 44.6203181,40.2973053 44.4774021,40.2182276 L44.0616327,39.9825075 L43.6701911,39.7510807 L42.1442339,38.8137564 L41.8429107,38.6399208 L41.5500301,38.4827343 C40.7780995,38.0874586 40.0701543,37.8900488 39.2176494,38.0527145 L39.0491803,38.0891982 C37.8762441,38.2633863 36.6706309,38.3547919 35.4375,38.3547919 C34.7381162,38.3547919 34.0468863,38.3254083 33.3654587,38.2680281 C42.187391,34.8486247 48.3089659,27.3594362 48.3089659,18.6706341 C48.3089659,13.4293219 46.0806325,8.62426855 42.3754797,4.88628429 L42.1950364,4.70590037 Z M22.60125,0 C35.0833555,0 45.2025,8.49153689 45.2025,18.9641803 C45.2025,29.4387141 35.0833555,37.930251 22.60125,37.930251 C21.4717546,37.930251 20.3632633,37.8606749 19.279058,37.7269926 L18.6239139,37.6372439 C17.7193374,37.4216018 16.9692681,37.5679148 16.1806043,37.9264024 L15.8826691,38.0704884 L15.8826691,38.0704884 L15.5788527,38.2327105 L15.5788527,38.2327105 L15.2668923,38.4113135 L15.2668923,38.4113135 L14.4365106,38.9179687 L14.4365106,38.9179687 L13.8923518,39.2544298 L13.8923518,39.2544298 L13.3044111,39.6080014 L13.3044111,39.6080014 L12.8843559,39.8502962 L12.8843559,39.8502962 L12.4391847,40.0958077 C12.3627708,40.1368968 12.2852162,40.1780468 12.2064736,40.2192211 L11.7193856,40.4662664 C10.5478251,41.0420212 9.12495048,41.6082087 7.32139857,42.0644877 C7.22283864,42.0895188 7.13025777,42.1111063 7.04365905,42.1292255 L6.80181244,42.1731529 C5.98086794,42.2958864 5.88413878,41.9959452 6.51575156,41.2403166 L6.70607075,41.0229133 L6.70607075,41.0229133 L6.81477971,40.9056916 C6.90483001,40.8103998 6.99473189,40.7123779 7.08412638,40.6119338 L7.35042872,40.3036425 L7.35042872,40.3036425 L7.6115329,39.9826647 L7.6115329,39.9826647 L7.8652851,39.6508474 L7.8652851,39.6508474 L8.10953147,39.3100378 L8.10953147,39.3100378 L8.34211821,38.9620828 C8.37979137,38.9035975 8.41688897,38.8448914 8.45336614,38.786003 L8.66442499,38.4307932 C9.64508761,36.7063495 10.0284927,34.8933305 8.72027152,33.9302305 L7.76682092,33.2712283 L7.76682092,33.2712283 L7.60306352,33.1532889 L7.60306352,33.1532889 C2.93952357,29.678791 0,24.6069314 0,18.9641803 C0,8.49153689 10.1172541,0 22.60125,0 Z" transform="translate(0 7)"></path>
                                </svg></span> <span class="link-text">Comment </span></a></div> -->
                </div>

                <!-- comment -->
                <div class="wp-container-flex-center font-size__small">
                    <a class="button button__small no-border no-border-radius color__blue" href="#comments" aria-label="View comments">
                        <span class="svg-icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 56 56">
                                <path d="M42.1950364,4.70590037 C50.2325622,7.05798578 56,13.4885137 56,21.0515337 C56,26.1995677 53.3256367,30.8267622 49.0827722,33.9966405 L48.7137694,34.2597431 L48.1260756,34.6661104 L48.0663422,34.7054653 C46.5765241,35.8053078 47.498083,38.1200638 48.8783303,39.9705116 L49.1339041,40.3014664 C49.2204904,40.4098301 49.3083268,40.5161145 49.3969786,40.6199458 L49.6649456,40.9237066 L49.9022318,41.1800346 L50.0803516,41.3846967 C50.6153298,42.0299765 50.562871,42.3068231 49.926109,42.2403769 L49.7175544,42.2101694 C49.6045157,42.1899414 49.4783372,42.1620459 49.3390296,42.1265677 C47.7257449,41.7172882 46.4472546,41.2110785 45.3916999,40.6949439 L44.9199432,40.45634 C44.767696,40.3767817 44.6203181,40.2973053 44.4774021,40.2182276 L44.0616327,39.9825075 L43.6701911,39.7510807 L42.1442339,38.8137564 L41.8429107,38.6399208 L41.5500301,38.4827343 C40.7780995,38.0874586 40.0701543,37.8900488 39.2176494,38.0527145 L39.0491803,38.0891982 C37.8762441,38.2633863 36.6706309,38.3547919 35.4375,38.3547919 C34.7381162,38.3547919 34.0468863,38.3254083 33.3654587,38.2680281 C42.187391,34.8486247 48.3089659,27.3594362 48.3089659,18.6706341 C48.3089659,13.4293219 46.0806325,8.62426855 42.3754797,4.88628429 L42.1950364,4.70590037 Z M22.60125,0 C35.0833555,0 45.2025,8.49153689 45.2025,18.9641803 C45.2025,29.4387141 35.0833555,37.930251 22.60125,37.930251 C21.4717546,37.930251 20.3632633,37.8606749 19.279058,37.7269926 L18.6239139,37.6372439 C17.7193374,37.4216018 16.9692681,37.5679148 16.1806043,37.9264024 L15.8826691,38.0704884 L15.8826691,38.0704884 L15.5788527,38.2327105 L15.5788527,38.2327105 L15.2668923,38.4113135 L15.2668923,38.4113135 L14.4365106,38.9179687 L14.4365106,38.9179687 L13.8923518,39.2544298 L13.8923518,39.2544298 L13.3044111,39.6080014 L13.3044111,39.6080014 L12.8843559,39.8502962 L12.8843559,39.8502962 L12.4391847,40.0958077 C12.3627708,40.1368968 12.2852162,40.1780468 12.2064736,40.2192211 L11.7193856,40.4662664 C10.5478251,41.0420212 9.12495048,41.6082087 7.32139857,42.0644877 C7.22283864,42.0895188 7.13025777,42.1111063 7.04365905,42.1292255 L6.80181244,42.1731529 C5.98086794,42.2958864 5.88413878,41.9959452 6.51575156,41.2403166 L6.70607075,41.0229133 L6.70607075,41.0229133 L6.81477971,40.9056916 C6.90483001,40.8103998 6.99473189,40.7123779 7.08412638,40.6119338 L7.35042872,40.3036425 L7.35042872,40.3036425 L7.6115329,39.9826647 L7.6115329,39.9826647 L7.8652851,39.6508474 L7.8652851,39.6508474 L8.10953147,39.3100378 L8.10953147,39.3100378 L8.34211821,38.9620828 C8.37979137,38.9035975 8.41688897,38.8448914 8.45336614,38.786003 L8.66442499,38.4307932 C9.64508761,36.7063495 10.0284927,34.8933305 8.72027152,33.9302305 L7.76682092,33.2712283 L7.76682092,33.2712283 L7.60306352,33.1532889 L7.60306352,33.1532889 C2.93952357,29.678791 0,24.6069314 0,18.9641803 C0,8.49153689 10.1172541,0 22.60125,0 Z" transform="translate(0 7)"></path>
                            </svg></span> <span class="link-text">Comment </span></a>
                </div>

                <!-- related  -->
                <div class="related-posts">
                    <div class="entry-content entry-block">
                        <h2 class="font-size__medium no-margin uppercase">Recommended For You</h2>
                        <div class="flex-container">
                            <?php if (!empty($related_posts)): ?>
                                <?php foreach ($related_posts as $index => $related_post): ?>
                                    <?php if ($related_post['id'] != $post['id']): // Loại bỏ bài viết hiện tại ?>
                                        <div class="flex-item">
                                            <article class="card has-shadow clickable">
                                                <div class="card-image">
                                                    <a href="<?php echo (APP_LANG === APP_LANG_DF) ? '/news/' . ($related_post['slug'] ?? '') : page_url($related_post['slug'] ?? '', 'news'); ?>" 
                                                       aria-label="<?php echo htmlspecialchars($related_post['title'] ?? 'Untitled', ENT_QUOTES, 'UTF-8'); ?>">
                                                        <?php 
                                                        // Lấy hình ảnh featured cho bài viết liên quan
                                                        $related_image = '';
                                                        if (!empty($related_post['feature'])) {
                                                            $related_image_data = is_string($related_post['feature']) ? json_decode($related_post['feature'], true) : $related_post['feature'];
                                                            if (isset($related_image_data['path'])) {
                                                                $related_image = rtrim(base_url(), '/') . '/uploads/' . $related_image_data['path'];
                                                            }
                                                        }
                                                        if (empty($related_image)) {
                                                            $related_image = 'images/editors/blog-sample-' . (($index % 3) + 1) . '.jpg';
                                                        }
                                                        ?>
                                                        <img width="540" height="360" 
                                                             src="<?php echo htmlspecialchars($related_image, ENT_QUOTES, 'UTF-8'); ?>" 
                                                             alt="<?php echo htmlspecialchars($related_post['title'] ?? 'Untitled', ENT_QUOTES, 'UTF-8'); ?>" 
                                                             decoding="async" loading="lazy" class="loaded">
                                                        <div class="card-body">
                                                            <div class="card-title">
                                                                <h3 class="truncate"><?php echo htmlspecialchars($related_post['title'] ?? 'Untitled', ENT_QUOTES, 'UTF-8'); ?></h3>
                                                            </div>
                                                            <p class="card-excerpt font-size__small truncate">
                                                                <?php echo htmlspecialchars($related_post['excerpt'] ?? substr(strip_tags($related_post['content'] ?? ''), 0, 100) . '...', ENT_QUOTES, 'UTF-8'); ?>
                                                            </p>
                                                        </div>
                                                    </a>
                                                </div>
                                            </article>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="no-related-posts">
                                    <p><?php echo __('No related posts found.', 'Không tìm thấy bài viết liên quan.'); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div>
        </section>

<!-- CSS for TOC functionality -->
<style>
#table-of-content {
    display: none;
}

#table-of-content[open] {
    display: block;
}
</style>

<!-- Initialize TOC functionality -->
<!-- <script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize table of contents
    const tocTrigger = document.getElementById('toc-trigger');
    const tableOfContent = document.getElementById('table-of-content');
    
    if (tocTrigger && tableOfContent) {
        tocTrigger.addEventListener('click', function() {
            const isOpen = tableOfContent.hasAttribute('open');
            if (isOpen) {
                tableOfContent.removeAttribute('open');
                tocTrigger.textContent = 'Show Contents';
            } else {
                tableOfContent.setAttribute('open', '');
                tocTrigger.textContent = 'Hide Contents';
            }
        });
    }
});
</script> -->
<!-- Load single news page script -->
<script src="/themes/<?php echo APP_THEME_NAME; ?>/Frontend/Assets/js/single-news.min.js"></script>

<?php get_footer(); ?>