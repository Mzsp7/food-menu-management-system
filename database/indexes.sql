-- Performance Optimization Indexes for Food Menu Management System
-- Run this file after creating the main schema for better performance

USE food_menu_system;

-- ============================================================================
-- PRIMARY INDEXES FOR SEARCH AND FILTERING
-- ============================================================================

-- Full-text search index for menu items (name and description)
CREATE INDEX idx_menu_items_search ON menu_items(name, description);

-- Composite index for activity log queries (most common query pattern)
CREATE INDEX idx_activity_log_composite ON activity_log(user_id, created_at, action);

-- Index for session cleanup operations
CREATE INDEX idx_sessions_cleanup ON user_sessions(expires_at, created_at);

-- ============================================================================
-- ADDITIONAL PERFORMANCE INDEXES
-- ============================================================================

-- Index for menu items by price range queries
CREATE INDEX idx_menu_items_price_range ON menu_items(price, is_available);

-- Index for menu items by category and availability
CREATE INDEX idx_menu_items_category_available ON menu_items(category_id, is_available, name);

-- Index for user authentication queries
CREATE INDEX idx_users_auth ON users(username, is_active);
CREATE INDEX idx_users_email_auth ON users(email, is_active);

-- Index for recent activity queries
CREATE INDEX idx_activity_recent ON activity_log(created_at DESC, user_id);

-- Index for menu items created by user
CREATE INDEX idx_menu_items_creator ON menu_items(created_by, created_at);

-- ============================================================================
-- FULL-TEXT SEARCH INDEXES (for advanced search)
-- ============================================================================

-- Full-text search for menu items (requires MyISAM or InnoDB with MySQL 5.6+)
-- ALTER TABLE menu_items ADD FULLTEXT(name, description);

-- Full-text search for categories
-- ALTER TABLE categories ADD FULLTEXT(name, description);

-- ============================================================================
-- COVERING INDEXES (for specific query patterns)
-- ============================================================================

-- Covering index for menu list with category names
CREATE INDEX idx_menu_list_covering ON menu_items(is_available, category_id, name, price, image_url);

-- Covering index for dashboard statistics
CREATE INDEX idx_dashboard_stats ON menu_items(is_available, price);

-- Covering index for user list queries
CREATE INDEX idx_user_list_covering ON users(is_active, role, username, email, created_at);

-- ============================================================================
-- FOREIGN KEY INDEXES (if not automatically created)
-- ============================================================================

-- Ensure foreign key indexes exist for better JOIN performance
-- These might already exist, but adding them explicitly for clarity

-- Index for menu_items.category_id foreign key
CREATE INDEX idx_menu_items_category_fk ON menu_items(category_id);

-- Index for menu_items.created_by foreign key  
CREATE INDEX idx_menu_items_created_by_fk ON menu_items(created_by);

-- Index for user_sessions.user_id foreign key
CREATE INDEX idx_user_sessions_user_fk ON user_sessions(user_id);

-- Index for activity_log.user_id foreign key
CREATE INDEX idx_activity_log_user_fk ON activity_log(user_id);

-- ============================================================================
-- QUERY-SPECIFIC INDEXES
-- ============================================================================

-- Index for finding available menu items by category
CREATE INDEX idx_available_by_category ON menu_items(category_id, is_available, name);

-- Index for price-based sorting and filtering
CREATE INDEX idx_price_sorting ON menu_items(is_available, price, name);

-- Index for recent menu items
CREATE INDEX idx_recent_items ON menu_items(is_available, created_at DESC);

-- Index for user session validation
CREATE INDEX idx_session_validation ON user_sessions(session_token, expires_at, user_id);

-- Index for category management
CREATE INDEX idx_category_management ON categories(is_active, name);

-- ============================================================================
-- ANALYZE TABLES FOR OPTIMIZER
-- ============================================================================

-- Update table statistics for query optimizer
ANALYZE TABLE users;
ANALYZE TABLE categories;
ANALYZE TABLE menu_items;
ANALYZE TABLE user_sessions;
ANALYZE TABLE activity_log;

-- ============================================================================
-- PERFORMANCE MONITORING QUERIES
-- ============================================================================

-- Use these queries to monitor index usage and performance:

/*
-- Check index usage
SELECT 
    TABLE_NAME,
    INDEX_NAME,
    CARDINALITY,
    SUB_PART,
    NULLABLE,
    INDEX_TYPE
FROM information_schema.STATISTICS 
WHERE TABLE_SCHEMA = 'food_menu_system'
ORDER BY TABLE_NAME, INDEX_NAME;

-- Check table sizes
SELECT 
    TABLE_NAME,
    TABLE_ROWS,
    ROUND(((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024), 2) AS 'Size (MB)',
    ROUND((INDEX_LENGTH / 1024 / 1024), 2) AS 'Index Size (MB)'
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'food_menu_system'
ORDER BY (DATA_LENGTH + INDEX_LENGTH) DESC;

-- Check slow queries (enable slow query log first)
-- SET GLOBAL slow_query_log = 'ON';
-- SET GLOBAL long_query_time = 1;

-- Monitor query performance
-- SHOW PROCESSLIST;
-- SHOW STATUS LIKE 'Slow_queries';
*/

-- ============================================================================
-- MAINTENANCE PROCEDURES
-- ============================================================================

-- Procedure to rebuild indexes (run periodically for maintenance)
DELIMITER //

CREATE PROCEDURE RebuildIndexes()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE table_name VARCHAR(64);
    DECLARE cur CURSOR FOR 
        SELECT TABLE_NAME 
        FROM information_schema.TABLES 
        WHERE TABLE_SCHEMA = 'food_menu_system' 
        AND TABLE_TYPE = 'BASE TABLE';
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    OPEN cur;
    read_loop: LOOP
        FETCH cur INTO table_name;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        SET @sql = CONCAT('OPTIMIZE TABLE ', table_name);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END LOOP;
    CLOSE cur;
END //

-- Procedure to analyze query performance
CREATE PROCEDURE AnalyzePerformance()
BEGIN
    -- Update table statistics
    ANALYZE TABLE users, categories, menu_items, user_sessions, activity_log;
    
    -- Show table information
    SELECT 
        TABLE_NAME,
        TABLE_ROWS,
        ROUND(((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024), 2) AS 'Total_Size_MB',
        ROUND((DATA_LENGTH / 1024 / 1024), 2) AS 'Data_Size_MB',
        ROUND((INDEX_LENGTH / 1024 / 1024), 2) AS 'Index_Size_MB'
    FROM information_schema.TABLES 
    WHERE TABLE_SCHEMA = 'food_menu_system'
    ORDER BY (DATA_LENGTH + INDEX_LENGTH) DESC;
END //

DELIMITER ;

-- ============================================================================
-- NOTES FOR DEVELOPERS
-- ============================================================================

/*
PERFORMANCE TIPS:

1. Use EXPLAIN to analyze query execution plans:
   EXPLAIN SELECT * FROM menu_items WHERE name LIKE '%chicken%';

2. Monitor slow queries:
   - Enable slow query log in MySQL configuration
   - Review queries taking longer than 1 second

3. Regular maintenance:
   - Run OPTIMIZE TABLE monthly
   - Update table statistics with ANALYZE TABLE
   - Monitor index usage and remove unused indexes

4. Query optimization:
   - Use LIMIT for pagination
   - Avoid SELECT * in production
   - Use appropriate WHERE clauses
   - Consider covering indexes for frequently used queries

5. Index guidelines:
   - Don't over-index (impacts INSERT/UPDATE performance)
   - Monitor index usage with SHOW INDEX
   - Remove unused indexes
   - Consider composite indexes for multi-column queries

6. For large datasets:
   - Consider partitioning for activity_log table
   - Implement archiving for old data
   - Use read replicas for reporting queries
*/
