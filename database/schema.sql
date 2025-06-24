-- Food Menu Management System Database Schema
-- MySQL Database Setup

-- Create database
CREATE DATABASE IF NOT EXISTS food_menu_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE food_menu_system;

-- Users table for authentication and role management
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'manager', 'staff') DEFAULT 'staff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role)
);

-- Categories table for menu item categorization
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    
    INDEX idx_name (name)
);

-- Menu items table
CREATE TABLE menu_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    category_id INT,
    image_url VARCHAR(500),
    is_available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_name (name),
    INDEX idx_category (category_id),
    INDEX idx_price (price),
    INDEX idx_available (is_available)
);

-- User sessions table for session management
CREATE TABLE user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(255) UNIQUE NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    user_agent TEXT,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_token (session_token),
    INDEX idx_user_id (user_id),
    INDEX idx_expires (expires_at)
);

-- Activity log table for audit trail
CREATE TABLE activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50),
    record_id INT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_table_name (table_name),
    INDEX idx_created_at (created_at)
);

-- Insert default admin user (password: admin123)
INSERT INTO users (username, email, password_hash, role) VALUES 
('admin', 'admin@menumanager.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Insert default categories
INSERT INTO categories (name, description) VALUES 
('Appetizers', 'Starters and small plates'),
('Main Courses', 'Primary dishes and entrees'),
('Desserts', 'Sweet treats and desserts'),
('Beverages', 'Drinks and refreshments'),
('Salads', 'Fresh salads and healthy options'),
('Soups', 'Hot and cold soups');

-- Insert sample menu items with images (prices in Indian Rupees)
INSERT INTO menu_items (name, description, price, category_id, image_url, created_by) VALUES
('Caesar Salad', 'Fresh romaine lettuce with parmesan cheese and croutons', 299.00, 5, 'https://images.unsplash.com/photo-1546793665-c74683f339c1?w=400&h=300&fit=crop&crop=center', 1),
('Grilled Chicken Breast', 'Tender grilled chicken with herbs and spices', 449.00, 2, 'https://images.unsplash.com/photo-1532550907401-a500c9a57435?w=400&h=300&fit=crop&crop=center', 1),
('Chocolate Cake', 'Rich chocolate cake with chocolate frosting', 199.00, 3, 'https://images.unsplash.com/photo-1578985545062-69928b1d9587?w=400&h=300&fit=crop&crop=center', 1),
('Fresh Orange Juice', 'Freshly squeezed orange juice', 89.00, 4, 'https://images.unsplash.com/photo-1621506289937-a8e4df240d0b?w=400&h=300&fit=crop&crop=center', 1),
('Tomato Basil Soup', 'Creamy tomato soup with fresh basil', 149.00, 6, 'https://images.unsplash.com/photo-1547592166-23ac45744acd?w=400&h=300&fit=crop&crop=center', 1),
('Buffalo Wings', 'Spicy chicken wings with blue cheese dip', 249.00, 1, 'https://images.unsplash.com/photo-1527477396000-e27163b481c2?w=400&h=300&fit=crop&crop=center', 1),
('Margherita Pizza', 'Classic pizza with tomato, mozzarella, and fresh basil', 349.00, 2, 'https://images.unsplash.com/photo-1604382354936-07c5d9983bd3?w=400&h=300&fit=crop&crop=center', 1),
('Greek Salad', 'Fresh vegetables with feta cheese and olive oil', 229.00, 5, 'https://images.unsplash.com/photo-1540420773420-3366772f4999?w=400&h=300&fit=crop&crop=center', 1),
('Beef Burger', 'Juicy beef patty with lettuce, tomato, and special sauce', 329.00, 2, 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=400&h=300&fit=crop&crop=center', 1),
('Tiramisu', 'Classic Italian dessert with coffee and mascarpone', 179.00, 3, 'https://images.unsplash.com/photo-1571877227200-a0d98ea607e9?w=400&h=300&fit=crop&crop=center', 1),
('Garlic Bread', 'Toasted bread with garlic butter and herbs', 119.00, 1, 'https://images.unsplash.com/photo-1573140247632-f8fd74997d5c?w=400&h=300&fit=crop&crop=center', 1),
('Iced Coffee', 'Cold brew coffee with ice and cream', 79.00, 4, 'https://images.unsplash.com/photo-1461023058943-07fcbe16d735?w=400&h=300&fit=crop&crop=center', 1);

-- Create views for easier data access
CREATE VIEW menu_items_with_categories AS
SELECT 
    mi.id,
    mi.name,
    mi.description,
    mi.price,
    mi.image_url,
    mi.is_available,
    mi.created_at,
    mi.updated_at,
    c.name AS category_name,
    c.id AS category_id,
    u.username AS created_by_username
FROM menu_items mi
LEFT JOIN categories c ON mi.category_id = c.id
LEFT JOIN users u ON mi.created_by = u.id
WHERE mi.is_available = TRUE
ORDER BY c.name, mi.name;

CREATE VIEW dashboard_stats AS
SELECT 
    (SELECT COUNT(*) FROM menu_items WHERE is_available = TRUE) AS total_items,
    (SELECT COUNT(*) FROM categories WHERE is_active = TRUE) AS total_categories,
    (SELECT COUNT(*) FROM users WHERE is_active = TRUE) AS total_users,
    (SELECT AVG(price) FROM menu_items WHERE is_available = TRUE) AS average_price;

-- Create stored procedures for common operations
DELIMITER //

CREATE PROCEDURE GetMenuItemsByCategory(IN category_id INT)
BEGIN
    SELECT * FROM menu_items_with_categories 
    WHERE (category_id IS NULL OR category_id = category_id)
    ORDER BY name;
END //

CREATE PROCEDURE SearchMenuItems(IN search_term VARCHAR(255))
BEGIN
    SELECT * FROM menu_items_with_categories 
    WHERE name LIKE CONCAT('%', search_term, '%') 
       OR description LIKE CONCAT('%', search_term, '%')
    ORDER BY name;
END //

CREATE PROCEDURE LogActivity(
    IN p_user_id INT,
    IN p_action VARCHAR(100),
    IN p_table_name VARCHAR(50),
    IN p_record_id INT,
    IN p_old_values JSON,
    IN p_new_values JSON,
    IN p_ip_address VARCHAR(45)
)
BEGIN
    INSERT INTO activity_log (user_id, action, table_name, record_id, old_values, new_values, ip_address)
    VALUES (p_user_id, p_action, p_table_name, p_record_id, p_old_values, p_new_values, p_ip_address);
END //

DELIMITER ;

-- Create triggers for automatic logging
DELIMITER //

CREATE TRIGGER menu_items_after_insert
AFTER INSERT ON menu_items
FOR EACH ROW
BEGIN
    INSERT INTO activity_log (user_id, action, table_name, record_id, new_values)
    VALUES (NEW.created_by, 'INSERT', 'menu_items', NEW.id, JSON_OBJECT(
        'name', NEW.name,
        'description', NEW.description,
        'price', NEW.price,
        'category_id', NEW.category_id
    ));
END //

CREATE TRIGGER menu_items_after_update
AFTER UPDATE ON menu_items
FOR EACH ROW
BEGIN
    INSERT INTO activity_log (user_id, action, table_name, record_id, old_values, new_values)
    VALUES (NEW.created_by, 'UPDATE', 'menu_items', NEW.id, 
        JSON_OBJECT(
            'name', OLD.name,
            'description', OLD.description,
            'price', OLD.price,
            'category_id', OLD.category_id
        ),
        JSON_OBJECT(
            'name', NEW.name,
            'description', NEW.description,
            'price', NEW.price,
            'category_id', NEW.category_id
        )
    );
END //

CREATE TRIGGER menu_items_after_delete
AFTER DELETE ON menu_items
FOR EACH ROW
BEGIN
    INSERT INTO activity_log (action, table_name, record_id, old_values)
    VALUES ('DELETE', 'menu_items', OLD.id, JSON_OBJECT(
        'name', OLD.name,
        'description', OLD.description,
        'price', OLD.price,
        'category_id', OLD.category_id
    ));
END //

DELIMITER ;

-- Create indexes for better performance
CREATE INDEX idx_menu_items_search ON menu_items(name, description);
CREATE INDEX idx_activity_log_composite ON activity_log(user_id, created_at, action);
CREATE INDEX idx_sessions_cleanup ON user_sessions(expires_at, created_at);

-- Clean up expired sessions (can be run as a scheduled job)
CREATE EVENT IF NOT EXISTS cleanup_expired_sessions
ON SCHEDULE EVERY 1 HOUR
DO
DELETE FROM user_sessions WHERE expires_at < NOW();

-- Grant permissions (adjust as needed for your environment)
-- CREATE USER 'menu_app'@'localhost' IDENTIFIED BY 'secure_password_here';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON food_menu_system.* TO 'menu_app'@'localhost';
-- FLUSH PRIVILEGES;
