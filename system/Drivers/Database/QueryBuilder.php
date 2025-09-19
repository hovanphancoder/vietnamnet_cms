<?php

namespace System\Drivers\Database;

use Closure;
use System\Drivers\Database\Database;

/**
 * Flexible QueryBuilder class, supporting Laravel-like chaining
 * 
 * Provides methods to build SQL queries flexibly with support for chaining multiple statements.
 * Supports operations such as SELECT, JOIN, WHERE, GROUP BY, ORDER BY, LIMIT, and OFFSET.
 */
class QueryBuilder
{
    /** @var Database */
    protected $db;

    /** @var string */
    protected $table;

    /** @var array */
    protected $columns = ['*'];

    /** @var array */
    protected $joins = [];

    /** @var array */
    protected $wheres = [];

    /** @var array */
    protected $bindings = [];

    /** @var array */
    protected $orders = [];

    /** @var array */
    protected $groups = [];

    /** @var array */
    protected $havings = [];

    /** @var int|null */
    protected $limit;

    /** @var int|null */
    protected $offset;

    /** @var string|null */
    protected ?string $rawSql = null;

    /** @var array */
    protected $relations = [];

    // ====== Timestamps ======
    /**
     * @var array
     */
    protected $timestamps = [];

    /**
     * Enable automatic timestamps for given columns.
     * @param array $columns
     * @return self
     */
    public function timestamps(array $columns = ['created_at', 'updated_at'])
    {
        $this->timestamps = $columns;
        return $this;
    }

    // ====== Fillable / Guarded ======
    /**
     * @var array
     */
    protected $fillable = [];
    /**
     * @var array
     */
    protected $guarded = [];

    /**
     * Set fillable columns.
     * @param array $columns
     * @return self
     */
    public function fillable(array $columns)
    {
        $this->fillable = $columns;
        return $this;
    }

    /**
     * Set guarded columns.
     * @param array $columns
     * @return self
     */
    public function guarded(array $columns)
    {
        $this->guarded = $columns;
        return $this;
    }

    /**
     * Mass assignment for fillable fields.
     * @param array $data
     * @return array
     */
    public function fill(array $data)
    {
        if (!empty($this->fillable)) {
            return array_intersect_key($data, array_flip($this->fillable));
        }
        if (!empty($this->guarded)) {
            return array_diff_key($data, array_flip($this->guarded));
        }
        return $data;
    }

    // ====== Distinct ======
    /**
     * @var bool
     */
    protected $isDistinct = false;

    /**
     * Set SELECT DISTINCT.
     * @return self
     */
    public function distinct()
    {
        $this->isDistinct = true;
        return $this;
    }

    // ====== Enhanced WHERE ======
    public function whereNotIn(string $column, $values, string $boolean = 'AND')
    {
        if (is_array($values)) {
            $placeholders = implode(',', array_fill(0, count($values), '?'));
            $this->wheres[] = ['raw' => "{$column} NOT IN ({$placeholders})", 'boolean' => $boolean];
            $this->bindings = array_merge($this->bindings, $values);
        } else {
            $sub = new QueryBuilder($this->db);
            $values($sub);
            $sqlSub = $sub->toSql();
            $this->wheres[] = ['raw' => "{$column} NOT IN ({$sqlSub})", 'boolean' => $boolean];
            $this->bindings = array_merge($this->bindings, $sub->getBindings());
        }
        return $this;
    }

    public function whereColumn(string $first, string $operator, string $second, string $boolean = 'AND')
    {
        $this->wheres[] = ['raw' => "{$first} {$operator} {$second}", 'boolean' => $boolean];
        return $this;
    }

    public function whereDate(string $column, $value, string $operator = '=', string $boolean = 'AND')
    {
        $this->wheres[] = [
            'raw' => "DATE({$column}) {$operator} ?",
            'boolean' => $boolean
        ];
        $this->bindings[] = $value;
        return $this;
    }

    public function whereMonth(string $column, $value, string $operator = '=', string $boolean = 'AND')
    {
        $this->wheres[] = [
            'raw' => "MONTH({$column}) {$operator} ?",
            'boolean' => $boolean
        ];
        $this->bindings[] = $value;
        return $this;
    }

    public function whereYear(string $column, $value, string $operator = '=', string $boolean = 'AND')
    {
        $this->wheres[] = [
            'raw' => "YEAR({$column}) {$operator} ?",
            'boolean' => $boolean
        ];
        $this->bindings[] = $value;
        return $this;
    }

    public function whereDay(string $column, $value, string $operator = '=', string $boolean = 'AND')
    {
        $this->wheres[] = [
            'raw' => "DAY({$column}) {$operator} ?",
            'boolean' => $boolean
        ];
        $this->bindings[] = $value;
        return $this;
    }

    // ====== Transaction ======
    public function beginTransaction()
    {
        return $this->db->beginTransaction();
    }
    public function commit()
    {
        return $this->db->commit();
    }
    public function rollBack()
    {
        return $this->db->rollBack();
    }
    public function transaction(callable $callback)
    {
        $this->beginTransaction();
        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->rollBack();
            throw $e;
        }
    }

    // ====== Utility Methods ======
    public function exists()
    {
        return $this->count() > 0;
    }
    public function doesntExist()
    {
        return !$this->exists();
    }
    public function value(string $column)
    {
        $row = $this->first();
        return $row[$column] ?? null;
    }
    public function sum(string $column)
    {
        $sql = "SELECT SUM({$column}) as aggregate FROM {$this->table} " . $this->buildWhere();
        $result = $this->db->query($sql, $this->bindings);
        return $result[0]['aggregate'] ?? 0;
    }
    public function avg(string $column)
    {
        $sql = "SELECT AVG({$column}) as aggregate FROM {$this->table} " . $this->buildWhere();
        $result = $this->db->query($sql, $this->bindings);
        return $result[0]['aggregate'] ?? 0;
    }
    public function min(string $column)
    {
        $sql = "SELECT MIN({$column}) as aggregate FROM {$this->table} " . $this->buildWhere();
        $result = $this->db->query($sql, $this->bindings);
        return $result[0]['aggregate'] ?? null;
    }
    public function max(string $column)
    {
        $sql = "SELECT MAX({$column}) as aggregate FROM {$this->table} " . $this->buildWhere();
        $result = $this->db->query($sql, $this->bindings);
        return $result[0]['aggregate'] ?? null;
    }
    public function toArray()
    {
        return $this->get();
    }
    public function toJson(int $options = 0)
    {
        return json_encode($this->get(), $options);
    }

    /**
     * QueryBuilder constructor.
     * 
     * @param Database $db Instance of the Database class.
     */
    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Specifies the table to query.
     * 
     * @param string $table Table name.
     * @return self
     */
    public function table(string $table)
    {
        $this->table = $table;
        return $this;
    }

    /**
     * Alias for the table() method.
     * 
     * @param string $table Table name.
     * @return self
     */
    public function from(string $table)
    {
        return $this->table($table);
    }

    /**
     * Allows the builder to execute raw SQL.
     * 
     * @param string $sql Raw SQL statement.
     * @param array $bindings Values to bind to the SQL.
     * @return self
     */
    public function raw(string $sql, array $bindings = [])
    {
        $this->rawSql = $sql;
        $this->bindings = $bindings;
        return $this;
    }

    /**
     * Selects columns to query.
     * 
     * @param array $columns List of columns.
     * @return self
     */
    public function select(array $columns)
    {
        $this->columns = $columns;
        return $this;
    }

    /**
     * Builds a WHERE condition.
     * 
     * @param string|Closure $column Column name or Closure for subquery.
     * @param string|null $operator Comparison operator.
     * @param mixed|null $value Comparison value.
     * @param string $boolean Logical operator (AND/OR).
     * @return self
     */
    public function where($column, $operator = null, $value = null, string $boolean = 'AND')
    {
        if ($column instanceof \Closure) {
            $sub = new QueryBuilder($this->db);
            $column($sub);
            $sql = $sub->buildWhere();
            $inner = trim(substr($sql, 6)); // remove "WHERE "
            $this->wheres[] = [
                'raw' => "({$inner})",
                'boolean' => $boolean,
            ];
            $this->bindings = array_merge($this->bindings, $sub->getBindings());
            return $this;
        }

        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = compact('column', 'operator', 'value', 'boolean');
        $this->bindings[] = $value;
        return $this;
    }

    /**
     * Alias for the where() method with OR boolean.
     * 
     * @param string $column Column name.
     * @param string $operator Comparison operator.
     * @param mixed|null $value Comparison value.
     * @return self
     */
    public function orWhere(string $column, $operator, $value = null)
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        return $this->where($column, $operator, $value, 'OR');
    }

    /**
     * Performs a WHERE condition for a JSON column.
     * 
     * @param string $column JSON column name.
     * @param string $path Path within the JSON.
     * @param mixed $value Value to compare.
     * @param string $operator Comparison operator.
     * @param string $boolean Logical operator (AND/OR).
     * @return self
     */
    public function whereJson(string $column, string $path, $value, $operator = "=", string $boolean = 'AND')
    {
        $jsonColumn = 'JSON_EXTRACT(' . $column . ', "$.' . $path . '")';
        if (is_numeric($value)) {
            $jsonColumn = "CAST($jsonColumn AS SIGNED)";
        }
        $this->wheres[] = [
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => $boolean,
            'raw' => "$jsonColumn $operator ?"
        ];

        $this->bindings[] = $value;
        return $this;
    }

    /**
     * Builds a WHERE condition for an IN clause.
     * 
     * @param string $column Column name.
     * @param array|Closure $values Values to compare.
     * @param string $boolean Logical operator (AND/OR).
     * @return self
     */
    public function whereIn(string $column, $values, string $boolean = 'AND')
    {

        if (is_array($values)) {
            if (count($values) === 0) {
                $this->wheres[] = ['raw' => '0 = 1', 'boolean' => $boolean];
                return $this;
            }
            $placeholders = implode(',', array_fill(0, count($values), '?'));
            $this->wheres[]    = ['raw' => "{$column} IN ({$placeholders})", 'boolean' => $boolean];
            $this->bindings    = array_merge($this->bindings, $values);
            return $this;
        } else {
            $sub = new QueryBuilder($this->db);
            $values($sub);
            $sqlSub = $sub->toSql();
            $this->wheres[] = ['raw' => "{$column} IN ({$sqlSub})", 'boolean' => $boolean];
            $this->bindings = array_merge($this->bindings, $sub->getBindings());
        }
        return $this;
    }

    /**
     * Performs a JOIN between two tables.
     * 
     * @param string $table Table name.
     * @param string $first First column.
     * @param string $operator Comparison operator.
     * @param string $second Second column.
     * @param string $type Type of JOIN (INNER, LEFT, etc.).
     * @return self
     */
    public function join(string $table, string $first, string $operator, string $second, string $type = 'INNER')
    {
        $this->joins[] = compact('type', 'table', 'first', 'operator', 'second');
        return $this;
    }

    /**
     * Performs a LEFT JOIN between two tables.
     * 
     * @param string $table Table name.
     * @param string $first First column.
     * @param string $operator Comparison operator.
     * @param string $second Second column.
     * @return self
     */
    public function leftJoin(string $table, string $first, string $operator, string $second)
    {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }

    /**
     * Specifies the sorting order of the results.
     * 
     * @param string $column Column name to sort by.
     * @param string $direction Sort direction (ASC or DESC).
     * @return self
     */
    public function orderBy(string $column, string $direction = 'ASC')
    {
        $this->orders[] = ['column' => $column, 'direction' => strtoupper($direction)];
        return $this;
    }

    /**
     * Specifies columns to group the results by.
     * 
     * @param string ...$columns List of columns.
     * @return self
     */
    public function groupBy(string ...$columns)
    {
        $this->groups = array_merge($this->groups, $columns);
        return $this;
    }

    /**
     * Builds a HAVING condition.
     * 
     * @param string $column Column name.
     * @param string $operator Comparison operator.
     * @param mixed $value Value to compare.
     * @return self
     */
    public function having(string $column, string $operator, $value)
    {
        $this->havings[] = compact('column', 'operator', 'value');
        $this->bindings[] = $value;
        return $this;
    }

    /**
     * Specifies the maximum number of records to retrieve.
     * 
     * @param int $limit Number of records.
     * @return self
     */
    public function limit(int $limit)
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Specifies the starting position for retrieving records.
     * 
     * @param int $offset Starting position.
     * @return self
     */
    public function offset(int $offset)
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * Executes a callback if the condition is met.
     * 
     * @param mixed $condition Condition to check.
     * @param callable $callback Callback to execute if the condition is met.
     * @return self
     */
    public function when($condition, callable $callback)
    {
        if ($condition) {
            $callback($this);
        }
        return $this;
    }

    /**
     * Builds the WHERE clause of the SQL statement.
     * 
     * @return string WHERE clause.
     */
    protected function buildWhere()
    {
        if (empty($this->wheres)) return '';
        $parts = [];
        foreach ($this->wheres as $i => $w) {
            $bool = $i === 0 ? '' : ' ' . ($w['boolean'] ?? 'AND') . ' ';
            if (isset($w['raw'])) {
                $parts[] = $bool . $w['raw'];
            } else {
                $parts[] = $bool . "{$w['column']} {$w['operator']} ?";
            }
        }
        return 'WHERE ' . implode('', $parts);
    }

    /**
     * Retrieves the final SQL statement as a string.
     * 
     * @param bool $isDebug Enable debug mode to replace parameter bindings in the SQL statement.
     * @return string SQL statement.
     */
    public function toSql($isDebug = false)
    {
        if ($this->rawSql !== null) {
            return $this->rawSql;
        }
        $select = $this->isDistinct ? 'SELECT DISTINCT' : 'SELECT';
        $sql = $select . ' ' . implode(', ', $this->columns)
            . ' FROM ' . $this->table;
        foreach ($this->joins as $j) {
            $sql .= " {$j['type']} JOIN {$j['table']} ON {$j['first']} {$j['operator']} {$j['second']}";
        }
        $where = $this->buildWhere();
        if ($where) $sql .= ' ' . $where;
        if (!empty($this->groups)) {
            $sql .= ' GROUP BY ' . implode(', ', $this->groups);
        }
        if (!empty($this->havings)) {
            $h = array_map(fn($h) => "{$h['column']} {$h['operator']} ?", $this->havings);
            $sql .= ' HAVING ' . implode(' AND ', $h);
        }
        if (!empty($this->orders)) {
            $parts = array_map(fn($o) => "{$o['column']} {$o['direction']}", $this->orders);
            $sql .= ' ORDER BY ' . implode(', ', $parts);
        }
        if (isset($this->limit)) {
            $sql .= ' LIMIT ' . $this->limit;
        }
        if (isset($this->offset)) {
            $sql .= ' OFFSET ' . $this->offset;
        }
        if ($isDebug) {
            $sql = $this->replaceBindings($sql, $this->bindings);
        }
        return $sql;
    }

    /**
     * Replaces placeholders in the SQL with actual binding values.
     * 
     * @param string $sql SQL statement.
     * @param array $bindings Binding values.
     * @return string SQL statement with replaced parameters.
     */
    private function replaceBindings(string $sql, array $bindings)
    {
        foreach ($bindings as $binding) {
            if (is_string($binding)) {
                $binding = "'$binding'";
            } elseif (is_null($binding)) {
                $binding = 'NULL';
            } elseif (is_bool($binding)) {
                $binding = $binding ? 'TRUE' : 'FALSE';
            }

            $sql = preg_replace('/\?/', $binding, $sql, 1);
        }
        return $sql;
    }

    /**
     * Executes the query and retrieves all results.
     * 
     * @return array Array of results.
     */
    /**
     * Relations to load on fetch.
     * @var array
     */
    protected $eagerLoad = [];

    /**
     * @var object|null
     */
    protected $model = null;

    /**
     * Let builder know which model it belongs to
     */
    public function setModel(object $model)
    {
        $this->model = $model;
        return $this;
    }

    /**
     * Get the registered relations metadata array (hasOne/hasMany…)
     */
    public function getRelationDefinitions()
    {
        return $this->relations;
    }

    /**
     * Merge new metadatas into the builder
     */
    public function addRelationDefinitions(array $defs)
    {
        $this->relations = array_merge($this->relations, $defs);
        return $this;
    }

    /**
     * Register relations for eager-loading.
     *
     * @param array $relations
     * @return self
     */
    public function with(array $relations)
    {
        $this->eagerLoad = $relations;

        if ($this->model) {
            foreach ($relations as $name) {
                if (method_exists($this->model, $name)) {
                    $relationBuilder = $this->model->$name();
                    $this->addRelationDefinitions($relationBuilder->getRelationDefinitions());
                }
            }
        }

        return $this;
    }

    /**
     * Override get() to perform batch eager-loading.
     *
     * @return array
     */
    public function get(): array
    {
        // 1) Fetch base records
        $results    = $this->db->query($this->toSql(), $this->bindings);
        $collection = array_map(fn($r) => (array)$r, $results);

        // 2) If no eager-load relations, return
        if (empty($this->eagerLoad) || empty($this->relations)) {
            return $collection;
        }

        // 3) Process each registered relation
        foreach ($this->relations as $relation) {
            $name = $relation['relationName'];
            if (! in_array($name, $this->eagerLoad, true)) {
                continue;
            }

            switch ($relation['type']) {
                case 'hasOne':
                    $localKey = $relation['localKey'];
                    $keys     = array_unique(array_column($collection, $localKey));
                    if (empty($keys)) {
                        foreach ($collection as &$item) {
                            $item[$name] = null;
                        }
                        break;
                    }

                    $related = (new QueryBuilder($this->db))
                        ->table($relation['relatedTable'])
                        ->select($relation['select'])
                        ->whereIn($relation['foreignKeyOnRelated'], $keys)
                        ->get();

                    $map = [];
                    foreach ($related as $row) {
                        $map[$row[$relation['foreignKeyOnRelated']]] = $row;
                    }
                    foreach ($collection as &$item) {
                        $item[$name] = $map[$item[$localKey]] ?? null;
                    }
                    break;

                case 'hasMany':
                    $localKey = $relation['localKey'];
                    $keys     = array_unique(array_column($collection, $localKey));
                    if (empty($keys)) {
                        foreach ($collection as &$item) {
                            $item[$name] = [];
                        }
                        break;
                    }

                    $related = (new QueryBuilder($this->db))
                        ->table($relation['relatedTable'])
                        ->select($relation['select'])
                        ->whereIn($relation['foreignKeyOnRelated'], $keys)
                        ->get();

                    $map = [];
                    foreach ($related as $row) {
                        $map[$row[$relation['foreignKeyOnRelated']]][] = $row;
                    }
                    foreach ($collection as &$item) {
                        $item[$name] = $map[$item[$localKey]] ?? [];
                    }
                    break;

                case 'hasOneThrough':
                    $localKey = $relation['localKey'];
                    $keys     = array_unique(array_column($collection, $localKey));
                    if (empty($keys)) {
                        foreach ($collection as &$item) {
                            $item[$name] = null;
                        }
                        break;
                    }

                    $rows = (new QueryBuilder($this->db))
                        ->table($relation['intermediateTable'])
                        ->select($relation['select'])
                        ->join(
                            $relation['targetTable'],
                            "{$relation['targetTable']}.{$relation['foreignKeyOnTarget']}",
                            '=',
                            "{$relation['intermediateTable']}.{$relation['localKey']}"
                        )
                        ->whereIn(
                            "{$relation['intermediateTable']}.{$relation['foreignKeyOnIntermediate']}",
                            $keys
                        )
                        ->get();

                    $map = [];
                    foreach ($rows as $row) {
                        $map[$row[$relation['foreignKeyOnIntermediate']]] = $row;
                    }
                    foreach ($collection as &$item) {
                        $item[$name] = $map[$item[$localKey]] ?? null;
                    }
                    break;

                case 'hasManyThrough':
                    $localKey = $relation['localKey'];
                    $keys     = array_unique(array_column($collection, $localKey));
                    if (empty($keys)) {
                        foreach ($collection as &$item) {
                            $item[$name] = [];
                        }
                        break;
                    }

                    $rows = (new QueryBuilder($this->db))
                        ->table($relation['intermediateTable'])
                        ->select($relation['select'])
                        ->join(
                            $relation['targetTable'],
                            "{$relation['targetTable']}.{$relation['foreignKeyOnTarget']}",
                            '=',
                            "{$relation['intermediateTable']}.{$relation['localKey']}"
                        )
                        ->whereIn(
                            "{$relation['intermediateTable']}.{$relation['foreignKeyOnIntermediate']}",
                            $keys
                        )
                        ->get();

                    $map = [];
                    foreach ($rows as $row) {
                        $map[$row[$relation['foreignKeyOnIntermediate']]][] = $row;
                    }
                    foreach ($collection as &$item) {
                        $item[$name] = $map[$item[$localKey]] ?? [];
                    }
                    break;

                case 'manyToMany':
                    $parentKey = $relation['parentKey'];
                    $keys      = array_unique(array_column($collection, $parentKey));
                    if (empty($keys)) {
                        foreach ($collection as &$item) {
                            $item[$name] = [];
                        }
                        break;
                    }

                    $rows = (new QueryBuilder($this->db))
                        ->table($relation['pivotTable'])
                        ->select(array_merge(
                            ["{$relation['pivotTable']}.{$relation['pivotForeignKey']} as pivot_fk"],
                            array_map(
                                fn($col) =>
                                $col === '*'
                                    ? "{$relation['relatedTable']}.*"
                                    : "{$relation['relatedTable']}.$col as $col",
                                $relation['select']
                            )
                        ))
                        ->join(
                            $relation['relatedTable'],
                            "{$relation['relatedTable']}.{$relation['relatedKey']}",
                            '=',
                            "{$relation['pivotTable']}.{$relation['pivotRelatedKey']}"
                        )
                        ->whereIn(
                            "{$relation['pivotTable']}.{$relation['pivotForeignKey']}",
                            $keys
                        )
                        ->get();

                    $map = [];
                    foreach ($rows as $r) {
                        $fk = $r['pivot_fk'];
                        unset($r['pivot_fk']);
                        $map[$fk][] = $r;
                    }

                    foreach ($collection as &$item) {
                        $item[$name] = $map[$item[$parentKey]] ?? [];
                    }
                    break;
            }
        }

        return $collection;
    }

    /**
     * Retrieves the first record.
     * 
     * @return mixed First record or null if no results.
     */
    public function first()
    {
        $this->limit(1);
        $res = $this->get();
        return $res[0] ?? null;
    }

    /**
     * Retrieves the number of records.
     * 
     * @return int Number of records.
     */
    public function count()
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} " . $this->buildWhere();
        $result = $this->db->query($sql, $this->bindings);
        return $result[0]['count'];
    }
    /**
     * Paginates the results.
     * 
     * @param int $perPage Number of records per page.
     * @param int|null $page Page number.
     * @return array Paginated data.
     */
    public function paginate(int $perPage = 10, ?int $page = null, $totalget = false)
    {
        if ($page === null) {
            $uri = $_SERVER['REQUEST_URI'];

            $pathParts = explode('/', $uri);

            $key = array_search('paged', $pathParts);
            $page = ($key !== false && isset($pathParts[$key + 1])) ? (int)$pathParts[$key + 1] : 1;
        }
        $offset = ($page - 1) * $perPage;
        // if $total is true, then get total
        if ($totalget) {
            $totalSql = "SELECT COUNT(*) as total FROM {$this->table} " . $this->buildWhere();
            $total = $this->db->query($totalSql, $this->bindings)[0]['total'];
            $data = $this->limit($perPage)->offset($offset)->get();
            return [
                'data'        => $data,
                'total'       => (int)$total,
                'per_page'    => $perPage,
                'current_page' => $page,
                'page'         => $page,
                'is_next'     => (int)$page < ceil($total / $perPage),
                'last_page'   => ceil($total / $perPage)
            ];
        } else {
            // get data limit + 1 (check next page)
            $data = $this->limit($perPage + 1)->offset($offset)->get();
            if (count($data) > $perPage) {
                $hasNextPage = true;
                array_pop($data);
            } else {
                $hasNextPage = false;
            }
            return [
                'data'        => $data,
                'per_page'    => $perPage,
                'current_page' => $page,
                'page'         => $page,
                'is_next'     => $hasNextPage,
            ];
        }
    }

    /**
     * Returns an array of values for a specific column.
     * 
     * @param string $column Column name to retrieve values from.
     * @return array Array of column values.
     */
    public function pluck(string $column)
    {
        return array_column($this->get(), $column);
    }

    /**
     * Retrieves the binding values.
     * 
     * @return array Array of binding values.
     */
    public function getBindings()
    {
        return $this->bindings;
    }

    // =================
    // INSERT / UPDATE / DELETE
    // =================

    /**
     * Performs an INSERT operation and returns the ID of the inserted record.
     * 
     * @param array $data Data to insert into the table.
     * @return int ID of the inserted record.
     */
    public function insert(array $data)
    {
        $this->db->insert($this->table, $data);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Performs an INSERT operation and returns the ID of the inserted record.
     * 
     * @param array $data Data to insert.
     * @return int ID of the inserted record.
     */
    public function insertGetId(array $data)
    {
        return $this->insert($data);
    }

    /**
     * Performs an INSERT or UPDATE operation depending on whether the record exists.
     * 
     * If the record exists (based on the WHERE condition), this method performs an UPDATE.
     * If the record does not exist, this method performs an INSERT.
     * 
     * @param array $data Data to insert or update.
     * @param array $updateData Data to update if the record exists.
     * @return int ID of the inserted record or number of affected records if updated.
     */
    public function insertOrUpdate(array $data, array $updateData = [])
    {
        $whereSql = $this->buildWhere();

        $existing = $this->db->query("SELECT COUNT(*) as count FROM {$this->table} $whereSql", $this->bindings);

        if ($existing[0]['count'] > 0) {
            return $this->update($updateData);
        } else {
            return $this->insert($data);
        }
    }

    /**
     * Performs an UPDATE operation based on the built conditions.
     * 
     * @param array $data Data to update.
     * @return int Number of affected records.
     */
    public function update(array $data, $timestamps = true)
    {
        $whereSql = $this->buildWhere();
        return $this->db->update(
            $this->table,
            $data,
            trim(str_replace('WHERE ', '', $whereSql)),
            $this->bindings
        );
    }

    /**
     * Performs a DELETE operation based on the built conditions.
     * 
     * @return int Number of deleted records.
     */
    public function delete()
    {
        $whereSql = $this->buildWhere();
        return $this->db->delete(
            $this->table,
            trim(str_replace('WHERE ', '', $whereSql)),
            $this->bindings
        );
    }

    /**
     * Define a hasOne relationship between the main table and a related table.
     *
     * @param string $relatedTable         Name of the related table.
     * @param string $foreignKeyOnRelated  Foreign key column on the related table.
     * @param string $localKey             Local key column on the main table.
     * @param string $relationName         Key name for the relation in results.
     * @param array  $select               Columns to select from related table.
     * @return self
     */
    public function hasOne(
        string $relatedTable,
        string $foreignKeyOnRelated,
        string $localKey,
        string $relationName = 'relation',
        array $select = ['*']
    ) {
        $type = 'hasOne';
        $this->relations[] = compact(
            'relatedTable',
            'foreignKeyOnRelated',
            'localKey',
            'relationName',
            'type',
            'select'
        );
        return $this;
    }

    /**
     * Retrieve a hasOne relationship record.
     *
     * @param array  $relation   Relation metadata.
     * @param mixed  $localValue Value of the local key.
     * @return array
     */
    protected function getHasOneRelation(array $relation, $localValue)
    {
        $query = (new QueryBuilder($this->db))
            ->table($relation['relatedTable'])
            ->select($relation['select'])
            ->where(
                "{$relation['foreignKeyOnRelated']}",
                '=',
                $localValue
            );

        return [$relation['relationName'] => $query->first()];
    }
    /**
     * Define a hasMany relationship between the main table and a related table.
     *
     * @param string $relatedTable         Name of the related table.
     * @param string $foreignKeyOnRelated  Foreign key column on the related table.
     * @param string $localKey             Local key column on the main table.
     * @param string $relationName         Key name for the relation in results.
     * @param array  $select               Columns to select from related table.
     * @return self
     */
    public function hasMany(
        string $relatedTable,
        string $foreignKeyOnRelated,
        string $localKey,
        string $relationName = 'relation',
        array $select = ['*']
    ) {
        $type = 'hasMany';
        $this->relations[] = compact(
            'relatedTable',
            'foreignKeyOnRelated',
            'localKey',
            'relationName',
            'type',
            'select'
        );
        return $this;
    }

    /**
     * Retrieve a hasMany relationship records.
     *
     * @param array  $relation   Relation metadata.
     * @param mixed  $localValue Value of the local key.
     * @return array
     */
    protected function getHasManyRelation(array $relation, $localValue)
    {
        $query = (new QueryBuilder($this->db))
            ->table($relation['relatedTable'])
            ->select($relation['select'])
            ->where(
                "{$relation['foreignKeyOnRelated']}",
                '=',
                $localValue
            );

        return [$relation['relationName'] => $query->get()];
    }

    /**
     * Define a hasOneThrough relationship through an intermediate table.
     *
     * @param string $targetTable                Final related table.
     * @param string $intermediateTable          Intermediate table name.
     * @param string $foreignKeyOnIntermediate   Foreign key column on intermediate table referencing main.
     * @param string $foreignKeyOnTarget         Foreign key column on target table referencing intermediate.
     * @param string $localKey                   Local key column on main table.
     * @param string $relationName               Key name for the relation in results.
     * @param array  $select                     Columns to select from target table.
     * @return self
     */
    public function hasOneThrough(
        string $targetTable,
        string $intermediateTable,
        string $foreignKeyOnIntermediate,
        string $foreignKeyOnTarget,
        string $localKey,
        string $relationName = 'relation',
        array $select = ['*']
    ) {
        $type = 'hasOneThrough';
        $this->relations[] = compact(
            'targetTable',
            'intermediateTable',
            'foreignKeyOnIntermediate',
            'foreignKeyOnTarget',
            'localKey',
            'relationName',
            'type',
            'select'
        );
        return $this;
    }

    /**
     * Retrieve a hasOneThrough relationship record.
     *
     * @param array  $relation   Relation metadata.
     * @param mixed  $localValue Value of the local key.
     * @return array
     */
    protected function getHasOneThroughRelation(array $relation, $localValue)
    {
        $query = (new QueryBuilder($this->db))
            ->table($relation['intermediateTable'])
            ->select($relation['select'])
            ->join(
                $relation['targetTable'],
                "{$relation['targetTable']}.{$relation['foreignKeyOnTarget']}",
                '=',
                "{$relation['intermediateTable']}.{$relation['localKey']}"
            )
            ->where(
                "{$relation['intermediateTable']}.{$relation['foreignKeyOnIntermediate']}",
                '=',
                $localValue
            );

        return [$relation['relationName'] => $query->first()];
    }

    /**
     * Define a hasManyThrough relationship through an intermediate table.
     *
     * @param string $targetTable                Final related table.
     * @param string $intermediateTable          Intermediate table name.
     * @param string $foreignKeyOnIntermediate   Foreign key column on intermediate table referencing main.
     * @param string $foreignKeyOnTarget         Foreign key column on target table referencing intermediate.
     * @param string $localKey                   Local key column on main table.
     * @param string $relationName               Key name for the relation in results.
     * @param array  $select                     Columns to select from target table.
     * @return self
     */
    public function hasManyThrough(
        string $targetTable,
        string $intermediateTable,
        string $foreignKeyOnIntermediate,
        string $foreignKeyOnTarget,
        string $localKey,
        string $relationName = 'relation',
        array $select = ['*']
    ) {
        $type = 'hasManyThrough';
        $this->relations[] = compact(
            'targetTable',
            'intermediateTable',
            'foreignKeyOnIntermediate',
            'foreignKeyOnTarget',
            'localKey',
            'relationName',
            'type',
            'select'
        );
        return $this;
    }

    /**
     * Defines a many-to-many relationship.
     *
     * @param string $relatedTable The name of the target table (e.g. 'fast_terms')
     * @param string $pivotTable The name of the pivot table (e.g. 'fast_posts_movie_rel')
     * @param string $pivotForeignKey The name of the FK column on the pivot that points to the parent table (e.g. 'post_id')
     * @param string $pivotRelatedKey The name of the FK column on the pivot that points to the target table (e.g. 'term_id')
     * @param string $parentKey The name of the parent table's primary key (default 'id')
     * @param string $relatedKey The name of the target table's primary key (default 'id')
     * @param string $relationName The key name for the resulting array (e.g. 'categories')
     * @param array $select Columns to retrieve from the target table
     * @return self
     */
    public function belongsToMany(
        string $relatedTable,
        string $pivotTable,
        string $pivotForeignKey,
        string $pivotRelatedKey,
        string $parentKey     = 'id',
        string $relatedKey    = 'id',
        string $relationName  = null,
        array  $select        = ['*']
    ): self {
        $relationName = $relationName ?: $relatedTable;
        $this->relations[] = [
            'type'             => 'manyToMany',
            'relatedTable'     => $relatedTable,
            'pivotTable'       => $pivotTable,
            'pivotForeignKey'  => $pivotForeignKey,
            'pivotRelatedKey'  => $pivotRelatedKey,
            'parentKey'        => $parentKey,
            'relatedKey'       => $relatedKey,
            'relationName'     => $relationName,
            'select'           => $select,
        ];
        return $this;
    }


    /**
     * Retrieve a hasManyThrough relationship records.
     *
     * @param array  $relation   Relation metadata.
     * @param mixed  $localValue Value of the local key.
     * @return array
     */
    protected function getHasManyThroughRelation(array $relation, $localValue)
    {
        $query = (new QueryBuilder($this->db))
            ->table($relation['intermediateTable'])
            ->select($relation['select'])
            ->join(
                $relation['targetTable'],
                "{$relation['targetTable']}.{$relation['foreignKeyOnTarget']}",
                '=',
                "{$relation['intermediateTable']}.{$relation['localKey']}"
            )
            ->where(
                "{$relation['intermediateTable']}.{$relation['foreignKeyOnIntermediate']}",
                '=',
                $localValue
            );

        return [$relation['relationName'] => $query->get()];
    }

    /**
     * Retrieves related data based on a hasMany relationship.
     *
     * @param array $relation Table name and foreign key column.
     * @param int $id Primary key value of the main table.
     * @return array
     */
    protected function getRelation(array $relation, string $localValue)
    {
        switch ($relation['type']) {
            case 'hasMany':
                return $this->getHasManyRelation($relation, $localValue);
            case 'hasOne':
                return $this->getHasOneRelation($relation, $localValue);
            case 'hasOneThrough':
                return $this->getHasOneThroughRelation($relation, $localValue);
            case 'hasManyThrough':
                return $this->getHasManyThroughRelation($relation, $localValue);
        }

        return [];
    }
}
