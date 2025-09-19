<?php
namespace App\Controllers;

use System\Core\BaseController;
use System\Core\AppException;
use System\Drivers\Cache\UriCache;
use System\Libraries\Render;
use App\Libraries\Fastlang as Flang;

class FrontendController extends BaseController
{
    public $query;
    public $cachingDefaultLevel;
    public function __construct()
    {
        parent::__construct();        
    }

    public function index($layout = '', ...$params)
    {
        $cache = $this->caching($layout);
        // $cachedata = $cache ?  $cache->get() : false;
        $cachedata = false;
        if(empty($cachedata)) {

            load_helpers(['frontend', 'images', 'string', 'database', 'shortcode', 'languages']);
            $this->cachingDefaultLevel = option('cache_gzip') ?? 0;
    
            //Render::asset('css', 'css/blaze-slider.css', ['area' => 'frontend', 'location' => 'head']);
            //Render::asset('css', 'css/swiper-bundle.min.css', ['area' => 'frontend', 'location' => 'head']);
            // Render::asset('css', 'css/main.css', ['area' => 'frontend', 'location' => 'head']);
            // Render::asset('css', 'css/layout_styles.css', ['area' => 'frontend', 'location' => 'head']);
            // Render::asset('css', 'css/custom-backgrounds.css', ['area' => 'frontend', 'location' => 'head']);
    
            //Render::asset('js', 'js/jfast.1.2.3.js', ['area' => 'frontend', 'location' => 'head']);
            Render::asset('js', 'js/lazysizes.min.js', ['area' => 'frontend', 'location' => 'head']);
            Render::asset('js', 'js/main.js', ['area' => 'frontend', 'location' => 'head']);
            Render::asset('js', 'js/blaze-slider.min.js', ['area' => 'frontend', 'location' => 'head']);
            //Render::asset('js', 'js/feather.min.js', ['area' => 'frontend', 'location' => 'footer']);

            Flang::load('CMS', APP_LANG);
            $this->load_theme_functions();

            $layout = $this->_detectLayout();

            $this->data['params'] = $params;

            // Scan shortcode as before
            // $shortcodes = glob(PATH_ROOT . '/plugins/*/Shortcode/*.php');
            // foreach ($shortcodes as $shortcode) {
            //     $shortcode_name = basename($shortcode, '.php');
            //     $this->data['shortcode_' . $shortcode_name] = require $shortcode;
            // }
            // $shortcodes = glob(APP_THEME_PATH . 'Frontend/shortcodes/*.php');
            // foreach ($shortcodes as $shortcode) {
            //     $shortcode_name = basename($shortcode, '.php');
            //     $this->data['shortcode_' . $shortcode_name] = require $shortcode;
            // }

            // throw new AppException('Invalid source path - not found 404', 404, null, 500);

            // //print_r($layout);
            
            // if (!empty($GLOBALS['APP_URI']['split'])){
            //     $pages_main = $GLOBALS['APP_URI']['split'][0];
            //     //if (slugValidNotHaveSqlInjection($pages_main)){
            //         //$layout = 'page';
            //         //echo 'Goi model check $pages_main co ton tai trong Posttype Pages khong<br />Neu co: $layout = page.php. Sau do check page-$pages_main.php co ton tai khong neu co set lay $layout.';
            //     //}
            //     if ($pages_main == 'search'){
            //         $layout = 'search';
            //         echo 'set layout la search';die;
            //     }
            //     if ($pages_main == 'author'){
            //         $layout = 'author';
            //         echo 'set layout la author';die;
            //     }
            //     if (in_array($pages_main, APP_POSTTYPES)){
            //         $layout = 'archive';
            //         echo 'Neu phan tu dau co trong list APP_POSTTYPES: set $layout = archive.<br />Sau do check neu co ton tai file archive-$posttype thi set lai $layout<br />';
            //         echo $pages_main;

            //         // /products/ - archive show all san pham va tab list category & terms.
            //         // /products/slug-ten-san-pham/ - detail show san pham.
            //         // /products/category/ten-danh-muc/ - archive show all san pham trong danh muc.
            //         // /products/tag/ten-tag/ - archive show all san pham trong tag.
            //         // /products/brand/ten-thuong-hieu/ - archive show all san pham trong thuong hieu.


            //         $pages_sub = $GLOBALS['APP_URI']['split'][1];


            //         die;
            //     }
            // }else{
            //     //code show page 404
            // }
            // echo '<br />'.$layout;
            // echo '<br />';
            // print_r(APP_POSTTYPES);
            // die;

            $result = Render::html('Frontend/' . $layout, $this->data);
             // cache
             if ($cache) {
                $cachedata = $cache->set($result, true);
            } else {
                echo $result;
                return;
            }
        }
        $cache->render($cachedata);

    }

    protected function caching($functionName = '')
    {
        $cacheConfig = option('cache_config') ?? [];
        // decode cache config
        $cacheConfig = is_string($cacheConfig) ? json_decode($cacheConfig, true) : $cacheConfig;
        $config      = [];
        foreach ($cacheConfig as $cache) {
            if ($cache['cache_function'] == $functionName) {
                $config = $cache;
                break;
            }
        }

        if (isset($config['cache_caching']) && $config['cache_caching']) {
            if (empty($config['cache_level']) || $config['cache_level'] == 'default') {
                $config['cache_level'] = $this->cachingDefaultLevel;
            }
            $cache = new UriCache($config['cache_level'], $config['cache_type']);
            $cache->cacheLogin($config['cache_login'] ?? 0);
            $cache->cacheMobile($config['cache_mobile'] ?? 0);
            return $cache;
        } else {
            return false;
        }
    }

    /**
     * Detect layout based on WordPress Template Hierarchy
     * Uses Database_helper.php functions for data validation
     * Supports default posttype feature
     * 
     * @return string Layout name
     */
    protected function _detectLayout()
    {
        // Get URI segments
        $segments = APP_URI['split'] ?? [];
        // Homepage (no segments)
        if (empty($segments)) {
            return $this->templateExists('front-page') ? 'front-page' : 'index';
        }

        $segmentCount = count($segments);
        $firstSegment = $segments[0];


        // 1. Search Results
        if ($firstSegment === 'search') {
            return $this->getSearchTemplate($segments);
        }
        // 2. 404 Error
        if ($firstSegment === '404') {
            return $this->templateExists('404') ? '404' : 'index';
        }
        // 3. Author Archives
        if ($firstSegment === 'author') {
            $authorSlug = $segments[1] ?? null;
            return $this->getAuthorTemplate($authorSlug);
        }
        
        // 4. Check if first segment is a PAGE (regardless of segment count)
        $posttype = str_replace('-', '_', $firstSegment);
        $page = get_post([
            'slug' => $firstSegment,
            'posttype' => 'pages',
            'active' => true
        ]);
        if ($page && $segmentCount < 2) {
            return $this->getPageTemplate($firstSegment, $page);
        }

        // 5. Check DEFAULT POSTTYPE routes (if default posttype is set)
        $defaultPosttype = $this->defaultPosttype();
        if ($defaultPosttype) {
            $defaultPosttypeLayout = $this->defaultPosttypeLayout($segments, $defaultPosttype);
            if ($defaultPosttypeLayout !== null) {
                return $defaultPosttypeLayout;
            }
        }
        
        // 6. Check if first segment is a POSTTYPE (explicit posttype)
        if (posttype_exists($posttype, APP_LANG)) {
            // 6a. Taxonomy Archive (posttype/taxonomy/term-slug)
            if ($segmentCount >= 3) {
                $taxonomy = $segments[1];
                $termSlug = $segments[2];
                return $this->getTaxonomyTemplate($posttype, $taxonomy, $termSlug);
            }
            
            // 6b. Single Post (posttype/slug)
            if ($segmentCount >= 2) {
                $slug = $segments[1];
                $post = get_post([
                    'slug' => $slug,
                    'posttype' => $posttype,
                    'active' => true
                ]);
                
                if ($post) {
                    return $this->getSingleTemplate($posttype, $slug, $post);
                }
            }elseif ($page && $segmentCount >= 2) {
                return $this->getPageTemplate($firstSegment, $page);
            }
            
            // 6c. Posttype Archive (posttype/)
            return $this->getArchiveTemplate($posttype);
        }
        if ($page && $segmentCount >= 2) {
            return $this->getPageTemplate($firstSegment, $page);
        }
        
        // Fallback
        return 'index';
    }
    
    /**
     * Get search template following WordPress hierarchy
     * search-{query}.php > search.php > index.php
     */
    protected function getSearchTemplate($segments)
    {
        // search-{query}.php
        if (!empty($segments[1]) && $this->templateExists("search-{$segments[1]}")) {
            return "search-{$segments[1]}";
        }
        // search.php
        if ($this->templateExists('search')) {
            return 'search';
        }
        return 'index';
    }
    
    /**
     * Get single template following WordPress hierarchy
     * single-{posttype}-{slug}.php > single-{posttype}.php > single.php > singular.php > index.php
     */
    protected function getSingleTemplate($posttype, $slug, $postData)
    {
        global $post;
        $post = $postData;
        $post['posttype'] = $posttype;
        // For pages: page-{slug}.php > page-{id}.php > page-{template}.php > page.php
        if ($posttype === 'pages') {
            return $this->getPageTemplate($slug, $post);
        }
        
        // single-{posttype}-{slug}.php
        if ($this->templateExists("single-{$posttype}-{$slug}")) {
            return "single-{$posttype}-{$slug}";
        }
        
        // single-{posttype}.php
        if ($this->templateExists("single-{$posttype}")) {
            return "single-{$posttype}";
        }
        
        // single.php
        if ($this->templateExists('single')) {
            return 'single';
        }
        
        // singular.php
        if ($this->templateExists('singular')) {
            return 'singular';
        }
        
        return 'index';
    }
    
    /**
     * Get page template following WordPress hierarchy
     * page-{slug}.php > page-{id}.php > page-{template}.php > page.php > singular.php > index.php
     */
    protected function getPageTemplate($slug, $pageData)
    {
        global $post, $page;
        $pageData['posttype'] = 'pages';
        $post = $pageData;
        $page = $pageData;
        // page-{slug}.php
        if ($this->templateExists("page-{$slug}")) {
            return "page-{$slug}";
        }
        // page-{id}.php
        if (isset($page['id']) && $this->templateExists("page-{$page['id']}")) {
            return "page-{$page['id']}";
        }
        // page-{template}.php (if custom template is set)
        if (isset($page['template']) && !empty($page['template']) && $this->templateExists("page-{$page['template']}")) {
            return "page-{$page['template']}";
        }
        // page.php
        if ($this->templateExists('page')) {
            return 'page';
        }
        // singular.php
        if ($this->templateExists('singular')) {
            return 'singular';
        }
        return 'index';
    }
    
    /**
     * Get archive template following WordPress hierarchy
     * archive-{posttype}.php > archive.php > index.php
     */
    protected function getArchiveTemplate($posttype)
    {
        // archive-{posttype}.php
        if ($this->templateExists("archive-{$posttype}")) {
            return "archive-{$posttype}";
        }
        
        // archive.php
        if ($this->templateExists('archive')) {
            return 'archive';
        }
        
        return 'index';
    }
    
    /**
     * Get taxonomy template following WordPress hierarchy
     * taxonomy-{taxonomy}-{term}.php > taxonomy-{taxonomy}.php > taxonomy.php > archive.php > index.php
     */
    protected function getTaxonomyTemplate($posttype, $taxonomy, $termSlug)
    {
        // Validate term exists using get_term function - much more efficient
        $term = get_term($termSlug, $posttype, $taxonomy, APP_LANG);
        if (!$term) {
            return 'index';
        }
        // taxonomy-{taxonomy}-{term}.php
        if ($this->templateExists("taxonomy-{$taxonomy}-{$termSlug}")) {
            return "taxonomy-{$taxonomy}-{$termSlug}";
        }
        // taxonomy-{taxonomy}.php
        if ($this->templateExists("taxonomy-{$taxonomy}")) {
            return "taxonomy-{$taxonomy}";
        }
        // taxonomy.php
        if ($this->templateExists('taxonomy')) {
            return 'taxonomy';
        }
        // archive.php
        if ($this->templateExists('archive')) {
            return 'archive';
        }
        return 'index';
    }
    
    /**
     * Get author template following WordPress hierarchy
     * author-{nicename}.php > author-{id}.php > author.php > archive.php > index.php
     */
    protected function getAuthorTemplate($authorSlug = null)
    {
        if ($authorSlug) {
            // Check if author exists (you can implement this)
            // $author = getAuthor($authorSlug);
            
            // author-{nicename}.php
            if ($this->templateExists("author-{$authorSlug}")) {
                return "author-{$authorSlug}";
            }
        }
        
        // author.php
        if ($this->templateExists('author')) {
            return 'author';
        }
        
        // archive.php
        if ($this->templateExists('archive')) {
            return 'archive';
        }
        
        return 'index';
    }
    
    // /**
    //  * Get date archive template following WordPress hierarchy
    //  * date.php > archive.php > index.php
    //  */
    // protected function getDateTemplate($segments)
    // {
    //     // date.php
    //     if ($this->templateExists('date')) {
    //         return 'date';
    //     }
        
    //     // archive.php
    //     if ($this->templateExists('archive')) {
    //         return 'archive';
    //     }
        
    //     return 'index';
    // }
    
    /**
     * Check if segment is a date archive (year/month/day)
     */
    protected function isDateArchive($segment)
    {
        // Check if it's a 4-digit year
        return preg_match('/^\d{4}$/', $segment);
    }
    
    /**
     * Get default posttype from settings
     * 
     * @return string|null Default posttype slug or null if not set
     */
    protected function defaultPosttype()
    {
        $defaultPosttype = option('default_posttype', APP_LANG);
        if ($defaultPosttype && posttype_exists($defaultPosttype, APP_LANG)) {
            return $defaultPosttype;
        }
        return null;
    }
    
    /**
     * Check routes for default posttype (URLs without posttype prefix)
     * 
     * @param array $segments URI segments
     * @param string $defaultPosttype Default posttype slug
     * @return string|null Template name or null if not matched
     */
    protected function defaultPosttypeLayout($segments, $defaultPosttype)
    {
        $segmentCount = count($segments);
        $firstSegment = $segments[0];
        // 1. Taxonomy Archive for default posttype
        // /category/tech/ -> taxonomy-category-tech.php (default posttype)
        if ($segmentCount >= 2) {
            //$firstSegment is 'category', 'tags', etc.
            $termSlug = $segments[1];
            // Check if this taxonomy/term exists for default posttype
            $term = get_term($termSlug, $defaultPosttype, $firstSegment, APP_LANG);
            if ($term) {
                return $this->getTaxonomyTemplate($defaultPosttype, $firstSegment, $termSlug);
            }
        }
        // 2. Single Post for default posttype
        // /this-is-slug-post/ -> single-{defaultPosttype}.php
        $post = get_post([
            'slug' => $firstSegment,
            'posttype' => $defaultPosttype,
            'active' => true
        ]);
        if ($post) {
            return $this->getSingleTemplate($defaultPosttype, $firstSegment, $post);
        }
        return null;
    }
    
    /**
     * Check if template file exists in theme
     */
    protected function templateExists($template)
    {
        $templatePath = APP_THEME_PATH . 'Frontend/' . $template . '.php';
        return file_exists($templatePath);
    }

        /**
     * Load theme functions.php file if exists
     * Similar to WordPress functions.php
     */
    protected function load_theme_functions()
    {
        $functions_file = APP_THEME_PATH . 'Frontend/functions.php';
        
        if (file_exists($functions_file)) {
            // Load theme functions
            require_once $functions_file;
            
            // Log that functions.php was loaded (optional)
            if (defined('APP_DEBUG') && APP_DEBUG) {
                error_log("Theme functions.php loaded from: " . $functions_file);
            }
        }
    }
}
