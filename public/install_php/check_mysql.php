<?php
/**
 * Check MySQL database connection
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

function checkMySQLConnection($config) {
    $errors = [];
    
    // Validate required fields
    $required = ['host', 'port', 'name', 'user'];
    foreach ($required as $field) {
        if (empty($config[$field])) {
            $errors[$field] = ucfirst($field) . ' is required';
        }
    }
    
    if (!empty($errors)) {
        return [
            'status' => 'error',
            'message' => 'Missing required database configuration',
            'errors' => $errors
        ];
    }
    
    try {
        // Test connection directly with the specified database
        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['name']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['user'], $config['password'] ?? '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Test a simple query
        $stmt = $pdo->query("SELECT 1");
        $result = $stmt->fetch();
        
        if (!$result) {
            throw new Exception('Database connection test failed');
        }
        
        // Get MySQL version
        $stmt = $pdo->query("SELECT VERSION() as version");
        $versionResult = $stmt->fetch();
        $mysqlVersion = $versionResult['version'] ?? 'Unknown';
        
        // Get database charset and collation
        $stmt = $pdo->prepare("SELECT DEFAULT_CHARACTER_SET_NAME, DEFAULT_COLLATION_NAME 
                              FROM INFORMATION_SCHEMA.SCHEMATA 
                              WHERE SCHEMA_NAME = ?");
        $stmt->execute([$config['name']]);
        $dbInfo = $stmt->fetch();
        
        return [
            'status' => 'success',
            'message' => 'Database connection successful',
            'data' => [
                'host' => $config['host'],
                'port' => $config['port'],
                'database' => $config['name'],
                'mysql_version' => $mysqlVersion,
                'database_exists' => true,
                'charset' => $dbInfo['DEFAULT_CHARACTER_SET_NAME'] ?? 'utf8mb4',
                'collation' => $dbInfo['DEFAULT_COLLATION_NAME'] ?? 'utf8mb4_unicode_ci'
            ]
        ];
        
    } catch (PDOException $e) {
        $errorCode = $e->getCode();
        $errorMessage = $e->getMessage();
        
        // Parse common MySQL errors
        if (strpos($errorMessage, 'Access denied') !== false) {
            return [
                'status' => 'error',
                'message' => 'Database authentication failed',
                'errors' => [
                    'credentials' => 'Invalid username or password'
                ]
            ];
        } elseif (strpos($errorMessage, 'Connection refused') !== false) {
            return [
                'status' => 'error',
                'message' => 'Cannot connect to database server',
                'errors' => [
                    'connection' => 'Database server is not accessible. Check host and port.'
                ]
            ];
        } elseif (strpos($errorMessage, 'Unknown database') !== false) {
            return [
                'status' => 'error',
                'message' => 'Database does not exist',
                'errors' => [
                    'database' => 'The specified database does not exist. Please create the database first or check the database name.'
                ]
            ];
        } else {
            return [
                'status' => 'error',
                'message' => 'Database connection failed',
                'errors' => [
                    'database' => $errorMessage
                ]
            ];
        }
    } catch (Exception $e) {
        return [
            'status' => 'error',
            'message' => 'Unexpected error occurred',
            'errors' => [
                'system' => $e->getMessage()
            ]
        ];
    }
}

// Handle the request
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = file_get_contents('php://input');
        $config = json_decode($input, true);
        
        if (!$config) {
            throw new Exception('Invalid JSON data');
        }
        
        $result = checkMySQLConnection($config);
        
    } else {
        $result = [
            'status' => 'error',
            'message' => 'Only POST requests are allowed'
        ];
    }
    
    http_response_code($result['status'] === 'success' ? 200 : 400);
    echo json_encode($result, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Internal server error',
        'errors' => ['system' => $e->getMessage()]
    ], JSON_PRETTY_PRINT);
}
?>
