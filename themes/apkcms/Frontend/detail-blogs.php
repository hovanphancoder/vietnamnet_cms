<?php

/**
 * Template Name: Blog Detail
 * Description: Trang chi tiết blog
 */

use System\Libraries\Render;
use App\Libraries\Fastlang as Flang;
use App\Blocks\Schema\Templates\Article;
use App\Blocks\Meta\MetaBlock;

// Load language files
Flang::load('CMS', APP_LANG);
Flang::load('Library', APP_LANG);
Flang::load('Blog', APP_LANG);
$blog = get_post([
    'posttype' => 'blogs',
    'slug' => $params[0] ?? ''
], APP_LANG);

$recent_blogs = get_posts([
    'posttype' => 'blogs',
    'perPage' => 3,
    'sort' => ['created_at', 'DESC']
], APP_LANG)['data'] ?? [];
updateViews('blogs', $blog['id']);
// Validate blog data
if (empty($blog) || !is_array($blog)) {
    header('HTTP/1.0 404 Not Found');
    get_template('404');
    exit;
}
$author = getAuthor($blog['author']);
// Load blog-specific assets
Render::asset('css', theme_assets('css/blog-detail.css'), ['area' => 'frontend', 'location' => 'head']);
Render::asset('js', theme_assets('js/blog-detail.js'), ['area' => 'frontend', 'location' => 'footer']);

// Create meta tags
$meta = new MetaBlock();

// Use the correct language for the blog (detected from database)
$blogLang = APP_LANG;

$meta
    ->title(($blog['seo_title'] ?: $blog['title']) . ' - ' . option('site_blog_title', $blogLang))
    ->description($blog['seo_desc'] ?? '')
    ->og('image', theme_assets(get_image_full($blog['thumb_url'])))
    ->og('url', base_url('blogs/' . $blog['slug'], $blogLang))
    ->og('type', 'article')
    ->canonical(base_url('blogs/' . $blog['slug'], $blogLang));
if (option('favicon')) {
    $logoUrl = theme_assets(option('favicon')['path'] ?? '/images/logo/logo-icon.webp');
    $meta->custom('<link rel="icon" href="' . $logoUrl . '" sizes="32x32" />')
        ->custom('<link rel="apple-touch-icon" href="' . $logoUrl . '" />');
}
// Schema for blog article
$blogSchema = new Article();
$blogSchema->setSchemaData([
    'headline' => $blog['title'],
    'description' => $blog['seo_desc'] ?? '',
    'image' => theme_assets(get_image_full($blog['thumb_url'])),
    'url' => base_url('blogs/' . $blog['slug'], $blogLang),
    'datePublished' => date('c', strtotime($blog['created_at'])),
    'dateModified' => date('c', strtotime($blog['updated_at'])),
    'author' => [
        '@type' => 'Person',
        'name' => 'Admin',
    ],
    'publisher' => [
        '@type' => 'Organization',
        'name' => option('site_name', $blogLang),
        'logo' => theme_assets(option('site_logo')['path'] ?? '/images/logo/Logo.png')
    ],
    'mainEntityOfPage' => base_url('blogs/' . $blog['slug'], $blogLang)
]);
$related_blogs = getRelated('blogs', $blog['id'], 3);

// Render meta tags and schema
get_header([
    'meta' => $meta->render(),
    'schema' => $blogSchema->render(),
    'layout' => 'blog_detail'
]);
?>
<script>
    const blogId = <?= $blog['id'] ?>;
</script>
<!-- Blog Detail Page - Modern Design -->
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-blue-50">

    <?php get_template('sections/blog_detail/blog_detail_hero', [
        'blog' => $blog
    ]); ?>

    <!-- Main Content Section -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="grid lg:grid-cols-4 gap-12">

            <?php get_template('sections/blog_detail/blog_detail_content', [
                'blog' => $blog,
                'author' => $author
            ]); ?>

            <?php get_template('sections/blog_detail/blog_detail_sidebar', [
                'blog' => $blog,
                'recent_blogs' => $recent_blogs
            ]); ?>

        </div>
    </section>

    <?php get_template('sections/blog_detail/blog_detail_related', [
        'related_blogs' => $related_blogs ?? []
    ]); ?>
</div>

<?php get_template('sections/blog_detail/blog_detail_modal', ['blog' => $blog]); ?>

<?php get_template('sections/blog_detail/blog_detail_scripts'); ?>

<?php get_footer(); ?>
