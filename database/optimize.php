<?php
/**
 * Database Optimization Script for Food Menu Management System
 * This script optimizes database performance by creating indexes and analyzing tables
 */

require_once '../config/config.php';
require_once '../config/database.php';

class DatabaseOptimizer {
    private $database;
    private $results = [];
    
    public function __construct() {
        global $database;
        $this->database = $database;
    }
    
    public function optimize() {
        echo "Food Menu Management System - Database Optimization\n";
        echo "==================================================\n\n";
        
        try {
            // Step 1: Create performance indexes
            $this->createPerformanceIndexes();
            
            // Step 2: Analyze tables
            $this->analyzeTables();
            
            // Step 3: Optimize tables
            $this->optimizeTables();
            
            // Step 4: Check index usage
            $this->checkIndexUsage();
            
            // Step 5: Display results
            $this->displayResults();
            
        } catch (Exception $e) {
            echo "❌ Optimization failed: " . $e->getMessage() . "\n";
        }
    }
    
    private function createPerformanceIndexes() {
        echo "Creating performance indexes...\n";
        
        $indexes = [
            // Search indexes
            "CREATE INDEX IF NOT EXISTS idx_menu_items_search ON menu_items(name, description)",
            "CREATE INDEX IF NOT EXISTS idx_menu_items_price_range ON menu_items(price, is_available)",
            "CREATE INDEX IF NOT EXISTS idx_menu_items_category_available ON menu_items(category_id, is_available, name)",
            
            // Authentication indexes
            "CREATE INDEX IF NOT EXISTS idx_users_auth ON users(username, is_active)",
            "CREATE INDEX IF NOT EXISTS idx_users_email_auth ON users(email, is_active)",
            
            // Activity log indexes
            "CREATE INDEX IF NOT EXISTS idx_activity_log_composite ON activity_log(user_id, created_at, action)",
            "CREATE INDEX IF NOT EXISTS idx_activity_recent ON activity_log(created_at DESC, user_id)",
            
            // Session indexes
            "CREATE INDEX IF NOT EXISTS idx_sessions_cleanup ON user_sessions(expires_at, created_at)",
            "CREATE INDEX IF NOT EXISTS idx_session_validation ON user_sessions(session_token, expires_at, user_id)",
            
            // Covering indexes for common queries
            "CREATE INDEX IF NOT EXISTS idx_menu_list_covering ON menu_items(is_available, category_id, name, price, image_url)",
            "CREATE INDEX IF NOT EXISTS idx_user_list_covering ON users(is_active, role, username, email, created_at)",
            
            // Foreign key indexes
            "CREATE INDEX IF NOT EXISTS idx_menu_items_category_fk ON menu_items(category_id)",
            "CREATE INDEX IF NOT EXISTS idx_menu_items_created_by_fk ON menu_items(created_by)",
            "CREATE INDEX IF NOT EXISTS idx_user_sessions_user_fk ON user_sessions(user_id)",
            "CREATE INDEX IF NOT EXISTS idx_activity_log_user_fk ON activity_log(user_id)"
        ];
        
        $created = 0;
        foreach ($indexes as $indexSql) {
            try {
                $this->database->execute($indexSql);
                $created++;
                echo "✓ Index created successfully\n";
            } catch (Exception $e) {
                // Index might already exist, which is fine
                if (strpos($e->getMessage(), 'Duplicate key name') === false) {
                    echo "⚠ Index creation warning: " . $e->getMessage() . "\n";
                }
            }
        }
        
        $this->results['indexes_created'] = $created;
        echo "✓ Created {$created} performance indexes\n\n";
    }
    
    private function analyzeTables() {
        echo "Analyzing tables...\n";
        
        $tables = ['users', 'categories', 'menu_items', 'user_sessions', 'activity_log'];
        $analyzed = 0;
        
        foreach ($tables as $table) {
            try {
                $result = $this->database->fetchOne("ANALYZE TABLE `{$table}`");
                if ($result['Msg_type'] === 'status' && $result['Msg_text'] === 'OK') {
                    echo "✓ Analyzed table: {$table}\n";
                    $analyzed++;
                } else {
                    echo "⚠ Analysis warning for {$table}: " . $result['Msg_text'] . "\n";
                }
            } catch (Exception $e) {
                echo "❌ Failed to analyze {$table}: " . $e->getMessage() . "\n";
            }
        }
        
        $this->results['tables_analyzed'] = $analyzed;
        echo "✓ Analyzed {$analyzed} tables\n\n";
    }
    
    private function optimizeTables() {
        echo "Optimizing tables...\n";
        
        $tables = ['users', 'categories', 'menu_items', 'user_sessions', 'activity_log'];
        $optimized = 0;
        
        foreach ($tables as $table) {
            try {
                $result = $this->database->fetchOne("OPTIMIZE TABLE `{$table}`");
                if ($result['Msg_type'] === 'status' && $result['Msg_text'] === 'OK') {
                    echo "✓ Optimized table: {$table}\n";
                    $optimized++;
                } else {
                    echo "⚠ Optimization note for {$table}: " . $result['Msg_text'] . "\n";
                }
            } catch (Exception $e) {
                echo "❌ Failed to optimize {$table}: " . $e->getMessage() . "\n";
            }
        }
        
        $this->results['tables_optimized'] = $optimized;
        echo "✓ Optimized {$optimized} tables\n\n";
    }
    
    private function checkIndexUsage() {
        echo "Checking index usage...\n";
        
        try {
            $sql = "
                SELECT 
                    TABLE_NAME,
                    INDEX_NAME,
                    CARDINALITY,
                    INDEX_TYPE
                FROM information_schema.STATISTICS 
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME IN ('users', 'categories', 'menu_items', 'user_sessions', 'activity_log')
                ORDER BY TABLE_NAME, INDEX_NAME
            ";
            
            $indexes = $this->database->fetchAll($sql);
            $this->results['total_indexes'] = count($indexes);
            
            echo "✓ Found " . count($indexes) . " indexes across all tables\n\n";
            
        } catch (Exception $e) {
            echo "⚠ Could not check index usage: " . $e->getMessage() . "\n\n";
        }
    }
    
    private function displayResults() {
        echo "Optimization Results Summary\n";
        echo "===========================\n";
        
        echo "📊 Performance Indexes: " . ($this->results['indexes_created'] ?? 0) . " created\n";
        echo "📈 Tables Analyzed: " . ($this->results['tables_analyzed'] ?? 0) . "\n";
        echo "⚡ Tables Optimized: " . ($this->results['tables_optimized'] ?? 0) . "\n";
        echo "🔍 Total Indexes: " . ($this->results['total_indexes'] ?? 0) . "\n\n";
        
        // Get table sizes
        try {
            $sql = "
                SELECT 
                    TABLE_NAME,
                    TABLE_ROWS,
                    ROUND(((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024), 2) AS 'Size_MB',
                    ROUND((INDEX_LENGTH / 1024 / 1024), 2) AS 'Index_Size_MB'
                FROM information_schema.TABLES 
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME IN ('users', 'categories', 'menu_items', 'user_sessions', 'activity_log')
                ORDER BY (DATA_LENGTH + INDEX_LENGTH) DESC
            ";
            
            $tableStats = $this->database->fetchAll($sql);
            
            echo "Database Table Statistics:\n";
            echo "-------------------------\n";
            printf("%-15s %-10s %-10s %-12s\n", "Table", "Rows", "Size (MB)", "Index (MB)");
            echo str_repeat("-", 50) . "\n";
            
            foreach ($tableStats as $stat) {
                printf("%-15s %-10s %-10s %-12s\n", 
                    $stat['TABLE_NAME'], 
                    number_format($stat['TABLE_ROWS']), 
                    $stat['Size_MB'], 
                    $stat['Index_Size_MB']
                );
            }
            
        } catch (Exception $e) {
            echo "⚠ Could not retrieve table statistics: " . $e->getMessage() . "\n";
        }
        
        echo "\n✅ Database optimization completed successfully!\n\n";
        
        echo "Performance Tips:\n";
        echo "- Run this optimization script monthly\n";
        echo "- Monitor slow queries with MySQL slow query log\n";
        echo "- Use EXPLAIN to analyze query performance\n";
        echo "- Consider adding more specific indexes based on usage patterns\n";
    }
    
    public function showPerformanceReport() {
        echo "Performance Analysis Report\n";
        echo "==========================\n\n";
        
        // Show current query performance
        try {
            echo "Current Database Status:\n";
            echo "-----------------------\n";
            
            // Show slow queries count
            $slowQueries = $this->database->fetchOne("SHOW STATUS LIKE 'Slow_queries'");
            echo "Slow Queries: " . $slowQueries['Value'] . "\n";
            
            // Show query cache hit rate
            $qcacheHits = $this->database->fetchOne("SHOW STATUS LIKE 'Qcache_hits'");
            $qcacheInserts = $this->database->fetchOne("SHOW STATUS LIKE 'Qcache_inserts'");
            
            if ($qcacheHits && $qcacheInserts) {
                $hitRate = ($qcacheHits['Value'] / ($qcacheHits['Value'] + $qcacheInserts['Value'])) * 100;
                echo "Query Cache Hit Rate: " . number_format($hitRate, 2) . "%\n";
            }
            
            // Show connection info
            $connections = $this->database->fetchOne("SHOW STATUS LIKE 'Connections'");
            echo "Total Connections: " . number_format($connections['Value']) . "\n";
            
        } catch (Exception $e) {
            echo "⚠ Could not retrieve performance statistics: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
}

// Run the optimizer
if (php_sapi_name() === 'cli') {
    $optimizer = new DatabaseOptimizer();
    
    $action = $argv[1] ?? 'optimize';
    
    switch ($action) {
        case 'optimize':
            $optimizer->optimize();
            break;
        case 'report':
            $optimizer->showPerformanceReport();
            break;
        default:
            echo "Usage: php optimize.php [optimize|report]\n";
            echo "  optimize - Run full database optimization\n";
            echo "  report   - Show performance report\n";
    }
} else {
    echo "This script should be run from the command line.\n";
    echo "Usage: php database/optimize.php\n";
}
?>
