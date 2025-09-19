<?php

/**
 * Template Name: Download
 * Description: Download page for CMS Full Form.
 *
 * @package CMSFullForm
 */

namespace System\Libraries;

use System\Libraries\Render;
use App\Libraries\Fastlang as Flang;
use App\Blocks\Schema\Templates\WebPage;
use App\Blocks\Schema\Templates\WebSite;

Flang::load('CMS', APP_LANG);
Flang::load('General', APP_LANG);
Flang::load('Download', APP_LANG);
// cdn tailwind
Render::asset('css', '/themes/cmsfullform/Frontend/assets/css/features_styles.css', ['area' => 'frontend', 'location' => 'head']);

use App\Blocks\Meta\MetaBlock;

// Create meta tags for download page
$meta = new MetaBlock();

$meta
    ->title(Flang::_e('page_title'))
    ->description(Flang::_e('meta_description'))
    ->keywords(Flang::_e('meta_keywords'))
    ->robots('index, follow')
    ->canonical(base_url('download'));

// Add basic meta tags
$meta
    ->custom('<meta name="generator" content="CMSFullForm">')
    ->custom('<meta name="language" content="' . APP_LANG . '">')
    ->custom('<meta name="author" content="CMS Full Form">')
    ->custom('<meta name="theme-color" content="#354BD9">');

// Add Open Graph tags
$locale = APP_LANG === 'en' ? 'en_US' : 'vi_VN';
$meta
    ->og('locale', $locale)
    ->og('type', 'website')
    ->og('title', Flang::_e('og_title'))
    ->og('description', Flang::_e('og_description'))
    ->og('url', base_url('download'))
    ->og('site_name', 'CMS Full Form')
    ->og('updated_time', date('c'));

// Add Twitter Card tags
$meta
    ->twitter('card', 'summary_large_image')
    ->twitter('title', Flang::_e('twitter_title'))
    ->twitter('description', Flang::_e('twitter_description'))
    ->twitter('site', '@CMSFullForm');

// Add favicon if available
if (option('favicon')) {
    $logoUrl = theme_assets(option('favicon')['path'] ?? '/images/logo/logo-icon.webp');
    $meta
        ->og('image', $logoUrl)
        ->twitter('image', $logoUrl)
        ->custom('<link rel="icon" href="' . $logoUrl . '" sizes="32x32" />')
        ->custom('<link rel="apple-touch-icon" href="' . $logoUrl . '" />');
}

// Initialize WebPage schema for download page
$downloadSchema = new WebPage([
    '@id'             => base_url('download') . '#webpage',
    'name'            => Flang::_e('schema_name'),
    'description'     => Flang::_e('schema_description'),
    'url'             => base_url('download'),
    'inLanguage'      => 'en-US',
    'isPartOf'        => [
        '@type' => 'WebSite',
        '@id'   => base_url() . '#website',
        'name'  => option('site_title'),
        'url'   => base_url()
    ],
    'breadcrumb'      => [
        '@type'           => 'BreadcrumbList',
        'itemListElement' => [
            [
                '@type'    => 'ListItem',
                'position' => 1,
                'name'     => Flang::_e('breadcrumb_home'),
                'item'     => base_url()
            ],
            [
                '@type'    => 'ListItem',
                'position' => 2,
                'name'     => Flang::_e('breadcrumb_download'),
                'item'     => base_url('download')
            ]
        ]
    ]
]);

// Render meta tags and schema in header
get_header([
    'meta' => $meta->render(),
    'schema' => $downloadSchema->render(),
    'layout' => 'download'
]);
?>
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        theme: {
            extend: {
                animation: {
                    'blob': 'blob 7s infinite',
                },
                keyframes: {
                    blob: {
                        '0%': {
                            transform: 'translate(0px, 0px) scale(1)',
                        },
                        '33%': {
                            transform: 'translate(30px, -50px) scale(1.1)',
                        },
                        '66%': {
                            transform: 'translate(-20px, 20px) scale(0.9)',
                        },
                        '100%': {
                            transform: 'translate(0px, 0px) scale(1)',
                        },
                    }
                }
            }
        }
    }
</script>
<style>
    .animation-delay-2000 {
        animation-delay: 2s;
    }

    .animation-delay-4000 {
        animation-delay: 4s;
    }

    .animation-delay-6000 {
        animation-delay: 6s;
    }
</style>

<main class="flex-1">
    <section class="w-full py-20 md:py-28 lg:py-32 relative overflow-hidden">
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute -top-40 -right-40 w-80 h-80 bg-gradient-to-br from-purple-400 to-pink-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob"></div>
            <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-gradient-to-br from-yellow-400 to-orange-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-2000"></div>
            <div class="absolute top-40 left-40 w-80 h-80 bg-gradient-to-br from-blue-400 to-cyan-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-4000"></div>
            <div class="absolute bottom-40 right-40 w-60 h-60 bg-gradient-to-br from-green-400 to-emerald-400 rounded-full mix-blend-multiply filter blur-xl opacity-60 animate-blob animation-delay-6000"></div>
        </div>
        <div class="container px-4 md:px-6 relative z-10">
            <div class="flex flex-col items-center justify-center space-y-6 text-center">
                <div class="space-y-4">
                    <div class="inline-flex items-center rounded-full border transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 bg-secondary hover:bg-secondary/80 bg-gradient-to-r from-purple-100 to-pink-100 text-purple-800 border-purple-200 px-4 py-2 text-sm font-semibold">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-rocket mr-2 h-4 w-4">
                            <path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"></path>
                            <path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"></path>
                            <path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5 0 5 0"></path>
                            <path d="M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5"></path>
                        </svg>
                        <?php echo Flang::_e('hero_badge'); ?>
                    </div>
                    <h1 class="text-4xl font-bold tracking-tighter sm:text-5xl md:text-6xl bg-clip-text text-transparent bg-gradient-to-r from-purple-600 via-pink-600 to-blue-600 pb-2 leading-tight md:leading-[1.2]"><?php echo Flang::_e('hero_title'); ?></h1>
                    <p class="mx-auto max-w-[700px] md:text-xl leading-relaxed"><?php echo Flang::_e('hero_description'); ?></p>
                </div>
            </div>
            <div class="mx-auto grid max-w-7xl items-stretch gap-8 py-16 sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
                <!-- PhpFast Framework Card -->
                <div class="rounded-lg text-card-foreground flex flex-col h-full transition-all duration-500 hover:shadow-2xl hover:-translate-y-3 bg-white/90 backdrop-blur-sm border-0 shadow-lg bg-gradient-to-br from-emerald-50 to-teal-50">
                    <div class="flex flex-col space-y-1.5 p-6 pb-6 relative">
                        <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br shadow-lg from-emerald-400 to-teal-600">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-code h-8 w-8 text-white">
                                <polyline points="16 18 22 12 16 6"></polyline>
                                <polyline points="8 6 2 12 8 18"></polyline>
                            </svg>
                        </div>
                        <div class="tracking-tight text-2xl font-bold text-slate-800 mb-2"><?php echo Flang::_e('framework_title'); ?></div>
                        <div class="text-sm text-slate-600 min-h-[80px] leading-relaxed"><?php echo Flang::_e('framework_description'); ?></div>
                    </div>
                    <div class="p-6 flex-1 py-6">
                        <ul class="space-y-4">
                            <li class="flex items-start">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-check mr-3 h-5 w-5 flex-shrink-0 mt-0.5 text-emerald-500">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <path d="m9 12 2 2 4-4"></path>
                                </svg>
                                <span class="text-sm text-slate-700 leading-relaxed"><?php echo Flang::_e('framework_feature_1'); ?></span>
                            </li>
                            <li class="flex items-start">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-check mr-3 h-5 w-5 flex-shrink-0 mt-0.5 text-emerald-500">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <path d="m9 12 2 2 4-4"></path>
                                </svg>
                                <span class="text-sm text-slate-700 leading-relaxed"><?php echo Flang::_e('framework_feature_2'); ?></span>
                            </li>
                            <li class="flex items-start">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-check mr-3 h-5 w-5 flex-shrink-0 mt-0.5 text-emerald-500">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <path d="m9 12 2 2 4-4"></path>
                                </svg>
                                <span class="text-sm text-slate-700 leading-relaxed"><?php echo Flang::_e('framework_feature_3'); ?></span>
                            </li>
                            <li class="flex items-start">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-check mr-3 h-5 w-5 flex-shrink-0 mt-0.5 text-emerald-500">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <path d="m9 12 2 2 4-4"></path>
                                </svg>
                                <span class="text-sm text-slate-700 leading-relaxed"><?php echo Flang::_e('framework_feature_4'); ?></span>
                            </li>
                        </ul>
                    </div>
                    <div class="p-6 flex flex-col items-start space-y-6 pt-6 mt-auto">
                        <div class="w-full text-sm text-slate-600 flex justify-between bg-white/50 rounded-lg p-3">
                            <span class="font-semibold"><?php echo Flang::_e('version_label'); ?>: <span class="text-slate-800 "><?php echo Flang::_e('framework_version'); ?></span></span>
                            <span class="font-semibold"><?php echo Flang::_e('size_label'); ?>: <span class="text-slate-800"><?php echo Flang::_e('framework_size'); ?></span></span>
                        </div>
                        <button class="inline-flex items-center justify-center gap-2 whitespace-nowrap ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 bg-primary hover:bg-primary/90 h-11 rounded-md px-8 w-full text-base font-bold transition-all duration-300 shadow-lg transform hover:scale-105 py-6 bg-gradient-to-r from-emerald-400 to-teal-600 text-white border-0 hover:shadow-2xl">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-download mr-2 h-5 w-5">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="7 10 12 15 17 10"></polyline>
                                <line x1="12" x2="12" y1="15" y2="3"></line>
                            </svg>
                            <?php echo Flang::_e('framework_button'); ?>
                        </button>
                    </div>
                </div>

                <!-- CMS Full Data Card (Recommended) -->
                <div class="rounded-lg text-card-foreground flex flex-col h-full transition-all duration-500 hover:shadow-2xl hover:-translate-y-3 bg-white/90 backdrop-blur-sm border-0 ring-4 ring-purple-500/30 shadow-2xl scale-105 bg-gradient-to-br from-white via-purple-50/50 to-pink-50/50">
                    <div class="flex flex-col space-y-1.5 p-6 pb-6 relative">
                        <div class="inline-flex items-center rounded-full border transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 border-transparent bg-primary hover:bg-primary/80 absolute -top-3 -right-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white px-4 py-2 text-sm font-bold shadow-lg animate-pulse">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-star mr-1 h-3 w-3">
                                <path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"></path>
                            </svg>
                            <?php echo Flang::_e('full_badge'); ?>
                        </div>
                        <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br shadow-lg from-purple-500 to-pink-600">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-star h-8 w-8 text-white">
                                <path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"></path>
                            </svg>
                        </div>
                        <div class="tracking-tight text-2xl font-bold text-slate-800 mb-2"><?php echo Flang::_e('full_title'); ?></div>
                        <div class="text-sm text-slate-600 min-h-[80px] leading-relaxed"><?php echo Flang::_e('full_description'); ?></div>
                    </div>
                    <div class="p-6 flex-1 py-6">
                        <ul class="space-y-4">
                            <li class="flex items-start">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-check mr-3 h-5 w-5 flex-shrink-0 mt-0.5 text-purple-500">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <path d="m9 12 2 2 4-4"></path>
                                </svg>
                                <span class="text-sm text-slate-700 leading-relaxed"><?php echo Flang::_e('full_feature_1'); ?></span>
                            </li>
                            <li class="flex items-start">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-check mr-3 h-5 w-5 flex-shrink-0 mt-0.5 text-purple-500">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <path d="m9 12 2 2 4-4"></path>
                                </svg>
                                <span class="text-sm text-slate-700 leading-relaxed"><?php echo Flang::_e('full_feature_2'); ?></span>
                            </li>
                            <li class="flex items-start">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-check mr-3 h-5 w-5 flex-shrink-0 mt-0.5 text-purple-500">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <path d="m9 12 2 2 4-4"></path>
                                </svg>
                                <span class="text-sm text-slate-700 leading-relaxed"><?php echo Flang::_e('full_feature_3'); ?></span>
                            </li>
                            <li class="flex items-start">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-check mr-3 h-5 w-5 flex-shrink-0 mt-0.5 text-purple-500">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <path d="m9 12 2 2 4-4"></path>
                                </svg>
                                <span class="text-sm text-slate-700 leading-relaxed"><?php echo Flang::_e('full_feature_4'); ?></span>
                            </li>
                        </ul>
                    </div>
                    <div class="p-6 flex flex-col items-start space-y-6 pt-6 mt-auto">
                        <div class="w-full text-sm text-slate-600 flex justify-between bg-white/50 rounded-lg p-3">
                            <span class="font-semibold"><?php echo Flang::_e('version_label'); ?>: <span class="text-slate-800"><?php echo Flang::_e('full_version'); ?></span></span>
                            <span class="font-semibold"><?php echo Flang::_e('size_label'); ?>: <span class="text-slate-800"><?php echo Flang::_e('full_size'); ?></span></span>
                        </div>
                        <button class="inline-flex items-center justify-center gap-2 whitespace-nowrap ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 bg-primary hover:bg-primary/90 h-11 rounded-md px-8 w-full text-base font-bold transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105 py-6 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white border-0">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-download mr-2 h-5 w-5">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="7 10 12 15 17 10"></polyline>
                                <line x1="12" x2="12" y1="15" y2="3"></line>
                            </svg>
                            <?php echo Flang::_e('full_button'); ?>
                        </button>
                    </div>
                </div>

                <!-- CMS Blank Card -->
                <div class="rounded-lg text-card-foreground flex flex-col h-full transition-all duration-500 hover:shadow-2xl hover:-translate-y-3 bg-white/90 backdrop-blur-sm border-0 shadow-lg bg-gradient-to-br from-blue-50 to-indigo-50">
                    <div class="flex flex-col space-y-1.5 p-6 pb-6 relative">
                        <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br shadow-lg from-blue-400 to-indigo-600">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-zap h-8 w-8 text-white">
                                <path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"></path>
                            </svg>
                        </div>
                        <div class="tracking-tight text-2xl font-bold text-slate-800 mb-2"><?php echo Flang::_e('blank_title'); ?></div>
                        <div class="text-sm text-slate-600 min-h-[80px] leading-relaxed"><?php echo Flang::_e('blank_description'); ?></div>
                    </div>
                    <div class="p-6 flex-1 py-6">
                        <ul class="space-y-4">
                            <li class="flex items-start">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-check mr-3 h-5 w-5 flex-shrink-0 mt-0.5 text-emerald-500">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <path d="m9 12 2 2 4-4"></path>
                                </svg>
                                <span class="text-sm text-slate-700 leading-relaxed"><?php echo Flang::_e('blank_feature_1'); ?></span>
                            </li>
                            <li class="flex items-start">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-check mr-3 h-5 w-5 flex-shrink-0 mt-0.5 text-emerald-500">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <path d="m9 12 2 2 4-4"></path>
                                </svg>
                                <span class="text-sm text-slate-700 leading-relaxed"><?php echo Flang::_e('blank_feature_2'); ?></span>
                            </li>
                            <li class="flex items-start">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-check mr-3 h-5 w-5 flex-shrink-0 mt-0.5 text-emerald-500">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <path d="m9 12 2 2 4-4"></path>
                                </svg>
                                <span class="text-sm text-slate-700 leading-relaxed"><?php echo Flang::_e('blank_feature_3'); ?></span>
                            </li>
                            <li class="flex items-start">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-check mr-3 h-5 w-5 flex-shrink-0 mt-0.5 text-emerald-500">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <path d="m9 12 2 2 4-4"></path>
                                </svg>
                                <span class="text-sm text-slate-700 leading-relaxed"><?php echo Flang::_e('blank_feature_4'); ?></span>
                            </li>
                        </ul>
                    </div>
                    <div class="p-6 flex flex-col items-start space-y-6 pt-6 mt-auto">
                        <div class="w-full text-sm text-slate-600 flex justify-between bg-white/50 rounded-lg p-3">
                            <span class="font-semibold"><?php echo Flang::_e('version_label'); ?>: <span class="text-slate-800"><?php echo Flang::_e('blank_version'); ?></span></span>
                            <span class="font-semibold"><?php echo Flang::_e('size_label'); ?>: <span class="text-slate-800"><?php echo Flang::_e('blank_size'); ?></span></span>
                        </div>
                        <button class="inline-flex items-center justify-center gap-2 whitespace-nowrap ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 bg-primary hover:bg-primary/90 h-11 rounded-md px-8 w-full text-base font-bold transition-all duration-300 shadow-lg transform hover:scale-105 py-6 bg-gradient-to-r from-blue-400 to-indigo-600 text-white border-0 hover:shadow-2xl">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-download mr-2 h-5 w-5">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="7 10 12 15 17 10"></polyline>
                                <line x1="12" x2="12" y1="15" y2="3"></line>
                            </svg>
                            <?php echo Flang::_e('blank_button'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="w-full py-20 md:py-28 lg:py-32 relative overflow-hidden">
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute top-20 left-20 w-72 h-72 bg-gradient-to-br from-cyan-300 to-blue-400 rounded-full mix-blend-multiply filter blur-xl opacity-60 animate-blob"></div>
            <div class="absolute bottom-20 right-20 w-72 h-72 bg-gradient-to-br from-purple-300 to-pink-400 rounded-full mix-blend-multiply filter blur-xl opacity-60 animate-blob animation-delay-2000"></div>
            <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-gradient-to-br from-yellow-200 to-orange-300 rounded-full mix-blend-multiply filter blur-xl opacity-40 animate-blob animation-delay-4000"></div>
        </div>
        <div class="container px-4 md:px-6 relative z-10">
            <div class="flex flex-col items-center justify-center space-y-6 text-center mb-16">
                <div class="inline-flex items-center rounded-full border transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 bg-secondary hover:bg-secondary/80 bg-gradient-to-r from-cyan-100 to-blue-100 text-cyan-800 border-cyan-200 px-4 py-2 text-sm font-semibold">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-sparkles mr-2 h-4 w-4">
                        <path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"></path>
                        <path d="M20 3v4"></path>
                        <path d="M22 5h-4"></path>
                        <path d="M4 17v2"></path>
                        <path d="M5 18H3"></path>
                    </svg>
                    <?php echo Flang::_e('ecosystem_badge'); ?>
                </div>
                <h2 class="text-3xl font-bold tracking-tighter sm:text-4xl md:text-5xl bg-clip-text text-transparent bg-gradient-to-r from-cyan-600 via-blue-600 to-purple-600 leading-tight md:leading-[1.3]"><?php echo Flang::_e('ecosystem_title'); ?></h2>
                <p class="mx-auto max-w-[700px] md:text-xl leading-relaxed"><?php echo Flang::_e('ecosystem_description'); ?></p>
            </div>
            <div class="mx-auto grid grid-cols-1 gap-8 md:grid-cols-3 lg:max-w-6xl">
                <a class="group block" href="/library/themes/">
                    <div class="rounded-lg text-card-foreground h-full overflow-hidden transition-all duration-500 group-hover:shadow-2xl group-hover:-translate-y-3 border-0 shadow-lg bg-gradient-to-br from-blue-50 to-cyan-50 bg-white/90 backdrop-blur-sm">
                        <div class="flex flex-col space-y-1.5 p-6 pb-6">
                            <div class="mb-6 flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br shadow-lg transform group-hover:scale-110 transition-transform duration-300 from-blue-500 to-cyan-500">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-palette h-8 w-8 text-white">
                                    <circle cx="13.5" cy="6.5" r=".5" fill="currentColor"></circle>
                                    <circle cx="17.5" cy="10.5" r=".5" fill="currentColor"></circle>
                                    <circle cx="8.5" cy="7.5" r=".5" fill="currentColor"></circle>
                                    <circle cx="6.5" cy="12.5" r=".5" fill="currentColor"></circle>
                                    <path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2z"></path>
                                </svg>
                            </div>
                            <div class="tracking-tight text-2xl font-bold text-slate-800 group-hover:text-slate-700 transition-all duration-300"><?php echo Flang::_e('ecosystem_themes_title'); ?></div>
                        </div>
                        <div class="p-6 pt-0 pb-8">
                            <p class="text-slate-600 leading-relaxed mb-6"><?php echo Flang::_e('ecosystem_themes_description'); ?></p>
                            <div class="font-bold text-lg flex items-center transition-all duration-300 group-hover:scale-105 bg-gradient-to-r from-blue-500 to-cyan-500 bg-clip-text text-transparent">
                                <?php echo Flang::_e('ecosystem_themes_link'); ?>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-right ml-2 h-5 w-5 transition-transform group-hover:translate-x-2 text-current">
                                    <path d="M5 12h14"></path>
                                    <path d="m12 5 7 7-7 7"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </a>
                <a class="group block" href="/library/plugins/">
                    <div class="rounded-lg text-card-foreground h-full overflow-hidden transition-all duration-500 group-hover:shadow-2xl group-hover:-translate-y-3 border-0 shadow-lg bg-gradient-to-br from-pink-50 to-rose-50 bg-white/90 backdrop-blur-sm">
                        <div class="flex flex-col space-y-1.5 p-6 pb-6">
                            <div class="mb-6 flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br shadow-lg transform group-hover:scale-110 transition-transform duration-300 from-pink-500 to-rose-500">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-puzzle h-8 w-8 text-white">
                                    <path d="M19.439 7.85c-.049.322.059.648.289.878l1.568 1.568c.47.47.706 1.087.706 1.704s-.235 1.233-.706 1.704l-1.611 1.611a.98.98 0 0 1-.837.276c-.47-.07-.802-.48-.968-.925a2.501 2.501 0 1 0-3.214 3.214c.446.166.855.497.925.968a.979.979 0 0 1-.276.837l-1.61 1.61a2.404 2.404 0 0 1-1.705.707 2.402 2.402 0 0 1-1.704-.706l-1.568-1.568a1.026 1.026 0 0 0-.877-.29c-.493.074-.84.504-1.02.968a2.5 2.5 0 1 1-3.237-3.237c.464-.18.894-.527.967-1.02a1.026 1.026 0 0 0-.289-.877l-1.568-1.568A2.402 2.402 0 0 1 1.998 12c0-.617.236-1.234.706-1.704L4.23 8.77c.24-.24.581-.353.917-.303.515.077.877.528 1.073 1.01a2.5 2.5 0 1 0 3.259-3.259c-.482-.196-.933-.558-1.01-1.073-.05-.336.062-.676.303-.917l1.525-1.525A2.402 2.402 0 0 1 12 1.998c.617 0 1.234.236 1.704.706l1.568 1.568c.23.23.556.338.877.29.493-.074.84-.504 1.02-.968a2.5 2.5 0 1 1 3.237 3.237c-.464.18-.894.527-.967 1.02Z"></path>
                                </svg>
                            </div>
                            <div class="tracking-tight text-2xl font-bold text-slate-800 group-hover:text-slate-700 transition-all duration-300"><?php echo Flang::_e('ecosystem_plugins_title'); ?></div>
                        </div>
                        <div class="p-6 pt-0 pb-8">
                            <p class="text-slate-600 leading-relaxed mb-6"><?php echo Flang::_e('ecosystem_plugins_description'); ?></p>
                            <div class="font-bold text-lg flex items-center transition-all duration-300 group-hover:scale-105 bg-gradient-to-r from-pink-500 to-rose-500 bg-clip-text text-transparent">
                                <?php echo Flang::_e('ecosystem_plugins_link'); ?>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-right ml-2 h-5 w-5 transition-transform group-hover:translate-x-2 text-current">
                                    <path d="M5 12h14"></path>
                                    <path d="m12 5 7 7-7 7"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </a>
                <a class="group block" href="#docs">
                    <div class="rounded-lg text-card-foreground h-full overflow-hidden transition-all duration-500 group-hover:shadow-2xl group-hover:-translate-y-3 border-0 shadow-lg bg-gradient-to-br from-emerald-50 to-teal-50 bg-white/90 backdrop-blur-sm">
                        <div class="flex flex-col space-y-1.5 p-6 pb-6">
                            <div class="mb-6 flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br shadow-lg transform group-hover:scale-110 transition-transform duration-300 from-emerald-500 to-teal-500">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-book-open h-8 w-8 text-white">
                                    <path d="M12 7v14"></path>
                                    <path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"></path>
                                </svg>
                            </div>
                            <div class="tracking-tight text-2xl font-bold text-slate-800 group-hover:text-slate-700 transition-all duration-300"><?php echo Flang::_e('ecosystem_docs_title'); ?></div>
                        </div>
                        <div class="p-6 pt-0 pb-8">
                            <p class="text-sm text-slate-600 leading-relaxed mb-6"><?php echo Flang::_e('ecosystem_docs_description'); ?></p>
                            <div class="font-bold text-lg flex items-center transition-all duration-300 group-hover:scale-105 bg-gradient-to-r from-emerald-500 to-teal-500 bg-clip-text text-transparent">
                                <?php echo Flang::_e('ecosystem_docs_link'); ?>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-right ml-2 h-5 w-5 transition-transform group-hover:translate-x-2 text-current">
                                    <path d="M5 12h14"></path>
                                    <path d="m12 5 7 7-7 7"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </section>
</main>

<?php get_footer(); ?>
