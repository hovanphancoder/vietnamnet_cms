<?php
namespace App\Models;
use System\Core\BaseModel;

class PostrelModel extends BaseModel {
    public function __construct()
    {
        parent::__construct();
    }
    public function getPosts($post_id = '', $posttype = '', $posttype_rel = '', $save_rel = false, $lang = '', $litmit = 1000, $page = 1)
    {
        if (empty($post_id) || empty($posttype) || empty($posttype_rel) || empty($lang)) {
            return []; // Return empty array if missing parameters
        }
        $table = 'fast_posts_' . $posttype_rel . '_' . $lang;
        if(empty($lang) || $lang === 'all') {
            $table = 'fast_posts_' . $posttype_rel;
        } else {
            $table = 'fast_posts_' . $posttype_rel . '_' . $lang;
        }
        
        $tablerel = $save_rel
            ? 'fast_posts_' . $posttype . '_rel'
            : 'fast_posts_' . $posttype_rel . '_rel';
        $selectby = $save_rel ? 'post_rel_id' : 'post_id';
        $whereby = $save_rel ? 'post_id' : 'post_rel_id';
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $table) || !preg_match('/^[a-zA-Z0-9_-]+$/', $tablerel)) {
            throw new \InvalidArgumentException('Table name not valid');
        }
        $limit = $litmit;
        $offset = ($page - 1) * $limit;
        $sql = "SELECT * FROM {$table} WHERE id IN ( SELECT {$selectby} FROM {$tablerel} WHERE {$whereby} = ? ) LIMIT $limit OFFSET {$offset};";
        try {
            $result = $this->query($sql, [$post_id]);
            return $result;
        } catch (\PDOException $e) {
            error_log("Database error in Postrel->getPosts: " . $e->getMessage());
            return 1;
        } catch (\Exception $e) {

            error_log("Error in Postrel->getPosts: " . $e->getMessage());
            return null;
        }
    }
    
    public function countPosts($post_id = '', $posttype = '', $posttype_rel = '', $save_rel = false, $lang = '')
{


    // Validate required parameters
    if (empty($post_id) || empty($posttype) || empty($posttype_rel) || empty($lang)) {
        return 0; // Return 0 if missing parameters
    }

    // Determine table names based on parameters
    if(empty($lang) || $lang === 'all') {
        $table = 'fast_posts_' . $posttype_rel;
    } else {
        $table = 'fast_posts_' . $posttype_rel . '_' . $lang;
    }
    $tablerel = $save_rel 
        ? 'fast_posts_' . $posttype . '_rel' 
        : 'fast_posts_' . $posttype_rel . '_rel';
    
    // Determine fields to query
    $selectby = $save_rel ? 'post_rel_id' : 'post_id';
    $whereby = $save_rel ? 'post_id' : 'post_rel_id';

    // Validate table names to prevent SQL injection
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $table) || !preg_match('/^[a-zA-Z0-9_-]+$/', $tablerel)) {
        throw new \InvalidArgumentException('Table name not valid');
    }

    // Query to count number of relationships
    $sql = "SELECT COUNT(*) as count FROM {$table} WHERE id IN ( SELECT {$selectby} FROM {$tablerel} WHERE {$whereby} = ? );";

    try {
        $result = $this->query($sql, [$post_id]);
        return isset($result[0]['count']) ? (int)$result[0]['count'] : 0;
    } catch (\PDOException $e) {
        error_log("Database error in Postrel->countPosts: " . $e->getMessage());
        return 0;
    } catch (\Exception $e) {
        error_log("Error in Postrel->countPosts: " . $e->getMessage());
        return 0;
    }
}

public function getChapter($posttype, $post_id, $index, $lang = APP_LANG)
{
    // Check required parameters
    if (empty($posttype) || empty($post_id) || empty($index) || empty($lang)) {
        return null; // Return null if missing parameters
    }

    // Determine relationship table and chapter table names
    $table_rel  = 'fast_posts_' . $posttype . '_chapter_rel';
    $table_chap = 'fast_posts_' . $posttype . '_chapter_' . $lang;
    // Check table name security (prevent SQL injection)
    if (
        !preg_match('/^[a-zA-Z0-9_-]+$/', $table_rel) ||
        !preg_match('/^[a-zA-Z0-9_-]+$/', $table_chap)
    ) {
        throw new \InvalidArgumentException('Table name not valid');
    }

    // SQL:
    // 1) Find all chapter ids in relationship table with post_id = $post_id
    // 2) Filter further in chapter table with condition `id` in ids from step 1
    //    and `index` = $index (episode number to find)
    // 3) Get 1 record (LIMIT 1) because each index usually corresponds to 1 unique episode
    $sql = "SELECT *
            FROM {$table_chap}
            WHERE id IN (
                SELECT post_id
                FROM {$table_rel}
                WHERE post_rel_id = ?
            )
            AND `index` = ?
            LIMIT 1;";
    try {
        // Execute query, pass 2 parameters: post_id and index (episode number)
        $result = $this->query($sql, [$post_id, $index]);

        // Check and return first record if exists
        return isset($result[0]) ? $result[0] : null;
    } catch (\PDOException $e) {
        // Log if PDO error
        error_log("Database error in Postrel->getChapter: " . $e->getMessage());
        return null;
    } catch (\Exception $e) {
        // Log if other error
        error_log("Error in Postrel->getChapter: " . $e->getMessage());
        return null;
    }
}

public function getListChapter($posttype, $post_id, $lang = APP_LANG)
{
    // Check required parameters
    if (empty($posttype) || empty($post_id) || empty($lang)) {
        return null; // Return null if missing parameters
    }

    // Determine relationship table and chapter table names
    $table_rel  = 'fast_posts_' . $posttype . '_chapter_rel';
    $table_chap = 'fast_posts_' . $posttype . '_chapter_' . $lang;
    // Check table name security (prevent SQL injection)
    if (
        !preg_match('/^[a-zA-Z0-9_-]+$/', $table_rel) ||
        !preg_match('/^[a-zA-Z0-9_-]+$/', $table_chap)
    ) {
        throw new \InvalidArgumentException('Table name not valid');
    }

    // SQL:
    // 1) Find all chapter ids in relationship table with post_id = $post_id
    // 2) Filter further in chapter table with condition `id` in ids from step 1
    //    and `index` = $index (episode number to find)
    // 3) Get 1 record (LIMIT 1) because each index usually corresponds to 1 unique episode
    $sql = "SELECT *
            FROM {$table_chap}
            WHERE id IN (
                SELECT post_id
                FROM {$table_rel}
                WHERE post_rel_id = ?
            );";
    try {
        // Execute query, pass 2 parameters: post_id and index (episode number)
        $result = $this->query($sql, [$post_id]);

        // Check and return first record if exists
        return $result;
    } catch (\PDOException $e) {
        // Log if PDO error
        error_log("Database error in Postrel->getChapter: " . $e->getMessage());
        return null;
    } catch (\Exception $e) {
        // Log if other error
        error_log("Error in Postrel->getChapter: " . $e->getMessage());
        return null;
    }
}

public function getListChapterPanigation($posttype, $post_id, $lang = APP_LANG, $paged = 1, $limit = 10)
{
    // Check required parameters
    if (empty($posttype) || empty($post_id) || empty($lang)) {
        return null; // Return null if missing parameters
    }

    // Determine relationship table and chapter table names
    $table_rel  = 'fast_posts_' . $posttype . '_chapter_rel';
    $table_chap = 'fast_posts_' . $posttype . '_chapter_' . $lang;
    
    // Check table name security (prevent SQL injection)
    if (
        !preg_match('/^[a-zA-Z0-9_-]+$/', $table_rel) ||
        !preg_match('/^[a-zA-Z0-9_-]+$/', $table_chap)
    ) {
        throw new \InvalidArgumentException('Table name not valid');
    }

    // Calculate offset and limit
    $offset = ($paged - 1) * $limit;
    $queryLimit = $limit + 1; // Get an extra record to determine next page

    // SQL: Get chapter records with id in relationship table
    $sql = "SELECT *
            FROM {$table_chap}
            WHERE id IN (
                SELECT post_id
                FROM {$table_rel}
                WHERE post_rel_id = ?
            )
            LIMIT {$offset}, {$queryLimit}";
    try {
        // Execute query, pass parameter post_id
        $result = $this->query($sql, [$post_id]);

        // Determine if there is next page
        $is_next = false;
        if (count($result) > $limit) {
            $is_next = true;
            // Remove extra record
            array_pop($result);
        } else {
            $is_next = false;
        }

        return [
            'data'    => $result,
            'is_next' => $is_next,
            'paged'   => $paged
        ];
    } catch (\PDOException $e) {
        error_log("Database error in Postrel->getListChapterPanigation: " . $e->getMessage());
        return null;
    } catch (\Exception $e) {
        error_log("Error in Postrel->getListChapterPanigation: " . $e->getMessage());
        return null;
    }
}

    // Get fast_term movie id
    public function getTagsMovie($post_id)
    {
        // Check required parameters
        if (empty($post_id)) {
            return null; // Return null if missing parameters
        }
        $query = "SELECT rel_id FROM fast_posts_movie_rel WHERE post_id=? AND lang=? OR lang = 'all'";
        return $this->query($query, [$post_id, APP_LANG]);
    }

}