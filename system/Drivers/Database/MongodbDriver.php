<?php
namespace System\Drivers\Database;
use MongoDB\Client;
use MongoDB\Driver\Exception\Exception as MongoDBException;
use System\Core\AppException;

class MongodbDriver extends Database {

    protected $client;
    protected $db;
    protected $lastInsertedId;

    /**
     * Initialize MongoDB connection
     * 
     * @param array $config Array containing connection configuration information
     */
    public function __construct($config) {
        try {
            $uri = $config['mongo_uri'];
            $options = $config['mongo_options'] ?? [];
            $driverOptions = $config['mongo_driver_options'] ?? [];
            $this->client = new Client($uri, $options, $driverOptions);
            $this->db = $this->client->selectDatabase($config['mongo_database']);
        } catch (MongoDBException $e) {
            throw new \System\Core\AppException("Connect MongodbDriver failed: " . $e->getMessage(), 500);
        }
    }

    /**
     * Execute arbitrary query
     * 
     * @param array $command MongoDB command array to execute
     * @return mixed Query result
     */
    public function query($command, $params = []) {
        try {
            $result = $this->db->command($command)->toArray();
            return $result;
        } catch (MongoDBException $e) {
            throw new \System\Core\AppException("MongodbDriver->query(): " . $e->getMessage(), 500);
        }
    }

    /**
     * Get ID of the last inserted record
     * 
     * @return mixed ID of the last inserted record
     */
    public function lastInsertId() {
        return $this->lastInsertedId ?? null;
    }

    /**
     * Count records in collection
     * 
     * @param string $collection Collection name to count records
     * @param string|array $where WHERE condition to count records (optional)
     * @param array $params Array of values corresponding to parameters in WHERE condition (optional)
     * @return int Number of records in collection
     */
    public function count($collection, $where = '', $params = []) {
        try {
            $filter = $this->buildFilter($where, $params);
            return $this->db->$collection->countDocuments($filter);
        } catch (MongoDBException $e) {
            throw new \System\Core\AppException("MongodbDriver->count(): " . $e->getMessage(), 500);
        }
    }

    /**
     * Execute SELECT query to get multiple rows
     * 
     * @param string $collection Collection name to query
     * @param string|array $where WHERE condition (optional)
     * @param array $params Array of values corresponding to parameters in WHERE condition (optional)
     * @param string $orderBy ORDER BY clause (optional)
     * @param int $page Current page position (optional)
     * @param int $limit Number of results to limit (optional)
     * @return array Array containing query results
     */
    public function fetchAll($collection, $where = '', $params = [], $orderBy = '', $page = 1, $limit = null) {
        try {
            $filter = $this->buildFilter($where, $params);
            $options = [];
            if ($orderBy) {
                $options['sort'] = $this->buildSort($orderBy);
            }
            if (!is_null($limit)) {
                $page = max((int)$page, 1);
                $limit = (int)$limit;
                $skip = ($page - 1) * $limit;
                $options['limit'] = $limit;
                $options['skip'] = $skip;
            }
            $cursor = $this->db->$collection->find($filter, $options);
            return iterator_to_array($cursor);
        } catch (MongoDBException $e) {
            throw new \System\Core\AppException("MongodbDriver->fetchAll(): " . $e->getMessage(), 500);
        }
    }

    /**
     * Execute SELECT query to get multiple rows with pagination
     * 
     * @param string $collection Collection name
     * @param string|array $where WHERE condition (optional)
     * @param array $params Array of values corresponding to WHERE condition (optional)
     * @param string $orderBy ORDER BY clause (optional)
     * @param int $page Current page position (optional)
     * @param int $limit Number of results returned per page (optional)
     * @return array Query result and information about whether there's a next page
     */
    public function fetchPagination($collection, $where = '', $params = [], $orderBy = '', $page = 1, $limit = null) {
        try {
            $hasNextPage = false;
            $page = max((int)$page, 1);
            $limit = (int)$limit ?: 10;
            $skip = ($page - 1) * $limit;

            $filter = $this->buildFilter($where, $params);
            $options = [
                'limit' => $limit + 1,
                'skip' => $skip,
            ];
            if ($orderBy) {
                $options['sort'] = $this->buildSort($orderBy);
            }

            $cursor = $this->db->$collection->find($filter, $options);
            $results = iterator_to_array($cursor);

            if (count($results) > $limit) {
                $hasNextPage = true;
                array_pop($results);
            }

            return [
                'data' => $results,
                'is_next' => $hasNextPage,
                'page' => $page
            ];
        } catch (MongoDBException $e) {
            throw new \System\Core\AppException("MongodbDriver->fetchPagination(): " . $e->getMessage(), 500);
        }
    }

    /**
     * Execute SELECT query to get 1 row
     * 
     * @param string $collection Collection name to query
     * @param string|array $where WHERE condition
     * @param array $params Array of values corresponding to parameters in WHERE condition
     * @param string $orderBy ORDER BY clause (optional)
     * @param int $page Current page position (optional)
     * @return array|null Array containing query result or null if no result
     */
    public function fetchRow($collection, $where = '', $params = [], $orderBy = '', $page = 1) {
        try {
            $filter = $this->buildFilter($where, $params);
            $options = [];
            if ($orderBy) {
                $options['sort'] = $this->buildSort($orderBy);
            }
            $skip = ($page - 1);
            if ($skip > 0) {
                $options['skip'] = $skip;
            }
            $options['limit'] = 1;

            $cursor = $this->db->$collection->find($filter, $options);
            $result = iterator_to_array($cursor);

            return $result[0] ?? null;
        } catch (MongoDBException $e) {
            throw new \System\Core\AppException("MongodbDriver->fetchRow(): " . $e->getMessage(), 500);
        }
    }

    /**
     * Execute INSERT query
     * 
     * @param string $collection Collection name to insert data
     * @param array $data Array of data to insert
     * @return bool Returns true if data insertion successful, false otherwise
     */
    public function insert($collection, $data) {
        try {
            $result = $this->db->$collection->insertOne($data);
            if ($result->isAcknowledged()) {
                $this->lastInsertedId = $result->getInsertedId();
                return true;
            }
            return false;
        } catch (MongoDBException $e) {
            throw new \System\Core\AppException("MongodbDriver->insert(): " . $e->getMessage(), 500);
        }
    }

    /**
     * Execute UPDATE query
     * 
     * @param string $collection Collection name to update
     * @param array $data Array of data to update
     * @param string|array $where WHERE condition to update data
     * @param array $params Array of values corresponding to parameters in WHERE condition
     * @return bool Returns true if update successful, false otherwise
     */
    public function update($collection, $data, $where = '', $params = []) {
        try {
            $filter = $this->buildFilter($where, $params);
            $update = ['$set' => $data];
            $result = $this->db->$collection->updateMany($filter, $update);
            return $result->isAcknowledged();
        } catch (MongoDBException $e) {
            throw new \System\Core\AppException("MongodbDriver->update(): " . $e->getMessage(), 500);
        }
    }

    /**
     * Execute DELETE query
     * 
     * @param string $collection Collection name to delete data
     * @param string|array $where WHERE condition to delete data
     * @param array $params Array of values corresponding to parameters in WHERE condition
     * @return bool Returns true if delete successful, false otherwise
     */
    public function delete($collection, $where = '', $params = []) {
        try {
            $filter = $this->buildFilter($where, $params);
            $result = $this->db->$collection->deleteMany($filter);
            return $result->isAcknowledged();
        } catch (MongoDBException $e) {
            throw new \System\Core\AppException("MongodbDriver->delete(): " . $e->getMessage(), 500);
        }
    }

    /**
     * Build filter from $where string and $params array
     * 
     * @param string|array $where Condition string or condition array
     * @param array $params Parameters for condition
     * @return array Filter for MongoDB
     */
    private function buildFilter($where, $params) {
        if (is_array($where)) {
            // If $where is an array, assume it's already a valid MongoDB filter
            return $where;
        } elseif (is_string($where) && !empty($where)) {
            // If $where is a string, we need to parse and convert to MongoDB filter
            // This is a complex task, we'll build a simple parser

            $filter = [];
            $tokens = $this->tokenizeWhereClause($where);
            $filter = $this->parseTokens($tokens, $params);

            return $filter;
        } else {
            // If $where is empty or undefined, return empty filter
            return [];
        }
    }

    /**
     * Tokenize the WHERE clause into an array of tokens
     * 
     * @param string $where WHERE condition string
     * @return array Token array
     */
    private function tokenizeWhereClause($where) {
        // Define patterns for tokens
        $pattern = '/\s*(AND|OR|\(|\)|!=|<=|>=|<>|=|<|>|\bIN\b|\bNOT IN\b|\bLIKE\b|\bBETWEEN\b|\?|\w+|\S)\s*/i';
        preg_match_all($pattern, $where, $matches);
        return $matches[0];
    }

    /**
     * Parse tokens into MongoDB filter
     * 
     * @param array $tokens Token array from WHERE clause
     * @param array $params Parameter array
     * @return array MongoDB filter
     */
    private function parseTokens($tokens, $params) {
        $filter = [];
        $operators = [];
        $operands = [];

        $paramIndex = 0;

        $stack = [];

        while (count($tokens) > 0) {
            $token = array_shift($tokens);
            $tokenUpper = strtoupper($token);

            if (in_array($tokenUpper, ['AND', 'OR'])) {
                // Logical operators
                $operators[] = $tokenUpper;
            } elseif ($token === '(') {
                // Handle sub-expressions
                $subTokens = [];
                $depth = 1;
                while ($depth > 0 && count($tokens) > 0) {
                    $subToken = array_shift($tokens);
                    if ($subToken === '(') {
                        $depth++;
                    } elseif ($subToken === ')') {
                        $depth--;
                    }
                    if ($depth > 0) {
                        $subTokens[] = $subToken;
                    }
                }
                $subFilter = $this->parseTokens($subTokens, $params);
                $operands[] = $subFilter;
            } elseif (preg_match('/^\w+$/', $token)) {
                // Field name
                $field = $token;
                // Get the operator
                $operatorToken = array_shift($tokens);
                $operatorUpper = strtoupper($operatorToken);

                if (in_array($operatorUpper, ['=', '!=', '<>', '>', '<', '>=', '<=', 'IN', 'NOT IN', 'LIKE', 'BETWEEN'])) {
                    // Comparison operators
                    $value = null;
                    if ($operatorUpper === 'BETWEEN') {
                        // BETWEEN needs two parameters
                        $value1 = $params[$paramIndex++] ?? null;
                        $andToken = array_shift($tokens); // Should be 'AND'
                        $value2 = $params[$paramIndex++] ?? null;
                        if ($value1 !== null && $value2 !== null) {
                            $value = ['$gte' => $value1, '$lte' => $value2];
                        }
                    } elseif ($operatorUpper === 'IN' || $operatorUpper === 'NOT IN') {
                        // IN and NOT IN expect a list of parameters (array)
                        $paramValue = $params[$paramIndex++] ?? null;
                        if (is_array($paramValue)) {
                            if ($operatorUpper === 'IN') {
                                $value = ['$in' => $paramValue];
                            } else {
                                $value = ['$nin' => $paramValue];
                            }
                        }
                    } elseif ($operatorUpper === 'LIKE') {
                        // LIKE operator
                        $paramValue = $params[$paramIndex++] ?? null;
                        if ($paramValue !== null) {
                            // Convert SQL LIKE pattern to MongoDB regex
                            $regexPattern = str_replace(['%', '_'], ['.*', '.'], $paramValue);
                            $value = new \MongoDB\BSON\Regex('^' . $regexPattern . '$', 'i');
                        }
                    } else {
                        // Other comparison operators
                        $paramValue = $params[$paramIndex++] ?? null;
                        if ($paramValue !== null) {
                            switch ($operatorUpper) {
                                case '=':
                                    $value = $paramValue;
                                    break;
                                case '!=':
                                case '<>':
                                    $value = ['$ne' => $paramValue];
                                    break;
                                case '>':
                                    $value = ['$gt' => $paramValue];
                                    break;
                                case '<':
                                    $value = ['$lt' => $paramValue];
                                    break;
                                case '>=':
                                    $value = ['$gte' => $paramValue];
                                    break;
                                case '<=':
                                    $value = ['$lte' => $paramValue];
                                    break;
                            }
                        }
                    }
                    if ($value !== null) {
                        $operands[] = [$field => $value];
                    }
                } else {
                    // Invalid operator, skip
                    continue;
                }
            }
        }

        // Build the final filter using the operands and operators
        if (empty($operators)) {
            // Only one condition
            return $operands[0] ?? [];
        } else {
            // Multiple conditions
            $filter = [];
            $currentOperator = null;
            $currentOperands = [];

            for ($i = 0; $i < count($operands); $i++) {
                $currentOperands[] = $operands[$i];
                if (isset($operators[$i])) {
                    $operator = $operators[$i];
                    if ($currentOperator === null) {
                        $currentOperator = $operator;
                    } elseif ($currentOperator !== $operator) {
                        // Different operator, need to nest
                        $filter = $this->combineOperands($filter, $currentOperands, $currentOperator);
                        $currentOperator = $operator;
                        $currentOperands = [];
                    }
                }
            }

            // Combine any remaining operands
            if (!empty($currentOperands)) {
                $filter = $this->combineOperands($filter, $currentOperands, $currentOperator);
            }

            return $filter;
        }
    }

    /**
     * Combine operands using the specified logical operator
     * 
     * @param array $filter Current filter
     * @param array $operands Array of conditions
     * @param string $operator Logical operator ('AND' or 'OR')
     * @return array Combined filter
     */
    private function combineOperands($filter, $operands, $operator) {
        if ($operator === 'AND') {
            if (!isset($filter['$and'])) {
                $filter['$and'] = [];
            }
            $filter['$and'] = array_merge($filter['$and'], $operands);
        } elseif ($operator === 'OR') {
            if (!isset($filter['$or'])) {
                $filter['$or'] = [];
            }
            $filter['$or'] = array_merge($filter['$or'], $operands);
        }
        return $filter;
    }

    /**
     * Build sort options from ORDER BY clause
     * 
     * @param string $orderBy ORDER BY clause
     * @return array Sort options for MongoDB
     */
    private function buildSort($orderBy) {
        $sort = [];
        $fields = explode(',', $orderBy);
        foreach ($fields as $field) {
            $field = trim($field);
            if (stripos($field, 'DESC') !== false) {
                $fieldName = trim(str_ireplace('DESC', '', $field));
                $sort[$fieldName] = -1;
            } else if (stripos($field, 'ASC') !== false) {
                $fieldName = trim(str_ireplace('ASC', '', $field));
                $sort[$fieldName] = 1;
            } else {
                $sort[$field] = 1;
            }
        }
        return $sort;
    }
}
