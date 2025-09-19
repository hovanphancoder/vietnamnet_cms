<?php
namespace System\Drivers\Database;
use PDO;
use PDOException;
use System\Core\AppException;

class PostgresqlDriver extends Database {

    protected $pdo;

    /**
     * Initialize PostgreSQL connection
     * 
     * @param array $config Array containing connection configuration information
     */
    public function __construct($config) {
        try {
            $dsn = 'pgsql:host=' . $config['db_host'] . ';port=' . $config['db_port'] . ';dbname=' . $config['db_database'];
            $this->pdo = new PDO($dsn, $config['db_username'], $config['db_password']);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            throw new \System\Core\AppException("Connect PostgresqlDriver failed: " . $e->getMessage(), 500);
        }
    }

    /**
     * Execute arbitrary SQL query
     * 
     * @param string $query SQL statement to execute
     * @param array $params Array of values corresponding to parameters in SQL statement
     * @return mixed Query result (used for SELECT, INSERT, UPDATE, DELETE)
     */
    public function query($query, $params = [])
    {
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            if (preg_match('/^\s*(SELECT|SHOW|DESCRIBE|EXPLAIN)\s/i', $query)) {
                return $stmt->fetchAll(\PDO::FETCH_ASSOC);
            }
            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new \System\Core\AppException("PostgresqlDriver->query(): " . $e->getMessage(), 500);
        }
    }

    /**
     * Get ID of the last inserted record
     * 
     * @return string ID of the last inserted record
     */
    public function lastInsertId() {
        try {
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            throw new \System\Core\AppException("PostgresqlDriver->lastInsertId(): " . $e->getMessage(), 500);
        }
    }

    /**
     * Count records in table
     * 
     * @param string $table Table name to count records
     * @param string $where WHERE condition to count records (optional)
     * @param array $params Array of values corresponding to parameters in WHERE string (optional)
     * @return int Number of records in table
     */
    public function count($table, $where = '', $params = []) {
        $table = '"' . str_replace('"', '""', $table) . '"';
        $query = "SELECT COUNT(*) as count FROM {$table}";
        if ($where) {
            $query .= " WHERE {$where}";
        }
        try {
            $result = $this->query($query, $params);
        } catch (PDOException $e) {
            throw new \System\Core\AppException("PostgresqlDriver->count(): " . $e->getMessage(), 500);
        }
        if (!empty($result)){
            return $result[0]['count'];
        }
        return 0;
    }

    /**
     * Prepare an SQL query (PDO::prepare)
     * 
     * @param string $query SQL query
     * @return PDOStatement PDOStatement object
     */
    public function prepare($query) {
        try {
            return $this->pdo->prepare($query);
        } catch (PDOException $e) {
            throw new \System\Core\AppException("PostgresqlDriver->prepare(): " . $e->getMessage(), 500);
        }
    }

    /**
     * Execute query with passed parameters (PDO::execute)
     * 
     * @param PDOStatement $stmt Prepared PDOStatement object
     * @param array $params Parameters passed to query
     * @return bool Execution result
     */
    public function execute($stmt, $params = []) {
        try {
            return $stmt->execute($params);
        } catch (PDOException $e) {
            throw new \System\Core\AppException("PostgresqlDriver->execute(): " . $e->getMessage(), 500);
        }
    }

    /**
     * Execute SELECT query to get multiple rows
     * 
     * @param string $table Table name to query
     * @param string $where WHERE condition as string (optional)
     * @param array $params Array of values corresponding to parameters in WHERE string (optional)
     * @param string $orderBy ORDER BY clause (optional)
     * @param int $page Current page position (optional)
     * @param int $limit Number of results to limit (optional)
     * @return array Array containing query results
     */
    public function fetchAll($table, $where = '', $params = [], $orderBy = '', $page = 1, $limit = null) {
        $table = '"' . str_replace('"', '""', $table) . '"';
        $sql = "SELECT * FROM {$table}";
        if ($where) {
            $sql .= " WHERE {$where}";
        }
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        if (!is_null($limit)) {
            $page = max((int)$page, 1);
            $limit = (int)$limit;
            $offset = ($page - 1) * $limit;
            $sql .= " LIMIT {$limit}";
            if ($offset > 0) {
                $sql .= " OFFSET {$offset}";
            }
        }
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
        } catch (PDOException $e) {
            throw new \System\Core\AppException("PostgresqlDriver->fetchAll(): " . $e->getMessage(), 500);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Execute SELECT query to get multiple rows with pagination
     * 
     * @param string $table Table name
     * @param string $where WHERE condition as string (optional)
     * @param array $params Array of values corresponding to WHERE string (optional)
     * @param string $orderBy ORDER BY clause (optional)
     * @param int $page Current page position (optional)
     * @param int $limit Number of results returned per page (optional)
     * @return array Query result and information about whether there's a next page
     */
    public function fetchPagination($table, $where = '', $params = [], $orderBy = '', $page = 1, $limit = null) {
        try {
            $table = '"' . str_replace('"', '""', $table) . '"';
            $hasNextPage = false;
            $page = max((int)$page, 1);
            $limit = (int)$limit ?: 10;
            $offset = ($page - 1) * $limit;
            $limitExtra = $limit + 1;

            $sql = "SELECT * FROM {$table}";
            if ($where) {
                $sql .= " WHERE {$where}";
            }
            if ($orderBy) {
                $sql .= " ORDER BY {$orderBy}";
            }
            $sql .= " LIMIT {$limitExtra} OFFSET {$offset}";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($results) > $limit) {
                $hasNextPage = true;
                array_pop($results);
            }
            return [
                'data' => $results,
                'is_next' => $hasNextPage,
                'page' => $page
            ];
        } catch (PDOException $e) {
            throw new \System\Core\AppException("PostgresqlDriver->fetchPagination(): " . $e->getMessage(), 500);
        }
    }

    /**
     * Execute SELECT query to get 1 row
     * 
     * @param string $table Table name to query
     * @param string $where WHERE condition as string
     * @param array $params Array of values corresponding to parameters in WHERE string
     * @param string $orderBy ORDER BY clause (optional)
     * @param int $page Current page position (optional)
     * @return array|null Array containing query result or null if no result
     */
    public function fetchRow($table, $where = '', $params = [], $orderBy = '', $page = 1) {
        $table = '"' . str_replace('"', '""', $table) . '"';
        $sql = "SELECT * FROM {$table}";
        if ($where) {
            $sql .= " WHERE {$where}";
        }
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }

        // Calculate OFFSET based on $page (default is 1)
        $page = max((int)$page, 1);
        $offset = ($page - 1);

        // Always get 1 row
        $sql .= " LIMIT 1";
        if ($offset > 0) {
            $sql .= " OFFSET {$offset}";
        }

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new \System\Core\AppException("PostgresqlDriver->fetchRow(): " . $e->getMessage(), 500);
        }
    }

    /**
     * Execute INSERT query
     * 
     * @param string $table Table name to insert data
     * @param array $data Array of data to insert (in 'column' => 'value' format)
     * @return bool Returns true if data insertion successful, false otherwise
     */
    public function insert($table, $data) {
        $table = '"' . str_replace('"', '""', $table) . '"';
        $columns = array_keys($data);
        $columns_escaped = array_map(function($col) {
            return '"' . str_replace('"', '""', $col) . '"';
        }, $columns);
        $keys = implode(',', $columns_escaped);
        $placeholders = implode(',', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO {$table} ({$keys}) VALUES ({$placeholders})";
        try {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute(array_values($data));
        } catch (PDOException $e) {
            throw new \System\Core\AppException("PostgresqlDriver->insert(): " . $e->getMessage(), 500);
        }
    }

    /**
     * Execute UPDATE query
     * 
     * @param string $table Table name to update
     * @param array $data Array of data to update (in 'column' => 'value' format)
     * @param string $where WHERE condition to update data
     * @param array $params Array of values corresponding to parameters in WHERE string
     * @return bool Returns true if update successful, false otherwise
     */
    public function update($table, $data, $where = '', $params = []) {
        try {
            $table = '"' . str_replace('"', '""', $table) . '"';
            $columns = array_keys($data);
            $set = implode(', ', array_map(function($col) {
                return '"' . str_replace('"', '""', $col) . '" = ?';
            }, $columns));
            $sql = "UPDATE {$table} SET {$set}";
            if ($where) {
                $sql .= " WHERE {$where}";
            }
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute(array_merge(array_values($data), $params));
        } catch (PDOException $e) {
            throw new \System\Core\AppException("PostgresqlDriver->update(): " . $e->getMessage(), 500);
        }
    }

    /**
     * Execute DELETE query
     * 
     * @param string $table Table name to delete data
     * @param string $where WHERE condition to delete data
     * @param array $params Array of values corresponding to parameters in WHERE string
     * @return bool Returns true if delete successful, false otherwise
     */
    public function delete($table, $where = '', $params = []) {
        try {
            $table = '"' . str_replace('"', '""', $table) . '"';
            $sql = "DELETE FROM {$table}";
            if ($where) {
                $sql .= " WHERE {$where}";
            }
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            throw new \System\Core\AppException("PostgresqlDriver->delete(): " . $e->getMessage(), 500);
        }
    }
}
