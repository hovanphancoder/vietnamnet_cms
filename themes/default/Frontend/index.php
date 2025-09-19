<?php

/**
 * Template Name: Trang Chủ
 * Description: Trang chủ của website truyện tranh.
 *
 * @package FastComic
 */

namespace System\Libraries;

use System\Libraries\Render;
use App\Libraries\Fastlang as Flang;
use App\Blocks\Schema\SchemaGraph;
use App\Blocks\Schema\Templates\Organization;
use App\Blocks\Schema\Templates\WebSite;
use App\Blocks\Schema\Templates\WebPage;
use App\Blocks\Schema\Templates\BreadcrumbList;
use App\Blocks\Schema\Templates\FAQPage;


Flang::load('CMS', APP_LANG);
Flang::load('Home', APP_LANG);
Render::asset('css', 'css/home-index.min.css', ['area' => 'frontend', 'location' => 'head']);
Render::asset('js', 'js/home-index.js', ['area' => 'frontend', 'location' => 'footer']);
// Create meta tags for homepage
// Include Meta blocks
use App\Blocks\Meta\MetaBlock;

// Create meta tags for homepage directly from MetaBlock
$meta = new MetaBlock();

$meta
    ->title(option('site_title', APP_LANG))
    ->description(option('site_desc', APP_LANG))
    ->robots('index, follow')
    ->canonical(base_url());

// Add basic meta tags
$meta
    ->custom('<meta name="generator" content="CMSFullForm">')
    ->custom('<meta name="language" content="' . APP_LANG . '">')
    ->custom('<meta name="author" content="' . option('site_title', APP_LANG) . '">')
    ->custom('<meta name="theme-color" content="#354BD9">');

// Add Open Graph tags

$locale = APP_LANG === 'en' ? 'en_US' : 'vi_VN';
$meta
    ->og('locale', $locale)
    ->og('type', 'website')
    ->og('title', option('site_title', APP_LANG))
    ->og('description', option('site_desc', APP_LANG))
    ->og('url', base_url())
    ->og('site_name', option('site_title', APP_LANG))
    ->og('updated_time', date('c'));

// Add Twitter Card tags
$meta
    ->twitter('card', 'summary_large_image')
    ->twitter('title', option('site_title', APP_LANG))
    ->twitter('description', option('site_desc', APP_LANG))
    ->twitter('site', '@' . option('site_title', APP_LANG));

// Add favicon if available
if (option('site_logo')) {
    $logoUrl = theme_assets(option('favicon')['path'] ?? '/images/logo-icon.webp');
    $meta
        ->og('image', $logoUrl)
        ->twitter('image', $logoUrl)
        ->custom('<link rel="icon" href="' . $logoUrl . '" sizes="32x32" />')
        ->custom('<link rel="apple-touch-icon" href="' . $logoUrl . '" />');
}

// Sử dụng get_posts helper để lấy themes và plugins theo download count

// list of themes (top download)
$theme = get_posts([
    'posttype' => 'themes',
    'perPage' => 8,
    'sort' => ['download', 'DESC'],
    'filters' => [
        'status' => 'active'
    ],
]);

// list of plugins (top download)  
$plugin = get_posts([
    'posttype' => 'plugins',
    'perPage' => 8,
    'sort' => ['download', 'DESC'],
    'filters' => [
        ['status', '=', 'active']
    ]
]);

$blogs = get_posts([
    'posttype' => 'blogs',
    'perPage' => 6,
], APP_LANG);

$threeinone = [
    'tabs' => [
        [
            'themes' => $theme['data'],
        ],
        [
            'plugins' => $plugin['data'],
        ],
    ],
];


$project = get_posts([
    'posttype' => 'project',
    'perPage' => 8,
], APP_LANG);

$reviews_vi = get_posts([
    'posttype' => 'review',
    'perPage' => 6,
], APP_LANG);

// Tạo Schema Graph theo chuẩn RankMath
$schemaGraph = new SchemaGraph(base_url());

// 1. WebPage Schema
$webPageSchema = new WebPage([
    'url' => base_url(),
    'name' => option('site_title', APP_LANG),
    'description' => option('site_desc', APP_LANG)
]);

// 2. BreadcrumbList Schema
$breadcrumbSchema = BreadcrumbList::forHomepage([
    'url' => base_url(),
    'siteName' => option('site_title', APP_LANG)
]);

// 3. WebSite Schema  
$webSiteSchema = new WebSite([
    'name' => option('site_title', APP_LANG),
    'description' => option('site_desc', APP_LANG)
]);

// 4. Organization Schema
$organizationSchema = new Organization([
    'name' => option('site_title', APP_LANG),
    'description' => option('site_desc', APP_LANG),
    'logo' => option('site_logo', APP_LANG),
    'email' => option('site_email'),
    'telephone' => option('site_phone'),
    'social' => option('social', APP_LANG)
]);

// 5. Thêm các schema vào graph
$schemaGraph
    ->addItem($webPageSchema)
    ->addItem($breadcrumbSchema)
    ->addItem($webSiteSchema)
    ->addItem($organizationSchema);

// 6. Tạo primary image nếu có
$logoUrl = option('site_logo', APP_LANG);
if (!empty($logoUrl)) {
    $imageSchema = [
        '@type' => 'ImageObject',
        '@id' => rtrim(base_url(), '/') . '/#/schema/logo/image/',
        'url' => is_string($logoUrl) ? $logoUrl : base_url('assets/images/logo.png'),
        'width' => 600,
        'height' => 60,
        'inLanguage' => option('site_lang') ?? 'en-US'
    ];
    $schemaGraph->addItem($imageSchema);
}

// Render meta tags and schema in header
get_header([
    'meta' => $meta->render(),
    'schema' => $schemaGraph->render(),
    'append' => '<link rel="preload" href="/themes/'.APP_THEME_NAME.'/Frontend/Assets/css/home-index.min.css" as="style" type="text/css" media="all" />'
]);
?>

<?php get_template('sections/home_index/home_index_hero'); ?>

<?php get_template('sections/home_index/home_index_solution'); ?>

<?php get_template('sections/home_index/home_index_why'); ?>

<?php get_template('sections/home_index/home_index_perfomance'); ?>

<?php get_template('sections/home_index/home_index_manager'); ?>

<?php get_template('sections/home_index/home_index_file'); ?>

<?php get_template('sections/home_index/home_index_multipost'); ?>

<?php get_template('sections/home_index/home_index_mutilang'); ?>

<?php get_template('sections/home_index/home_index_webmodern'); ?>

<?php get_template('sections/home_index/home_index_fourstep'); ?>

<?php get_template('sections/home_index/home_index_dev'); ?>

<?php get_template('sections/home_index/home_index_move'); ?>

<?php get_template('sections/home_index/home_index_service'); ?>

<?php get_template('sections/home_index/home_index_project', ['projects' => $project]); ?>

<?php get_template('sections/home_index/home_index_themesplugs', $threeinone); ?>

<?php get_template('sections/home_index/home_index_partner'); ?>

<?php get_template('sections/home_index/home_index_review', ['reviews' => $reviews_vi['data']]); ?>

<?php get_template('sections/home_index/home_index_blogs', ['blogs' => $blogs['data']]); ?>

<?php get_template('sections/home_index/home_index_faq'); ?>

<?php get_template('sections/cta'); ?>

<?php get_footer(); ?>
