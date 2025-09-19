<?php
// Kiểm tra posttype trong database
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== CHECK POSTTYPE IN DATABASE ===\n";

try {
    // Kết nối database
    $host = 'localhost';
    $dbname = 'vietnamnet2_vn'; // Thay đổi tên database nếu cần
    $username = 'root'; // Thay đổi username nếu cần
    $password = ''; // Thay đổi password nếu cần
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Kiểm tra bảng fast_posttypes
    $stmt = $pdo->query("SHOW TABLES LIKE 'fast_posttypes'");
    if ($stmt->rowCount() > 0) {
        echo "Table 'fast_posttypes' exists\n";
        
        // Lấy tất cả posttypes
        $stmt = $pdo->query("SELECT * FROM fast_posttypes ORDER BY id");
        $posttypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Total posttypes: " . count($posttypes) . "\n";
        
        foreach ($posttypes as $pt) {
            echo "ID: {$pt['id']}, Name: {$pt['name']}, Slug: {$pt['slug']}, Status: {$pt['status']}\n";
        }
        
        // Kiểm tra ID 2 cụ thể
        $stmt = $pdo->prepare("SELECT * FROM fast_posttypes WHERE id = ?");
        $stmt->execute([2]);
        $posttype = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($posttype) {
            echo "\nPosttype ID 2 found:\n";
            print_r($posttype);
        } else {
            echo "\nPosttype ID 2 NOT FOUND\n";
        }
        
    } else {
        echo "Table 'fast_posttypes' does NOT exist\n";
    }
    
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}

echo "\n=== CHECK OTHER TABLES ===\n";
try {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Available tables:\n";
    foreach ($tables as $table) {
        if (strpos($table, 'post') !== false) {
            echo "- $table\n";
        }
    }
} catch (Exception $e) {
    echo "Error listing tables: " . $e->getMessage() . "\n";
}
?>
