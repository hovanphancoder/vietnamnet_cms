<?php
App\Libraries\Fastlang::load('Homepage');
require_once __DIR__ . '/functions.php';
global $post;

// Lấy thông tin tác giả đăng bài
$author = 'Admin'; // Mặc định
$author_avatar = '/themes/apkcms/Frontend/images/default-user.png'; // Mặc định




//Get Object Data for this Pages
$locale = APP_LANG.'_'.strtoupper(lang_country(APP_LANG));
get_template('_metas/meta_single', ['locale' => $locale]);



var_dump($post['slug']);
var_dump(link_single($post['slug'], 'posts', APP_LANG));
die;
?>

<section>
            <div class="container">
                <!-- breadcrumb -->
                <div class="entry-content">
                    <div id="breadcrumb" class="margin-bottom-15 font-size__small color__gray truncate">
                        <span>
                            <span><a class="color__gray" href="/" aria-label="Home">Home</a></span> / 
                            <span><a class="color__gray" href="/posts/category/<?php echo htmlspecialchars($post['categories'][0]['name'] ?? 'general', ENT_QUOTES, 'UTF-8'); ?>" aria-label="<?php echo htmlspecialchars($post['categories'][0]['name'] ?? 'general', ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($post['categories'][0]['name'] ?? 'general', ENT_QUOTES, 'UTF-8'); ?></a></span> / 
                            <span class="color__gray" aria-current="page"><?php echo htmlspecialchars($post['title'] ?? 'Untitled', ENT_QUOTES, 'UTF-8'); ?></span>
                        </span>
                    </div>
                </div>

                <!-- Header App  -->
                <div class="app app__large">
                    <div class="app-icon">
                        <?php
                        // Lấy hình ảnh featured
                        $featured_image = '';
                        if (!empty($post['feature'])) {
                            $image_data = is_string($post['feature']) ? json_decode($post['feature'], true) : $post['feature'];
                            if (isset($image_data['path'])) {
                                $featured_image = rtrim(base_url(), '/') . '/uploads/' . $image_data['path'];
                            }
                        }
                        ?>
                        <?php if (!empty($featured_image)): ?>
                            <img fetchpriority="high" src="<?php echo htmlspecialchars($featured_image, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($post['title'] ?? 'Untitled', ENT_QUOTES, 'UTF-8'); ?> icon" width="90" height="90">
                        <?php else: ?>
                            <!-- <img fetchpriority="high" src="https://via.placeholder.com/90x90/2196F3/FFFFFF?text=App" alt="<?php echo htmlspecialchars($post['title'] ?? 'Untitled', ENT_QUOTES, 'UTF-8'); ?> icon" width="90" height="90"> -->
                        <?php endif; ?>
                    </div>
                    <div class="app-name">
                        <h1 class="font-size__medium no-margin" id="title-post">
                            <strong><?php echo htmlspecialchars($post['title'] ?? 'updating', ENT_QUOTES, 'UTF-8'); ?></strong> 
                            <span>  <?php echo htmlspecialchars($post['mod_features'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
                        </h1>
                        <span class="font-size__small truncate">
                            <!-- <a href="#publisher-items" aria-label="Jump to publisher-items"><?php echo htmlspecialchars($author, ENT_QUOTES, 'UTF-8'); ?></a> -->
                        </span>
                    </div>
                </div>
                <!-- screenshots -->
                <?php
                // Lấy screenshots từ database
                $screenshots = [];
                
                // Thử lấy từ field screenshots
                if (!empty($post['screenshot'])) {
                    $screenshots_data = is_string($post['screenshot']) ? json_decode($post['screenshot'], true) : $post['screenshot'];
                    if (is_array($screenshots_data)) {
                        $screenshots = $screenshots_data;
                    }
                }
                
                // Chỉ hiển thị container nếu có screenshots
                if (!empty($screenshots)):
                ?>
                <div class="screenshots-container">
                    <div class="screenshots horizontal-scroll" id="lightgallery-container">
                        <?php
                        // Hiển thị screenshots
                        foreach ($screenshots as $index => $screenshot):
                            // Lấy URL từ path
                            $screenshot_url = rtrim(base_url(), '/') . '/uploads/' . $screenshot['path'];
                            $screenshot_alt = $screenshot['name'] ?? ($post['title'] ?? 'App') . ' screenshot ' . ($index + 1);
                            $screenshot_id = 'screenshot-' . $index . '-' . uniqid();
                        ?>
                            <a class="screenshot" data-src="<?php echo htmlspecialchars($screenshot_url, ENT_QUOTES, 'UTF-8'); ?>" data-lg-id="<?php echo $screenshot_id; ?>" aria-label="View screenshot">
                                <img class="horizontal-screenshot <?php echo $index > 0 ? 'loaded' : ''; ?>" width="512" height="288" decoding="async" <?php echo $index === 0 ? 'fetchpriority="high"' : 'loading="lazy"'; ?> src="<?php echo htmlspecialchars($screenshot_url, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($screenshot_alt, ENT_QUOTES, 'UTF-8'); ?>">
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                <!-- app info -->
                <div class="entry-content border-block" id="app-info">
                    <h2 class="hide">App Info</h2>
                    <figure class="wp-block-table margin-bottom-5">
                        <table class="has-fixed-layout">
                            <tbody>
                                <tr>
                                    <th class="text-align__left font-size__small color__gray">Updated On</th>
                                    <td class="text-align__right"><time datetime="<?php echo date('c', strtotime($post['updated_at'] ?? $post['created_at'] ?? 'now')); ?>"><?php echo date('F j, Y', strtotime($post['updated_at'] ?? $post['created_at'] ?? 'now')); ?></time></td>
                                </tr>
                                <tr>
                                    <th class="text-align__left font-size__small color__gray">Google Play ID</th>
                                    <td class="text-align__right"><span class="truncate"><a class="color__black" href="<?php echo htmlspecialchars($post['google_play_url'] ?? '#', ENT_QUOTES, 'UTF-8'); ?>" rel="noopener" target="_blank"><?php echo htmlspecialchars($post['google_play_id'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></a></span></td>
                                </tr>
                                <tr>
                                    <th class="text-align__left font-size__small color__gray">Category</th>
                                    <td class="text-align__right"><span class="truncate"><a class="color__black" href="/games/<?php echo htmlspecialchars($post['categories'][0]['name'] ?? 'general', ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($post['categories'][0]['name'] ?? 'General', ENT_QUOTES, 'UTF-8'); ?></a></span></td>
                                </tr>
                                <tr>
                                    <th class="text-align__left font-size__small color__gray">Version</th>
                                    <td class="text-align__right"><span class="truncate"><?php echo htmlspecialchars($post['version'] ?? 'v1.0.0', ENT_QUOTES, 'UTF-8'); ?></span></td>
                                </tr>
                                <tr>
                                    <th class="text-align__left font-size__small color__gray">Size</th>
                                    <td class="text-align__right"><?php echo htmlspecialchars($post['size'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                                </tr>
                                <tr>
                                    <th class="text-align__left font-size__small color__gray">MOD Features</th>
                                    <td class="text-align__right" aria-label="Link"><a href="#h-mod-apk-version-of-<?php echo htmlspecialchars($post['slug'] ?? 'app', ENT_QUOTES, 'UTF-8'); ?>" class="color__black truncate"><?php echo htmlspecialchars($post['mod_features'] ?? 'Menu, Unlimited Money', ENT_QUOTES, 'UTF-8'); ?></a></td>
                                </tr>
                                <tr>
                                    <th class="text-align__left font-size__small color__gray">Requires</th>
                                    <td class="text-align__right"><span class="color__green"><span class="svg-icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 -960 960 960">
                                                    <path d="M73.85-259.62q8.61-98.92 60.7-181.94 52.1-83.01 138.76-131.9l-68.23-118q-5.62-8.62-2.81-17.65 2.81-9.04 12.04-14.04 8-4.62 16.85-1.81 8.84 2.81 14.38 11.13l68.31 118.29q79.38-33.27 166.15-33.27 86.77 0 166.15 33.27l68.31-118.29q5.54-8.32 14.38-11.13 8.85-2.81 16.85 1.81 9.23 5 12.04 14.04 2.81 9.03-2.81 17.65l-68.23 118q86.66 48.89 138.76 131.9 52.09 83.02 60.7 181.94H73.85Zm221.59-101.53q19.41 0 32.75-13.4 13.35-13.41 13.35-32.81 0-19.41-13.4-32.76-13.4-13.34-32.81-13.34-19.41 0-32.75 13.4-13.35 13.4-13.35 32.81 0 19.4 13.4 32.75 13.4 13.35 32.81 13.35Zm369.23 0q19.41 0 32.75-13.4 13.35-13.41 13.35-32.81 0-19.41-13.4-32.76-13.4-13.34-32.81-13.34-19.41 0-32.75 13.4-13.35 13.4-13.35 32.81 0 19.4 13.4 32.75 13.4 13.35 32.81 13.35Z"></path>
                                                </svg></span> </span> <?php echo htmlspecialchars($post['android_version'] ?? 'Android 5.0', ENT_QUOTES, 'UTF-8'); ?></td>
                                </tr>
                                <tr class="collapse-row">
                                    <th class="text-align__left font-size__small color__gray">Price</th>
                                    <td class="text-align__right"><?php echo htmlspecialchars($post['price'] ?? 'Free', ENT_QUOTES, 'UTF-8'); ?></td>
                                </tr>
                                <tr class="collapse-row">
                                    <th class="text-align__left font-size__small color__gray">Content Rating</th>
                                    <td class="text-align__right"><?php echo htmlspecialchars($post['content_rating'] ?? 'Everyone', ENT_QUOTES, 'UTF-8'); ?></td>
                                </tr>
                                <tr class="collapse-row">
                                    <th class="text-align__left font-size__small color__gray">Internet Required</th>
                                    <td class="text-align__right"><?php echo htmlspecialchars($post['internet_required'] ?? 'Not Required', ENT_QUOTES, 'UTF-8'); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </figure>
                    <span class="block text-align__center color__gray" id="unfold-table"><span class="svg-icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 -960 960 960">
                                <path d="M249.23-420q-24.75 0-42.37-17.63-17.63-17.62-17.63-42.37 0-24.75 17.63-42.37Q224.48-540 249.23-540q24.75 0 42.38 17.63 17.62 17.62 17.62 42.37 0 24.75-17.62 42.37Q273.98-420 249.23-420ZM480-420q-24.75 0-42.37-17.63Q420-455.25 420-480q0-24.75 17.63-42.37Q455.25-540 480-540q24.75 0 42.37 17.63Q540-504.75 540-480q0 24.75-17.63 42.37Q504.75-420 480-420Zm230.77 0q-24.75 0-42.38-17.63-17.62-17.62-17.62-42.37 0-24.75 17.62-42.37Q686.02-540 710.77-540q24.75 0 42.37 17.63 17.63 17.62 17.63 42.37 0 24.75-17.63 42.37Q735.52-420 710.77-420Z"></path>
                            </svg></span> </span>
                </div>
                <!-- Button download -->
                <a id="main-download-button" href="/games/football-league-2023/download" class="button button__blue clickable" aria-label="Download now">
                    <span class="svg-icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 -960 960 960">
                            <path d="M480-343.54q-7.23 0-13.46-2.31-6.23-2.3-11.85-7.92L330.31-478.15q-8.92-8.93-8.81-20.89.12-11.96 8.81-21.27 9.31-9.3 21.38-9.61 12.08-.31 21.39 9L450-444v-306q0-12.77 8.62-21.38Q467.23-780 480-780t21.38 8.62Q510-762.77 510-750v306l76.92-76.92q8.93-8.92 21.19-8.81 12.27.12 21.58 9.42 8.69 9.31 9 21.08.31 11.77-9 21.08L505.31-353.77q-5.62 5.62-11.85 7.92-6.23 2.31-13.46 2.31ZM252.31-180Q222-180 201-201q-21-21-21-51.31v-78.46q0-12.77 8.62-21.38 8.61-8.62 21.38-8.62t21.38 8.62q8.62 8.61 8.62 21.38v78.46q0 4.62 3.85 8.46 3.84 3.85 8.46 3.85h455.38q4.62 0 8.46-3.85 3.85-3.84 3.85-8.46v-78.46q0-12.77 8.62-21.38 8.61-8.62 21.38-8.62t21.38 8.62q8.62 8.61 8.62 21.38v78.46Q780-222 759-201q-21 21-51.31 21H252.31Z"></path>
                        </svg>
                    </span> Download
                </a>
                <a id="join-telegram-button" href="https://t.me/" class="button button__blue clickable" aria-label="Join Telegram">
                    <span class="svg-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 240 240">
                            <path d="M120 0C53.73 0 0 53.73 0 120s53.73 120 120 120 120-53.73 120-120S186.27 0 120 0zm56.69 80.47l-19.92 94.23c-1.5 6.77-5.52 8.46-11.18 5.27l-30.89-22.8-14.9 14.34c-1.64 1.64-3.01 3.01-6.17 3.01l2.22-31.53 57.4-51.78c2.49-2.22-0.54-3.48-3.86-1.26l-71.01 44.74-30.56-9.55c-6.65-2.07-6.77-6.65 1.38-9.85l119.3-46.05c5.52-2.07 10.34 1.26 8.61 9.53z" fill="#fff" />
                        </svg>
                    </span>
                    Join Telegram
                </a>
                <!-- Contents -->
                <div class="entry-block entry-content main-entry-content">
                    <div class="entry-author" href="" aria-label="Author profile">
                        <a class="entry-author" href="#" aria-label="Author profile">
                            <img decoding="async" loading="lazy" 
                                 src="<?php echo htmlspecialchars($author_avatar, ENT_QUOTES, 'UTF-8'); ?>" 
                                 alt="Author avatar" width="36" height="36" class="avatar circle loaded">
                            <div class="font-size__small">
                                <span>Written by</span>
                                <strong><?=$author?></strong>
                            </div>
                        </a>
                        <div class="font-size__small"><button id="toc-trigger" aria-label="Toggle table of contents">Show Contents</button></div>
                    </div>
                    <details id="table-of-content" class="table-of-contents">
                        <summary class="pointer"></summary>
                        <ul></ul>
                    </details>
                    <?php if (!empty($post['content'])): ?>
                        <?php echo $post['content']; ?>
                    <?php else: ?>
                        <p></p>
                    <?php endif; ?>
                    <div class="wp-container-flex-center font-size__small">
                        <a class="button button__small no-border no-border-radius color__blue" href="#comments" aria-label="View comments">
                            <span class="svg-icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 56 56">
                                    <path d="M42.1950364,4.70590037 C50.2325622,7.05798578 56,13.4885137 56,21.0515337 C56,26.1995677 53.3256367,30.8267622 49.0827722,33.9966405 L48.7137694,34.2597431 L48.1260756,34.6661104 L48.0663422,34.7054653 C46.5765241,35.8053078 47.498083,38.1200638 48.8783303,39.9705116 L49.1339041,40.3014664 C49.2204904,40.4098301 49.3083268,40.5161145 49.3969786,40.6199458 L49.6649456,40.9237066 L49.9022318,41.1800346 L50.0803516,41.3846967 C50.6153298,42.0299765 50.562871,42.3068231 49.926109,42.2403769 L49.7175544,42.2101694 C49.6045157,42.1899414 49.4783372,42.1620459 49.3390296,42.1265677 C47.7257449,41.7172882 46.4472546,41.2110785 45.3916999,40.6949439 L44.9199432,40.45634 C44.767696,40.3767817 44.6203181,40.2973053 44.4774021,40.2182276 L44.0616327,39.9825075 L43.6701911,39.7510807 L42.1442339,38.8137564 L41.8429107,38.6399208 L41.5500301,38.4827343 C40.7780995,38.0874586 40.0701543,37.8900488 39.2176494,38.0527145 L39.0491803,38.0891982 C37.8762441,38.2633863 36.6706309,38.3547919 35.4375,38.3547919 C34.7381162,38.3547919 34.0468863,38.3254083 33.3654587,38.2680281 C42.187391,34.8486247 48.3089659,27.3594362 48.3089659,18.6706341 C48.3089659,13.4293219 46.0806325,8.62426855 42.3754797,4.88628429 L42.1950364,4.70590037 Z M22.60125,0 C35.0833555,0 45.2025,8.49153689 45.2025,18.9641803 C45.2025,29.4387141 35.0833555,37.930251 22.60125,37.930251 C21.4717546,37.930251 20.3632633,37.8606749 19.279058,37.7269926 L18.6239139,37.6372439 C17.7193374,37.4216018 16.9692681,37.5679148 16.1806043,37.9264024 L15.8826691,38.0704884 L15.8826691,38.0704884 L15.5788527,38.2327105 L15.5788527,38.2327105 L15.2668923,38.4113135 L15.2668923,38.4113135 L14.4365106,38.9179687 L14.4365106,38.9179687 L13.8923518,39.2544298 L13.8923518,39.2544298 L13.3044111,39.6080014 L13.3044111,39.6080014 L12.8843559,39.8502962 L12.8843559,39.8502962 L12.4391847,40.0958077 C12.3627708,40.1368968 12.2852162,40.1780468 12.2064736,40.2192211 L11.7193856,40.4662664 C10.5478251,41.0420212 9.12495048,41.6082087 7.32139857,42.0644877 C7.22283864,42.0895188 7.13025777,42.1111063 7.04365905,42.1292255 L6.80181244,42.1731529 C5.98086794,42.2958864 5.88413878,41.9959452 6.51575156,41.2403166 L6.70607075,41.0229133 L6.70607075,41.0229133 L6.81477971,40.9056916 C6.90483001,40.8103998 6.99473189,40.7123779 7.08412638,40.6119338 L7.35042872,40.3036425 L7.35042872,40.3036425 L7.6115329,39.9826647 L7.6115329,39.9826647 L7.8652851,39.6508474 L7.8652851,39.6508474 L8.10953147,39.3100378 L8.10953147,39.3100378 L8.34211821,38.9620828 C8.37979137,38.9035975 8.41688897,38.8448914 8.45336614,38.786003 L8.66442499,38.4307932 C9.64508761,36.7063495 10.0284927,34.8933305 8.72027152,33.9302305 L7.76682092,33.2712283 L7.76682092,33.2712283 L7.60306352,33.1532889 L7.60306352,33.1532889 C2.93952357,29.678791 0,24.6069314 0,18.9641803 C0,8.49153689 10.1172541,0 22.60125,0 Z" transform="translate(0 7)"></path>
                                </svg></span> <span class="link-text">Comment </span></a>
                    </div>

                </div>
                <!-- versions -->
                <!-- <div class="entry-content" id="main-download-list">
                    <h2 class="font-size__medium no-margin">Available Versions</h2>
                    <div class="download-list margin-top-10"><a href="/games/football-league-2023/download/0" class="clickable" aria-label="Download football-league-2023">
                            <div class="download-item">
                                <div class="download-item-icon"><img decoding="async" loading="lazy" src="https://static.apkmody.com/play-lh.googleusercontent.com/koXfW3JR_z4_3KihWWL0k-Xhdc8Ak6kSMFrQFz2FqTULKuiC5L0w_LTTA37LFWYcF98=s180-rw"  alt="Football League 2025 icon" width="90" height="90" class="loaded"></div>
                                <div class="download-item-name">
                                    <div class="color__blue">Football League 2025 v0.1.61</div>
                                    <div class="color__gray font-size__small"><span class="app-tag">APK</span><span class="app-tag">MOD Menu</span>Unlimited Money</div>
                                </div>

                            </div>
                        </a aria-label="Link"><a href="/games/football-league-2023/download/1" class="clickable">
                            <div class="download-item">
                                <div class="download-item-icon"><img decoding="async" loading="lazy" src="https://static.apkmody.com/play-lh.googleusercontent.com/koXfW3JR_z4_3KihWWL0k-Xhdc8Ak6kSMFrQFz2FqTULKuiC5L0w_LTTA37LFWYcF98=s180-rw"  alt="Football League 2025 icon" width="90" height="90" class="loaded"></div>
                                <div class="download-item-name">
                                    <div class="color__blue">Football League 2025 v0.1.63</div>
                                    <div class="color__gray font-size__small"><span class="app-tag">XAPK</span><span class="app-tag">Original</span></div>
                                </div>
                            </div>
                        </a></div>
                </div> -->
                <!-- related -->
                <?php
                // Lấy related posts - thử cách khác
                $related_posts = get_posts([
                    'posttype' => 'posts',
                    'perPage' => 10,
                    'withCategories' => true,
                    'active' => true,
                    'filters' => [
                        ['id', '!=', $post['id'] ?? 0]
                    ],
                    'sort' => ['views', 'DESC']
                ]);
                
              
                // var_dump($related_posts);
                
                // Chỉ hiển thị container nếu có related posts
                if (!empty($related_posts)):
                ?>
                <div class="related-posts">
                    <div class="entry-content entry-block">
                        <h2 class="font-size__medium no-margin">Recommended For You</h2>
                        <div class="flex-container-2 horizontal-scroll">
                            <?php
                            // Hiển thị related posts
                            // Kiểm tra cấu trúc dữ liệu 
                            if (isset($related_posts['data'])) {
                                $posts_data = $related_posts['data'];
                            } else {
                                $posts_data = $related_posts;
                            }
                            
                            foreach ($posts_data as $index => $related_post):
                                $related_title = $related_post['title'] ?? 'Untitled';
                                $related_slug = $related_post['slug'] ?? 'app';
                                $related_image = '';
                                
                                // Lấy hình ảnh featured
                                if (!empty($related_post['feature'])) {
                                    $image_data = is_string($related_post['feature']) ? json_decode($related_post['feature'], true) : $related_post['feature'];
                                    if (isset($image_data['path'])) {
                                        $related_image = rtrim(base_url(), '/') . '/uploads/' . $image_data['path'];
                                    } elseif (is_string($related_post['feature'])) {
                                        $related_image = $related_post['feature'];
                                    }
                                }
                                
                                // if (empty($related_image)) {
                                //     $related_image = 'https://via.placeholder.com/90x90/2196F3/FFFFFF?text=App';
                                // }
                                
                                $related_url = (APP_LANG === APP_LANG_DF) ? '/post/' . $related_slug : page_url($related_slug, 'posts');
                            ?>
                                <article class="flex-item">
                                    <a href="<?php echo htmlspecialchars($related_url, ENT_QUOTES, 'UTF-8'); ?>" class="app clickable" aria-label="<?php echo htmlspecialchars($related_slug, ENT_QUOTES, 'UTF-8'); ?> game">
                                    <div class="app-icon">
                                            <img decoding="async" loading="lazy" src="<?php echo htmlspecialchars($related_image, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($related_title, ENT_QUOTES, 'UTF-8'); ?> icon" width="90" height="90" class="loaded">
                                    </div>
                                    <div class="app-name truncate">
                                            <h3 class="font-size__small no-margin no-padding truncate"><?php echo htmlspecialchars($related_title, ENT_QUOTES, 'UTF-8'); ?></h3>
                                    </div>
                                    </a>
                                </article>
                               <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                <!-- comments -->
                <div class="">
                    <div class="">
                        <h2 class="font-size__medium text-align__center no-margin">Comments</h2>
                        <div class="text-align__center margin-top-15"></div>
                        <div id="comments" class="center">
                            <p class="text-align__center font-size__small">You have to <a href="/login" aria-label="Login">LOGIN</a> to submit &amp; see all comments</p>
                        </div>
                    </div>
                </div>

            </div>
        </section>
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

<!-- Load single page script -->
<script src="/themes/<?php echo APP_THEME_NAME; ?>/Frontend/Assets/js/single.min.js"></script>

<?php get_footer(); ?>

