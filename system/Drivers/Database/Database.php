<?php

namespace System\Drivers\Database;

abstract class Database
{

    protected $pdo;

    /**
     * Initialize database connection
     * Each driver will implement this connection method
     *
     * @param array $config Array containing connection configuration information
     */
    abstract public function __construct($config);

    /**
     * Execute arbitrary SQL query
     * 
     * @param string $query SQL statement to execute
     * @param array $params Array of values corresponding to parameters in SQL statement
     * @return mixed Query result (used for SELECT, INSERT, UPDATE, DELETE)
     */
    abstract public function query($query, $params = []);

    /**
     * Get ID of the last inserted record
     * 
     * @return string ID of the last inserted record
     */
    abstract public function lastInsertId();

    /**
     * Count records in table
     * 
     * @param string $table Table name to count records
     * @param string $where WHERE condition to count records (optional)
     * @param array $params Array of values corresponding to parameters in WHERE string (optional)
     * @return int Number of records in table
     */
    abstract public function count($table, $where = '', $params = []);

    /**
     * Execute SELECT query to get multiple rows
     * 
     * @param string $table Table name to query
     * @param string $where WHERE condition as string (optional)
     * @param array $params Array of values corresponding to parameters in WHERE string (optional)
     * @param string $orderBy ORDER BY clause (optional)
     * @param int $limit Number of results to limit (optional)
     * @param int $offset Starting position to get results (optional)
     * @return array Array containing query results
     */
    abstract public function fetchAll($table, $where = '', $params = [], $orderBy = '', $page = 1, $limit = null);

    /**
     * Execute SELECT query with specific fields
     * 
     * @param string $table Table name to query
     * @param string $where WHERE condition as string (optional)
     * @param array $params Array of values corresponding to parameters in WHERE string (optional)
     * @param string $orderBy ORDER BY clause (optional)
     * @param int $limit Number of results to limit (optional)
     * @param int $offset Starting position to get results (optional)
     * @return array Array containing query results
     */
    abstract public function fetchAllWithField($table, $fields = '*', $where = '', $params = [], $orderBy = '', $page = 1, $limit = null);

    /**
     * Execute SELECT query to get multiple rows with pagination, supports calculating offset from page number
     * Example: $users = $db->fetchPagination('users', 'age > ? AND status = ?', [30, 'active'], 'age DESC', 10, 20);
     * 
     * @param string $table Table name
     * @param string $where WHERE condition as string (optional)
     * @param array $params Array of values corresponding to WHERE string (optional)
     * @param string $orderBy ORDER BY clause (optional)
     * @param int $page Starting position to get results (optional)
     * @param int $limit Number of results returned per page (optional)
     * @return array Query result and information about whether there is a next page
     */
    abstract public function fetchPagination($table, $where = '', $params = [], $orderBy = '', $page = 1, $limit = null);

    /**
     * Execute SELECT query to get multiple rows with pagination, supports calculating offset from page number
     * Example: $users = $db->fetchPaginationWithField('users', 'user_id, username', 'age > ? AND status = ?', [30, 'active'], 'age DESC', 10, 20);
     * 
     * @param string $table Table name
     * @param string $where WHERE condition as string (optional)
     * @param array $params Array of values corresponding to WHERE string (optional)
     * @param string $orderBy ORDER BY clause (optional)
     * @param int $page Starting position to get results (optional)
     * @param int $limit Number of results returned per page (optional)
     * @return array Query result and information about whether there is a next page
     */
    abstract public function fetchPaginationWithField($table, $fields = '*', $where = '', $params = [], $orderBy = '', $page = 1, $limit = null);

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
    abstract public function fetchRow($table, $where = '', $params = [], $orderBy = '', $page = 1);

    /**
     * Execute INSERT query
     * 
     * @param string $table Table name to insert data
     * @param array $data Array of data to insert (in format 'column' => 'value')
     * @return bool Returns true if data insertion successful, false otherwise
     */
    abstract public function insert($table, $data);

    /**
     * Execute UPDATE query
     * 
     * @param string $table Table name to update
     * @param array $data Array of data to update (in format 'column' => 'value')
     * @param string $where WHERE condition to update data
     * @param array $params Array of values corresponding to parameters in WHERE string
     * @return bool Returns true if update successful, false otherwise
     */
    abstract public function update($table, $data, $where = '', $params = []);

    /**
     * Execute DELETE query
     * 
     * @param string $table Table name to delete data
     * @param string $where WHERE condition to delete data
     * @param array $params Array of values corresponding to parameters in WHERE string
     * @return bool Returns true if delete successful, false otherwise
     */
    abstract public function delete($table, $where = '', $params = []);


    /**
     * Begin transaction
     */
    abstract public function beginTransaction();

    /**
     * Commit transaction
     */
    abstract public function commit();

    /**
     * Rollback transaction
     */
    abstract public function rollBack();
}
