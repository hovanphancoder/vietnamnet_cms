<?php 
add_shortcode('top-rating', function ($posttype, $post_id) {
    $comments = get_posts([
        'posttype' => 'comment',
        'filters' => [
            'posttype' => $posttype,
            'post_id' => $post_id,
        ],
        'sort' => ['like_count', 'DESC'],
        'perPage' => 5,
    ]);

    ob_start();
    ?>
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-6">
            Trích Dẫn Nổi Bật
        </h2>
        <div class="bg-purple-50 relative rounded-lg p-6">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                stroke-linejoin="round"
                class="lucide lucide-quote absolute top-4 left-4 h-8 w-8 text-purple-200">
                <path d="M16 3a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2 1 1 0 0 1 1 1v1a2 2 0 0 1-2 2 1 1 0 0 0-1 1v2a1 1 0 0 0 1 1 6 6 0 0 0 6-6V5a2 2 0 0 0-2-2z"></path>
                <path d="M5 3a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2 1 1 0 0 1 1 1v1a2 2 0 0 1-2 2 1 1 0 0 0-1 1v2a1 1 0 0 0 1 1 6 6 0 0 0 6-6V5a2 2 0 0 0-2-2z"></path>
            </svg>
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                stroke-linejoin="round"
                class="lucide lucide-quote absolute bottom-4 right-4 h-8 w-8 text-purple-200 transform rotate-180">
                <path d="M16 3a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2 1 1 0 0 1 1 1v1a2 2 0 0 1-2 2 1 1 0 0 0-1 1v2a1 1 0 0 0 1 1 6 6 0 0 0 6-6V5a2 2 0 0 0-2-2z"></path>
                <path d="M5 3a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2 1 1 0 0 1 1 1v1a2 2 0 0 1-2 2 1 1 0 0 0-1 1v2a1 1 0 0 0 1 1 6 6 0 0 0 6-6V5a2 2 0 0 0-2-2z"></path>
            </svg>
            
            <!-- Swiper -->
            <div class="swiper quotes-swiper">
                <div class="swiper-wrapper">
                    <?php foreach ($comments['data'] as $quote): ?>
                        <?php $user = get_user($quote['user_id']); ?>
                        <div class="swiper-slide">
                            <div class="rounded-lg p-6 relative">
                                <div class="text-center px-8">
                                    <p class="text-lg md:text-xl text-gray-800 italic mb-4">
                                        <?= htmlspecialchars($quote['content']); ?>
                                    </p>
                                    <div class="text-purple-600 font-medium">
                                        <?= htmlspecialchars($user['fullname'] ?? 'Anonymous User'); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Navigation controls -->
                <div class="flex justify-center gap-4 mt-6">
                    <button id="prev-button"
                        class="inline-flex items-center justify-center gap-2 whitespace-nowrap text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 w-10 rounded-full">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="lucide lucide-chevron-left h-4 w-4">
                            <path d="m15 18-6-6 6-6"></path>
                        </svg>
                    </button>
                    <div class="custom-pagination flex items-center gap-1"></div>
                    <button id="next-button"
                        class="inline-flex items-center justify-center gap-2 whitespace-nowrap text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 w-10 rounded-full">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="lucide lucide-chevron-right h-4 w-4">
                            <path d="m9 18 6-6-6-6"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <script>
    // Check if Swiper is already loaded, if not then load it
    (function() {
        function loadSwiperAssets(callback) {
            if (window.Swiper) {
                callback();
                return;
            }
            // Add CSS
            if (!document.getElementById('swiper-css')) {
                var link = document.createElement('link');
                link.id = 'swiper-css';
                link.rel = 'stylesheet';
                link.href = '<?php echo theme_assets('css/swiper-bundle.min.css', 'reactix'); ?>';
                document.head.appendChild(link);
            }
            // Add JS
            if (!document.getElementById('swiper-js')) {
                var script = document.createElement('script');
                script.id = 'swiper-js';
                script.src = '<?php echo theme_assets('js/swiper-bundle.min.js', 'reactix'); ?>';
                script.onload = callback;
                document.body.appendChild(script);
            } else {
                document.getElementById('swiper-js').addEventListener('load', callback);
            }
        }

        loadSwiperAssets(function() {
            document.dispatchEvent(new Event('swiper-ready'));
        });
    })();

    // Wait for Swiper to be ready before running initialization code
    document.addEventListener('swiper-ready', function () {
        const paginationContainer = document.querySelector('.custom-pagination');
        const updateDots = (realIndex) => {
            const dots = document.querySelectorAll('.custom-pagination .dot');
            dots.forEach((dot, index) => {
                dot.classList.toggle('bg-purple-600', index === realIndex);
                dot.classList.toggle('bg-purple-200', index !== realIndex);
            });
        };

        // Initialize swiper first
        const swiper = new Swiper('.quotes-swiper', {
            loop: true,
            effect: 'fade',
            fadeEffect: { crossFade: true },
            speed: 500,
            autoplay: {
                delay: 5000,
                disableOnInteraction: false,
            },
            on: {
                init: function () {
                    updateDots(this.realIndex);
                },
                slideChange: function () {
                    updateDots(this.realIndex);
                },
            },
        });

        // ✅ After Swiper has been created, create dots
        const totalSlides = document.querySelectorAll('.quotes-swiper .swiper-slide').length;
        for (let i = 0; i < totalSlides; i++) {
            const dot = document.createElement('div');
            dot.className = `dot h-2 w-2 rounded-full ${i === 0 ? 'bg-purple-600' : 'bg-purple-200'} transition-colors`;
            dot.setAttribute('data-index', i);
            dot.addEventListener('click', () => {
                swiper.slideToLoop(i);
            });
            paginationContainer.appendChild(dot);
        }

        // Navigation buttons
        document.getElementById('prev-button').addEventListener('click', () => swiper.slidePrev());
        document.getElementById('next-button').addEventListener('click', () => swiper.slideNext());
    });
    </script>
    <?php
    return ob_get_clean();
});