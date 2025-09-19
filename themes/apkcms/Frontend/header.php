<?php
use App\Models\FastModel;
require_once __DIR__ . '/functions.php';

$current_page = get_current_page();
?>
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
    <?= \System\Libraries\Render::renderAsset('head', 'frontend') ?>

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- <link href="css/style.css" rel="stylesheet"> -->
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>

<body class="<?= $page_class ?>">

  

      <!-- Main Navigation Bar -->
      <header class="bg-white border-b border-gray-200 fixed left-0 top-0 w-full z-40">
        <!-- Top Header Bar -->
        <div class="bg-white border-b border-gray-200  ">
            <div class="container max-w-[1140px] mx-auto px-4">
                <div class="flex items-center justify-between h-12">
                    <!-- Left Side - Logo and Date -->
                    <div class="flex items-center space-x-4">


                        <!-- Date -->
                        <div class="text-sm text-gray-600 font-medium">
                            <!-- Desktop: Goback link -->
                            <a class="goback hidden lg:inline-flex items-center gap-2 px-2 py-0 border border-gray-300 rounded-full" href="/">
                                <img src="https://static.vnncdn.net/v1/icon/return.png" class="w-4 h-4" alt="return icon">
                                <span class="hidden sm:inline">Vietnamnet.vn</span>
                            </a>

                            <!-- Mobile: Hamburger menu icon -->
                            <button class="lg:hidden inline-flex items-center justify-center w-8 h-8 text-gray-600 hover:text-gray-900" onclick="toggleMobileMenu()">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center space-x-4">
                        <!-- Vietnamnet Logo -->
                        <div class="flex items-center space-x-2">
                            <a href="index.html">
                                <img class="w-[140px]" src="https://static.vnncdn.net/v1/logo/logoVietnamNet.svg" alt="VietNamNet">
                            </a>
                        </div>
                    </div>

                    <!-- Right Side - Links and Login -->
                    <div class="flex items-center space-x-4 md:w-[200px] justify-end">

                        <!-- Search -->
                        <div class="flex items-center space-x-3 relative">
                            <!-- Desktop: Expandable search form -->
                            <div class="hidden md:block relative">
                                <form class="search-small__form  flex items-center" action="/search.html">
                                    <input id="searchInput" class="search-small__form-input w-0 h-[28px] px-0 pr-10 border border-gray-300 rounded-full text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-300 ease-in-out" name="key" type="text" placeholder="Type keywords....">
                                    <button id="searchToggleDesktop" class="search-small__form-btn absolute right-2 top-1/2 transform -translate-y-1/2 w-6 h-6 flex items-center justify-center hover:bg-gray-100 rounded-full transition-colors" type="button">
                                        <img src="https://static.vnncdn.net/v1/icon/search.png" alt="icon search" class="w-4 h-4">
                                    </button>
                                </form>
                            </div>

                            <!-- Mobile: Search icon with dropdown -->
                            <div class="md:hidden">
                                <button id="searchToggle" class="flex items-center space-x-1 text-gray-700 hover:text-[#2d67ad]">
                                    <img src="https://static.vnncdn.net/v1/icon/search.svg" class="w-6 h-6" alt="search icon">
                                </button>

                                <!-- Search Dropdown -->
                                <div id="searchDropdown" class="absolute right-0 top-full mt-2 w-80 bg-white border border-gray-200 rounded-lg shadow-lg hidden z-50">
                                    <form class="p-4" action="/search.html">
                                        <div class="relative">
                                            <input class="w-full h-10 px-4 pr-10 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" name="key" type="text" placeholder="Type keywords....">
                                            <button class="absolute right-2 top-1/2 transform -translate-y-1/2 w-6 h-6 flex items-center justify-center hover:bg-gray-100 rounded-full transition-colors" type="submit">
                                                <img src="https://static.vnncdn.net/v1/icon/search.png" alt="icon search" class="w-4 h-4">
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="hidden lg:block" id="mainBar">
            <div class="flex items-center justify-center h-14">
                <!-- Left Side - Home Icon and Navigation -->
                <div class="flex items-center space-x-6">
                    <!-- Home Icon -->
                    <a href="/" class="text-blue-600 hover:text-blue-700">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                        </svg>
                    </a>

                    <!-- Navigation Menu -->
                    <nav class="hidden lg:flex items-center space-x-2 text-sm font-medium whitespace-nowrap overflow-x-auto">
                        <a href="/category.html" class="text-gray-800 hover:text-[#447ec5] color-[#2d67ad] whitespace-nowrap text-sm font-bold">Chính trị</a>
                        <a href="/category.html" class="text-gray-800 hover:text-[#447ec5] color-[#2a2a2a] whitespace-nowrap text-sm font-bold">Thời sự</a>
                        <a href="/category.html" class="text-gray-800 hover:text-[#447ec5] color-[#2a2a2a] whitespace-nowrap text-sm font-bold">Kinh doanh</a>
                        <a href="/category.html" class="text-gray-800 hover:text-[#447ec5] color-[#2a2a2a] whitespace-nowrap text-sm font-bold">Dân tộc và Tôn giáo</a>
                        <a href="/category.html" class="text-gray-800 hover:text-[#447ec5] color-[#2a2a2a] whitespace-nowrap text-sm font-bold">Thể thao</a>
                        <a href="/category.html" class="text-gray-800 hover:text-[#447ec5] color-[#2a2a2a] whitespace-nowrap text-sm font-bold">Giáo dục</a>
                        <a href="/category.html" class="text-gray-800 hover:text-[#447ec5] color-[#2a2a2a] whitespace-nowrap text-sm font-bold">Thế giới</a>
                        <a href="/category.html" class="text-gray-800 hover:text-[#447ec5] color-[#2a2a2a] whitespace-nowrap text-sm font-bold">Đời sống</a>
                        <a href="/category.html" class="text-gray-800 hover:text-[#447ec5] color-[#2a2a2a] whitespace-nowrap text-sm font-bold">Văn hóa - Giải trí</a>
                        <a href="/category.html" class="text-gray-800 hover:text-[#447ec5] color-[#2a2a2a] whitespace-nowrap text-sm font-bold">Sức khỏe</a>
                        <a href="/category.html" class="text-gray-800 hover:text-[#447ec5] color-[#2a2a2a] whitespace-nowrap text-sm font-bold">Công nghệ</a>
                        <a href="/category.html" class="text-gray-800 hover:text-[#447ec5] color-[#2a2a2a] whitespace-nowrap text-sm font-bold">Pháp luật</a>
                        <a href="/category.html" class="text-gray-800 hover:text-[#447ec5] color-[#2a2a2a] whitespace-nowrap text-sm font-bold">Xe</a>
                        <a href="/category.html" class="text-gray-800 hover:text-[#447ec5] color-[#2a2a2a] whitespace-nowrap text-sm font-bold">Bất động sản</a>
                        <a href="/category.html" class="text-gray-800 hover:text-[#447ec5] color-[#2a2a2a] whitespace-nowrap text-sm font-bold">Du lịch</a>
                        <a href="/category.html" class="text-gray-800 hover:text-[#447ec5] color-[#2a2a2a] whitespace-nowrap text-sm font-bold">Bạn đọc</a>
                    </nav>
                    <!-- <button class="text-gray-700 hover:text-[#2d67ad]">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button> -->
                </div>

                <!-- Right Side - Mobile Menu -->
                <!-- <div class="lg:hidden">
                    <button class="text-gray-700 hover:text-[#2d67ad]">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                </div> -->
            </div>
        </div>


        <!-- Mobile Hamburger Menu -->
        <!-- <div class="mainHamburger hidden fixed inset-0 bg-white z-50" style="height: calc(-1px + 100vh);">
            <div class="mainHamburger__head p-4 border-b">
                <div class="vnn-logo flex items-center justify-between">
                    <button type="button" class="btn-hamburger-close">
                        <span class="icon-hamburger">×</span>
                    </button>
                    <a href="/en" title="Vietnamnet global">
                        <img src="https://static.vnncdn.net/v1/icon/VietnamNet_bridge.svg" alt="VietNamNet Global" class="h-8">
                    </a>
                </div>
            </div>

     
            <div class="main-search p-4 border-b">
                <form action="/tim-kiem" method="get">
                    <div class="relative">
                        <input name="q" type="text" placeholder="Type keywords...." class="w-full px-4 py-2 border border-gray-300 rounded">
                        <button class="btn-search absolute right-2 top-2" type="submit">
                            <img src="https://static.vnncdn.net/v1/icon/search.png" alt="search icon" class="w-5 h-5">
                        </button>
                    </div>
                </form>
            </div>

          
            <div class="main-menu p-4">
                <div class="hamburger__left mb-6">
                    <div class="header_submenu-main">
                        <div class="header_submenu-search mb-4">
                            <form target="_top" action="/tim-kiem">
                                <div class="relative">
                                    <input type="text" name="q" placeholder="Tìm kiếm..." class="w-full px-4 py-2 border border-gray-300 rounded">
                                    <button type="submit" class="absolute right-2 top-2">
                                        <img src="https://static.vnncdn.net/v1/icon/search.png" alt="search.png" class="w-5 h-5">
                                    </button>
                                </div>
                            </form>
                        </div>
                        <div class="header_submenu-button text-xs text-gray-600 space-y-1">
                            <ul>
                                <li>© Copyright of Vietnamnet Global.</li>
                                <li>Tel: 024 3772 7988 Fax: (024) 37722734</li>
                                <li>Email: evnn@vietnamnet.vn</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="hamburger__right">
                    <ul class="hamburger__list-right notosans-bold space-y-3 mb-4">
                        <li><a href="/talkshow" class="text-gray-700 hover:text-red-600">Talkshow</a></li>
                        <li><a href="/ho-so" class="text-gray-700 hover:text-red-600">Hồ sơ</a></li>
                        <li><a href="/anh" class="text-gray-700 hover:text-red-600">Ảnh</a></li>
                        <li><a href="/video" class="text-gray-700 hover:text-red-600">Video</a></li>
                        <li><a href="/multimedia" class="text-gray-700 hover:text-red-600">Multimedia</a></li>
                        <li class="item-podcast">
                            <a href="/podcast" title="podcast" class="flex items-center space-x-2 text-gray-700 hover:text-red-600">
                                <img src="https://static.vnncdn.net/v1/icon/podcast-icon.svg" alt="podcast icon" class="w-5 h-5">
                                <span>Podcast</span>
                            </a>
                        </li>
                    </ul>

                    <ul class="hamburger__list-right notosans-bold space-y-3 mb-4">
                        <li><a href="/tuyen-bai" class="text-gray-700 hover:text-red-600">Tuyến bài</a></li>
                        <li><a href="/su-kien" class="text-gray-700 hover:text-red-600">Sự kiện nóng</a></li>
                    </ul>

                    <ul class="hamburger__list-right space-y-3 mb-6">
                        <li><a title="Tuyển dụng" href="/tuyen-dung" class="text-gray-700 hover:text-red-600">Tuyển dụng</a></li>
                        <li><a title="Liên hệ tòa soạn" href="/thong-tin-toa-soan" class="text-gray-700 hover:text-red-600">Liên hệ tòa soạn</a></li>
                        <li><a title="Liên hệ quảng cáo" target="_blank" href="https://vads.vn/" class="text-gray-700 hover:text-red-600">Liên hệ quảng cáo</a></li>
                    </ul>

                    <a class="download-app flex items-center space-x-2 text-gray-700 hover:text-red-600" href="/download-app">
                        <img src="https://static.vnncdn.net/v1/icon/tai-app.svg" alt="download app" class="w-5 h-5">
                        <span>Download app</span>
                    </a>
                </div>
            </div>
        </div> -->
    </header>

    <!-- Main Content -->
    <main class="container max-w-[1140px] mx-auto py-6 px-5 mt-12 lg:mt-24">
