<?php
namespace App\Models;
use System\Core\BaseModel;

class UsersModel extends BaseModel {

    protected $table = APP_PREFIX.'users';

    // Columns allowed to add or edit
    protected $fillable = ['username', 'email', 'password', 'fullname', 'avatar', 'phone', 'whatsapp', 'telegram', 'skype', 'role', 'permissions', 'optional', 'status', 'birthday', 'gender', 'about_me', 'location', 'display', 'personal', 'online', 'address', 'country', 'activity_at'];

    // Columns not allowed to edit
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
            'whatsapp' => [
                'type' => 'varchar(30)',
                'null' => true,
                'default' => ''
            ],
            'telegram' => [
                'type' => 'varchar(100)',
                'null' => true,
                'default' => ''
            ],
            'skype' => [
                'type' => 'varchar(100)',
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
            'status' => [
                'type' => 'enum(\'active\', \'inactive\', \'banned\' , \'deleted\')',
                'null' => true,
                'default' => 'active'
            ],
            'birthday' => [
                'type'=> 'date',
                'null'=> true,
            ],
            'gender' => [
                'type' => 'enum(\'male\', \'female\', \'other\')',
                'null' => true,
                'default' => 'male'
            ],
            'about_me' => [
                'type'=> 'text',
            ],
            'location' => [
                'type'=> 'POINT',
                'null'=> true,
            ],
            'save' => [
                'type'=> 'json',
                'null'=> true,
            ],
            'display' => [
                'type' => 'boolean',
                'null' => false,
                'default' => 0
            ],
            'personal' => [
                'type' => 'json',
                'null' => true
            ],
            'online' => [
                'type' => 'boolean',
                'default' => '0'
            ],
            'activity_at' => [
                'type' => 'datetime',
                'null' => true,
                'default' => 'CURRENT_TIMESTAMP'
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
            'address' => [
                'type' => 'varchar(255)',
                'null' => true,
                'default' => ''
            ],
            'country' => [
                'type' => 'varchar(2)',
                'null' => true,
                'default' => ''
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
     * Get all users with pagination and specific fields
     * 
     * @param string|null $fields Fields to query (optional)
     * @param string|null $where Query condition (optional)
     * @param array $params Array of values corresponding to condition string
     * @param string|null $orderBy Sort by column (optional)
     * @param int|null $limit Limit number of results (optional)
     * @return array List of users
     */
    public function getFieldUsersPage($fields = '', $where = '', $params = [], $orderBy = 'id desc', $page = 1, $limit = null) {
        return $this->fetchPaginationWithField($this->table, $fields, $where, $params, $orderBy, $page, $limit);
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

    /**
     * Get user information by ID with specific fields
     * 
     * @param int $id User ID
     * @return array|false User information or false if not found
     */
    public function getUserByIdField($fields, $id)
    {
        return $this->rowField($this->table, $fields, 'id = ?', [$id]);
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
     * @return oleanol Success or failure
     */
    public function addUser($data) {
        $data = $this->fill($data); // Filter data allowed to add
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
        $data = $this->fill($data); // Filter data allowed to edit
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
     * @param $conditions array containing field and search character
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
    public function getLocation($userId) {
        // Check and clean input parameter
        $userId = (int)$userId;
    
        // Fields to get from APP_PREFIX.users table
        $fields = "ST_X(location) AS longitude, ST_Y(location) AS latitude";
    
        // SQL query with fully parameterized
        $sql = "
            SELECT $fields
            FROM {$this->table}
            WHERE id = ?
              AND status = 'active'
            LIMIT 1
        ";
    
        // Parameters for query
        $params = [$userId];
    
        // Execute query
        $result = $this->query($sql, $params);
    
        // Check and return result
        if (!empty($result)) {
            return [
                'longitude' => (float)$result[0]['longitude'],
                'latitude'  => (float)$result[0]['latitude']
            ];
        }
    
        // Return null if user not found or no location
        return null;
    }
    
    /**
     * Add relationship between user and target user
     * 
     * @param int $user_id ID of user performing action
     * @param int $target_user_id ID of target user
     * @param string $relation_type Type of relationship (e.g., 'like', 'dislike', 'superlike')
     * @return bool Returns true if added successfully, otherwise false
     */
    public function addRelation($user_id, $target_user_id, $relation_type) {
        try {
            // Check input data
            if (!in_array($relation_type, ['like', 'dislike', 'super_like'])) {
                throw new \InvalidArgumentException('Invalid relation type.');
            }

            // Data to add
            $data = [
                'user_id' => $user_id,
                'target_user_id' => $target_user_id,
                'relation_type' => $relation_type,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            // Add to APP_PREFIX.user_relations table
            return $this->add(APP_PREFIX.'user_relations', $data);
        } catch (\Exception $e) {
            // Log error if needed
            // error_log($e->getMessage());
            return false;
        }
    }

    public function get_user_relations($userId, $relationType = 'like', $limit = 10, $page = 1) {
        // Clean and check input parameters
        $userId = (int)$userId;
        $relationType = trim($relationType);
        $page = max((int)$page, 1);
        $limit = max((int)$limit, 1);
        $offset = ($page - 1) * $limit;
    
        $table_user_relations = APP_PREFIX.'user_relations';
        $table = APP_PREFIX.'users';
        // Query Using Window Functions to Get Data and Total Count
        $sql = "
            SELECT 
                r.user_id,
                COUNT(*) OVER() AS total_count
            FROM {$table_user_relations} r
            WHERE r.target_user_id = ?
              AND r.relation_type = ?
            ORDER BY r.created_at desc
            LIMIT $limit OFFSET $offset
        ";
        $params = [$userId, $relationType];
        $idsResult = $this->query($sql, $params);
    
        // Check if there are any records
        if (empty($idsResult)) {
            return [
                'data' => [],
                'is_next' => false,
                'page' => $page,
                'total' => 0
            ];
        }
    
        // Get total from first record
        $total = (int)$idsResult[0]['total_count'];
    
        // Determine if there is next page
        if ($page * $limit < $total ) {
            $is_next = true;
        } else {
            $is_next = false;
        }
    
        // Extract user_ids
        $userIds = array_column($idsResult, 'user_id');
    
        // Query 2: Get detailed user information based on retrieved user_ids
        // Create placeholder string for IN clause
        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $fields = "
            id,
            username,
            email,
            fullname,
            ST_X(location) AS longitude,
            ST_Y(location) AS latitude,
            avatar,
            phone,
            telegram,
            whatsapp,
            skype,
            birthday,
            about_me,
            personal,
            online
        ";
        $detailsSql = "
            SELECT $fields
            FROM {$table}
            WHERE id IN ($placeholders)
              AND status = 'active'
        ";
        $detailsParams = $userIds;
        $users = $this->query($detailsSql, $detailsParams);
    
        // Prepare return result
        $result = [
            'data' => $users,
            'is_next' => $is_next,
            'page' => $page,
            'total' => $total
        ];

        return $result;
    }
    
    public function get_user_matching($userId,  $limit = 10, $page = 1) 
    {
        // 1. Get list of users that $userId has "like" or "super_like"
        $table_user_relations = APP_PREFIX.'user_relations';
        $sqlLiked = "
            SELECT target_user_id
            FROM {$table_user_relations}
            WHERE user_id = ?
            AND relation_type IN ('like', 'super_like')
        ";
        
        $likedResult = $this->query($sqlLiked, [$userId]);
        // Array of userIds that $userId has liked
        $likedUserIds = array_map('intval', array_column($likedResult, 'target_user_id'));

        // 2. Get list of users who have "like" or "super_like" back to $userId
        $sqlLikedMe = "
            SELECT user_id
            FROM {$table_user_relations}
            WHERE target_user_id = ?
            AND relation_type IN ('like', 'super_like')
        ";
        $likedMeResult = $this->query($sqlLikedMe, [$userId]);
        // Array of userIds who have liked $userId
        $likedMeUserIds = array_map('intval', array_column($likedMeResult, 'user_id'));

        // 3. Find intersection of 2 arrays => matched users
        $matchedUserIds = array_values(array_intersect($likedUserIds, $likedMeUserIds));
        // Total number of matches
        $total = count($matchedUserIds);

        // 4. Pagination in PHP
        $limit = max((int)$limit, 1);
        $page = max((int)$page, 1);
        $offset = ($page - 1) * $limit;
        
        // Slice matchedUserIds array to get current page
        $pagedMatchedUserIds = array_slice($matchedUserIds, $offset, $limit);
        
        // If no matched users in this page, return early
        if (empty($pagedMatchedUserIds)) {
            return [
                'data'   => [],
                'is_next'=> false,
                'page'   => $page,
                'total'  => $total
            ];
        }

        // Determine if there is next page
        $is_next = ($offset + $limit < $total);

        // 5. Get detailed information of matched users from APP_PREFIX.users table
        // Create placeholders for IN statement
        $placeholders = implode(',', array_fill(0, count($pagedMatchedUserIds), '?'));
        $fields = "
            id,
            username,
            email,
            fullname,
            ST_X(location) AS longitude,
            ST_Y(location) AS latitude,
            avatar,
            phone,
            telegram,
            whatsapp,
            skype,
            birthday,
            about_me,
            personal,
            online
        ";
        $table = APP_PREFIX.'users';
        $detailsSql = "
            SELECT $fields
            FROM {$table}
            WHERE id IN ($placeholders)
            AND status = 'active'
        ";

        // Execute query to get user information
        $users = $this->query($detailsSql, $pagedMatchedUserIds);

        // 6. Return result
        return [
            'data'   => $users,     // List of matched users
            'is_next'=> $is_next,   // Is there next page
            'page'   => $page,      // Current page
            'total'  => $total      // Total number of matches
        ];
    }

        
}
