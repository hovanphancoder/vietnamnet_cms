<?php
use App\Models\FastModel;

// Sử dụng biến global từ header.php
$games_categories = $GLOBALS['games_categories'] ?? [];
$apps_categories = $GLOBALS['apps_categories'] ?? [];

// var_dump($games_categories);
// var_dump($apps_categories);
// Footer links - có thể chỉnh sửa sau
$footer_links = [
    'information' => [
        ['name' => 'About us', 'url' => '/page/about'],
        ['name' => 'Contact', 'url' => '/page/contact']
    ],
    'products' => [
        ['name' => 'APK Downloader', 'url' => '/'],
        ['name' => 'APKMODY Installer', 'url' => '/apps/apkmody-installer']
    ],
    'legal' => [
        ['name' => 'Terms of service', 'url' => '/page/terms-of-service'],
        ['name' => 'Privacy policy', 'url' => '/page/privacy-policy']
    ],
    'languages' => [
        ['name' => 'English', 'url' => '/'],
        ['name' => 'Tiếng Việt', 'url' => '/vi/']
    ]
];
?>
   
    
</main>
    <div id="nav-mobile" class="sidenav ">
        <div class="container">
            <div class="sidenav__content liquid-glass liquid-glass__100">
                <div class="sidenav-item"><a href="/login" class="sidenav-login clickable">
                        <span class="svg-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 -960 960 960">
                                <path d="M509.61-140q-12.76 0-21.38-8.62-8.61-8.61-8.61-21.38t8.61-21.38q8.62-8.62 21.38-8.62h238.08q4.62 0 8.46-3.85 3.85-3.84 3.85-8.46v-535.38q0-4.62-3.85-8.46-3.84-3.85-8.46-3.85H509.61q-12.76 0-21.38-8.62-8.61-8.61-8.61-21.38t8.61-21.38q8.62-8.62 21.38-8.62h238.08Q778-820 799-799q21 21 21 51.31v535.38Q820-182 799-161q-21 21-51.31 21H509.61Zm-28.38-310H170.39q-12.77 0-21.39-8.62-8.61-8.61-8.61-21.38t8.61-21.38q8.62-8.62 21.39-8.62h310.84l-76.85-76.92q-8.29-8.31-8.49-20.27-.19-11.96 8.49-21.27 8.67-9.31 21.03-9.62 12.36-.3 21.67 9l123.77 123.77q10.84 10.85 10.84 25.31 0 14.46-10.84 25.31L447.08-330.92q-8.92 8.92-21.19 8.8-12.27-.11-21.58-9.42-8.69-9.31-8.38-21.38.3-12.08 9-20.77l76.3-76.31Z"></path>
                            </svg></span> Login <img src=""  alt="login icon" width="28" height="28" class="sidenav-login-icon circle loaded" decoding="async">
                    </a></div>
                <div class="sidenav-item">
                    <div class="divider"></div>
                </div>
                <div class="sidenav-item">
                    <button class="sidenav-trigger" type="button" data-target="menu-game">
                        <span class="svg-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 56 56">
                                <path fill-rule="evenodd" d="M26,4.19774169 C32.1246521,4.15319844 40.5436115,-3.72780341 45.8036054,2.73567487 C51.0635992,9.19915314 53.0019674,26.086104 49.6166782,33.4829125 C48.4190056,36.09981 40.9112338,36.09981 36.2709234,26.5399879 L15.7290766,26.5399879 C11.0887662,36.09981 3.58099442,36.09981 2.38332183,33.4829125 C-1.00196739,26.086104 1.28965372,8.76507605 6.19639462,2.73567487 C11.1031355,-3.29372632 20.0255806,4.15429105 26,4.19774169 Z M15,12 L15,9.5 C15,8.67157288 14.3284271,8 13.5,8 C12.6715729,8 12,8.67157288 12,9.5 L12,12 L9.5,12 C8.67157288,12 8,12.6715729 8,13.5 C8,14.3284271 8.67157288,15 9.5,15 L12,15 L12,17.5 C12,18.3284271 12.6715729,19 13.5,19 C14.3284271,19 15,18.3284271 15,17.5 L15,15 L17.5,15 C18.3284271,15 19,14.3284271 19,13.5 C19,12.6715729 18.3284271,12 17.5,12 L15,12 Z M38,12 C39.1045695,12 40,11.1045695 40,10 C40,8.8954305 39.1045695,8 38,8 C36.8954305,8 36,8.8954305 36,10 C36,11.1045695 36.8954305,12 38,12 Z M42,16 C43.1045695,16 44,15.1045695 44,14 C44,12.8954305 43.1045695,12 42,12 C40.8954305,12 40,12.8954305 40,14 C40,15.1045695 40.8954305,16 42,16 Z M34,16 C35.1045695,16 36,15.1045695 36,14 C36,12.8954305 35.1045695,12 34,12 C32.8954305,12 32,12.8954305 32,14 C32,15.1045695 32.8954305,16 34,16 Z M38,20 C39.1045695,20 40,19.1045695 40,18 C40,16.8954305 39.1045695,16 38,16 C36.8954305,16 36,16.8954305 36,18 C36,19.1045695 36.8954305,20 38,20 Z" transform="translate(2 10)"></path>
                            </svg></span> Games
                    </button></div>
                <div class="sidenav-item">
                    <button class="sidenav-trigger" type="button" data-target="menu-app">
                        <span class="svg-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 56 56">
                                <path fill-rule="evenodd" d="M39.2584969,29.5990283 L43.5130807,31.4030317 C43.7706189,31.5639931 43.988118,31.7814922 44.1490794,32.0390304 C44.7345001,32.9757035 44.4497537,34.2096042 43.5130807,34.7950249 L28.5866625,41.1240363 C24.6954191,42.5560634 19.7579314,42.5560634 15.866688,41.1240363 L0.940269829,34.7950249 C0.682731565,34.6340635 0.465232516,34.4165644 0.304271101,34.1590262 C-0.281149563,33.2223531 0.00359676691,31.9884524 0.940269829,31.4030317 L5.1948536,29.5990283 L15.866688,34.1240363 C19.7579314,35.5560634 24.6954191,35.5560634 28.5866625,34.1240363 L39.2584969,29.5990283 Z M39.2584969,17.5990283 L43.5130807,19.4030317 C43.7706189,19.5639931 43.988118,19.7814922 44.1490794,20.0390304 C44.7345001,20.9757035 44.4497537,22.2096042 43.5130807,22.7950249 L28.5866625,29.1240363 C24.6954191,30.5560634 19.7579314,30.5560634 15.866688,29.1240363 L0.940269829,22.7950249 C0.682731565,22.6340635 0.465232516,22.4165644 0.304271101,22.1590262 C-0.281149563,21.2223531 0.00359676691,19.9884524 0.940269829,19.4030317 L5.1948536,17.5990283 L15.866688,22.1240363 C19.7579314,23.5560634 24.6954191,23.5560634 28.5866625,22.1240363 L39.2584969,17.5990283 Z M28.5866625,1.07402035 L43.5130807,7.40303169 C43.7706189,7.56399311 43.988118,7.78149216 44.1490794,8.03903042 C44.7345001,8.97570348 44.4497537,10.2096042 43.5130807,10.7950249 L28.5866625,17.1240363 C24.6954191,18.5560634 19.7579314,18.5560634 15.866688,17.1240363 L0.940269829,10.7950249 C0.682731565,10.6340635 0.465232516,10.4165644 0.304271101,10.1590262 C-0.281149563,9.22235312 0.00359676691,7.98845236 0.940269829,7.40303169 L15.866688,1.07402035 C19.7579314,-0.358006784 24.6954191,-0.358006784 28.5866625,1.07402035 Z" transform="translate(5.773 6.901)"></path>
                            </svg></span> Apps
                    </button></div>
                <div class="sidenav-item">
                    <a href="/news/">
                        <span class="svg-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 56 56">
                                <path d="M 15.5547 53.125 L 40.4453 53.125 C 45.2969 53.125 47.7109 50.6875 47.7109 45.7890 L 47.7109 10.2344 C 47.7109 5.3594 45.2969 2.8750 40.4453 2.8750 L 15.5547 2.8750 C 10.7266 2.8750 8.2891 5.3594 8.2891 10.2344 L 8.2891 45.7890 C 8.2891 50.6875 10.7266 53.125 15.5547 53.125 Z M 15.7422 49.3515 C 13.3281 49.3515 12.0625 48.0625 12.0625 45.7187 L 12.0625 10.3047 C 12.0625 7.9844 13.3281 6.6484 15.7656 6.6484 L 40.2578 6.6484 C 42.6953 6.6484 43.9375 7.9609 43.9375 10.3047 L 43.9375 45.7187 C 43.9375 48.0625 42.6953 49.3515 40.2813 49.3515 Z M 17.4062 25.2344 C 18.0391 25.2344 18.4375 24.9062 18.6484 24.1094 L 19.4687 21.7891 L 23.8515 21.7891 L 24.6953 24.1094 C 24.9062 24.8828 25.3047 25.2344 25.9140 25.2344 C 26.7344 25.2344 27.1797 24.7891 27.1797 24.0391 C 27.1797 23.875 27.1094 23.5703 27.0156 23.2656 L 23.5703 13.7266 C 23.2187 12.7422 22.6328 12.2734 21.6484 12.2734 C 20.6875 12.2734 20.125 12.7422 19.7734 13.7266 L 16.3281 23.2656 C 16.2109 23.5469 16.1640 23.875 16.1640 24.0391 C 16.1640 24.7891 16.6328 25.2344 17.4062 25.2344 Z M 31.3047 16.3281 L 38.1953 16.3281 C 39.0156 16.3281 39.6484 15.6953 39.6484 14.875 C 39.6484 14.0781 39.0156 13.4453 38.1953 13.4453 L 31.3047 13.4453 C 30.4375 13.4453 29.8281 14.0781 29.8281 14.875 C 29.8281 15.6953 30.4375 16.3281 31.3047 16.3281 Z M 19.9844 19.8437 L 21.5781 14.9218 L 21.7422 14.9218 L 23.3359 19.8437 Z M 31.3047 24.5078 L 38.1953 24.5078 C 39.0156 24.5078 39.6484 23.875 39.6484 23.0547 C 39.6484 22.2578 39.0156 21.625 38.1953 21.625 L 31.3047 21.625 C 30.4375 21.625 29.8281 22.2578 29.8281 23.0547 C 29.8281 23.875 30.4375 24.5078 31.3047 24.5078 Z M 15.7891 43.8906 L 17.4531 43.8906 L 20.5234 40.7734 C 20.9687 40.3515 21.4375 40.1875 21.9062 40.1875 C 22.375 40.1875 22.8906 40.3750 23.3359 40.7734 L 25.2578 42.5312 L 30.0625 38.2656 C 30.6015 37.7969 31.1640 37.5859 31.7266 37.5859 C 32.2891 37.5859 32.8984 37.7734 33.3203 38.2656 L 38.0078 43.6562 L 39.6484 43.6562 L 39.6484 32.4531 C 39.6484 30.3906 38.5703 29.3359 36.5078 29.3359 L 18.9531 29.3359 C 16.9609 29.3359 15.7891 30.3906 15.7891 32.4531 Z M 23.5234 38.2656 C 22.1172 38.2656 20.9922 37.0937 20.9922 35.7344 C 20.9922 34.3281 22.1172 33.1562 23.5234 33.2031 C 24.9062 33.2500 26.0547 34.3281 26.0547 35.7344 C 26.0547 37.0937 24.9062 38.2656 23.5234 38.2656 Z"></path>
                            </svg></span> News
                    </a></div>
                <div class="sidenav-item">
                    <div class="divider"></div>
                </div>
                <div class="sidenav-item">
                    <a href="/how-to-install/">
                        <span class="svg-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 -960 960 960">
                                <path d="M172.31-100Q142-100 121-121q-21-21-21-51.31v-615.38Q100-818 121-839q21-21 51.31-21h287.77q14.63 0 27.89 5.62 13.26 5.61 23.11 15.46l167.84 167.84q9.85 9.85 15.46 23.11 5.62 13.26 5.62 27.89v119.7q0 15.36-10.4 25.76-10.39 10.39-25.76 10.39h-46.92q-15.36 0-25.76 10.39-10.39 10.4-10.39 25.76v291.92q0 15.37-10.4 25.76-10.39 10.4-25.76 10.4h-372.3ZM460-620h180L460-800v180ZM219.23-215.39h361.54q-3.59-44.25-26.91-81.28-23.32-37.02-61.48-58.71l34.35-61.47q1.81-3.61.62-8.03-1.2-4.43-5.56-6.43-3.48-2-7.41-1-3.92 1-5.73 4.61l-35.34 63.08q-17.7-7.23-35.91-11.15-18.22-3.92-37.35-3.92-19.13 0-37.2 3.99-18.08 3.99-36.16 11.08l-35.15-63.07q-2-4.23-6.12-4.62-4.11-.38-8.34 1.62-.39 0-3.62 13.84L308-355.38q-38.04 21.67-61.59 58.7-23.55 37.04-27.18 81.29Zm99.62-54.23q-7.23 0-12.66-5.42-5.42-5.42-5.42-12.65t5.42-12.66q5.43-5.42 12.66-5.42t12.46 5.42q5.23 5.43 5.23 12.66t-5.31 12.65q-5.31 5.42-12.38 5.42Zm162.69 0q-7.23 0-12.66-5.42-5.42-5.42-5.42-12.65t5.42-12.66q5.43-5.42 12.66-5.42t12.65 5.42q5.42 5.43 5.42 12.66t-5.42 12.65q-5.42 5.42-12.65 5.42Zm259.23 54.08v-150.23q0-12.75 8.63-21.37 8.63-8.63 21.38-8.63 12.76 0 21.37 8.63 8.62 8.62 8.62 21.37v150.23l53.31-52.31q8.92-8.3 20.88-8.11 11.96.19 20.88 9.06 8.18 8.87 8.55 20.69.38 11.82-8.55 20.75l-99.77 100.15q-10.84 10.85-25.3 10.85t-25.31-10.85l-99.77-100.15q-8.18-8.21-8.55-20.53-.37-12.32 8.55-21.24 8.31-9.31 21.08-9.12 12.77.2 21.69 8.5l52.31 52.31Z"></path>
                            </svg></span> How to install?
                    </a></div>
            </div>
        </div>
    </div>
    <div class="sidenav-overlay " data-target="nav-mobile"></div>

    <!-- MENU GAME -->
    <div id="menu-game" class="sidenav">
        <div class="container">
            <div class="sidenav__content liquid-glass liquid-glass__100">
                <ul>
                    
                    <?php foreach ($games_categories as $category): ?>
                        <li>
                            <a href="<?php echo (APP_LANG === APP_LANG_DF) ? "/posts/category/{$category['slug']}" : page_url($category['slug'], 'games'); ?>" aria-label="<?php echo htmlspecialchars(strtolower($category['name']), ENT_QUOTES, 'UTF-8'); ?> games">
                                <?php echo htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8'); ?> 
                                <!-- <span class="right font-size__small color__gray"><?php echo $category['count'] ?? 0; ?></span> -->
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
    <div class="sidenav-overlay" data-target="menu-game"></div>

    <!-- MENU APPS -->
    <div id="menu-app" class="sidenav">
        <div class="container">
            <div class="sidenav__content liquid-glass liquid-glass__100">
                <ul>
               
                    <?php foreach ($apps_categories as $category): ?>
                        
                        <li>
                            <a href="<?php echo (APP_LANG === APP_LANG_DF) ? "/posts/category/{$category['slug']}" : page_url($category['slug'], 'apps'); ?>" aria-label="<?php echo htmlspecialchars(strtolower($category['name']), ENT_QUOTES, 'UTF-8'); ?> apps">
                                <?php echo htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8'); ?> 
                                <!-- <span class="right font-size__small color__gray"><?php echo $category['count'] ?? 0; ?></span> -->
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
    <div class="sidenav-overlay" data-target="menu-app"></div>


    <footer class="padding-top-30" style="padding-bottom: 90px; border-top: 1px solid #eee;">
        <div class="container padding-top-15">
            <div class="flex flex__start flex__l4 flex__m2 flex__s1">
                <div class="flex__item font-size__small">
                    <div><strong>Information</strong></div>
                    <ul>
                        <?php foreach ($footer_links['information'] as $link): ?>
                            <li>
                                <a class="footer-link" href="<?php echo htmlspecialchars($link['url'], ENT_QUOTES, 'UTF-8'); ?>" aria-label="<?php echo htmlspecialchars($link['name'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars($link['name'], ENT_QUOTES, 'UTF-8'); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="flex__item font-size__small">
                    <div><strong>Products &amp; Services</strong></div>
                    <ul>
                        <?php foreach ($footer_links['products'] as $link): ?>
                            <li>
                                <a class="footer-link" href="<?php echo htmlspecialchars($link['url'], ENT_QUOTES, 'UTF-8'); ?>" aria-label="<?php echo htmlspecialchars($link['name'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars($link['name'], ENT_QUOTES, 'UTF-8'); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="flex__item font-size__small">
                    <div><strong>Legal</strong></div>
                    <ul>
                        <?php foreach ($footer_links['legal'] as $link): ?>
                            <li>
                                <a class="footer-link" href="<?php echo htmlspecialchars($link['url'], ENT_QUOTES, 'UTF-8'); ?>" aria-label="<?php echo htmlspecialchars($link['name'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars($link['name'], ENT_QUOTES, 'UTF-8'); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="flex__item font-size__small">
                    <div><strong>Languages</strong></div>
                    <ul>
                        <?php foreach ($footer_links['languages'] as $link): ?>
                            <li>
                                <a class="footer-link" href="<?php echo htmlspecialchars($link['url'], ENT_QUOTES, 'UTF-8'); ?>" aria-label="<?php echo htmlspecialchars($link['name'], ENT_QUOTES, 'UTF-8'); ?> version">
                                    <?php echo htmlspecialchars($link['name'], ENT_QUOTES, 'UTF-8'); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </footer>
    <!-- Optimized JS loading -->
    <script>
        // Load optimized scripts
        function loadScript(src, defer = true) {
            const script = document.createElement('script');
            script.src = src;
            script.defer = defer;
            script.async = !defer;
            document.head.appendChild(script);
        }

        // Load error handler first
        loadScript('/themes/apkcms/Frontend/Assets/js/error-handler.min.js', false);
        
        // Load core functionality immediately
        loadScript('/themes/apkcms/Frontend/Assets/js/script-optimized.min.js', true);
        
        // Load lazy loading for images
        loadScript('/themes/apkcms/Frontend/Assets/js/lazy-load.min.js', true);

        // Load page-specific scripts
        document.addEventListener('DOMContentLoaded', function() {
            // Load single page scripts if needed
            if (document.querySelector('#unfold-table, #toc-trigger')) {
                loadScript('/themes/apkcms/Frontend/Assets/js/single.min.js', false);
            }
            
            if (document.querySelector('#title-post') && !document.querySelector('#unfold-table')) {
                loadScript('/themes/apkcms/Frontend/Assets/js/single-news.min.js', false);
            }
            
            // Remove loading indicators
            const loadingElements = document.querySelectorAll('.loading');
            loadingElements.forEach(el => el.classList.remove('loading'));
        });
    </script>

    <?php echo \System\Libraries\Render::renderAsset('footer', 'frontend') ?>
    </body>
</html>