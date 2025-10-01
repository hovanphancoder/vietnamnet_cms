<?php
namespace App\Models;
use System\Core\BaseModel;

class FilesModel extends BaseModel {

    protected $table = APP_PREFIX.'files';

    // Columns that are fillable (can be added or modified)
    protected $fillable = ['name', 'path', 'type', 'size', 'resize' ,  'autoclean', 'user_id' , 'created_at', 'updated_at'];
    // Columns that are guarded (cannot be modified)
    protected $guarded = ['id'];

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
            'path' => [
                'type' => 'varchar(255)',
                'null' => false,
                'default' => ''
            ],
            'type' => [
                'type' => 'varchar(50)',
                'null' => false,
                'default' => ''
            ],
            'size' => [
                'type' => 'bigint(20)',
                'null' => false,
                'default' => 0
            ],
            'autoclean' => [
                'type' => 'tinyint(1)',
                'null' => false,
                'default' => 0
            ],
            'user_id' => [
                'type' => 'int unsigned',
                'null' => false,
                'default' => 0,
            ],
            'post_used' => [
                'type' => 'varchar(255)',
                'null' => true,
                'default' => ''
            ],
            'created_at' => [
                'type' => 'datetime',
                'null' => false,
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

    /**
     * Fetches paginated files from the database with optional conditions and ordering
     *
     * @param string $where
     * @param array $params
     * @param string $orderBy
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function getFiles($where = '', $params = [], $orderBy = 'id desc', $page = 1, $limit = null) {
        return $this->listpaging($this->table, $where, $params, $orderBy, $page, $limit);
    }

    /**
     * Get file by ID
     *
     * @param int $id
     * @return array|false
     */
    public function getFileById($id) {
        return $this->row($this->table, 'id = ?', [$id]);
    }

    public function getFileByPath($path) {
        return $this->row($this->table, 'path = ?', [$path]);
    }

    /**
     * Add a new file
     *
     * @param array $data
     * @return bool
     */
    public function addFile($data) {
        $data = $this->fill($data);
        return $this->add($this->table, $data);
    }

    /**
     * Update file information
     *
     * @param int $id
     * @param array $data
     * @return int
     */
    public function updateFile($id, $data) {
        $data = $this->fill($data);
        return $this->set($this->table, $data, 'id = ?', [$id]);
    }

    /**
     * Delete a file
     *
     * @param int $id
     * @return int
     */
    public function deleteFile($id) {
        return $this->del($this->table, 'id = ?', [$id]);
    }

    // xoi wrote poorly

    public function replacePath($oldPath, $newPath) {        

        // SQL statement structure to update path
        $sql = "UPDATE {$this->table} 
                SET path = CONCAT(?, SUBSTRING(path, LENGTH(?) + 1)) 
                WHERE path LIKE ?";

        // Parameters for SQL statement
        $params = [
            $newPath,          // New value to concatenate to path
            $oldPath,          // oldPath value to calculate length
            $oldPath . '%'     // Condition to find paths starting with oldPath
        ];

        // Execute SQL statement
        $affectedRows = $this->execute($sql, $params);

        // Check if any records were updated
        if ($affectedRows === 0) {
            return ['error', 'No files found with the specified old path prefix.'];
        }

        // Success message
        return ['success', "Successfully replaced paths from '{$oldPath}' to '{$newPath}'. Total records updated: {$affectedRows}."];
    }
}