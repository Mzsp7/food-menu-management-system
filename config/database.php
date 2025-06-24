<?php
/**
 * Database Configuration for Food Menu Management System
 */

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $charset;
    private $pdo;
    
    public function __construct() {
        // Load configuration from environment variables or use defaults
        $this->host = $_ENV['DB_HOST'] ?? 'localhost';
        $this->db_name = $_ENV['DB_NAME'] ?? 'food_menu_system';
        $this->username = $_ENV['DB_USER'] ?? 'root';
        $this->password = $_ENV['DB_PASS'] ?? '';
        $this->charset = 'utf8mb4';
    }
    
    /**
     * Get database connection
     */
    public function getConnection() {
        if ($this->pdo === null) {
            try {
                $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset={$this->charset}";
                
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset}"
                ];
                
                $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
                
            } catch (PDOException $e) {
                error_log("Database connection failed: " . $e->getMessage());
                throw new Exception("Database connection failed");
            }
        }
        
        return $this->pdo;
    }
    
    /**
     * Close database connection
     */
    public function closeConnection() {
        $this->pdo = null;
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->getConnection()->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit() {
        return $this->getConnection()->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->getConnection()->rollback();
    }
    
    /**
     * Execute a prepared statement
     */
    public function execute($sql, $params = []) {
        try {
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Database query failed: " . $e->getMessage());
            throw new Exception("Database query failed");
        }
    }
    
    /**
     * Fetch single row
     */
    public function fetchOne($sql, $params = []) {
        $stmt = $this->execute($sql, $params);
        return $stmt->fetch();
    }
    
    /**
     * Fetch all rows
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->execute($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Get last inserted ID
     */
    public function lastInsertId() {
        return $this->getConnection()->lastInsertId();
    }
    
    /**
     * Get row count from last statement
     */
    public function rowCount($stmt) {
        return $stmt->rowCount();
    }
}

/**
 * Database utility functions
 */
class DatabaseUtils {
    private $db;
    
    public function __construct(Database $database) {
        $this->db = $database;
    }
    
    /**
     * Check if table exists
     */
    public function tableExists($tableName) {
        $sql = "SHOW TABLES LIKE ?";
        $result = $this->db->fetchOne($sql, [$tableName]);
        return !empty($result);
    }
    
    /**
     * Get table structure
     */
    public function getTableStructure($tableName) {
        $sql = "DESCRIBE `{$tableName}`";
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Create database backup
     */
    public function createBackup($backupPath = null) {
        if (!$backupPath) {
            $backupPath = __DIR__ . '/../backups/backup_' . date('Y-m-d_H-i-s') . '.sql';
        }
        
        // Create backups directory if it doesn't exist
        $backupDir = dirname($backupPath);
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        // This is a simplified backup - in production, use mysqldump
        $tables = $this->getAllTables();
        $backup = "-- Database Backup Created: " . date('Y-m-d H:i:s') . "\n\n";
        
        foreach ($tables as $table) {
            $backup .= $this->getTableBackup($table['Tables_in_food_menu_system']);
        }
        
        file_put_contents($backupPath, $backup);
        return $backupPath;
    }
    
    /**
     * Get all tables in database
     */
    private function getAllTables() {
        return $this->db->fetchAll("SHOW TABLES");
    }
    
    /**
     * Get backup data for a specific table
     */
    private function getTableBackup($tableName) {
        $backup = "\n-- Table: {$tableName}\n";
        $backup .= "DROP TABLE IF EXISTS `{$tableName}`;\n";
        
        // Get CREATE TABLE statement
        $createTable = $this->db->fetchOne("SHOW CREATE TABLE `{$tableName}`");
        $backup .= $createTable['Create Table'] . ";\n\n";
        
        // Get table data
        $data = $this->db->fetchAll("SELECT * FROM `{$tableName}`");
        if (!empty($data)) {
            $columns = array_keys($data[0]);
            $backup .= "INSERT INTO `{$tableName}` (`" . implode('`, `', $columns) . "`) VALUES\n";
            
            $values = [];
            foreach ($data as $row) {
                $rowValues = [];
                foreach ($row as $value) {
                    $rowValues[] = $value === null ? 'NULL' : "'" . addslashes($value) . "'";
                }
                $values[] = "(" . implode(', ', $rowValues) . ")";
            }
            
            $backup .= implode(",\n", $values) . ";\n\n";
        }
        
        return $backup;
    }
    
    /**
     * Restore database from backup
     */
    public function restoreFromBackup($backupPath) {
        if (!file_exists($backupPath)) {
            throw new Exception("Backup file not found: {$backupPath}");
        }
        
        $sql = file_get_contents($backupPath);
        $statements = explode(';', $sql);
        
        $this->db->beginTransaction();
        
        try {
            foreach ($statements as $statement) {
                $statement = trim($statement);
                if (!empty($statement) && !str_starts_with($statement, '--')) {
                    $this->db->execute($statement);
                }
            }
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Optimize database tables
     */
    public function optimizeTables() {
        $tables = $this->getAllTables();
        $results = [];
        
        foreach ($tables as $table) {
            $tableName = $table['Tables_in_food_menu_system'];
            $result = $this->db->fetchOne("OPTIMIZE TABLE `{$tableName}`");
            $results[$tableName] = $result;
        }
        
        return $results;
    }
    
    /**
     * Get database statistics
     */
    public function getDatabaseStats() {
        $stats = [];
        
        // Get table sizes
        $sql = "
            SELECT 
                table_name,
                table_rows,
                ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
            FROM information_schema.tables 
            WHERE table_schema = DATABASE()
            ORDER BY (data_length + index_length) DESC
        ";
        
        $stats['tables'] = $this->db->fetchAll($sql);
        
        // Get total database size
        $sql = "
            SELECT 
                ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS total_size_mb
            FROM information_schema.tables 
            WHERE table_schema = DATABASE()
        ";
        
        $result = $this->db->fetchOne($sql);
        $stats['total_size_mb'] = $result['total_size_mb'];
        
        return $stats;
    }
}

// Global database instance
$database = new Database();
$dbUtils = new DatabaseUtils($database);
?>
