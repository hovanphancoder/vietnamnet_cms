<?php
namespace App\Models;

use System\Core\BaseModel;

class LanguagesModel extends BaseModel
{
    protected $table = 'fast_languages';

    // Columns that are allowed to be added or modified
    protected $fillable = [ 'name', 'code', 'flag', 'is_default', 'status'];

    // Columns that are not allowed to be modified
    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * Define table structure with schema builder
     * 
     * @return array Table structure
     */
    public function _schema()
    {
        return [
            'id' => [
                'type' => 'int unsigned',
                'auto_increment' => true,
                'key' => 'primary',
                'null' => false
            ],
            'code' => [
                'type' => 'varchar(2)',
                'key' => 'unique',
                'null' => false
            ],
            'name' => [
                'type' => 'varchar(100)',
                'null' => false
            ],
            'flag' => [
                'type' => 'varchar(2)',
                'null' => true,
                'default' => null
            ],
            'is_default' => [
                'type' => 'tinyint(1)',
                'null' => false,
                'default' => 0
            ],
            'status' => [
                'type' => 'enum(\'active\', \'inactive\')',
                'null' => false,
                'default' => 'active'
            ],
            'created_at' => [
                'type' => 'datetime',
                'null' => true,
                'default' => 'CURRENT_TIMESTAMP'
            ],
            'updated_at' => [
                'type' => 'datetime',
                'null' => true,
                'default' => 'CURRENT_TIMESTAMP',
                'on_update' => 'CURRENT_TIMESTAMP'
            ]
        ];
    }
    public function getLanguagesFieldsPagination($fields = '*', $where = '', $params = [], $orderBy = 'id desc', $page = 1, $limit = null)
    {
        return $this->fetchPaginationWithField($this->table, $fields, $where, $params, $orderBy, $page, $limit);
    }
    /**
     * Get list of languages
     */
    public function getAllLanguages()
    {
        return $this->list($this->table);
    }
    /**
     * Get list of active languages (not inactive)
     */
    public function getActiveLanguages()
    {
        return $this->list($this->table, 'status = ?', ['active']);
    }
    
    /**
     * Get default language
     */
    public function getDefaultLanguage()
    {
        return $this->row($this->table, 'is_default = ?', [1]);
    }

    /**
     * Get default language code
     */
    public function getDefaultLanguageCode()
    {
        $lang = $this->row($this->table, 'is_default = ?', [1]);
        if (!empty($lang) && !empty($lang['code'])) {
            return $lang['code'];
        }
        return null;
    }
    
    /**
     * Get language by ID
     */
    public function getLanguageById($id)
    {
        return $this->row($this->table, 'id = ?', [$id]);
    }

    /**
     * Get language by Code
     */
    public function getLanguageByCode($code)
    {
        return $this->row($this->table, 'code = ?', [$code]);
    }

    /**
     * Set all languages to not default
     */
    public function unsetDefaultLanguage()
    {
        return $this->set($this->table, ['is_default' => 0], 'is_default = 1');
    }


    /**
     * Add language
     */
    public function addLanguage($data) {
        try {
            // Process input data before adding to database
            $data = $this->fill($data); 
            
            // Execute adding data to table, catch errors if occur
            $result = $this->add($this->table, $data);
            return [
                'success' => true,
                'id' => $result
            ];
        } catch (\PDOException $e) {
            // Return error message to user
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Edit language
     */
    public function setLanguage($id, $data) {
        try {
            // Process input data before adding to database
            $data = $this->fill($data); 
            
            // Execute editing data in table, catch errors if occur
            $result = $this->set($this->table, $data, 'id = ?', [$id]);
            return [
                'success' => true,
                'id' => $result
            ];
        } catch (\PDOException $e) {
            // Return error message to user
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Delete language
     */
    public function deleteLanguage($ids) {
        return $this->del($this->table, 'id IN (?)', [implode(',', $ids)]);
    }
}
