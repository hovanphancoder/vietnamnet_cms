<?php
namespace App\Models;
use System\Core\BaseModel;
use System\Libraries\Logger;

class OptionsModel extends BaseModel {

    protected $table = APP_PREFIX.'options';

    // Columns that are fillable (can be added or modified)
    protected $fillable = ['label', 'type', 'name', 'description', 'status', 'value', 'valuelang', 'optional'];

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
                'type' => 'int(10) unsigned',
                'auto_increment' => true,
                'key' => 'primary',
                'null' => false
            ],
            'label' => [
                'type' => 'varchar(100)',
                'default' => '',
                'null' => false
            ],
            'type' => [
                'type' => 'varchar(100)',
                'default' => '',
                'null' => false
            ],
            'name' => [
                'type' => 'varchar(100)',
                'default' => '',
                'null' => false
            ],
            'description' => [
                'type' => 'varchar(255)',
                'default' => '',
                'null' => false
            ],
            'status' => [
                'type' => 'enum(\'active\', \'inactive\')',
                'null' => false,
                'default' => 'active'
            ],
            'value' => [
                'type' => 'text',
                'null' => false
            ],
            'valuelang' => [
                'type' => 'longtext',
                'null' => false
            ],
            'optional' => [
                'type' => 'text',
                'null' => true
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
            ],
        ];
    }

    public function getByName($name = '') {
        try {
            if (empty($name) || !is_string($name)) {
                return false;
            }
            return $this->row($this->table, 'name = ?', [$name]);
        } catch (\PDOException $e) {
            Logger::error("Database error in OptionsModel->getByName: " . $e->getMessage(), $e->getFile(), $e->getLine());
            return null;
        } catch (\Exception $e) {
            Logger::error("Error in OptionsModel->getByName: " . $e->getMessage(), $e->getFile(), $e->getLine());
            return null;
        }
    }
    public function getById($id = 0) {
        try {
            if (empty($id) || $id <= 0) {
                return false;
            }
            return $this->row($this->table, 'id = ?', [$id]);
        } catch (\PDOException $e) {
            Logger::error("Database error in OptionsModel->getById: " . $e->getMessage(), $e->getFile(), $e->getLine());
            return null;
        } catch (\Exception $e) {
            Logger::error("Error in OptionsModel->getById: " . $e->getMessage(), $e->getFile(), $e->getLine());
            return null;
        }
    }

    /**
     * Get all records
     */
    public function getOptions($where = '', $params = [], $orderBy = 'id desc', $limit = null, $offset = null) {
        return $this->list($this->table, $where, $params, $orderBy, $limit, $offset);
    }

    /**
     * Add a new record
     */
    public function addOptions($data) {
        $data = $this->fill($data);
        return $this->add($this->table, $data);
    }

    /**
     * Update an existing record
     */
    public function setOptions($id, $data) {
        $data = $this->fill($data);
        return $this->set($this->table, $data, 'id = ?', [$id]);
    }

    public function setOptionbyMame($name, $data) {
        $data = $this->fill($data);
        return $this->set($this->table, $data, 'name = ?', [$name]);
    }

    public function setValueByName($data) {
        if (empty($data) || !is_array($data)) {
            return false; // Return false if no data
        }
    
        // Start SQL statement
        $query = "UPDATE $this->table SET value = CASE";
        $params = [];
    
        // Add WHEN ... THEN conditions
        foreach ($data as $name => $value) {
            $query .= " WHEN name = ? THEN ?";
            $params[] = $name;  // Add name to params
            $params[] = $value; // Add value to params
        }
    
        // End CASE statement and add WHERE condition
        $placeholders = implode(',', array_fill(0, count($data), '?')); // Placeholder for IN clause
        $query .= " END WHERE name IN ($placeholders);";
    
        // Add name values to params for WHERE clause
        $params = array_merge($params, array_keys($data));
        // Execute SQL statement with query and params
        return $this->query($query, $params);
    } 
    public function setValuelangByName($data) {
        if (empty($data) || !is_array($data)) {
            return false; // Return false if no data
        }
    
        // Start SQL statement
        $query = "UPDATE $this->table SET valuelang = CASE";
        $params = [];
    
        // Add WHEN ... THEN conditions
        foreach ($data as $name => $value) {
            $value = json_encode($value);
            $query .= " WHEN name = ? THEN ?";
            $params[] = $name;  // Add name to params
            $params[] = $value; // Add value to params
        }
    
        // End CASE statement and add WHERE condition
        $placeholders = implode(',', array_fill(0, count($data), '?')); // Placeholder for IN clause
        $query .= " END WHERE name IN ($placeholders);";
        // Add name values to params for WHERE clause
        $params = array_merge($params, array_keys($data));
        // Execute SQL statement with query and params
        return $this->query($query, $params);
    } 

    

    /**
     * Delete a record
     */
    public function delOptions($id) {
        return $this->del($this->table, 'id = ?', [$id]);
    }
}