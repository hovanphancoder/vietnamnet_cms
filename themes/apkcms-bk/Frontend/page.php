<?php
App\Libraries\Fastlang::load('Homepage');
//Render::asset('js', 'js/home-index.js', ['area' => 'frontend', 'location' => 'footer']);

$slug = get_current_slug();

// ===== LẤY THÔNG TIN PAGE =====
// Lấy thông tin page theo slug sử dụng get_post function
$page = get_post([
    'slug' => $slug,
    'posttype' => 'pages',
    'active' => true,
    'columns' => ['*']
]);

//Get Object Data for this Pages
$locale = APP_LANG.'_'.strtoupper(lang_country(APP_LANG));

// Tạo mảng dữ liệu để truyền vào template từ field admin
$meta_data = [
    'locale' => $locale,
    'page_title' => $page['seo_title'] ?? $page['title'] ?? 'Page - ' . option('site_title', APP_LANG),
    'page_description' => $page['seo_desc'] ?? $page['description'] ?? option('site_description', APP_LANG),
    'page_type' => 'page',
    'current_lang' => APP_LANG,
    'site_name' => option('site_title', APP_LANG),
    'page_data' => $page, // Truyền toàn bộ dữ liệu page
    'custom_data' => [
        'page_id' => $page['id'] ?? 0,
        'page_slug' => $page['slug'] ?? '',
        'page_status' => $page['status'] ?? 'inactive',
        'page_created' => $page['created_at'] ?? '',
        'page_updated' => $page['updated_at'] ?? '',
        'has_content' => !empty($page['content']),
        'content_length' => strlen($page['content'] ?? '')
    ]
];

get_template('_metas/meta_page', $meta_data);





?>
<section>
    <div class="container">
        <!-- Breadcrumb -->
        <div id="breadcrumb" class="font-size__small color__gray truncate margin-bottom-15">
            <span>
                <span><a class="color__gray" href="<?php echo (APP_LANG === APP_LANG_DF) ? '/' : page_url('', 'home'); ?>" aria-label="Home">Home</a></span> / 
                <span class="color__gray" aria-current="page"><?php echo htmlspecialchars($page['title'] ?? 'Page', ENT_QUOTES, 'UTF-8'); ?></span>
            </span>
        </div>

        <!-- Page Content -->
        <div id="main-content">
            <h1 class="font-size__larger margin-bottom-15"><?php echo htmlspecialchars($page['title'] ?? 'Page Title', ENT_QUOTES, 'UTF-8'); ?></h1>
            
            <!-- Page Excerpt/Description -->
            <?php if (!empty($page['description'])): ?>
                <div class="page-excerpt font-size__normal color__gray margin-bottom-20">
                    <?php echo htmlspecialchars($page['description'], ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <!-- Page Content -->
            <div class="page-content font-size__normal">
                <?php if (!empty($page['content'])): ?>
                    <div class="content-html">
                        <?php echo $page['content']; ?>
                    </div>
                <?php else: ?>
                    <p class="color__gray">No content available for this page.</p>
                <?php endif; ?>
            </div>

           
        </div>
    </div>
</section>


<?php get_footer(); ?>