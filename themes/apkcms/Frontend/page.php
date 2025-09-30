<?php
App\Libraries\Fastlang::load('Homepage');
//Render::asset('js', 'js/home-index.js', ['area' => 'frontend', 'location' => 'footer']);

// ===== LẤY THÔNG TIN PAGE =====
// Lấy thông tin page theo slug sử dụng get_post function
global $page;
// var_dump($page);

//Get Object Data for this Pages

$locale = APP_LANG.'_'.strtoupper(lang_country(APP_LANG));
get_template('_metas/meta_page', ['locale' => $locale]);



?>
<section>
    <div class="container">
      

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