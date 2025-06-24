# Database Guide - Food Menu Management System

This guide provides comprehensive information about the MySQL database implementation for the Food Menu Management System.

## 🗄️ **Database Architecture**

### **Database Schema Overview**
- **Database Name**: `food_menu_system`
- **Engine**: InnoDB (for ACID compliance and foreign keys)
- **Character Set**: utf8mb4 (full Unicode support)
- **Collation**: utf8mb4_unicode_ci

### **Tables Structure**

#### **1. Users Table**
```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'manager', 'staff') DEFAULT 'staff',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);
```

#### **2. Categories Table**
```sql
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### **3. Menu Items Table**
```sql
CREATE TABLE menu_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    category_id INT,
    image_url VARCHAR(500),
    is_available BOOLEAN DEFAULT TRUE,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);
```

#### **4. User Sessions Table**
```sql
CREATE TABLE user_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    session_token VARCHAR(255) UNIQUE NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

#### **5. Activity Log Table**
```sql
CREATE TABLE activity_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    table_name VARCHAR(50),
    record_id INT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

## 🚀 **Performance Optimizations**

### **Indexes Created**

#### **Search Indexes**
```sql
-- Full-text search for menu items
CREATE INDEX idx_menu_items_search ON menu_items(name, description);

-- Price range queries
CREATE INDEX idx_menu_items_price_range ON menu_items(price, is_available);

-- Category-based filtering
CREATE INDEX idx_menu_items_category_available ON menu_items(category_id, is_available, name);
```

#### **Authentication Indexes**
```sql
-- User login queries
CREATE INDEX idx_users_auth ON users(username, is_active);
CREATE INDEX idx_users_email_auth ON users(email, is_active);
```

#### **Activity Tracking Indexes**
```sql
-- Activity log queries
CREATE INDEX idx_activity_log_composite ON activity_log(user_id, created_at, action);
CREATE INDEX idx_activity_recent ON activity_log(created_at DESC, user_id);
```

#### **Session Management Indexes**
```sql
-- Session cleanup and validation
CREATE INDEX idx_sessions_cleanup ON user_sessions(expires_at, created_at);
CREATE INDEX idx_session_validation ON user_sessions(session_token, expires_at, user_id);
```

#### **Covering Indexes**
```sql
-- Menu list queries (covers most SELECT operations)
CREATE INDEX idx_menu_list_covering ON menu_items(is_available, category_id, name, price, image_url);

-- User list queries
CREATE INDEX idx_user_list_covering ON users(is_active, role, username, email, created_at);
```

## 📊 **Sample Data**

### **Default Categories**
- Appetizers
- Main Courses  
- Desserts
- Beverages
- Salads
- Soups

### **Sample Menu Items (with Indian Pricing)**
- Caesar Salad: ₹299
- Grilled Chicken Breast: ₹449
- Chocolate Cake: ₹199
- Fresh Orange Juice: ₹89
- Tomato Basil Soup: ₹149
- Buffalo Wings: ₹249
- Margherita Pizza: ₹349
- Greek Salad: ₹229
- Beef Burger: ₹329
- Tiramisu: ₹179

### **Default Users**
- **Admin User**: username: `admin`, password: `admin123`
- **Role**: admin (full access)

## 🔧 **Database Operations**

### **Setup Commands**
```bash
# Create database
mysql -u root -p -e "CREATE DATABASE food_menu_system"

# Import schema
mysql -u root -p food_menu_system < database/schema.sql

# Apply performance indexes
mysql -u root -p food_menu_system < database/indexes.sql

# Run optimization
php database/optimize.php
```

### **Maintenance Commands**
```bash
# Optimize database
php deploy.php optimize

# Create backup
php deploy.php backup

# Check health
php deploy.php check

# Performance report
php database/optimize.php report
```

## 🔒 **Security Features**

### **Data Protection**
- **Password Hashing**: bcrypt with salt
- **SQL Injection Prevention**: Prepared statements
- **Foreign Key Constraints**: Data integrity
- **Soft Deletes**: is_active flags instead of hard deletes

### **Access Control**
- **Role-based Permissions**: admin, manager, staff
- **Session Management**: Secure token-based sessions
- **Activity Logging**: Complete audit trail
- **IP Tracking**: Monitor access patterns

### **Data Validation**
- **Input Sanitization**: All user inputs validated
- **Type Checking**: Proper data types enforced
- **Length Limits**: Prevent buffer overflow attacks
- **Email Validation**: Proper email format checking

## 📈 **Performance Monitoring**

### **Key Metrics to Monitor**
```sql
-- Slow queries
SHOW STATUS LIKE 'Slow_queries';

-- Query cache hit rate
SHOW STATUS LIKE 'Qcache_hits';
SHOW STATUS LIKE 'Qcache_inserts';

-- Connection statistics
SHOW STATUS LIKE 'Connections';
SHOW STATUS LIKE 'Threads_connected';

-- Table sizes
SELECT 
    TABLE_NAME,
    TABLE_ROWS,
    ROUND(((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024), 2) AS 'Size_MB'
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'food_menu_system';
```

### **Query Optimization Tips**
1. **Use EXPLAIN** to analyze query execution plans
2. **Add LIMIT** to pagination queries
3. **Avoid SELECT *** in production
4. **Use appropriate WHERE clauses**
5. **Monitor index usage** with SHOW INDEX

## 🔄 **Backup and Recovery**

### **Automated Backup**
```bash
# Create backup
php deploy.php backup

# Backup with compression
mysqldump -u root -p food_menu_system | gzip > backup_$(date +%Y%m%d_%H%M%S).sql.gz
```

### **Recovery Process**
```bash
# Restore from backup
mysql -u root -p food_menu_system < backup_file.sql

# Or use the deploy script
php deploy.php restore
```

## 🚀 **Scaling Considerations**

### **For High Traffic**
1. **Read Replicas**: Separate read and write operations
2. **Connection Pooling**: Manage database connections efficiently
3. **Query Caching**: Enable MySQL query cache
4. **Partitioning**: Partition large tables (activity_log)
5. **CDN**: Use CDN for image storage

### **For Large Datasets**
1. **Archiving**: Move old data to archive tables
2. **Indexing Strategy**: Add specific indexes based on usage
3. **Sharding**: Distribute data across multiple databases
4. **Caching Layer**: Implement Redis/Memcached

## 🛠️ **Development Tools**

### **Database Management**
- **phpMyAdmin**: Web-based MySQL administration
- **MySQL Workbench**: Desktop database design tool
- **Adminer**: Lightweight database management

### **Performance Analysis**
- **MySQL Performance Schema**: Built-in performance monitoring
- **Percona Toolkit**: Advanced MySQL tools
- **pt-query-digest**: Analyze slow query logs

## 📝 **Best Practices**

### **Development**
1. **Use Migrations**: Version control for database changes
2. **Test with Real Data**: Use production-like datasets
3. **Monitor Performance**: Regular performance reviews
4. **Document Changes**: Keep schema documentation updated

### **Production**
1. **Regular Backups**: Automated daily backups
2. **Monitor Logs**: Check error and slow query logs
3. **Update Statistics**: Run ANALYZE TABLE regularly
4. **Security Updates**: Keep MySQL updated

### **Query Writing**
1. **Use Prepared Statements**: Prevent SQL injection
2. **Optimize JOINs**: Use appropriate JOIN types
3. **Limit Result Sets**: Always use LIMIT for large queries
4. **Index Foreign Keys**: Ensure all foreign keys are indexed

This database implementation provides a robust, scalable foundation for the Food Menu Management System with proper security, performance optimization, and maintenance procedures.
