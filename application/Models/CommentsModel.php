<?php
namespace App\Models;
use System\Core\BaseModel;

class CommentsModel extends BaseModel {

    protected $table = 'fast_comments';

    // Columns that are allowed to be added or modified
    protected $fillable = ['posttype', 'lang', 'post_id', 'parent', 'user_id', 'email', 'fullname', 'avatar', 'phone', 'whatsapp', 'telegram', 'skype', 'role', 'permissions', 'optional', 'status'];

    // Columns that are not allowed to be modified
    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * Define table structure with schema builder
     * 
     * @return array Table structure
     */
    public function _schema() {
        return [
            'id' => [
                'type' => 'int unsigned',
                'auto_increment' => true,
                'key' => 'primary',
                'null' => false
            ],
            'username' => [
                'type' => 'varchar(40)',
                'key' => 'unique',
                'null' => false
            ],
            'email' => [
                'type' => 'varchar(150)',
                'key' => 'unique',
                'null' => false
            ],
            'password' => [
                'type' => 'varchar(255)',
                'null' => false
            ],
            'fullname' => [
                'type' => 'varchar(150)',
                'null' => true,
                'default' => ''
            ],
            'avatar' => [
                'type' => 'varchar(255)',
                'null' => true,
                'default' => ''
            ],
            'phone' => [
                'type' => 'varchar(30)',
                'null' => true,
                'default' => ''
            ],
            'role' => [
                'type' => 'enum(\'admin\', \'moderator\', \'author\', \'member\')',
                'null' => false
            ],
            'permissions' => [
                'type' => 'json',
                'null' => true
            ],
            'optional' => [
                'type' => 'json',
                'null' => true
            ],
            'telegram' => [
                'type' => 'varchar(100)',
                'null' => true,
                'default' => ''
            ],
            'whatsapp' => [
                'type' => 'varchar(30)',
                'null' => true,
                'default' => ''
            ],
            'skype' => [
                'type' => 'varchar(100)',
                'null' => true,
                'default' => ''
            ],
            'status' => [
                'type' => 'enum(\'active\', \'inactive\', \'banned\')',
                'null' => true,
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

    /**
     * Get all users
     * 
     * @param string|null $where Query condition (optional)
     * @param array $params Array of values corresponding to condition string
     * @param string|null $orderBy Sort by column (optional)
     * @param int|null $limit Limit number of results (optional)
     * @return array List of users
     */
    public function getUsers($where = '', $params = [], $orderBy = 'id desc', $page = 1, $limit = null) {
        return $this->list($this->table, $where, $params, $orderBy, $page, $limit);
    }

    /**
     * Get all users with pagination
     * 
     * @param string|null $where Query condition (optional)
     * @param array $params Array of values corresponding to condition string
     * @param string|null $orderBy Sort by column (optional)
     * @param int|null $limit Limit number of results (optional)
     * @return array List of users
     */
    public function getUsersPage($where = '', $params = [], $orderBy = 'id desc', $page = 1, $limit = null) {
        return $this->listpaging($this->table, $where, $params, $orderBy, $page, $limit);
    }

    /**
     * Get user information by ID
     * 
     * @param int $id User ID
     * @return array|false User information or false if not found
     */
    public function getUserById($id)
    {
        return $this->row($this->table, 'id = ?', [$id]);
    }

    public function getUserByUsername($username)
    {
        return $this->row($this->table, 'username = ?', [$username]);
    }
    public function getUserByEmail($email)
    {
        return $this->row($this->table, 'email = ?', [$email]);
    }

    /**
     * Add new user
     * 
     * @param array $data User data to add
     * @return bool Success or failure
     */
    public function addUser($data) {
        $data = $this->fill($data); // Filter allowed data to add
        return $this->add($this->table, $data);
    }

    /**
     * Update user information
     * 
     * @param int $id User ID to update
     * @param array $data Data to update
     * @return int Number of affected rows
     */
    public function updateUser($id, $data) {
        $data = $this->fill($data); // Filter allowed data to modify
        return $this->set($this->table, $data, 'id = ?', [$id]);
    }

    /**
     * Delete user
     * 
     * @param int $id User ID to delete
     * @return int Number of affected rows
     */
    public function deleteUser($id) {
        return $this->del($this->table, 'id = ?', [$id]);
    }

    /**
     * Search users
     * 
     * @param $conditions field containing field and search character
     */
    public function searchUser($conditions = [])
    {
        $query = "SELECT * FROM " . $this->table;
        $params = [];

        if (!empty($conditions)) {
            $query .= " WHERE ";
            $whereClauses = [];

            foreach ($conditions as $field => $value) {
                $whereClauses[] = "$field LIKE ?";
                $params[] = '%' . $value . '%';
            }
            $query .= implode(' OR ', $whereClauses);
        }

        return $this->query($query, $params);
    }

}
