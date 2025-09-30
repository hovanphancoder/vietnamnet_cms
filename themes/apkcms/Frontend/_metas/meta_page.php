<?php
use App\Blocks\Schema\SchemaGraph;
use App\Blocks\Schema\Templates\Organization;
use App\Blocks\Schema\Templates\WebSite;
use App\Blocks\Schema\Templates\WebPage;
use App\Blocks\Schema\Templates\BreadcrumbList;
use App\Blocks\Schema\Templates\FAQPage;
// Create meta tags for homepage
// Include Meta blocks
use App\Blocks\Meta\MetaBlock;
// Create meta tags for homepage directly from MetaBlock
$meta = new MetaBlock();

// Các biến từ mảng đã truyền vào có thể sử dụng:
// $locale, $page_title, $page_description, $page_type, $posts_count, $current_lang, $site_name, $custom_data

// $current_page = get_current_page();
// $posttype = $current_page['page_slug'];
$slug = S_GET('slug');
$page = get_post([
    'slug' => $slug,
    'posttype' => 'pages',
    'active' => true,
    'lang' => APP_LANG ,
    'columns' => ['*']
]);
if(option('seo_follow') == 'nofollow'){
    $robots = 'noindex, nofollow';
}else{
    $robots = 'index, follow';
}



// Sử dụng dữ liệu từ mảng đã truyền vào
$final_title = $page_title ?? option('site_title', APP_LANG);
$final_description = $page_description ?? option('site_description', APP_LANG);

// Check if page exists, if not use default values
if (!$page) {
    $page = [
        'seo_title' => $final_title,
        'seo_desc' => $final_description,
        'description' => $final_description
    ];
} else {
    $page['seo_title'] = $page['seo_title'] ?? $page['title'] ?? $final_title;
    $page['seo_desc'] = $page['seo_desc'] ?? $page['description'] ?? $final_description;
}

$meta
    ->title($page['seo_title'])
    ->description($page['seo_desc'])
    ->robots($robots)
    ->canonical(base_url($_SERVER['REQUEST_URI']));
// Add basic meta tags
$meta
    ->custom('<meta name="generator" content="CMSFullForm">')
    ->custom('<meta name="language" content="' . APP_LANG . '">')
    ->custom('<meta name="author" content="' . $page['seo_title'] . '">')
    ->custom('<meta name="theme-color" content="#354BD9">');

// Add Open Graph tags
$meta
    ->og('locale', APP_LANG . '_' . strtoupper(APP_LANG))
    ->og('type', 'website')
    ->og('title', $page['seo_title'])
    ->og('description', $page['seo_desc'])
    ->og('url', base_url($_SERVER['REQUEST_URI']))
    ->og('site_name', $page['seo_title'])
    ->og('updated_time', date('c'));

// Add Twitter Card tags
$meta
    ->twitter('card', 'summary_large_image')
    ->twitter('title', $page['seo_title'])
    ->twitter('description', $page['seo_desc'])
    ->twitter('site', '@' . $page['seo_title']);

// Add favicon if available
if (option('site_logo')) {
    $logoUrl = theme_assets(option('favicon')['path'] ?? '/images/logo-icon.webp');
    $meta
        ->og('image', $logoUrl)
        ->twitter('image', $logoUrl)
        ->custom('<link rel="icon" href="' . $logoUrl . '" sizes="32x32" />')
        ->custom('<link rel="apple-touch-icon" href="' . $logoUrl . '" />');
}

// Create Schema Graph by format RankMath
$schemaGraph = new SchemaGraph(base_url($_SERVER['REQUEST_URI']));

// 1. WebPage Schema
$webPageSchema = new WebPage([
    'url' => base_url($_SERVER['REQUEST_URI']),
    'name' => $page['seo_title'],
    'description' => $page['seo_desc']
]);

// 2. BreadcrumbList Schema
$breadcrumbSchema = BreadcrumbList::forHomepage([
    'url' => base_url($_SERVER['REQUEST_URI']),
    'siteName' => $page['seo_title']
]);

// 3. WebSite Schema  
$webSiteSchema = new WebSite([
    'name' => $page['seo_title'],
    'description' => $page['seo_desc']
]);

// 4. Organization Schema
$organizationSchema = new Organization([
    'name' => $page['seo_title'],
    'description' => $page['seo_desc'],
    'logo' => option('site_logo', APP_LANG),
    'email' => option('site_email'),
    'telephone' => option('site_phone'),
    'social' => option('social', APP_LANG)
]);

// 5. Add schema to graph
$schemaGraph
    ->addItem($webPageSchema)
    ->addItem($breadcrumbSchema)
    ->addItem($webSiteSchema)
    ->addItem($organizationSchema);

// 6. Create primary image if available
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
    'append' => '<link rel="stylesheet" href="/themes/'.APP_THEME_NAME.'/Frontend/Assets/css/styles.min.css" as="style" type="text/css" media="all" />'
]);
