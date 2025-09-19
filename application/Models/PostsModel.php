<?php

namespace App\Models;

use System\Core\BaseModel;

class PostsModel extends BaseModel
{
    protected $table;
    public $connected = false;

    public function __construct($posttype = '', $lang = null)
    {
        $this->table = posttype_name($posttype, $lang);
        if (!empty($this->table)){
            $this->connected = true;
        }
        parent::__construct();
    }

    public function getPostsLists($where = '', $params = [], $orderBy = 'id desc', $page = 1, $limit = null)
    {
        return $this->list($this->table, $where, $params, $orderBy, $page, $limit);
    }

    public function getPostsFieldsLists($fields = '*', $where = '', $params = [], $orderBy = 'id desc', $page = 1, $limit = null)
    {
        return $this->listfield($this->table, $fields, $where, $params, $orderBy, $page, $limit);
    }

    public function getPostsFieldsPagination($fields = '*', $where = '', $params = [], $orderBy = 'id desc', $page = 1, $limit = null)
    {
        return $this->fetchPaginationWithField($this->table, $fields, $where, $params, $orderBy, $page, $limit);
    }


    public function getRow($tableName, $where = '', $params = [])
    {
        return $this->row($tableName, $where, $params);
    }

    public function getRowTable($where = '', $params = [])
    {
        return $this->row($this->table, $where, $params);
    }

    public function countPosts($where = '', $params = [])
    {
        if (empty($this->table)) {
            return 0;
        }
        return $this->count($this->table, $where, $params);
    }

    public function getById($id, $fields = "*")
    {
        $sql = "SELECT $fields FROM {$this->table} WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function getField($field)
    {
        $sql = "SELECT $field FROM {$this->table} GROUP BY $field";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    public function getPostByQuery($tableName, $query)
    {
        $sql = "SELECT * FROM {$tableName} {$query} LIMIT 100";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    public function getPostBySlug($tableName, $slug)
    {
        $sql = "SELECT * FROM {$tableName} WHERE slug = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$slug]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function getPostById($tableName, $id, $fields = "*")
    {
        $sql = "SELECT $fields FROM {$tableName} WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
    public function getBySlug($slug)
    {
        $sql = "SELECT * FROM {$this->table} WHERE slug =?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$slug]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function addPost($tableName, $data)
    {
        return $this->add($tableName, $data);
    }

    public function insert($data)
    {
        return $this->add($this->table, $data);
    }

    public function editPostTable($id, $data)
    {
        return $this->set($this->table, $data, 'id = ?', [$id]);
    }

    public function editPost($tableName, $id, $data)
    {
        return $this->set($tableName, $data, 'id = ?', [$id]);
    }
    public function deletePost($tableName, $id)
    {
        return $this->del($tableName, 'id = ?', [$id]);
    }

    public function addLoaction($tableName, $id, $column, $location = []) {}

    public function getCurrentID(array $tablesName)
    {
        if (empty($tablesName)) {
            throw new \InvalidArgumentException("Array of table names cannot be empty.");
        }

        // Check validity of each table name
        foreach ($tablesName as $table) {
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
                throw new \InvalidArgumentException("Invalid table name: {$table}");
            }
        }

        // Create UNION ALL part for each table
        $unionQueries = [];
        foreach ($tablesName as $table) {
            $unionQueries[] = "SELECT '{$table}' AS table_name, MAX(id) AS max_id FROM {$table}";
        }

        // Combine query parts with UNION ALL
        $fullQuery = "WITH max_ids AS (
          " . implode("\n        UNION ALL\n        ", $unionQueries) . "
      )
      SELECT table_name, max_id
      FROM max_ids
      WHERE max_id IS NOT NULL
      ORDER BY max_id desc
      LIMIT 1;";

        try {
            $stmt = $this->db->prepare($fullQuery);
            $stmt->execute();
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($result) {
                return $result['max_id'];
            } else {
                return null; // No result found
            }
        } catch (\PDOException $e) {
            // Handle query error
            throw new \RuntimeException("Error executing query: " . $e->getMessage());
        }
    }

    public function duplicateLanguage($tablesName, $oldTableName, $id)
    {

        $sql = "START TRANSACTION;";

        foreach ($tablesName as $tableName) {
            $sql .= "    INSERT INTO $tableName \n";
            $sql .= "    SELECT * \n";
            $sql .= "    FROM $oldTableName \n";
            $sql .= "    WHERE id = $id; \n";
        }

        $sql .= "COMMIT;";
        return $this->query($sql);
    }

    public function getPostIdByTerm($posttype, $term_id = null, $lang = APP_LANG, $limit = 4)
    {
        $tableRel = "fast_posts_{$posttype}_rel";
        $termWhere = '';
        if ($term_id !== null) {
            $termWhere = "rel_id = {$term_id} AND";
        }
        $sql = "SELECT post_id 
            FROM {$tableRel} 
            WHERE  {$termWhere} (lang = '{$lang}' OR lang = 'all') ORDER BY updated_at DESC LIMIT {$limit}";
        return $this->query($sql);
    }

    public function getPostsByTerm($posttype, $term_id = null, $lang = APP_LANG, $limit = 4)
    {
        $postsTable    = $this->table;
        $termsRelTable = "fast_posts_{$posttype}_rel";
        $sql = "SELECT p.id, p.title, p.slug, p.feature, p.like_count, p.rating_avg, p.views, p.created_at, p.updated_at
            FROM {$postsTable} p
            INNER JOIN {$termsRelTable} r ON p.id = r.post_id
            WHERE ";
        $conditions = [];
        $params     = [];
        if ($term_id !== null) {
            $conditions[] = "r.rel_id = ?";
            $params[]     = $term_id;
        }
        $conditions[] = "(r.lang = ? OR r.lang = 'all')";
        $params[]     = $lang;
        $conditions[] = "p.status = ?";
        $params[]     = 'active';
        $sql .= implode(" AND ", $conditions);
        $sql .= " ORDER BY p.views DESC LIMIT " . (int)$limit;
        return $this->query($sql, $params);
    }

    // update add more fields (default is *), where, params, order, limit, offset
    public function getPostsByTermPagination(
        $posttype,
        $term_id = null,
        $fields = '*',
        $where = '',
        $params = [],
        $orderBy = 'views DESC',
        $lang = APP_LANG,
        $page = 1,
        $limit = 10
    ) {
        // Ensure page and limit are integers
        $page = (int)$page;
        $limit = (int)$limit;

        // Calculate offset based on current page
        $offset = ($page - 1) * $limit;

        // Get limit + 1 to check if there's next page
        $queryLimit = $limit + 1;

        // Check and set table name
        if (empty($this->table)) {
            if ($lang == 'all') {
                $this->table = "fast_posts_{$posttype}";
            } else {
                $this->table = "fast_posts_{$posttype}_{$lang}";
            }
        }

        // process field to p. e.g. * becomes p.*. 'link, id' becomes 'p.link, p.id'
        $fields = str_replace('*', 'p.*', $fields);
        $fields = str_replace(',', ', p.', $fields);
        // Main query to get data
        $sql = "SELECT {$fields}
            FROM {$this->table} p
            INNER JOIN fast_posts_{$posttype}_rel rel ON p.id = rel.post_id
            WHERE ";
        $conditions = [];
        $queryParams = [];

        // Add term_id condition if exists
        if ($term_id !== null) {
            $conditions[] = "rel.rel_id = ?";
            $queryParams[] = $term_id;
        }

        // Add language condition
        $conditions[] = "(rel.lang = ? OR rel.lang = 'all')";
        $queryParams[] = $lang;

        // Add status condition
        $conditions[] = "p.status = ?";
        $queryParams[] = 'active';

        // Add where condition if exists
        if (!empty($where)) {
            $conditions[] = $where;
            // Ensure $params is array before merge
            if (!is_array($params)) {
                $params = [$params];
            }
            $queryParams = array_merge($queryParams, $params);
        }

        $sql .= implode(" AND ", $conditions);
        $sql .= " ORDER BY p.{$orderBy} LIMIT {$queryLimit} OFFSET {$offset}";
        $posts = $this->query($sql, $queryParams);

        // Check if there's next page
        $hasNext = count($posts) > $limit;

        // If there's next page, remove extra element
        if ($hasNext) {
            array_pop($posts);
        }

        return [
            'data' => $posts,
            'is_next' => $hasNext,
            'page' => $page
        ];
    }

    public function getPostIdByTerms($posttype, $terms = [], $lang = APP_LANG, $limit = 50)
    {
        if (empty($terms)) {
            return [];
        }
        $term_id = implode(',', array_map('intval', $terms));
        $tableRel = "fast_posts_{$posttype}_rel";
        $sql = "SELECT post_id 
            FROM {$tableRel} 
            WHERE rel_id IN ({$term_id}) AND (lang = '{$lang}' OR lang = 'all') 
            GROUP BY post_id 
            ORDER BY updated_at DESC
            LIMIT {$limit}";

        $result = $this->query($sql);
        // convert to list ids
        $post_ids = [];
        if (!empty($result)) {
            foreach ($result as $item) {
                $post_ids[] = $item['post_id'];
            }
        }
        return $post_ids;
    }

    public function getPostTermsByPostId($posttype, $post_id, $lang = APP_LANG)
    {
        $table = "fast_posts_{$posttype}_rel";
        $query = "SELECT * FROM fast_terms WHERE lang =? AND id_main IN (SELECT rel_id FROM {$table} WHERE post_id= ? AND (lang='all' OR lang= ? )) LIMIT 999;";
        $params = [$lang, $post_id, $lang];
        return $this->query($query, $params);
    }

    public function getTermsbyPostID($tableName, $post_id)
    {
        $sql = "SELECT `rel_id` FROM `$tableName` WHERE `post_id`= $post_id";
        return $this->query($sql);
    }
    public function getTermsbyPostIDAndLang($tableName, $post_id, $lang)
    {
        $sql = "SELECT `rel_id` 
            FROM `$tableName` 
            WHERE `post_id` = ? 
              AND (`lang` = ? OR `lang` = 'all') AND `rel_id` IS NOT NULL";

        return $this->query($sql, [$post_id, $lang]);
    }

    public function createRelationship($tableName, $post_id, $term_id, $lang)
    {
        $sql = "INSERT INTO `$tableName` (`post_id`, `rel_id`, `lang`) VALUES ("
            . intval($post_id) . ", "
            . intval($term_id) . ", '"
            . $lang . "')";
        return $this->query($sql);
    }

    public function addReferenceRelationship($tableName, $post_id, $postype_slug, $field_id,  $post_rel_id, $lang)
    {
        $sql = "INSERT INTO `$tableName` (`post_id`, `postype_slug`, `post_rel_id`, `field_id`, `lang`) VALUES ("
            . intval($post_id) . ", '"
            . $postype_slug . "', "
            . intval($post_rel_id) . ", "
            . intval($field_id) . ", '"
            . $lang . "')";
        return $this->query($sql);
    }

    public function deleteReferenceRelationship(
        $tableName,
        $post_id = null,
        $postype_slug = null,
        $field_id = null,
        $post_rel_id = null,
        $lang = null
    ) {
        $conditions = [];
        $params = [];

        // Build conditions dynamically based on non-null parameters
        if ($post_id !== null) {
            $conditions[] = "post_id = :post_id";
            $params[':post_id'] = $post_id;
        }

        if ($postype_slug !== null) {
            $conditions[] = "postype_slug = :postype_slug";
            $params[':postype_slug'] = $postype_slug;
        }

        if ($field_id !== null) {
            $conditions[] = "field_id = :field_id";
            $params[':field_id'] = $field_id;
        }

        if ($post_rel_id !== null) {
            $conditions[] = "post_rel_id = :post_rel_id";
            $params[':post_rel_id'] = $post_rel_id;
        }

        if ($lang !== null) {
            $conditions[] = "lang = :lang";
            $params[':lang'] = $lang;
        }

        // Construct the SQL query
        $sql = "DELETE FROM `$tableName`";
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        // Prepare and execute the query
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $type = is_int($value) ? \PDO::PARAM_INT : \PDO::PARAM_STR;
            $stmt->bindValue($key, $value, $type);
        }

        return $stmt->execute();
    }
    public function getAllPostIdByRenference($tableName, $postype_slug, $field_id, $post_rel_id, $lang = null, $allLang = true)
    {
        // Create basic query condition
        $query = "WHERE postype_slug = :postype_slug 
              AND field_id = :field_id 
              AND post_rel_id = :post_rel_id";

        // Add lang condition if specified
        if ($lang !== null) {
            if ($allLang) {
                $query .= " AND (lang = :lang OR lang = 'all')";
            } else {
                $query .= " AND lang = :lang";
            }
        }

        // Create SQL statement - only get post_id
        $sql = "SELECT post_id FROM {$tableName} {$query}";
        $stmt = $this->db->prepare($sql);

        // Assign parameter values
        $stmt->bindParam(':postype_slug', $postype_slug, \PDO::PARAM_STR);
        $stmt->bindParam(':field_id', $field_id, \PDO::PARAM_INT);
        $stmt->bindParam(':post_rel_id', $post_rel_id, \PDO::PARAM_INT);

        if ($lang !== null) {
            $stmt->bindParam(':lang', $lang, \PDO::PARAM_STR);
        }

        // Execute and return result
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_COLUMN); // Return array of post_ids
    }

    public function getAllRelPostByPostId($tableName, $postype_slug, $field_id, $post_id, $lang = null, $allLang = true)
    {
        // Create basic query condition
        $query = "WHERE post_id = :post_id 
              AND postype_slug = :postype_slug 
              AND field_id = :field_id";

        // Add lang condition if specified
        if ($lang !== null) {
            if ($allLang) {
                $query .= " AND (lang = :lang OR lang = 'all')";
            } else {
                $query .= " AND lang = :lang";
            }
        }
        // Create SQL statement - modify to only get post_rel_id
        $sql = "SELECT post_rel_id FROM {$tableName} {$query}";
        $stmt = $this->db->prepare($sql);

        // Assign parameter values
        $stmt->bindParam(':post_id', $post_id, \PDO::PARAM_INT);
        $stmt->bindParam(':postype_slug', $postype_slug, \PDO::PARAM_STR);
        $stmt->bindParam(':field_id', $field_id, \PDO::PARAM_INT);

        if ($lang !== null) {
            $stmt->bindParam(':lang', $lang, \PDO::PARAM_STR);
        }

        // Execute and return result
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_COLUMN); // Return array of post_rel_ids
    }

    public function removeTerms($tableName, $post_id, $term_id)
    {
        $sql = "DELETE FROM `$tableName` WHERE `post_id` = $post_id AND `rel_id` = $term_id";
        return $this->query($sql);
    }

    public function removeTermRelsbyPost($tableName, $post_id)
    {
        $sql = "DELETE FROM `$tableName` WHERE `post_id` = $post_id AND `rel_id` IS NOT NULL";
        return $this->query($sql);
    }
    public function removeTermRelsbyPostAndLang($tableName, $post_id, $lang)
    {
        $sql = "DELETE FROM `$tableName` WHERE `post_id` = $post_id AND `lang` = '$lang' AND `rel_id` IS NOT NULL";
        return $this->query($sql);
    }
    public function checkPosttypeExists()
    {
        $query = "SHOW TABLES LIKE '{$this->table}'";
        $result = $this->db->query($query);
        return $result && count($result) > 0;
    }
    public function getPostByIdTable($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }


}
