<?php

/**
 * Template Name: Trang Blog
 * Description: Trang blog của website.
 *
 * @package CMSFullForm
 */

use System\Libraries\Render;
use App\Blocks\Meta\MetaHelper;
use App\Libraries\Fastlang as Flang;
use App\Blocks\Schema\Templates\WebSite;
use App\Blocks\Schema\Templates\Organization;
use App\Blocks\Schema\Templates\FAQPage;
use App\Blocks\Schema\Templates\Product;
use App\Models\FastModel;

// Load helpers
load_helpers(['frontend', 'languges']);

Flang::load('CMS', APP_LANG);
Flang::load('Blog', APP_LANG);
Render::asset('css', theme_assets('css/blogs_styles.css'), ['area' => 'frontend', 'location' => 'head']);
Render::asset('css', theme_assets('css/blog-search.css'), ['area' => 'frontend', 'location' => 'head']);
Render::asset('js', theme_assets('js/blog-search-manager.js'), ['area' => 'frontend', 'location' => 'footer']);
Render::asset('js', theme_assets('js/blogs.js'), ['area' => 'frontend', 'location' => 'footer']);

// Parse filter parameters from $params array
$search = '';
$sortBy = 'created_at_desc';
$category = '';
$category_id = 0;
$tags = '';
$page = 1;
$perPage = 6;
$canonical = base_url('blogs', APP_LANG);
// Parse $params array to extract filter values
if (isset($params) && is_array($params)) {
    // Create a map of parameters for easier access
    $paramMap = [];
    for ($i = 0; $i < count($params); $i += 2) {
        if (isset($params[$i]) && isset($params[$i + 1])) {
            $key = $params[$i];
            $value = $params[$i + 1];
            $paramMap[$key] = $value;
        }
    }

    // Extract values from param map
    if (isset($paramMap['search'])) {
        $search = trim(str_replace('-', ' ', $paramMap['search']));
    }

    if (isset($paramMap['sort'])) {
        $sortBy = $paramMap['sort'];
    }

    if (isset($paramMap['category'])) {
        $category = $paramMap['category'];
        $category_id = (new FastModel('fast_terms'))->where('slug', $category)->where('lang', APP_LANG)->value('id_main');
        $canonical .= 'category/' . $category . '/';
    }

    if (isset($paramMap['tags'])) {
        $tags = trim(str_replace('-', ' ', $paramMap['tags']));
    }

    if (isset($paramMap['paged'])) {
        $page = max(1, (int)$paramMap['paged']);
        $canonical .= 'paged/' . $page . '/';
    }
}

// Also check query parameters for search, sort, and tags
$search = str_replace('-', ' ', S_GET('search', $search));
$sortBy = S_GET('sort', $sortBy);
$tags = str_replace('-', ' ', S_GET('tags', $tags));

// Map sort options to database columns
$sortColumn = 'created_at';
$sortDirection = 'DESC';

switch ($sortBy) {
    case 'created_at_desc':
        $sortColumn = 'created_at';
        $sortDirection = 'DESC';
        break;
    case 'created_at_asc':
        $sortColumn = 'created_at';
        $sortDirection = 'ASC';
        break;
    case 'title_asc':
        $sortColumn = 'title';
        $sortDirection = 'ASC';
        break;
    case 'title_desc':
        $sortColumn = 'title';
        $sortDirection = 'DESC';
        break;
    case 'views_desc':
        $sortColumn = 'views';
        $sortDirection = 'DESC';
        break;
    case 'views_asc':
        $sortColumn = 'views';
        $sortDirection = 'ASC';
        break;
}

// Use get_posts helper to get blogs data
$blogsData = get_posts([
    'posttype' => 'blogs',
    'perPage' => $perPage,
    'paged' => $page,
    'search' => $search,
    'searchcolumns' => ['title', 'seo_desc', 'content'],
    'cat' => !empty($category_id) ? $category_id : null,
    'sort' => [$sortColumn, $sortDirection],
    'filters' => [
        'status' => 'active'
    ],
    'tags' => !empty($tags) ? $tags : null,
    'pagination' => true,
    'totalpage' => true,
], APP_LANG);

// Get categories with counts from database
$categoriesData = get_terms('blogs', APP_LANG);

// Extract blogs data
$blogs = $blogsData['data'] ?? $blogsData['items'] ?? [];
$totalBlogs = $blogsData['total'] ?? count($blogs);
$currentPage = $blogsData['current_page'] ?? $page;
$totalPages = $blogsData['last_page'] ?? 1;
$perPageCount = $blogsData['per_page'] ?? $perPage;

// Process blogs data
foreach ($blogs as $index => $blog) {
    // Generate blog URL
    $blogs[$index]['url'] = base_url('blogs/' . $blog['slug'], APP_LANG);

    // Set default thumbnail if empty
    if (empty($blog['thumbnail_url'])) {
        $blogs[$index]['thumbnail_url'] = theme_assets('images/default-blog-thumbnail.jpg');
    }

    // Process views and other data
    $views = $blog['views'] ?? 0;
    $formatted_views = $views > 1000 ? round($views / 1000, 1) . 'k' : $views;

    // Add processed fields
    $blogs[$index] = array_merge($blog, [
        'formatted_views' => $formatted_views,
        'seo_description' => $blog['seo_desc'] ?? $blog['description'] ?? '',
        'author' => $blog['author'] ?? Flang::_e('anonymous_author'),
        'category_display' => ucfirst(str_replace(['_', '-'], ' ', $blog['category'] ?? 'general'))
    ]);
}

// Get additional data for sections
$top_blogs_by_views = get_posts([
    'posttype' => 'blogs',
    'perPage' => 3,
    'sort' => ['views', 'DESC'],
    'filters' => ['status' => 'active']
], APP_LANG)['data'] ?? [];

$trending_blogs = get_posts([
    'posttype' => 'blogs',
    'perPage' => 4,
    'sort' => ['created_at', 'DESC'],
    'filters' => ['status' => 'active']
], APP_LANG)['data'] ?? [];

// Get blog types and popular tags
$blogTypes = get_posts([
    'posttype' => 'blogs',
    'perPage' => 100,
    'filters' => ['status' => 'active'],
    'groupby' => 'type'
], APP_LANG)['data'] ?? [];

$popularTags = get_posts([
    'posttype' => 'blogs',
    'perPage' => 100,
    'filters' => ['status' => 'active'],
    'groupby' => 'tags'
], APP_LANG)['data'] ?? [];

// Create meta tags for homepage
use App\Blocks\Meta\MetaBlock;

// Create meta tags for homepage directly from MetaBlock
$meta = new MetaBlock();

$pageTitle = !empty($search) ?
    sprintf(Flang::_e('blogs.search.results_for', APP_LANG), $search) . ' - ' . option('site_blogs_title', APP_LANG) :
    option('site_blogs_title', APP_LANG);

$meta
    ->title($pageTitle)
    ->description(option('site_blogs_description', APP_LANG))
    ->robots('index, follow')
    ->canonical($canonical);

// Add basic meta tags
$meta
    ->custom('<meta name="generator" content="CMSFullForm">')
    ->custom('<meta name="language" content="' . APP_LANG . '">')
    ->custom('<meta name="author" content="' . option('site_blogs_title', APP_LANG) . '">')
    ->custom('<meta name="theme-color" content="#354BD9">');

// Add Open Graph tags
$locale = APP_LANG === 'en' ? 'en_US' : 'vi_VN';
$meta
    ->og('locale', $locale)
    ->og('type', 'website')
    ->og('title', $pageTitle)
    ->og('description', option('site_blogs_description', APP_LANG))
    ->og('url', $canonical)
    ->og('site_name', option('site_blogs_title', APP_LANG))
    ->og('updated_time', date('c'));

// Add Twitter Card tags
$meta
    ->twitter('card', 'summary_large_image')
    ->twitter('title', $pageTitle)
    ->twitter('description', option('site_blogs_description', APP_LANG))
    ->twitter('site', '@' . option('site_blogs_title', APP_LANG));

// Add favicon if available
if (option('favicon')) {
    $logoUrl = theme_assets(option('favicon')['path'] ?? '/images/logo/logo-icon.webp');
    $meta
        ->og('image', $logoUrl)
        ->twitter('image', $logoUrl)
        ->custom('<link rel="icon" href="' . $logoUrl . '" sizes="32x32" />')
        ->custom('<link rel="apple-touch-icon" href="' . $logoUrl . '" />');
}

//Schema
$schemaData = [
    'site' => [
        'title' => option('site_blogs_title', APP_LANG),
        'desc' => option('site_blogs_description', APP_LANG),
        'logo' => option('site_logo'),
        'social' => option('social'),
    ],
];

// 2) Prepare site + org
$blogsSchema = new WebSite([
    'name'            => option('site_title'),
    'description'     => option('site_desc'),
    'url'             => base_url(),
    'potentialAction' => [
        '@type'       => 'SearchAction',
        'target'      => [
            '@type'      => 'EntryPoint',
            'urlTemplate' => base_url('search/?s={search_term_string}')
        ],
        'query-input' => 'required name=search_term_string'
    ]
]);

$orgSchema = new Organization([
    'name'   => option('site_title'),
    'url'    => base_url(),
    'logo'   => [
        '@type'   => 'ImageObject',
        'url'     => (is_string(option('site_logo'))
            ? option('site_logo')
            : base_url('assets/images/logo.png')),
        'width'   => 600,
        'height'  => 60
    ],
    'sameAs' => [
        option('social.facebook') ?? '',
        option('social.twitter')  ?? '',
        option('social.youtube')  ?? '',
        option('social.instagram') ?? ''
    ]
]);

// 3) Add FAQ if available
if (!empty($schemaData['faq'])) {
    $faqSchema = new FAQPage();
    foreach ($schemaData['faq'] as $f) {
        $faqSchema->addFAQ($f['question'], $f['answer']);
    }
    $homeSchema->addSchemaChild('mainEntity', $faqSchema);
}

// 4) Prepare publisher data to set at once
$publisherData = $orgSchema->getSchemaData();

// 5) Build mainEntity with 2 ItemLists: featured blogs & all blogs
$mainEntities = [];

// --- Featured Blogs ---
if (!empty($top_blogs_by_views)) {
    $popularList = new \App\Blocks\Schema\SchemaBlock();
    $popularList->setSchemaType('ItemList');

    $popularItems = [];
    $position = 0;
    foreach ($top_blogs_by_views as $post) {
        $position++;
        // Process image
        $imageUrl = '';
        if (!empty($post['thumbnail_url'])) {
            if (is_string($post['thumbnail_url'])) {
                $imageUrl = $post['thumbnail_url'];
            } elseif (is_array($post['thumbnail_url']) && isset($post['thumbnail_url']['path'])) {
                $imageUrl = base_url('uploads/' . $post['thumbnail_url']['path']);
            }
        }

        $popularItems[] = [
            '@type'    => 'ListItem',
            'position' => $position,
            'item'     => [
                '@type'         => 'BlogPosting',
                'headline'      => $post['title'] ?? '',
                'image'         => $imageUrl,
                'url'           => $post['url'] ?? '',
                'datePublished' => $post['created_at'] ?? date('c'),
                'author'        => [
                    '@type' => 'Person',
                    'name'  => $post['author'] ?? Flang::_e('anonymous_author')
                ]
            ]
        ];
    }

    $popularList->setSchemaData([
        'name'            => Flang::_e('hot_title') . ' ' . Flang::_e('hot_highlight'),
        'itemListElement' => $popularItems
    ]);
    $mainEntities[] = $popularList->getSchemaData();
}

// --- All Blogs ---
if (!empty($blogs)) {
    $allList = new \App\Blocks\Schema\SchemaBlock();
    $allList->setSchemaType('ItemList');

    $allItems = [];
    $position = 0;
    foreach ($blogs as $post) {
        $position++;
        $imageUrl = '';
        if (!empty($post['thumbnail_url'])) {
            if (is_string($post['thumbnail_url'])) {
                $imageUrl = $post['thumbnail_url'];
            } elseif (is_array($post['thumbnail_url']) && isset($post['thumbnail_url']['path'])) {
                $imageUrl = base_url('uploads/' . $post['thumbnail_url']['path']);
            }
        }

        $allItems[] = [
            '@type'    => 'ListItem',
            'position' => $position,
            'item'     => [
                '@type'         => 'BlogPosting',
                'headline'      => $post['title'] ?? '',
                'image'         => $imageUrl,
                'url'           => $post['url'] ?? '',
                'datePublished' => $post['created_at'] ?? date('c'),
                'author'        => [
                    '@type' => 'Person',
                    'name'  => $post['author'] ?? Flang::_e('anonymous_author')
                ]
            ]
        ];
    }

    $allList->setSchemaData([
        'name'            => Flang::_e('latest_title') . ' ' . Flang::_e('latest_highlight'),
        'itemListElement' => $allItems
    ]);
    $mainEntities[] = $allList->getSchemaData();
}

// 6) Finally assign ALL at once
$blogsSchema->setSchemaData([
    'publisher'  => $publisherData,
    'mainEntity' => $mainEntities
]);

// Render meta tags and schema in header
get_header([
    'meta' => $meta->render(),
    'schema' => $blogsSchema->render(),
    'layout' => 'blogs'
]);
?>

<?php get_template('sections/blogs/blogs_title'); ?>


<?php get_template('sections/blogs/blogs_new', [
    'blogs' => $blogs,
    'pagination' => [
        'currentPage' => $currentPage,
        'totalPages' => $totalPages,
        'totalBlogs' => $totalBlogs,
        'perPage' => $perPageCount
    ],
    'searchParams' => [
        'search' => $search,
        'category' => $category, // Use slug for display purposes
        'category_id' => $category_id, // Use ID for database queries
        'tags' => $tags,
        'sortBy' => $sortBy
    ],
    'blogTypes' => $blogTypes,
    'popularTags' => $popularTags,
    'categoriesData' => $categoriesData
]); ?>
<?php get_template('sections/blogs/blogs_all', ['blogs' => $top_blogs_by_views]); ?>

<?php get_template('sections/blogs/blogs_list'); ?>

<?php get_template('sections/blogs/blogs_trend', ['trending_blogs' => $trending_blogs]); ?>

<?php get_template('sections/blogs/blogs_regis'); ?>

<?php get_template('sections/blogs/blogs_story'); ?>

<?php get_footer(); ?>
