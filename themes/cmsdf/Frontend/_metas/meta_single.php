<?php
use App\Blocks\Schema\SchemaGraph;
use App\Blocks\Schema\Templates\Organization;
use App\Blocks\Schema\Templates\WebSite;
use App\Blocks\Schema\Templates\WebPage;
use App\Blocks\Schema\Templates\BreadcrumbList;
use App\Blocks\Schema\Templates\FAQPage;
use App\Blocks\Meta\MetaBlock;

global $posttype, $post;


$locale = APP_LANG.'_'.strtoupper(lang_country(APP_LANG));

$meta = new MetaBlock();
if(option('seo_follow') == 'nofollow'){
    $robots = 'noindex, nofollow';
}else{
    $robots = 'index, follow';
}
$meta
    ->title($post['seo_title'])
    ->description( $post['seo_desc'])
    ->robots($robots)
    ->canonical(base_url($_SERVER['REQUEST_URI']));
// Add basic meta tags
$meta
    ->custom('<meta name="generator" content="CMSFullForm">')
    ->custom('<meta name="language" content="' . APP_LANG . '">')
    ->custom('<meta name="author" content="' .$post['seo_title'] . '">')
    ->custom('<meta name="theme-color" content="#354BD9">');

// Add Open Graph tags
$meta
    ->og('locale', $locale)
    ->og('type', 'website')
    ->og('title',$post['seo_title'])
    ->og('description', $post['seo_desc'])
    ->og('url', base_url($_SERVER['REQUEST_URI']))
    ->og('site_name',$post['seo_title'])
    ->og('updated_time', date('c'));

// Add Twitter Card tags
$meta
    ->twitter('card', 'summary_large_image')
    ->twitter('title',$post['seo_title'])
    ->twitter('description', $post['seo_desc'])
    ->twitter('site', '@' .$post['seo_title']);

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
    'name' =>$post['seo_title'],
    'description' => $post['seo_desc']
]);

// 2. BreadcrumbList Schema
$breadcrumbSchema = BreadcrumbList::forHomepage([
    'url' => base_url(),
    'siteName' =>$post['seo_title']
]);

// 3. WebSite Schema  
$webSiteSchema = new WebSite([
    'name' =>$post['seo_title'],
    'description' => $post['seo_desc']
]);

// 4. Organization Schema
$organizationSchema = new Organization([
    'name' =>$post['seo_title'],
    'description' => $post['seo_desc'],
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
    'append' => '<link rel="preload" href="'.theme_assets('css/single.css').'" as="style" />'
]);