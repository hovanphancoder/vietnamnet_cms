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

if(option('seo_follow') == 'nofollow'){
    $robots = 'noindex, nofollow';
}else{
    $robots = 'index, follow';
}

$meta
    ->title(option('site_title', APP_LANG))
    ->description(option('site_desc', APP_LANG))
    ->robots($robots)
    ->canonical(base_url());
// Add basic meta tags
$meta
    ->custom('<meta name="generator" content="CMSFullForm">')
    ->custom('<meta name="language" content="' . APP_LANG . '">')
    ->custom('<meta name="author" content="' . option('site_title', APP_LANG) . '">')
    ->custom('<meta name="theme-color" content="#354BD9">');

// Add Open Graph tags
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


if (option('favicon')) {
    $favicon_data = option('favicon');
    
    // Xử lý nếu favicon là JSON string
    if (is_string($favicon_data)) {
        $favicon_json = json_decode($favicon_data, true);
        $favicon_path = $favicon_json['path'] ?? null;
    } else {
        $favicon_path = $favicon_data->path ?? null;
    }
    
    $base_url = str_replace('/en', '', base_url());
    $logoUrl = rtrim($base_url.'/uploads/'.$favicon_path, '/');
    $meta
        ->og('image', $logoUrl)
        ->twitter('image', $logoUrl)
        ->custom('<link rel="icon" href="' . $logoUrl . '" sizes="32x32" />')
        ->custom('<link rel="apple-touch-icon" href="' . $logoUrl . '" />');
}

// Create Schema Graph by format RankMath
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
    'append' => ''
]);
