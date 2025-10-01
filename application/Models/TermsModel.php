<?php
namespace App\Models;
use System\Core\BaseModel;

class TermsModel extends BaseModel {

    protected $table = APP_PREFIX.'terms';

    // Columns that are fillable (can be added or modified)
    protected $fillable = ['name', 'slug', 'description', 'seo_title', 'seo_desc', 'type', 'posttype', 'parent', 'lang', 'id_main', 'status'];

    // Columns that are guarded (cannot be modified)
    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * Define the table schema
     * 
     * @return array Table schema
     */
    public function _schema() {
        return [
            'id' => [
                'type' => 'int unsigned',
                'auto_increment' => true,
                'key' => 'primary',
                'null' => false
            ],
            'name' => [
                'type' => 'varchar(150)',
                'null' => false,
                'default' => ''
            ],
            'slug' => [
                'type' => 'varchar(150)',
                'null' => false,
            ],
            'description' => [
                'type' => 'text',
                'null' => true
            ],
            'posttype' => [
                'type' => 'varchar(50)',
                'null' => true
            ],
            'seo_title' => [
                'type' => 'varchar(50)',
                'null' => true
            ],
            'seo_desc' => [
                'type' => 'text',
                'null' => true
            ],
            'type' => [
                'type' => 'varchar(50)',
                'null' => false,
                'default' => 'category'
            ],  
            'parent' => [
                'type' => 'int(10) unsigned',
                'null' => true
            ],
            'lang' => [
                'type' => 'varchar(3)',
                'null' => true
            ],
            'id_main' => [
                'type' => 'int unsigned',
                'null' => true,
                'default' => 0
            ],
            'status' => [
                'type' => 'enum(\'active\', \'inactive\')',
                'null' => false,
                'default' => 'active'
            ],
            'created_at' => [
                'type' => 'datetime',
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP'
            ],
            'updated_at' => [
                'type' => 'datetime',
                'null' => true
            ]
        ];
    }

    /**
     * Get all terms
     */
    public function getTerms($posttype, $type, $lang) {
        return $this->list($this->table, 'lang = ? AND posttype = ? AND type = ?', [$lang, $posttype, $type]);
    }  

    /**
     * Get terms by query
     */
    public function getTermsFieldsPagination($fields = '*', $where = '', $params = [], $orderBy = 'id desc', $page = 1, $limit = null)
    {
        return $this->fetchPaginationWithField($this->table, $fields, $where, $params, $orderBy, $page, $limit);
    }

    /**
     * Get all taxonomies
     */
    public function getTaxonomies($where = '', $params = [], $orderBy = 'id desc', $limit = null, $offset = null) {
        return $this->list($this->table, $where, $params, $orderBy, $limit, $offset);
    }

    /**
     * Get a single term by ID
     */
    public function getTermById($id) {
        return $this->row($this->table, 'id = ?', [$id]);
    }

    /**
     * Get a single term by slug
     */
    public function getTermBySlug($slug) {
        return $this->row($this->table, 'slug = ?', [$slug]);
    }
    /**
     * Get list term by parentd
     */
    public function getTermByParent($parent_id) {
        return $this->list($this->table, 'parent = ?', [$parent_id]);
    }
    /**
     * Get taxonomies by type (e.g., category or tag)
     */
    public function getTermsByType($type, $orderBy = 'id desc') {
        return $this->list($this->table, 'type = ?', [$type], $orderBy);
    }
    /**
     * Get taxonomies by type end posttype
     */
    public function getTermsByTypeAndPostType($posttype, $type) {
        return $this->list($this->table, 'type = ? AND posttype = ?', [$type, $posttype]);
    }  
    public function getTermsByTypeAndPostTypeAndLang($posttype, $type, $lang) {
        return $this->list($this->table, 'type = ? AND posttype = ? AND lang = ?', [$type, $posttype, $lang]);
    } 
    public function getTermsSlugAndByTypeAndPostTypeAndLang($slug, $posttype, $type, $lang) {
        return $this->list($this->table, 'slug = ? AND type = ? AND posttype = ? AND lang = ?', [$slug, $type, $posttype, $lang]);
    } 
    /**
     * Add a new term
     */
    public function addTerm($data) {
        $data = $this->fill($data);
        return $this->add($this->table, $data);
    }

    /**
     * Update an existing term
     */
    public function setTerm($id, $data) {
        $data = $this->fill($data);
        return $this->set($this->table, $data, 'id = ?', [$id]);
    }

    /**
     * Delete a term
     */
    public function delTerm($id) {
        return $this->del($this->table, 'id = ?', [$id]);
    }

    /**
     * Delete a all term by posttype
     */
    public function delTermByPostType($posttype) {
        return $this->del($this->table, 'posttype = ?', [$posttype]);
    }

    /**
     * Delete a all term by type
     */
    public function delTermByType($type) {
        return $this->del($this->table, 'type = ?', [$type]);
    }

    /**
     * Delete a all term by posttype and lang
     */
    public function delTermByPostTypeAndLang($posttype, $lang) {
        return $this->del($this->table, 'posttype = ? AND lang = ?', [$posttype, $lang]);
    }

    /**
     * Change type
     */
    public function updateTermType($oldType, $newType) {
        $data = ['type' => $newType];
        $where = 'type = ?';
        $params = [$oldType];
    
        return $this->set($this->table, $data, $where, $params);
    }

     /**
     * Change possttype
     */
    public function updateTermPostType($oldType, $newType) {
        $data = ['posttype' => $newType];
        $where = 'posttype = ?';
        $params = [$oldType];
    
        return $this->set($this->table, $data, $where, $params);
    }
    
    
    /**
     * Get posts by term without using JOIN
     */
    public function getPostsByTerm($termId, $postTable = 'posts', $termRelationshipTable = 'post_term_relationships') {
        // Step 1: Get all post IDs related to the term
        $relationships = $this->list($termRelationshipTable, 'rel_id = ?', [$termId]);
        $postIds = array_column($relationships, 'post_id');

        if (empty($postIds)) {
            return [];
        }

        // Step 2: Get posts by IDs
        $placeholders = implode(',', array_fill(0, count($postIds), '?'));
        return $this->list($postTable, "id IN ({$placeholders})", $postIds);
    }

    /**
     * Get a single term by slug
     */
    public function getTermBySlugAndPostType($posttype, $slug)
    {
        return $this->row($this->table, 'slug = ? AND posttype = ?', [$slug, $posttype]);
    }
    
    // get term by ids and type
    public function getTermsByIdsAndType($ids, $type, $lang = APP_LANG)
    {
        $query = "SELECT name from {$this->table} WHERE id IN (" . implode(',', $ids) . ") AND type =? AND lang=?";
        return $this->query($query, [$type, $lang]);
    }

    // get all term by id_main
    public function getTermByIdMain($id_main)
    {
        return $this->list($this->table, 'id_main = ?', [$id_main]);
    }
}