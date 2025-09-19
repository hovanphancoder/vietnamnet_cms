<!DOCTYPE html>
<html lang="<?= lang_code() ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    echo $meta ?? '';
    echo $schema ?? '';
    echo $append ?? '';
    ?> 
    <!-- <script src="https://cdn.tailwindcss.com"></script> -->
    <?= \System\Libraries\Render::renderAsset('head', 'frontend') ?>

    <link rel="apple-touch-icon" sizes="57x57" href="/themes/<?= APP_THEME_NAME ?>/Backend/Assets/favicon/apple-icon-57x57.png">
    <link rel="apple-touch-icon" sizes="60x60" href="/themes/<?= APP_THEME_NAME ?>/Backend/Assets/favicon/apple-icon-60x60.png">
    <link rel="apple-touch-icon" sizes="72x72" href="/themes/<?= APP_THEME_NAME ?>/Backend/Assets/favicon/apple-icon-72x72.png">
    <link rel="apple-touch-icon" sizes="76x76" href="/themes/<?= APP_THEME_NAME ?>/Backend/Assets/favicon/apple-icon-76x76.png">
    <link rel="apple-touch-icon" sizes="114x114" href="/themes/<?= APP_THEME_NAME ?>/Backend/Assets/favicon/apple-icon-114x114.png">
    <link rel="apple-touch-icon" sizes="120x120" href="/themes/<?= APP_THEME_NAME ?>/Backend/Assets/favicon/apple-icon-120x120.png">
    <link rel="apple-touch-icon" sizes="144x144" href="/themes/<?= APP_THEME_NAME ?>/Backend/Assets/favicon/apple-icon-144x144.png">
    <link rel="apple-touch-icon" sizes="152x152" href="/themes/<?= APP_THEME_NAME ?>/Backend/Assets/favicon/apple-icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/themes/<?= APP_THEME_NAME ?>/Backend/Assets/favicon/apple-icon-180x180.png">
    <link rel="icon" type="image/png" sizes="192x192"  href="/themes/<?= APP_THEME_NAME ?>/Backend/Assets/favicon/android-icon-192x192.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/themes/<?= APP_THEME_NAME ?>/Backend/Assets/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="96x96" href="/themes/<?= APP_THEME_NAME ?>/Backend/Assets/favicon/favicon-96x96.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/themes/<?= APP_THEME_NAME ?>/Backend/Assets/favicon/favicon-16x16.png">
    <link rel="manifest" href="/themes/<?= APP_THEME_NAME ?>/Backend/Assets/favicon/manifest.json">
    <meta name="msapplication-TileColor" content="#6b21a8">
    <meta name="msapplication-TileImage" content="/themes/<?= APP_THEME_NAME ?>/Backend/Assets/favicon/ms-icon-144x144.png">
    <meta name="theme-color" content="#6b21a8">
    <link rel="icon" href="/themes/<?= APP_THEME_NAME ?>/Backend/Assets/favicon/favicon.ico" type="image/x-icon">

<style type="text/css">
.blaze-slider{--slides-to-show:1;--slide-gap:20px;direction:ltr}.blaze-container{position:relative}.blaze-track-container{overflow:hidden}.blaze-track{will-change:transform;touch-action:pan-y;display:flex;gap:var(--slide-gap);--slide-width:calc( (100% - (var(--slides-to-show) - 1) * var(--slide-gap)) / var(--slides-to-show) );box-sizing:border-box}.blaze-track>*{box-sizing:border-box;width:var(--slide-width);flex-shrink:0}.blaze-slider.dragging .blaze-track{cursor:grabbing}.blaze-track>*{transition:width 200ms ease}.blaze-pagination{display:flex;gap:25px}.blaze-pagination button{font-size:0;width:15px;height:15px;border-radius:50%;outline:none;border:none;background:rgb(156 163 175 / .6);cursor:pointer;transition:transform 200ms ease,background-color 300ms ease}.blaze-pagination button.active{background:#3b82f6;transform:scale(1.2);border:1px solid #FFF}.controls{display:flex;justify-content:center;align-items:center;margin:20px 0;gap:20px}.blaze-track,.blaze-track>*{box-sizing:border-box}.blaze-next,.blaze-prev{border:none;background:none;align-items:center;display:flex;justify-content:center;transition:opacity .3s;}.blaze-next svg,.blaze-prev svg{fill:#6b7280}.blaze-prev{transform:rotate(180deg)}.blaze-slider.start .blaze-prev,.blaze-slider.end .blaze-next{opacity:.5;cursor:not-allowed}.hero-swiper{--slides-to-show:1;--slide-gap:0px} .hero-swiper .controls{ display: none; } @media screen and (min-width: 768px) { .hero-swiper .controls{ display: flex; } } .hero-swiper .blaze-next,.hero-swiper .blaze-prev{position:absolute;top:50%;transform:translateY(-50%);z-index:10;width:60px;height:60px;backdrop-filter:blur(30px);border:none;transition:all 0.3s ease;cursor:pointer;display:flex;align-items:center;justify-content:center}.hero-swiper .blaze-next:hover,.hero-swiper .blaze-prev:hover{background:rgb(0 0 0 / .3)}.hero-swiper .blaze-next{right:20px;margin-top:-15px}.hero-swiper .blaze-prev{left:20px;transform:rotate(180deg);margin-top:-45px}.hero-swiper .blaze-next svg,.hero-swiper .blaze-prev svg{fill:#ebda5c;width:36px;height:36px}.reviews-slider{--slides-to-show:4;--slide-gap:15px}@media (max-width:1360px){.reviews-slider{--slides-to-show:3;--slide-gap:40px}}@media (max-width:1020px){.reviews-slider{--slides-to-show:2;--slide-gap:40px}}@media (max-width:600px){.reviews-slider{--slides-to-show:1;--slide-gap:15px}}.services-slide{--slides-to-show:3;--slide-gap:20px}@media (max-width:1360px){.services-slide{--slides-to-show:2.5;--slide-gap:16px}}@media (max-width:1024px){.services-slide{--slides-to-show:2.2;--slide-gap:16px}}@media (max-width:768px){.services-slide{--slides-to-show:1.7;--slide-gap:16px}}@media (max-width:600px){.services-slide{--slides-to-show:1.3;--slide-gap:10px}}@media (max-width:480px){.services-slide{--slides-to-show:1;--slide-gap:8px}}
</style>
</head>

<body>
    <!-- Header -->
    <header class="bg-white/90 backdrop-blur-md shadow-sm sticky top-0 z-50">
        <div class="container mx-auto px-4 h-20 flex items-center justify-between">
            <!-- Logo -->
            <a href="<?= base_url(); ?>" class="flex items-center space-x-2">
                <?= _img(
                    theme_assets('images/logo/Logo.webp'),
                    option('site_brand'),
                    false,
                    'w-28 object-cover'
                ) ?>

            </a>
            <!-- Desktop Navigation -->
            <nav class="hidden md:flex items-center space-x-1">
                <a href="<?= base_url('features'); ?>" class=" <?= $layout == 'features' ? 'text-blue-600' : 'text-slate-600' ?> hover:text-blue-600 transition-colors px-3 py-2 rounded-md">
                    <?php __e('Features'); ?>
                </a>
                <a href="<?= base_url('library'); ?>" class="<?= ($layout == 'library' || $layout == 'plugins' || $layout == 'themes') ? 'text-blue-600' : 'text-slate-600' ?> hover:text-blue-600 transition-colors px-3 py-2 rounded-md">
                    <?php __e('Library'); ?>
                </a>
                <a href="<?= base_url('blogs'); ?>" class="<?= ($layout == 'blogs' || $layout == 'blog_detail') ? 'text-blue-600' : 'text-slate-600' ?> hover:text-blue-600 transition-colors px-3 py-2 rounded-md">
                    <?php __e('Blogs'); ?>
                </a>
                <a href="<?= docs_url(); ?>" class="text-slate-600 hover:text-blue-600 transition-colors px-3 py-2 rounded-md">
                    <?php __e('Documentation'); ?>
                </a>
                <!-- <a href="#docs" class="text-slate-600 hover:text-blue-600 transition-colors px-3 py-2 rounded-md inline md:hidden lg:inline">
                    <?php __e('Services'); ?>
                </a> -->
            </nav>

            
            <div class="flex items-center space-x-1">
                <!-- Desktop Actions -->
                <div class="flex items-center space-x-1">
                    <!-- Search Dropdown (Hidden on Mobile) -->
                    <div class="relative">
                        <button id="searchDropdownToggle" aria-label="Search"
                            class="flex items-center justify-center w-10 h-10 bg-white md:border border-slate-300 rounded-md text-slate-600 hover:text-blue-600 hover:border-blue-400 transition-colors duration-200 focus:outline-none focus:ring-0 focus:border-blue-500">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"></circle>
                                <path d="m21 21-4.35-4.35"></path>
                            </svg>
                        </button>

                        <!-- Search Dropdown Menu -->
                        <div id="searchDropdownMenu" class="absolute right-0 mt-2 w-80 bg-white border border-slate-200 rounded-lg shadow-xl z-50 invisible opacity-0 scale-95 origin-top-right transition-all duration-200">
                            <div class="p-4">
                                <form action="<?= base_url('search') ?>" method="GET" class="space-y-3">
                                    <div class="relative">
                                        <input
                                            type="text"
                                            name="q"
                                            id="searchInput"
                                            placeholder="<?php __e('Search for themes, plugins, blogs...'); ?>"
                                            class="w-full pl-10 pr-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-0 focus:border-blue-500 transition-all duration-200"
                                            autocomplete="off"
                                            required>
                                        <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-slate-400" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="11" cy="11" r="8"></circle>
                                            <path d="m21 21-4.35-4.35"></path>
                                        </svg>
                                    </div>
                                    <button
                                        type="submit"
                                        class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-medium py-2 px-4 rounded-lg transition-all duration-200 hover:shadow-md">
                                        <?php __e('search.search_button'); ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Language Dropdown -->
                    <div class="relative inline-block w-full max-w-xs">
                        <button id="languageDropdownToggle"
                            class="flex items-center justify-between w-full bg-white md:border border-slate-300 rounded-md max-h-[40px] px-2 py-2 text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 cursor-pointer transition-colors hover:border-slate-400" style="max-height: 40px;">
                            <div class="flex items-center space-x-2">
                                <span class="text-lg"><?= lang_flag() ?></span>
                                <span class="hidden md:block text-sm font-medium"><?= lang_name() ?></span>
                            </div>
                            <!-- Icon mũi tên -->
                            <svg id="languageDropdownArrow" class="w-4 h-4 text-slate-400 transition-transform duration-200" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <!-- Dropdown Menu -->
                        <div id="languageDropdownMenu" class="absolute right-0 mt-2 w-48 bg-white border border-slate-200 rounded-md shadow-lg z-50 invisible opacity-0 scale-95 origin-top-right transition-all duration-100">
                            <div class="py-1">
                                <?php foreach (APP_LANGUAGES as $lang => $langData) {
                                    $isCurrent = (APP_LANG === $lang);
                                    $url = lang_url($lang);
                                ?>
                                    <a href="<?= $url ?>"
                                        class="flex items-center space-x-3 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 hover:text-slate-900 transition-colors duration-150 <?= $isCurrent ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-500' : '' ?>">
                                        <span class="text-lg"><?= lang_flag($lang) ?></span>
                                        <span class="font-medium <?= $isCurrent ? 'text-blue-700' : 'text-slate-700' ?>"><?= $langData['name'] ?></span>
                                        <?php if ($isCurrent): ?>
                                            <svg class="w-4 h-4 ml-auto text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                        <?php endif; ?>
                                    </a>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                    <!-- Menu người dùng (ẩn khi chưa đăng nhập) -->
                    <div id="user-menu" style="display: none;" class="flex items-center gap-2">
                        <img id="user-avatar" src="" alt="avatar" class="w-8 h-8 rounded-full border">
                        <span id="user-name" class="text-sm font-medium text-gray-800"></span>
                    </div>

                    <a href="<?= base_url('download') ?>" class="hidden md:flex">
                        <button
                            class="flex items-center justify-center bg-gradient-to-r from-blue-600 to-indigo-700 border border-blue-600 hover:from-blue-700 hover:to-indigo-800 text-white transition-all duration-300 shadow-md hover:shadow-lg px-4 py-2 rounded-md">

                            <!-- Icon: chỉ hiển thị dưới lg -->
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-download"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>

                            <!-- Text: chỉ hiển thị từ lg trở lên -->
                            <span class="whitespace-nowrap ml-2 hidden lg:inline"><?php __e('download'); ?></span>
                        </button>
                    </a>

                </div>

                <!-- Mobile Menu Button -->
                <div class="md:hidden">
                    <button id="mobileMenuToggle" aria-label="Menu" class="text-slate-700 p-2">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect x="2" y="4" width="16" height="2" rx="1" fill="currentColor" />
                            <rect x="2" y="9" width="16" height="2" rx="1" fill="currentColor" />
                            <rect x="2" y="14" width="16" height="2" rx="1" fill="currentColor" />
                        </svg>
                    </button>
                </div>
            </div> 
        </div>

        <!-- Mobile Menu -->
        <div class="mobile-menu fixed inset-y-0 w-80 bg-white shadow-lg z-50 w-full hidden" id="mobileMenu">
            <div class="p-6 bg-white rounded-lg">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex justify-center items-center space-x-2">
                        <div class="">
                            <?= _img(
                                theme_assets('images/logo/logo-icon.webp'),
                                'Logo CMS',
                                true,
                                'mx-auto h-12'
                            ) ?>
                        </div>
                        <span class="text-xl font-bold text-blue-600">CMS Full Form</span>
                    </div>
                    <button aria-label="Close" id="closeMobileMenu" class="text-slate-600 p-2">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M15 5L5 15M5 5L15 15" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                        </svg>
                    </button>
                </div>
                <nav class="space-y-2 ">
                    <!-- Navigation Links -->
                    <div class="space-y-1 rounded-lg">
                        <a href="<?= base_url('features'); ?>"
                            class="group flex items-center space-x-4 p-4 rounded-xl hover:bg-slate-50 transition-all duration-300 hover:shadow-sm border border-transparent hover:border-slate-100">
                            <div class="w-10 h-10 bg-blue-50 group-hover:bg-blue-100 rounded-lg flex items-center justify-center transition-colors duration-300">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-600">
                                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <span class="text-slate-700 group-hover:text-slate-900 font-medium transition-colors duration-300">
                                    <?php __e('Features'); ?>
                                </span>
                                <p class="text-sm text-slate-500 group-hover:text-slate-600 transition-colors duration-300">
                                    Explore powerful features
                                </p>
                            </div>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-400 group-hover:text-slate-600 group-hover:translate-x-1 transition-all duration-300">
                                <path d="M9 18l6-6-6-6" />
                            </svg>
                        </a>

                        <a href="<?= base_url('library'); ?>"
                            class="group flex items-center space-x-4 p-4 rounded-xl hover:bg-slate-50 transition-all duration-300 hover:shadow-sm border border-transparent hover:border-slate-100">
                            <div class="w-10 h-10 bg-green-50 group-hover:bg-green-100 rounded-lg flex items-center justify-center transition-colors duration-300">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-green-600">
                                    <path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <span class="text-slate-700 group-hover:text-slate-900 font-medium transition-colors duration-300">
                                    <?php __e('Library'); ?>
                                </span>
                                <p class="text-sm text-slate-500 group-hover:text-slate-600 transition-colors duration-300">
                                    Browse resources
                                </p>
                            </div>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-400 group-hover:text-slate-600 group-hover:translate-x-1 transition-all duration-300">
                                <path d="M9 18l6-6-6-6" />
                            </svg>
                        </a>

                        <a href="<?= base_url('blogs'); ?>"
                            class="group flex items-center space-x-4 p-4 rounded-xl hover:bg-slate-50 transition-all duration-300 hover:shadow-sm border border-transparent hover:border-slate-100">
                            <div class="w-10 h-10 bg-purple-50 group-hover:bg-purple-100 rounded-lg flex items-center justify-center transition-colors duration-300">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-purple-600">
                                    <path d="M4 19.5A2.5 2.5 0 016.5 17H20" />
                                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z" />
                                </svg> 
                            </div>
                            <div class="flex-1">
                                <span class="text-slate-700 group-hover:text-slate-900 font-medium transition-colors duration-300">
                                    <?php __e('Blogs'); ?>
                                </span>
                                <p class="text-sm text-slate-500 group-hover:text-slate-600 transition-colors duration-300">
                                    Latest updates
                                </p>
                            </div>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-400 group-hover:text-slate-600 group-hover:translate-x-1 transition-all duration-300">
                                <path d="M9 18l6-6-6-6" />
                            </svg>
                        </a>
                    </div>

                    <!-- Divider -->
                    <div class="my-6">
                        <div class="h-px bg-gradient-to-r from-transparent via-slate-200 to-transparent"></div>
                    </div>

                    <!-- Mobile Search -->
                    <div class="mb-6">
                        <form action="<?= base_url('search') ?>" method="GET" id="mobileSearchForm">
                            <div class="relative">
                                <input
                                    type="text"
                                    name="q"
                                    id="mobileSearchInput"
                                    placeholder="<?php __e('Search for themes, plugins, blogs...'); ?>"
                                    class="w-full pl-12 pr-16 py-3 border border-slate-200 rounded-xl focus:outline-none focus:ring-0 focus:border-blue-500 transition-all duration-200 bg-white"
                                    autocomplete="off"
                                    value="<?= S_GET('q', '') ?>"
                                    required>
                                <!-- Search Icon -->
                                <svg class="absolute left-4 top-1/2 transform -translate-y-1/2 w-5 h-5 text-slate-400" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <path d="m21 21-4.35-4.35"></path>
                                </svg>
                                <!-- Search Button -->
                                <button
                                    type="submit"
                                    id="mobileSearchButton"
                                    class="absolute right-2 top-1/2 transform -translate-y-1/2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-medium py-2 px-3 rounded-lg transition-all duration-200 hover:shadow-md text-sm">
                                    <?php __e('search.search_button'); ?>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Action Buttons -->
                    <div class="space-y-3">
                        <!-- Login Button -->
                        <!-- <a href="<?= base_url('download') ?>" class="block group">
                            <button class="w-full flex items-center justify-center space-x-3 p-4 bg-white hover:bg-slate-50 border border-slate-200 hover:border-slate-300 rounded-xl font-medium text-slate-700 hover:text-slate-900 transition-all duration-300 hover:shadow-sm group-hover:scale-[1.02]">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-500 group-hover:text-slate-700 transition-colors duration-300">
                                    <path d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4" />
                                    <polyline points="10,17 15,12 10,7" />
                                    <line x1="15" y1="12" x2="3" y2="12" />
                                </svg>
                                <span><?php __e('auth.login'); ?></span>
                            </button>
                        </a> -->

                        <!-- Get Started Button -->
                        <a href="<?= base_url('download') ?>" class="block group">
                            <button class="w-full relative overflow-hidden">
                                <!-- Background gradient -->
                                <div class="absolute inset-0 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-xl"></div>
                                <div class="absolute inset-0 bg-gradient-to-r from-blue-700 to-indigo-700 rounded-xl opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>

                                <!-- Button content -->
                                <div class="relative flex items-center justify-center space-x-3 p-4 text-white font-medium group-hover:scale-[1.02] transition-transform duration-300">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="group-hover:rotate-12 transition-transform duration-300">
                                        <path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 00-2.91-.09z" />
                                        <path d="M12 15l-3-3a22 22 0 012-3.95A12.88 12.88 0 0122 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 01-4 2z" />
                                        <path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5 0 5 0" />
                                        <path d="M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5" />
                                    </svg>
                                    <span><?php __e('download'); ?></span>
                                </div>

                                <!-- Shine effect -->
                                <div class="absolute inset-0 -skew-x-12 bg-gradient-to-r from-transparent via-white/10 to-transparent opacity-0 group-hover:opacity-100 group-hover:animate-pulse transition-opacity duration-500"></div>
                            </button>
                        </a>
                    </div>

                </nav>


            </div>
        </div>

    </header>
    <div id="mobileMenuOverlay" class="fixed inset-0 bg-black bg-opacity-40 z-40 hidden md:hidden"></div>
    <main class="">
        <div class="bg-slate-50 text-slate-800 min-h-screen bg-background">

            <style>
                @keyframes shake {

                    0%,
                    100% {
                        transform: translateX(0);
                    }

                    25% {
                        transform: translateX(-5px);
                    }

                    75% {
                        transform: translateX(5px);
                    }
                }
            </style>

            <script>
                // Optimized Header JavaScript Module
                (function() {
                    'use strict';
                    
                    // Dropdown utility class
                    class Dropdown {
                        constructor(toggleId, menuId, arrowId = null) {
                            this.toggle = document.getElementById(toggleId);
                            this.menu = document.getElementById(menuId);
                            this.arrow = arrowId ? document.getElementById(arrowId) : null;
                            this.isOpen = false;
                            
                            if (this.toggle && this.menu) {
                                this.init();
                            }
                        }
                        
                        init() {
                            this.toggle.addEventListener('click', (e) => this.handleToggle(e));
                            document.addEventListener('click', (e) => this.handleOutsideClick(e));
                            document.addEventListener('keydown', (e) => this.handleEscape(e));
                        }
                        
                        handleToggle(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            this.isOpen ? this.close() : this.open();
                        }
                        
                        handleOutsideClick(e) {
                            if (!this.toggle.contains(e.target) && !this.menu.contains(e.target)) {
                                this.close();
                            }
                        }
                        
                        handleEscape(e) {
                            if (e.key === 'Escape') this.close();
                        }
                        
                        open() {
                            this.menu.classList.remove('opacity-0', 'invisible', 'scale-95');
                            this.menu.classList.add('opacity-100', 'visible', 'scale-100');
                            if (this.arrow) this.arrow.style.transform = 'rotate(180deg)';
                            this.isOpen = true;
                        }
                        
                        close() {
                            this.menu.classList.remove('opacity-100', 'visible', 'scale-100');
                            this.menu.classList.add('opacity-0', 'invisible', 'scale-95');
                            if (this.arrow) this.arrow.style.transform = 'rotate(0deg)';
                            this.isOpen = false;
                        }
                    }
                    
                    // Search form utility class
                    class SearchForm {
                        constructor(inputId, formId, minLength = 2) {
                            this.input = document.getElementById(inputId);
                            this.form = document.getElementById(formId);
                            this.minLength = minLength;
                            
                            if (this.input && this.form) {
                                this.init();
                            }
                        }
                        
                        init() {
                            this.form.addEventListener('submit', (e) => this.handleSubmit(e));
                            this.input.addEventListener('keydown', (e) => this.handleEnter(e));
                        }
                        
                        handleSubmit(e) {
                            const query = this.input.value.trim();
                            
                            if (!query || query.length < this.minLength) {
                                e.preventDefault();
                                this.showError();
                                return false;
                            }
                            
                            this.clearError();
                        }
                        
                        handleEnter(e) {
                            if (e.key === 'Enter') {
                                e.preventDefault();
                                this.form.dispatchEvent(new Event('submit'));
                            }
                        }
                        
                        showError() {
                            this.input.classList.add('border-red-500', 'focus:border-red-500');
                            this.input.classList.remove('border-slate-200', 'focus:border-blue-500');
                            this.input.style.animation = 'shake 0.5s ease-in-out';
                            
                            setTimeout(() => {
                                this.clearError();
                            }, 500);
                        }
                        
                        clearError() {
                            this.input.classList.remove('border-red-500', 'focus:border-red-500');
                            this.input.classList.add('border-slate-200', 'focus:border-blue-500');
                            this.input.style.animation = '';
                        }
                    }
                    
                    // Initialize when DOM is ready
                    document.addEventListener('DOMContentLoaded', function() {
                        // Initialize dropdowns
                        new Dropdown('languageDropdownToggle', 'languageDropdownMenu', 'languageDropdownArrow');
                        new Dropdown('searchDropdownToggle', 'searchDropdownMenu');
                        
                        // Initialize search forms
                        new SearchForm('searchInput', 'searchDropdownMenu form');
                        new SearchForm('mobileSearchInput', 'mobileSearchForm');
                        
                        // Focus mobile search when menu opens
                        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
                        const mobileSearchInput = document.getElementById('mobileSearchInput');
                        
                        if (mobileMenuToggle && mobileSearchInput) {
                            mobileMenuToggle.addEventListener('click', () => {
                                setTimeout(() => mobileSearchInput?.focus(), 300);
                            });
                        }
                        
                        // Focus search input when dropdown opens
                        const searchDropdown = new Dropdown('searchDropdownToggle', 'searchDropdownMenu');
                        const searchInput = document.getElementById('searchInput');
                        
                        if (searchInput) {
                            searchDropdown.toggle.addEventListener('click', () => {
                                if (!searchDropdown.isOpen) {
                                    setTimeout(() => searchInput.focus(), 100);
                                }
                            });
                        }
                    });
                })();
            </script>
