<?php

namespace App\Controllers\Backend\Backups;

use App\Models\FastModel;
use System\Core\AppException;

/**
 * Backup Restore Processor
 * Xử lý khôi phục backup trong background
 */
class BackupRestoreProcessor
{
    private $backupPath;
    private $extractPath;
    private $logFile;

    public function __construct()
    {
        $this->backupPath = PATH_WRITE . 'backups/';
        $this->extractPath = PATH_WRITE . 'temp_restore/';
        $this->logFile = PATH_WRITE . 'logs/backup_restore.log';
    }

    /**
     * Xử lý khôi phục backup
     * 
     * @param string $backupId ID của backup cần khôi phục
     * @return array Kết quả khôi phục
     */
    public function processRestore($backupId)
    {
        try {
            $this->log("Starting restore process for backup ID: {$backupId}");

            // Lấy thông tin backup
            $backup = $this->getBackupById($backupId);
            if (!$backup) {
                throw new \Exception("Backup not found: {$backupId}");
            }

            $filePath = $backup['file_path'];
            if (!file_exists($filePath)) {
                throw new \Exception("Backup file not found: {$filePath}");
            }

            $this->log("Backup file found: {$filePath}");

            // Tạo thư mục extract nếu chưa có
            if (!is_dir($this->extractPath)) {
                mkdir($this->extractPath, 0755, true);
            }

            // Giải nén file backup
            $this->log("Extracting backup file...");
            $extractResult = $this->extractBackup($filePath);
            if (!$extractResult['success']) {
                throw new \Exception("Failed to extract backup: " . $extractResult['message']);
            }

            // Xóa database cũ nếu có file SQL
            $dbFile = $this->extractPath . 'database.sql';
            if (file_exists($dbFile)) {
                $this->log("Database file found, clearing old database...");
                $this->clearDatabase();
                
                $this->log("Restoring database...");
                $dbResult = $this->restoreDatabase($dbFile);
                if (!$dbResult['success']) {
                    throw new \Exception("Database restore failed: " . $dbResult['message']);
                }
                $this->log("Database restored successfully");
            }

            // Khôi phục files
            $this->log("Restoring files...");
            $filesResult = $this->restoreFiles($this->extractPath);
            if (!$filesResult['success']) {
                throw new \Exception("Files restore failed: " . $filesResult['message']);
            }
            $this->log("Files restored successfully");

            // Cleanup
            $this->cleanup();

            $this->log("Restore process completed successfully");
            return [
                'success' => true,
                'message' => 'Backup restored successfully'
            ];

        } catch (\Exception $e) {
            $this->log("Restore process failed: " . $e->getMessage());
            $this->cleanup();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Lấy thông tin backup theo ID
     */
    private function getBackupById($id)
    {
        $backups_list = option('site_backups_list');
        if (!is_array($backups_list)) {
            return null;
        }

        foreach ($backups_list as $backup) {
            if ($backup['id'] == $id) {
                return $backup;
            }
        }
        return null;
    }

    /**
     * Giải nén file backup
     */
    private function extractBackup($filePath)
    {
        try {
            $zip = new \ZipArchive();
            if ($zip->open($filePath) !== TRUE) {
                return ['success' => false, 'message' => 'Cannot open backup file'];
            }

            // Xóa thư mục extract cũ nếu có
            if (is_dir($this->extractPath)) {
                $this->removeDirectory($this->extractPath);
            }
            mkdir($this->extractPath, 0755, true);

            $zip->extractTo($this->extractPath);
            $zip->close();

            return ['success' => true];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Xóa toàn bộ database cũ
     */
    private function clearDatabase()
    {
        try {
            $db_config = config('db');
            
            // Kết nối database
            $pdo = new \PDO(
                "mysql:host={$db_config['db_host']};port={$db_config['db_port']};dbname={$db_config['db_database']};charset={$db_config['db_charset']}",
                $db_config['db_username'],
                $db_config['db_password'],
                [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$db_config['db_charset']}"
                ]
            );

            // Lấy danh sách tất cả tables
            $stmt = $pdo->query("SHOW TABLES");
            $tables = $stmt->fetchAll(\PDO::FETCH_COLUMN);

            if (empty($tables)) {
                $this->log("No tables found to clear");
                return;
            }

            // Tắt foreign key checks
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

            // Xóa tất cả tables
            foreach ($tables as $table) {
                $this->log("Dropping table: {$table}");
                $pdo->exec("DROP TABLE IF EXISTS `{$table}`");
            }

            // Bật lại foreign key checks
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

            $this->log("Database cleared successfully");

        } catch (\Exception $e) {
            throw new \Exception("Failed to clear database: " . $e->getMessage());
        }
    }

    /**
     * Khôi phục database từ file SQL
     */
    private function restoreDatabase($sqlFile)
    {
        try {
            $db_config = config('db');
            
            // Đọc file SQL
            $sqlContent = file_get_contents($sqlFile);
            if ($sqlContent === false) {
                throw new \Exception('Cannot read SQL file');
            }
            
            // Tách các câu lệnh SQL
            $sqlStatements = array_filter(
                array_map('trim', explode(';', $sqlContent)),
                function($sql) {
                    return !empty($sql) && !preg_match('/^--/', $sql);
                }
            );
            
            // Kết nối database
            $pdo = new \PDO(
                "mysql:host={$db_config['db_host']};port={$db_config['db_port']};dbname={$db_config['db_database']};charset={$db_config['db_charset']}",
                $db_config['db_username'],
                $db_config['db_password'],
                [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$db_config['db_charset']}"
                ]
            );
            
            // Thực thi từng câu lệnh SQL
            $this->log("Executing " . count($sqlStatements) . " SQL statements");
            foreach ($sqlStatements as $index => $sql) {
                if (!empty(trim($sql))) {
                    try {
                        $pdo->exec($sql);
                        if (($index + 1) % 100 == 0) {
                            $this->log("Executed " . ($index + 1) . " statements");
                        }
                    } catch (\Exception $e) {
                        $this->log("Error executing statement " . ($index + 1) . ": " . $e->getMessage());
                        // Continue with next statement
                    }
                }
            }
            
            $this->log("Database restore completed");
            return ['success' => true];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Khôi phục files
     */
    private function restoreFiles($extractPath)
    {
        try {
            // Khôi phục toàn bộ từ extract_path về PATH_ROOT
            $this->copyDirectory($extractPath, PATH_ROOT);
            return ['success' => true];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Copy directory recursively
     */
    private function copyDirectory($source, $destination)
    {
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            $target = $destination . str_replace($source, '', $file->getPathname());
            
            if ($file->isDir()) {
                if (!is_dir($target)) {
                    mkdir($target, 0755, true);
                }
            } else {
                // Tạo thư mục cha nếu chưa có
                $targetDir = dirname($target);
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }
                copy($file->getPathname(), $target);
            }
        }
    }

    /**
     * Xóa directory
     */
    private function removeDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }

        rmdir($dir);
    }

    /**
     * Cleanup temporary files
     */
    private function cleanup()
    {
        try {
            if (is_dir($this->extractPath)) {
                $this->removeDirectory($this->extractPath);
                $this->log("Cleanup completed");
            }
        } catch (\Exception $e) {
            $this->log("Cleanup error: " . $e->getMessage());
        }
    }

    /**
     * Ghi log
     */
    private function log($message)
    {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$message}" . PHP_EOL;
        
        // Tạo thư mục logs nếu chưa có
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }

    /**
     * Chạy restore trong background
     * 
     * @param string $backupId ID của backup
     * @return bool True nếu đã khởi tạo background process
     */
    public static function runInBackground($backupId)
    {
        try {
            // Tạo file PHP để chạy restore
            $restoreScript = PATH_WRITE . "temp_restore_script_{$backupId}.php";
            
            $scriptContent = '<?php
// Auto-generated restore script
require_once "' . PATH_ROOT . '/init";

use App\Controllers\Backend\Backups\BackupRestoreProcessor;

$backupId = "' . $backupId . '";
$processor = new BackupRestoreProcessor();
$result = $processor->processRestore($backupId);

// Ghi kết quả vào file
file_put_contents("' . PATH_WRITE . 'restore_result_' . $backupId . '.json", json_encode($result));

// Xóa script sau khi chạy xong
unlink(__FILE__);
?>';

            file_put_contents($restoreScript, $scriptContent);

            // Chạy script trong background
            $command = "php {$restoreScript} > /dev/null 2>&1 &";
            
            if (PHP_OS_FAMILY === 'Windows') {
                // Windows
                pclose(popen("start /B {$command}", "r"));
            } else {
                // Unix/Linux/macOS
                exec($command);
            }

            return true;

        } catch (\Exception $e) {
            error_log("Failed to start background restore: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Kiểm tra kết quả restore
     * 
     * @param string $backupId ID của backup
     * @return array|null Kết quả restore hoặc null nếu chưa hoàn thành
     */
    public static function getRestoreResult($backupId)
    {
        $resultFile = PATH_WRITE . "restore_result_{$backupId}.json";
        
        if (file_exists($resultFile)) {
            $result = json_decode(file_get_contents($resultFile), true);
            unlink($resultFile); // Xóa file kết quả sau khi đọc
            return $result;
        }
        
        return null;
    }
}
