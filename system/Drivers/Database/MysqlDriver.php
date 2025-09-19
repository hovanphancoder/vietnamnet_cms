<?php

namespace System\Drivers\Database;

use PDO;
use PDOException;
use System\Core\AppException;
use System\Libraries\Logger;

class MysqlDriver extends Database
{

    protected $pdo;

    /**
     * Log debug information for SQL, params and execution time.
     *
     * @param string $sql SQL statement executed.
     * @param array $params SQL statement parameters.
     * @param float $startTime Execution start time.
     * @param float $endTime Execution end time.
     */
    private function recordDebug($sql, $params, $startTime, $endTime)
    {
        if (isset($GLOBALS['debug_sql']) && is_array($GLOBALS['debug_sql'])) {
            //if ($endTime - $startTime > 0.0001){
            $GLOBALS['debug_sql'][] = [
                'sql'    => $sql,
                'params' => $params,
                'time'   => $endTime - $startTime
            ];
            //}
        }
    }

    /**
     * Initialize MySQL connection.
     *
     * @param array $config Array containing connection configuration information.
     */
    public function __construct($config)
    {
        try {
            global $pdo;
            $dsn = 'mysql:host=' . $config['db_host'] . ';dbname=' . $config['db_database'] . ';charset=' . $config['db_charset'];
            if (empty($pdo)) {
                $pdo = new PDO($dsn, $config['db_username'], $config['db_password']);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            }
            $this->pdo = $pdo;
        } catch (PDOException $e) {
            throw new AppException("Connect MysqlDriver failed: " . $e->getMessage(), 500);
        }
    }

    /**
     * Execute arbitrary SQL query.
     *
     * @param string $query SQL statement to execute.
     * @param array $params Array of values for parameters.
     * @return mixed Query result.
     */
    public function query($query, $params = [])
    {
        $startTime = microtime(true);
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            $endTime = microtime(true);
            $this->recordDebug($query, $params, $startTime, $endTime);

            if (preg_match('/^\s*(SELECT|SHOW|DESCRIBE|EXPLAIN)\s/i', $query)) {
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new AppException("MysqlDriver->query(): " . $e->getMessage(), 500);
        }
    }

    /**
     * Get ID of the last inserted record.
     *
     * @return string ID of the last inserted record.
     */
    public function lastInsertId()
    {
        $startTime = microtime(true);
        try {
            $result = $this->pdo->lastInsertId();
            $endTime = microtime(true);
            $this->recordDebug('LAST_INSERT_ID()', [], $startTime, $endTime);
            return $result;
        } catch (PDOException $e) {
            throw new AppException("MysqlDriver->lastInsertId(): " . $e->getMessage(), 500);
        }
    }

    /**
     * Count records in table.
     *
     * @param string $table Table name to count.
     * @param string $where WHERE condition (optional).
     * @param array $params Array of corresponding values (optional).
     * @return int Number of records.
     */
    public function count($table, $where = '', $params = [])
    {
        $table = '`' . str_replace('`', '``', $table) . '`';
        $query = "SELECT COUNT(*) as count FROM {$table}";
        if ($where) {
            $query .= " WHERE {$where}";
        }
        $startTime = microtime(true);
        try {
            $result = $this->query($query, $params);
            $endTime = microtime(true);
            $this->recordDebug($query, $params, $startTime, $endTime);
        } catch (PDOException $e) {
            throw new AppException("MysqlDriver->count(): " . $e->getMessage(), 500);
        }
        if (!empty($result)) {
            return $result[0]['count'];
        }
        return 0;
    }

    /**
     * Prepare an SQL query (PDO::prepare).
     *
     * @param string $query SQL query.
     * @return PDOStatement PDOStatement object.
     */
    public function prepare($query)
    {
        $startTime = microtime(true);
        try {
            $stmt = $this->pdo->prepare($query);
            $endTime = microtime(true);
            $this->recordDebug($query, [], $startTime, $endTime);
            return $stmt;
        } catch (PDOException $e) {
            throw new AppException("MysqlDriver->prepare(): " . $e->getMessage(), 500);
        }
    }

    /**
     * Execute query with passed parameters (PDO::execute).
     *
     * @param PDOStatement $stmt Prepared PDOStatement object.
     * @param array $params Parameters passed to query.
     * @return bool Execution result.
     */
    public function execute($stmt, $params = [])
    {
        $startTime = microtime(true);
        try {
            $result = $stmt->execute($params);
            $endTime = microtime(true);
            // No direct SQL so no detailed logging.
            return $result;
        } catch (PDOException $e) {
            throw new AppException("MysqlDriver->execute(): " . $e->getMessage(), 500);
        }
    }

    /**
     * Execute SELECT query to get multiple rows.
     *
     * @param string $table Table name to query.
     * @param string $where WHERE condition (optional).
     * @param array $params Array of corresponding values (optional).
     * @param string $orderBy ORDER BY clause (optional).
     * @param int $page Current page position (optional).
     * @param int $limit Number of results to limit (optional).
     * @return array Array containing query results.
     */
    public function fetchAll($table, $where = '', $params = [], $orderBy = '', $page = 1, $limit = null)
    {
        $table = '`' . str_replace('`', '``', $table) . '`';
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
        $startTime = microtime(true);
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $endTime = microtime(true);
            $this->recordDebug($sql, $params, $startTime, $endTime);
        } catch (PDOException $e) {
            throw new AppException("MysqlDriver->fetchAll(): " . $e->getMessage(), 500);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function fetchAllWithField($table, $fields = '*', $where = '', $params = [], $orderBy = '', $page = 1, $limit = null)
    {
        $table = '`' . str_replace('`', '``', $table) . '`';
        $sql = "SELECT {$fields} FROM {$table}";
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
        $startTime = microtime(true);
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $endTime = microtime(true);
            $this->recordDebug($sql, $params, $startTime, $endTime);
        } catch (PDOException $e) {
            throw new AppException("MysqlDriver->fetchAll(): " . $e->getMessage(), 500);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Execute SELECT query to get multiple rows with pagination.
     *
     * @param string $table Table name.
     * @param string $where WHERE condition (optional).
     * @param array $params Array of corresponding values (optional).
     * @param string $orderBy ORDER BY clause (optional).
     * @param int $page Current page position (optional).
     * @param int $limit Number of results returned per page (optional).
     * @return array Query result and information about whether there's a next page.
     */
    public function fetchPagination($table, $where = '', $params = [], $orderBy = '', $page = 1, $limit = null)
    {
        try {
            $table = '`' . str_replace('`', '``', $table) . '`';
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

            $startTime = microtime(true);
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $endTime = microtime(true);
            $this->recordDebug($sql, $params, $startTime, $endTime);

            if (count($results) > $limit) {
                $hasNextPage = true;
                array_pop($results);
            }
            return [
                'data'    => $results,
                'is_next' => $hasNextPage,
                'page'    => $page
            ];
        } catch (PDOException $e) {
            throw new AppException("MysqlDriver->fetchPagination(): " . $e->getMessage(), 500);
        }
    }

    /**
     * Execute SELECT query to get multiple rows with pagination and select required fields.
     *
     * @param string $table Table name.
     * @param string $fields Fields to query (default is *).
     * @param string $where WHERE condition (optional).
     * @param array $params Array of corresponding values (optional).
     * @param string $orderBy ORDER BY clause (optional).
     * @param int $page Current page position (optional).
     * @param int $limit Number of results returned per page (optional).
     * @return array Query result and information about whether there's a next page.
     */
    public function fetchPaginationWithField($table, $fields = '*', $where = '', $params = [], $orderBy = '', $page = 1, $limit = null)
    {
        try {
            $table = '`' . str_replace('`', '``', $table) . '`';
            $hasNextPage = false;
            $page = max((int)$page, 1);
            $limit = (int)$limit ?: 10;
            $offset = ($page - 1) * $limit;
            $limitExtra = $limit + 1;

            $sql = "SELECT {$fields} FROM {$table}";
            if ($where) {
                $sql .= " WHERE {$where}";
            }
            if ($orderBy) {
                $sql .= " ORDER BY {$orderBy}";
            }
            $sql .= " LIMIT {$limitExtra} OFFSET {$offset}";

            $startTime = microtime(true);
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $endTime = microtime(true);
            $this->recordDebug($sql, $params, $startTime, $endTime);

            if (count($results) > $limit) {
                $hasNextPage = true;
                array_pop($results);
            }
            return [
                'data'    => $results,
                'is_next' => $hasNextPage,
                'page'    => $page
            ];
        } catch (PDOException $e) {
            throw new AppException("MysqlDriver->fetchPaginationWithField(): " . $e->getMessage(), 500);
        }
    }

    /**
     * Execute SELECT query to get 1 row.
     *
     * @param string $table Table name to query.
     * @param string $where WHERE condition (optional).
     * @param array $params Array of corresponding values (optional).
     * @param string $orderBy ORDER BY clause (optional).
     * @param int $page Current page position (optional).
     * @return array|null Array containing query result or null if no result.
     */
    public function fetchRow($table, $where = '', $params = [], $orderBy = '', $page = 1)
    {
        $table = '`' . str_replace('`', '``', $table) . '`';
        $sql = "SELECT * FROM {$table}";
        if ($where) {
            $sql .= " WHERE {$where}";
        }
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        $page = max((int)$page, 1);
        $offset = ($page - 1);
        $sql .= " LIMIT 1";
        if ($offset > 0) {
            $sql .= " OFFSET {$offset}";
        }
        $startTime = microtime(true);
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $endTime = microtime(true);
            $this->recordDebug($sql, $params, $startTime, $endTime);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new AppException("MysqlDriver->fetchRow(): " . $e->getMessage(), 500);
        }
    }

    /**
     * Execute SELECT query to get 1 row with specified fields.
     *
     * @param string $table Table name to query.
     * @param string $fields Fields to get (default is *).
     * @param string $where WHERE condition (optional).
     * @param array $params Array of corresponding values (optional).
     * @param string $orderBy ORDER BY clause (optional).
     * @param int $page Current page position (optional).
     * @return array|null Array containing query result or null if no result.
     */
    public function fetchRowField($table, $fields = '*', $where = '', $params = [], $orderBy = '', $page = 1)
    {
        $table = '`' . str_replace('`', '``', $table) . '`';
        $sql = "SELECT {$fields} FROM {$table}";
        if ($where) {
            $sql .= " WHERE {$where}";
        }
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        $page = max((int)$page, 1);
        $offset = ($page - 1);
        $sql .= " LIMIT 1";
        if ($offset > 0) {
            $sql .= " OFFSET {$offset}";
        }
        $startTime = microtime(true);
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $endTime = microtime(true);
            $this->recordDebug($sql, $params, $startTime, $endTime);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new AppException("MysqlDriver->fetchRowField(): " . $e->getMessage(), 500);
        }
    }

    /**
     * Execute INSERT query.
     *
     * @param string $table Table name to insert data.
     * @param array $data Array of data to insert (in 'column' => 'value' format).
     * @return bool Returns true if insert successful, false otherwise.
     */
    public function insert($table, $data)
    {
        $table = '`' . str_replace('`', '``', $table) . '`';
        $columns = array_keys($data);
        $columns_escaped = array_map(function ($col) {
            return '`' . str_replace('`', '``', $col) . '`';
        }, $columns);
        $keys = implode(',', $columns_escaped);
        $placeholders = implode(',', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO {$table} ({$keys}) VALUES ({$placeholders})";
        $startTime = microtime(true);
        try {
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute(array_values($data));
            $endTime = microtime(true);
            $this->recordDebug($sql, array_values($data), $startTime, $endTime);
            return $result;
        } catch (PDOException $e) {
            throw new AppException("MysqlDriver->insert(): " . $e->getMessage(), 500);
        }
    }

    /**
     * Execute UPDATE query.
     *
     * @param string $table Table name to update.
     * @param array $data Array of data to update (in 'column' => 'value' format or array containing 'expr' and 'params').
     * @param string $where WHERE condition to update.
     * @param array $params Array of values for WHERE string.
     * @return bool Returns true if update successful, false otherwise.
     */
    public function update($table, $data, $where = '', $params = [])
    {
        try {
            $table = '`' . str_replace('`', '``', $table) . '`';
            $columns = array_keys($data);
            $set = implode(', ', array_map(function ($col) use ($data) {
                if (isset($data[$col]['expr'])) {
                    return "`$col` = {$data[$col]['expr']}";
                } else {
                    return "`$col` = ?";
                }
            }, $columns));

            $sql = "UPDATE {$table} SET {$set}";
            if ($where) {
                $sql .= " WHERE {$where}";
            }
            $startTime = microtime(true);
            $stmt = $this->pdo->prepare($sql);
            $finalParams = [];
            foreach ($columns as $col) {
                if (isset($data[$col]['params'])) {
                    $finalParams = array_merge($finalParams, $data[$col]['params']);
                } else {
                    $finalParams[] = $data[$col];
                }
            }
            $result = $stmt->execute(array_merge($finalParams, $params));
            $endTime = microtime(true);
            $this->recordDebug($sql, array_merge($finalParams, $params), $startTime, $endTime);
            return $result;
        } catch (PDOException $e) {
            throw new AppException("MysqlDriver->update(): " . $e->getMessage(), 500);
        }
    }

    /**
     * Execute DELETE query.
     *
     * @param string $table Table name to delete data.
     * @param string $where WHERE condition to delete data.
     * @param array $params Array of values for WHERE string.
     * @return bool Returns true if delete successful, false otherwise.
     */
    public function delete($table, $where = '', $params = [])
    {
        try {
            $table = '`' . str_replace('`', '``', $table) . '`';
            $sql = "DELETE FROM {$table}";
            if ($where) {
                $sql .= " WHERE {$where}";
            }
            $startTime = microtime(true);
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute($params);
            $endTime = microtime(true);
            $this->recordDebug($sql, $params, $startTime, $endTime);
            return $result;
        } catch (PDOException $e) {
            throw new AppException("MysqlDriver->delete(): " . $e->getMessage(), 500);
        }
    }


    /**
     * Start transaction
     */
    public function beginTransaction()
    {
        try {
            return $this->pdo->beginTransaction();
        } catch (PDOException $e) {
            throw new AppException("MysqlDriver->beginTransaction(): " . $e->getMessage(), 500);
        }
    }

    /**
     * Commit transaction
     */
    public function commit()
    {
        try {
            return $this->pdo->commit();
        } catch (PDOException $e) {
            throw new AppException("MysqlDriver->commit(): " . $e->getMessage(), 500);
        }
    }

    /**
     * Rollback transaction
     */
    public function rollBack()
    {
        try {
            return $this->pdo->rollBack();
        } catch (PDOException $e) {
            throw new AppException("MysqlDriver->rollBack(): " . $e->getMessage(), 500);
        }
    }
}
