<?php

namespace System\Core;

use System\Core\AppException;
use System\Drivers\Database\Database;
use System\Drivers\Database\MysqlDriver;
use BadMethodCallException;
use InvalidArgumentException;
use System\Drivers\Database\QueryBuilder;

abstract class BaseModel
{
    /** @var array|Database */
    protected static $dbSource;

    /** @var Database */
    protected $db;

    /** @var string */
    protected $table = '';

    /** @var array Columns allowed for insert/update */
    protected $fillable = [];

    /** @var array Columns guarded from update */
    protected $guarded = [];

    /**
     * Set DB configuration or Database instance (call once in bootstrap)
     * @param array|Database $dbConfig
     */
    public static function setDbSource($dbConfig)
    {
        self::$dbSource = $dbConfig;
    }

    public function __construct($config = null)
    {
        $config = config('db');
        $conf = $config ?: self::$dbSource;
        if (is_array($conf)) {
            $this->db = new MysqlDriver($conf);
        } elseif ($conf instanceof Database) {
            $this->db = $conf;
        } else {
            throw new InvalidArgumentException('Invalid database config/instance');
        }
    }

    // Initialize QueryBuilder for current model
    public function newQuery(): QueryBuilder
    {
        $builder = new QueryBuilder($this->db);
        $builder
            ->setModel($this)
            ->from($this->table);
        return $builder;
    }

    public function __call($method, $arguments)
    {
        // Any method not in model, proxy to QueryBuilder
        return $this->newQuery()->{$method}(...$arguments);
    }

    public static function __callStatic($method, $arguments)
    {
        // For static calls like Model::where(...)
        $instance = new static(...$arguments[0] ?? []);
        return $instance->__call($method, $arguments);
    }


    // /**
    //  * Factory query for fast_users table
    //  *
    //  * @return QueryBuilder
    //  */
    // public static function of(): QueryBuilder
    // {
    //     return (new static())->newQuery();
    // }

    // // Proxy for QueryBuilder methods
    // public static function __callStatic($method, $arguments)
    // {
    //     // $instance = new static();
    //     // if (method_exists($instance->newQuery(), $method)) {
    //     //     return $instance->newQuery()->{$method}(...$arguments);
    //     // }
    //     $instance = new static();

    //     // 1) If model has that method (e.g. relation()), call and return builder
    //     if (method_exists($instance, $method)) {
    //         // e.g.: $instance->relation() will return a QueryBuilder
    //         return $instance->{$method}(...$arguments)
    //             ->setModel($instance);
    //     }

    //     // 2) If QueryBuilder has that method, proxy normally
    //     $qb = $instance->newQuery();
    //     if (method_exists($qb, $method)) {
    //         return $qb->{$method}(...$arguments);
    //     }

    //     throw new BadMethodCallException("Method {$method} does not exist on " . static::class);
    // }

    // ===========================
    // Basic CRUD methods remain unchanged
    // ===========================

    public function row($table, $where = '', $params = [])
    {
        return $this->db->fetchRow($table, $where, $params);
    }

    public function list($table, $where = '', $params = [], $orderBy = '', $page = 1, $limit = null)
    {
        return $this->db->fetchAll($table, $where, $params, $orderBy, $page, $limit);
    }

    public function listfield($table, $fields = '*', $where = '', $params = [], $orderBy = '', $page = 1, $limit = null)
    {
        return $this->db->fetchAllWithField($table, $fields, $where, $params, $orderBy, $page, $limit);
    }

    public function listpaging($table, $where = '', $params = [], $orderBy = '', $page = 1, $limit = null)
    {
        return $this->db->fetchPagination($table, $where, $params, $orderBy, $page, $limit);
    }

    public function fetchPaginationWithField($table, $fields = '*', $where = '', $params = [], $orderBy = '', $page = 1, $limit = null)
    {
        return $this->db->fetchPaginationWithField($table, $fields, $where, $params, $orderBy, $page, $limit);
    }

    public function add($table, $data)
    {
        if ($this->db->insert($table, $data)) {
            return $this->lastInsertId();
        }
        return null;
    }

    public function set($table, $data, $where = '', $params = [])
    {
        return $this->db->update($table, $data, $where, $params);
    }

    public function del($table, $where = '', $params = [])
    {
        return $this->db->delete($table, $where, $params);
    }

    public function query($query, $params = [])
    {
        return $this->db->query($query, $params);
    }

    public function lastInsertId()
    {
        return $this->db->lastInsertId();
    }

    public function count($table, $where = '', $params = [])
    {
        return $this->db->count($table, $where, $params);
    }
}
