<?php
/**
 * Deployment Script for Food Menu Management System
 * This script helps with deployment tasks and maintenance
 */

require_once 'config/config.php';
require_once 'config/database.php';

class Deployer {
    private $database;
    
    public function __construct() {
        global $database;
        $this->database = $database;
    }
    
    public function run($action = null) {
        echo "Food Menu Management System - Deployment Tool\n";
        echo "============================================\n\n";
        
        if (!$action) {
            $this->showHelp();
            return;
        }
        
        switch ($action) {
            case 'backup':
                $this->createBackup();
                break;
            case 'restore':
                $this->restoreBackup();
                break;
            case 'optimize':
                $this->optimizeDatabase();
                break;
            case 'clean':
                $this->cleanupFiles();
                break;
            case 'check':
                $this->healthCheck();
                break;
            case 'migrate':
                $this->runMigrations();
                break;
            default:
                echo "Unknown action: {$action}\n";
                $this->showHelp();
        }
    }
    
    private function showHelp() {
        echo "Available actions:\n";
        echo "  backup   - Create database backup\n";
        echo "  restore  - Restore from backup\n";
        echo "  optimize - Optimize database tables\n";
        echo "  clean    - Clean up temporary files\n";
        echo "  check    - Run health check\n";
        echo "  migrate  - Run database migrations\n\n";
        echo "Usage: php deploy.php <action>\n";
        echo "Example: php deploy.php backup\n";
    }
    
    private function createBackup() {
        echo "Creating database backup...\n";
        
        try {
            $backupFile = 'backups/backup_' . date('Y-m-d_H-i-s') . '.sql';
            
            // Create backups directory if it doesn't exist
            if (!is_dir('backups')) {
                mkdir('backups', 0755, true);
            }
            
            // Get all tables
            $tables = $this->database->fetchAll("SHOW TABLES");
            $backup = "-- Database Backup Created: " . date('Y-m-d H:i:s') . "\n";
            $backup .= "-- Food Menu Management System\n\n";
            
            foreach ($tables as $table) {
                $tableName = array_values($table)[0];
                $backup .= $this->getTableBackup($tableName);
            }
            
            file_put_contents($backupFile, $backup);
            
            echo "✓ Backup created: {$backupFile}\n";
            echo "  Size: " . $this->formatFileSize(filesize($backupFile)) . "\n";
            
        } catch (Exception $e) {
            echo "✗ Backup failed: " . $e->getMessage() . "\n";
        }
    }
    
    private function getTableBackup($tableName) {
        $backup = "\n-- Table: {$tableName}\n";
        $backup .= "DROP TABLE IF EXISTS `{$tableName}`;\n";
        
        // Get CREATE TABLE statement
        $createTable = $this->database->fetchOne("SHOW CREATE TABLE `{$tableName}`");
        $backup .= $createTable['Create Table'] . ";\n\n";
        
        // Get table data
        $data = $this->database->fetchAll("SELECT * FROM `{$tableName}`");
        if (!empty($data)) {
            $columns = array_keys($data[0]);
            $backup .= "INSERT INTO `{$tableName}` (`" . implode('`, `', $columns) . "`) VALUES\n";
            
            $values = [];
            foreach ($data as $row) {
                $rowValues = [];
                foreach ($row as $value) {
                    if ($value === null) {
                        $rowValues[] = 'NULL';
                    } else {
                        $rowValues[] = "'" . addslashes($value) . "'";
                    }
                }
                $values[] = "(" . implode(', ', $rowValues) . ")";
            }
            
            $backup .= implode(",\n", $values) . ";\n\n";
        }
        
        return $backup;
    }
    
    private function restoreBackup() {
        echo "Available backups:\n";
        
        $backups = glob('backups/*.sql');
        if (empty($backups)) {
            echo "No backup files found.\n";
            return;
        }
        
        foreach ($backups as $index => $backup) {
            $size = $this->formatFileSize(filesize($backup));
            $date = date('Y-m-d H:i:s', filemtime($backup));
            echo "  " . ($index + 1) . ". " . basename($backup) . " ({$size}, {$date})\n";
        }
        
        echo "\nEnter backup number to restore (or 0 to cancel): ";
        $handle = fopen("php://stdin", "r");
        $choice = (int)trim(fgets($handle));
        fclose($handle);
        
        if ($choice === 0 || !isset($backups[$choice - 1])) {
            echo "Restore cancelled.\n";
            return;
        }
        
        $backupFile = $backups[$choice - 1];
        
        echo "⚠ WARNING: This will overwrite all current data!\n";
        echo "Are you sure you want to restore from " . basename($backupFile) . "? (yes/no): ";
        $handle = fopen("php://stdin", "r");
        $confirm = trim(fgets($handle));
        fclose($handle);
        
        if (strtolower($confirm) !== 'yes') {
            echo "Restore cancelled.\n";
            return;
        }
        
        try {
            echo "Restoring from backup...\n";
            
            $sql = file_get_contents($backupFile);
            $statements = explode(';', $sql);
            
            $this->database->beginTransaction();
            
            foreach ($statements as $statement) {
                $statement = trim($statement);
                if (!empty($statement) && !str_starts_with($statement, '--')) {
                    $this->database->execute($statement);
                }
            }
            
            $this->database->commit();
            
            echo "✓ Restore completed successfully.\n";
            
        } catch (Exception $e) {
            $this->database->rollback();
            echo "✗ Restore failed: " . $e->getMessage() . "\n";
        }
    }
    
    private function optimizeDatabase() {
        echo "Optimizing database tables and indexes...\n";

        try {
            // Run the database optimizer
            $optimizerPath = __DIR__ . '/database/optimize.php';
            if (file_exists($optimizerPath)) {
                echo "Running comprehensive database optimization...\n";
                $output = shell_exec("php {$optimizerPath} optimize");
                echo $output;
            } else {
                // Fallback to basic optimization
                echo "Running basic table optimization...\n";
                $tables = $this->database->fetchAll("SHOW TABLES");

                foreach ($tables as $table) {
                    $tableName = array_values($table)[0];
                    echo "Optimizing {$tableName}... ";

                    $result = $this->database->fetchOne("OPTIMIZE TABLE `{$tableName}`");

                    if ($result['Msg_type'] === 'status' && $result['Msg_text'] === 'OK') {
                        echo "✓\n";
                    } else {
                        echo "⚠ " . $result['Msg_text'] . "\n";
                    }
                }
            }

            echo "\n✓ Database optimization completed.\n";

        } catch (Exception $e) {
            echo "✗ Optimization failed: " . $e->getMessage() . "\n";
        }
    }
    
    private function cleanupFiles() {
        echo "Cleaning up temporary files...\n";
        
        $cleaned = 0;
        
        // Clean old log files
        $logFiles = glob('logs/*.log.*');
        foreach ($logFiles as $logFile) {
            if (filemtime($logFile) < strtotime('-30 days')) {
                unlink($logFile);
                $cleaned++;
                echo "Deleted old log: " . basename($logFile) . "\n";
            }
        }
        
        // Clean old backups
        $backupFiles = glob('backups/*.sql');
        foreach ($backupFiles as $backupFile) {
            if (filemtime($backupFile) < strtotime('-' . BACKUP_RETENTION_DAYS . ' days')) {
                unlink($backupFile);
                $cleaned++;
                echo "Deleted old backup: " . basename($backupFile) . "\n";
            }
        }
        
        // Clean cache files
        $cacheFiles = glob('cache/*');
        foreach ($cacheFiles as $cacheFile) {
            if (is_file($cacheFile) && filemtime($cacheFile) < strtotime('-1 day')) {
                unlink($cacheFile);
                $cleaned++;
                echo "Deleted cache file: " . basename($cacheFile) . "\n";
            }
        }
        
        // Clean expired sessions
        $this->database->execute("DELETE FROM user_sessions WHERE expires_at < NOW()");
        $expiredSessions = $this->database->rowCount($this->database->execute("SELECT ROW_COUNT()"));
        
        if ($expiredSessions > 0) {
            echo "Cleaned {$expiredSessions} expired sessions\n";
            $cleaned += $expiredSessions;
        }
        
        echo "\n✓ Cleanup completed. {$cleaned} items removed.\n";
    }
    
    private function healthCheck() {
        echo "Running health check...\n\n";
        
        $issues = 0;
        
        // Check database connection
        try {
            $this->database->getConnection();
            echo "✓ Database connection: OK\n";
        } catch (Exception $e) {
            echo "✗ Database connection: FAILED - " . $e->getMessage() . "\n";
            $issues++;
        }
        
        // Check required directories
        $directories = ['logs', 'backups', 'uploads', 'cache'];
        foreach ($directories as $dir) {
            if (is_dir($dir) && is_writable($dir)) {
                echo "✓ Directory {$dir}: OK\n";
            } else {
                echo "✗ Directory {$dir}: NOT WRITABLE\n";
                $issues++;
            }
        }
        
        // Check configuration
        if (file_exists('.env')) {
            echo "✓ Configuration file: OK\n";
        } else {
            echo "✗ Configuration file: MISSING\n";
            $issues++;
        }
        
        // Check API endpoints
        $endpoints = [
            'api/auth/login.php',
            'api/menu/list.php',
            'api/categories/list.php',
            'api/dashboard/stats.php'
        ];
        
        foreach ($endpoints as $endpoint) {
            if (file_exists($endpoint)) {
                echo "✓ Endpoint {$endpoint}: OK\n";
            } else {
                echo "✗ Endpoint {$endpoint}: MISSING\n";
                $issues++;
            }
        }
        
        // Check disk space
        $freeSpace = disk_free_space('.');
        $totalSpace = disk_total_space('.');
        $usedPercent = (($totalSpace - $freeSpace) / $totalSpace) * 100;
        
        if ($usedPercent < 90) {
            echo "✓ Disk space: OK (" . number_format($usedPercent, 1) . "% used)\n";
        } else {
            echo "⚠ Disk space: LOW (" . number_format($usedPercent, 1) . "% used)\n";
        }
        
        echo "\n";
        
        if ($issues === 0) {
            echo "🎉 Health check passed! No issues found.\n";
        } else {
            echo "⚠ Health check found {$issues} issue(s). Please review and fix.\n";
        }
    }
    
    private function runMigrations() {
        echo "Running database migrations...\n";
        
        // This is a placeholder for future migration functionality
        echo "No migrations to run.\n";
    }
    
    private function formatFileSize($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}

// Run the deployer
if (php_sapi_name() === 'cli') {
    $action = $argv[1] ?? null;
    $deployer = new Deployer();
    $deployer->run($action);
} else {
    echo "This script should be run from the command line.\n";
    echo "Usage: php deploy.php <action>\n";
}
?>
