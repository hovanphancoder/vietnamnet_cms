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
use App\Blocks\Schema\Templates\WebPage;
use App\Blocks\Schema\Templates\Organization;


Flang::load('CMS', APP_LANG);
Flang::load('auth', APP_LANG);
Render::asset('css', '/themes/cmsfullform/Frontend/Assets/css/login_styles.css', ['area' => 'frontend', 'location' => 'head']);
Render::asset('js', '/themes/cmsfullform/Frontend/Assets/js/auth.js', ['area' => 'frontend', 'location' => 'footer']);

// Create meta tags for homepage
use App\Blocks\Meta\MetaBlock;

// Create meta tags for homepage directly from MetaBlock
$meta = new MetaBlock();

$meta
    ->title(option('site_login_title', APP_LANG))
    ->description(option('site_login_description', APP_LANG))
    ->robots('index, follow')
    ->canonical(base_url('login'));

// Add basic meta tags
$meta
    ->custom('<meta name="generator" content="CMSFullForm">')
    ->custom('<meta name="language" content="' . APP_LANG . '">')
    ->custom('<meta name="author" content="' . option('site_login_title', APP_LANG) . '">')
    ->custom('<meta name="theme-color" content="#354BD9">');

// Add Open Graph tags

$locale = APP_LANG === 'en' ? 'en_US' : 'vi_VN';
$meta
    ->og('locale', $locale)
    ->og('type', 'website')
    ->og('title', option('site_login_title', APP_LANG))
    ->og('description', option('site_login_description', APP_LANG))
    ->og('url', base_url('login'))
    ->og('site_name', option('site_login_title', APP_LANG))
    ->og('updated_time', date('c'));

// Add Twitter Card tags
$meta
    ->twitter('card', 'summary_large_image')
    ->twitter('title', option('site_login_title', APP_LANG))
    ->twitter('description', option('site_login_description', APP_LANG))
    ->twitter('site', '@' . option('site_login_title', APP_LANG));

// Add favicon if available
if (option('favicon')) {
    $logoUrl = theme_assets(option('favicon')['path'] ?? '/images/logo/logo-icon.webp');
    $meta
        ->custom('<link rel="icon" href="' . $logoUrl . '" sizes="32x32" />')
        ->custom('<link rel="apple-touch-icon" href="' . $logoUrl . '" />');
}

//Schema
$schemaData = [
    'site' => [
        'title' => option('site_login_title', APP_LANG),
        'desc' => option('site_login_description', APP_LANG),
        'logo' => option('site_logo', APP_LANG),
        'social' => option('social', APP_LANG),
    ],

];
$locale = APP_LANG === 'en' ? 'en_US' : 'vi_VN';

// Create schema for homepage directly from blocks
$loginSchema = new WebPage([
    'name'        => option('site_login_title', APP_LANG),
    'description' => option('site_login_description', APP_LANG),
    'url'         => base_url('/account/login/'),
    'inLanguage'  => $locale,
    'isPartOf'    => [
        '@type' => 'WebSite',
        'name'  => option('site_title'),
        'url'   => base_url()
    ],
    'breadcrumb'  => [
        '@type'           => 'BreadcrumbList',
        'itemListElement' => [
            [
                '@type'    => 'ListItem',
                'position' => 1,
                'item'     => [
                    '@id'  => base_url(),
                    'name' => 'Home'
                ]
            ],
            [
                '@type'    => 'ListItem',
                'position' => 2,
                'item'     => [
                    '@id'  => base_url('login'),
                    'name' => option('site_login_title', APP_LANG)
                ]
            ]
        ]
    ],
    'potentialAction' => [
        '@type'  => 'LoginAction',
        'target' => [
            '@type'       => 'EntryPoint',
            'urlTemplate' => base_url('login')
        ]
    ],
    // Original Publisher (Organization) if you want to nest in WebPage
    'publisher' => [
        '@type' => 'Organization',
        'name'  => option('site_title'),
        'url'   => base_url(),
        'logo'  => [
            '@type' => 'ImageObject',
            // Only push string URL, not object
            'url'   => (is_string(option('site_logo'))
                ? option('site_logo')
                : base_url('assets/images/logo.png'))
        ]
    ]
]);

// Add Organization schema
$orgSchema = new Organization([
    'name' => $schemaData['site']['title'],
    'url' => base_url(),
    'logo' => [
        '@type' => 'ImageObject',
        'url' => is_string($schemaData['site']['logo']) ? $schemaData['site']['logo'] : base_url('assets/images/logo.png'),
        'width' => 600,
        'height' => 60
    ],
    'sameAs' => [
        $schemaData['site']['social']['facebook'] ?? '',
        $schemaData['site']['social']['twitter'] ?? '',
        $schemaData['site']['social']['youtube'] ?? '',
        $schemaData['site']['social']['instagram'] ?? ''
    ]
]);

// Render meta tags and schema in header
get_header([
    'meta' => $meta->render(),
    'schema' => $loginSchema->render()

]);
?>


<?php get_template('sections/auth/loginform2'); ?>


<?php get_footer(); ?>
