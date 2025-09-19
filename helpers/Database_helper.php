<?php

/**
 * Helper – get_posts()
 * Lấy bài viết + (tuỳ chọn) danh mục qua FastModel / QueryBuilder
 */

use App\Models\FastModel;
use App\Models\UsersModel;
use System\Core\AppException;

if (!function_exists('get_term')) {
    /**
     * Get single term by slug, posttype and type
     * 
     * @param string $slug Term slug
     * @param string $posttype Posttype name
     * @param string $type Term type (category, tags, etc.)
     * @param string $lang Language code
     * @return array|null Term data or null if not found
     */
    function get_term($slug, $posttype, $type = 'category', $lang = APP_LANG)
    {
        try {
            $term = (new FastModel('fast_terms'))
                ->where('slug', $slug)
                ->where('posttype', $posttype)
                ->where('type', $type)
                ->where('lang', $lang)
                ->first();
            
            return $term;
        } catch (Exception $e) {
            return null;
        }
    }
}

function get_terms($posttype, $type = 'categories', $lang = APP_LANG)
{
    $terms = (new FastModel('fast_terms'))->where('posttype', $posttype)->where('type', $type)->where('lang', $lang)->get();
    return $terms;
}

if (!function_exists('get_posts')) {
    /**
     * Lấy bài viết từ bảng fast_posts_stories
     * 
     * @param array $args Mảng các tham số
     * @return array Mảng các bài viết (array) ['posttype' => 'stories', 'filters' => [], 'cat' => null, 'cat__in' => [], 'cat__not_in' => [], 'sort' => ['created_at', 'DESC'], 'perPage' => null, 'withCategories' => false, 'columns' => ['*'], 'paged' => 1]
     */
    function get_posts(array $args = [], $lang = ''): array
    {
        /* ---------- 0. Mặc định ---------- */
        $defaults = [
            'posttype'        => 'posts',
            'filters' => [
                'status' => 'active'
            ],
            'cat'             => null,   // 1 id_main
            'cat__in'         => [],     // multiple id_main
            'cat__not_in'     => [],
            'sort'            => ['created_at', 'DESC'],
            'perPage'         => null,
            'withCategories'  => false,
            'columns'         => ['*'],
            'paged'           => 1,
            'search'          => null,
            'searchcolumns'   => ['search_string'],
            'totalpage'      => false,
        ];
        if (isset($args['limit']) && $args['limit'] > 0) {
            $args['perPage'] = $args['limit'];
        }
        $args = array_replace($defaults, $args);

        /* ---------- 1. Tên bảng ---------- */
        if (empty($lang)) {
            $table       = posttype_name($args['posttype'], APP_LANG);              // fast_posts_stories
        } else {
            $table       = posttype_name($args['posttype'], $lang);              // fast_posts_stories
        }
        $pivotTable  = table_posttype_relationship($args['posttype']); // fast_posts_stories_rel
        $termTable   = 'fast_terms';

        /* ---------- 2. Builder gốc ---------- */
        $qb = (new FastModel($table))
            ->newQuery()
            ->select($args['columns']);

        /* ---------- 3. Filters thường ---------- */
        foreach ($args['filters'] as $key => $rule) {
            if (!is_int($key)) {
                is_array($rule)
                    ? $qb->whereIn($key, $rule)
                    : $qb->where($key, '=', $rule);
                continue;
            }
            [$col, $op, $val, $bool] = $rule + [3 => 'AND'];
            $bool = strtoupper($bool) === 'OR' ? 'OR' : 'AND';
            if ($op === 'IN')      $qb->whereIn($col, $val, $bool);
            elseif ($op === 'NOT IN')  $qb->whereNotIn($col, $val, $bool);
            else                     $qb->where($col, $op, $val, $bool);
        }

        /* ---------- 3.1. Tìm kiếm ---------- */
        if (!empty($args['search'])) {
            $search = $args['search'];
            $qb->where(function ($query) use ($search, $table, $args) {
                foreach ($args['searchcolumns'] as $column) {
                    $query->orWhere("{$table}.{$column}", 'LIKE', "%{$search}%");
                }
            });
        }

        /* ---------- 4. Lọc theo category (sub-query) ---------- */
        $needCat = $args['cat'] || $args['cat__in'] || $args['cat__not_in'];
        if ($needCat) {

            $qb->whereIn(
                "{$table}.id",                                           // <-- NO join
                function ($sub) use ($pivotTable, $termTable, $args) {
                    $sub->table($pivotTable)
                        ->select(["{$pivotTable}.post_id"])
                        ->join(
                            $termTable,
                            "{$termTable}.id_main",
                            '=',
                            "{$pivotTable}.rel_id"
                        );

                    if ($args['cat'])
                        $sub->where("{$termTable}.id_main", '=', $args['cat']);

                    if ($args['cat__in'])
                        $sub->whereIn("{$termTable}.id_main", (array)$args['cat__in']);

                    if ($args['cat__not_in'])
                        $sub->whereNotIn("{$termTable}.id_main", (array)$args['cat__not_in']);
                }
            );
        }

        /* ---------- 5. Sắp xếp ---------- */
        [$col, $dir] = $args['sort'] + [1 => 'DESC'];
        if (strpos($col, '.') === false) {
            $col = "{$table}.{$col}";
        }
        $qb->orderBy($col, strtoupper($dir));

        /* ---------- 6. Eager-load categories ---------- */
        if ($args['withCategories']) {
            $qb->belongsToMany(
                $termTable,
                $pivotTable,
                'post_id',
                'rel_id',
                'id',
                'id_main',          // get by id_main if you want to keep
                'categories',
                ['id', 'id_main', 'name', 'slug', 'type']
            )
                ->with(['categories']);
        }

        /* ---------- 7. Kết quả ---------- */
        if ($args['perPage']) {
            return $qb->paginate($args['perPage'], $args['paged'], $args['totalpage']);
        }
        return $qb->get();
    }
}
/**
 * Lấy bài viết từ bảng fast_posts_stories
 * 
 * @param array $args Mảng các tham số
 * @return array Mảng các bài viết (array) ['posttype' => 'stories', 'filters' => [], 'cat' => null, 'cat__in' => [], 'cat__not_in' => [], 'sort' => ['created_at', 'DESC'], 'perPage' => null, 'withCategories' => false, 'columns' => ['*'], 'paged' => 1]
 */
if (!function_exists('get_post')) {

    function get_post($args = [])
    {
        /* ---------- 0. Mặc định ---------- */
        $defaults = [
            'id'             => 0,
            'slug'           => '',
            'posttype'       => 'plugins',
            'active'         => true,
            'columns'        => ['*'],
            'withCategories' => false,
            'totalpage'      => false,
        ];
        // Ensure $args is an array
        $args = is_array($args) ? $args : [];
        $args = array_replace($defaults, $args);
        // If both id & slug are empty → return null
        if (!$args['id'] && $args['slug'] === '') {
            return null;
        }

        /* ---------- 1. Tên bảng ---------- */
        $table       = posttype_name($args['posttype'], APP_LANG);              // fast_posts_stories
        $pivotTable  = table_posttype_relationship($args['posttype']); // fast_posts_stories_rel
        $termTable   = 'fast_terms';

        /* ---------- 2. Builder ---------- */
        $qb = (new FastModel($table))
            ->newQuery()
            ->select($args['columns']);

        if ($args['active']) {
            $qb->where('status', '=', 'active');
        }

        if ($args['id']) {
            $qb->where("{$table}.id", '=', $args['id']);
        } else {
            $qb->where("{$table}.slug", '=', $args['slug']);
        }

        /* ---------- 3. eager-load categories ---------- */
        if ($args['withCategories']) {
            $qb->belongsToMany(
                $termTable,
                $pivotTable,
                'post_id',     // FK pivot → parent
                'rel_id',     // FK pivot → term
                'id',          // PK parent
                'id_main',     // relatedKey (keep id_main if multilingual)
                'categories',
                ['id', 'id_main', 'name', 'slug', 'type', 'lang']
            )
                ->with(['categories']);
        }

        /* ---------- 4. Kết quả ---------- */
        return $qb->first();   // return array|null
    }
}

if (!function_exists('getRelated')) {
    function getRelated($post, $postId, $limit = 4)
    {
        // Lấy tên bảng đúng với ngôn ngữ
        $tableName = posttype_name($post, APP_LANG); // fast_posts_themes_en
        $relTableName = table_posttype_relationship($post); // fast_posts_themes_rel
        $relIds = (new FastModel($relTableName))->where('post_id', $postId)->pluck('rel_id');
        $postIds = (new FastModel($relTableName))->whereIn('rel_id', $relIds)->pluck('post_id');
        // Sử dụng FastModel trực tiếp để lấy themes liên quan
        $model = new FastModel($tableName);
        $related = $model->newQuery()
            ->where('status', 'active')
            ->whereIn('id', $postIds)
            ->where('id', '!=', $postId)
            ->orderBy('views', 'DESC')
            ->limit($limit)
            ->get();

        return $related;
    }
}


if (!function_exists('getAuthorNames')) {
    function getAuthorNames($authorIds)
    {
        if (empty($authorIds)) {
            return [];
        }

        try {
            $usersModel = new UsersModel();
            $authors = $usersModel->whereIn('id', $authorIds)->get();

            $authorNames = [];
            foreach ($authors as $author) {
                $authorNames[$author['id']] = $author['fullname'] ?? $author['username'] ?? 'Theme Team';
            }

            return $authorNames;
        } catch (AppException $e) {
            return [];
        }
    }
}

if (!function_exists('getAuthorName')) {
    function getAuthorName($authorId)
    {
        // You can implement this to get author name from users table
        // For now, return a default name
        return 'Admin'; // or get from database: SELECT name FROM users WHERE id = $authorId
    }
}

if (!function_exists('calculateReadTime')) {
    function calculateReadTime($content)
    {
        $wordCount = str_word_count(strip_tags($content));
        $readingSpeed = 200; // Average words per minute
        $minutes = ceil($wordCount / $readingSpeed);
        return max(1, $minutes); // At least 1 minute
    }
}

if (!function_exists('formatViews')) {
    function formatViews($views)
    {
        $views = (int) $views;

        if ($views < 1000) {
            return (string) $views;
        } elseif ($views < 1000000) {
            return round($views / 1000, 1) . 'K';
        } elseif ($views < 1000000000) {
            return round($views / 1000000, 1) . 'M';
        } else {
            return round($views / 1000000000, 1) . 'B';
        }
    }
}

if (!function_exists('updateViews')) {
    function updateViews($posttype, $id)
    {
        try {
            // Get table name with language
            $tableName = posttype_name($posttype, APP_LANG);

            // Use FastModel to update views
            $model = new FastModel($tableName);

            // Get current plugin data
            $post = $model->newQuery()->where('id', $id)->first();
            if ($post) {
                // Update view counts
                $updateData = [
                    'views' => ($post['views'] ?? 0) + 1,
                    'views_day' => ($post['views_day'] ?? 0) + 1,
                    'views_week' => ($post['views_week'] ?? 0) + 1,
                    'updated_at' => date('Y-m-d H:i:s')
                ];

                $model->newQuery()->where('id', $id)->update($updateData);
            }
        } catch (\Exception $e) {
            // Log error but don't break page loading
            error_log('Error updating views: ' . $e->getMessage());
            return false;
        }
    }
}
if (!function_exists('getAuthor')) {
    function getAuthor($authorId)
    {
        $author = new FastModel('fast_users');
        $author = $author->where('id', $authorId)->first();
        return $author;
    }
}

if (!function_exists('countAuthorThemesPlugins')) {
    function countAuthorThemesPlugins($posttype, $authorId)
    {
        $tableName = posttype_name($posttype, APP_LANG);
        $model = new FastModel($tableName);
        $count = $model->where('author', $authorId)->count();
        return $count;
    }
}

if (!function_exists('searching')) {
    function searching($keyword = '')
    {
        $keyword = remove_accents($keyword);
        $blogsModel = posttype_name('blogs', APP_LANG);
        $model = new FastModel($blogsModel);
        $blogs = $model->where('title', 'like', '%' . $keyword . '%')->orWhere('seo_desc', 'like', '%' . $keyword . '%')->orderBy('created_at', 'DESC')->limit(8)->get();
        $themesModel = posttype_name('themes', APP_LANG);
        $model = new FastModel($themesModel);
        $themes = $model->where('title', 'like', '%' . $keyword . '%')->orWhere('seo_desc', 'like', '%' . $keyword . '%')->orderBy('created_at', 'DESC')->limit(8)->get();
        $pluginsModel = posttype_name('plugins', APP_LANG);
        $model = new FastModel($pluginsModel);
        $plugins = $model->where('title', 'like', '%' . $keyword . '%')->orWhere('seo_desc', 'like', '%' . $keyword . '%')->orderBy('created_at', 'DESC')->limit(8)->get();
        return [
            'blogs' => $blogs,
            'themes' => $themes,
            'plugins' => $plugins
        ];
    }
}