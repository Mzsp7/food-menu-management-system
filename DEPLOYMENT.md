# Deployment Guide - Food Menu Management System

This guide provides detailed instructions for deploying the Food Menu Management System to various environments.

## Quick Start

For immediate testing and development:

```bash
# 1. Setup database
mysql -u root -p -e "CREATE DATABASE food_menu_system"
mysql -u root -p food_menu_system < database/schema.sql

# 2. Configure environment
cp .env.example .env
# Edit .env with your database credentials

# 3. Run setup
php setup.php

# 4. Start development server
php -S localhost:8000

# 5. Access application
# Open http://localhost:8000 in your browser
# Login: admin / admin123
```

## Production Deployment

### Prerequisites

- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **PHP**: 7.4+ with extensions: pdo, pdo_mysql, json, mbstring, openssl
- **MySQL**: 5.7+ or MariaDB 10.3+
- **SSL Certificate**: Recommended for production
- **Domain**: Configured and pointing to your server

### Step 1: Server Preparation

#### Ubuntu/Debian
```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install required packages
sudo apt install apache2 mysql-server php7.4 php7.4-mysql php7.4-json php7.4-mbstring php7.4-curl php7.4-xml -y

# Enable Apache modules
sudo a2enmod rewrite ssl headers

# Start services
sudo systemctl start apache2 mysql
sudo systemctl enable apache2 mysql
```

#### CentOS/RHEL
```bash
# Update system
sudo yum update -y

# Install required packages
sudo yum install httpd mysql-server php php-mysql php-json php-mbstring -y

# Start services
sudo systemctl start httpd mysqld
sudo systemctl enable httpd mysqld
```

### Step 2: Database Setup

```bash
# Secure MySQL installation
sudo mysql_secure_installation

# Create database and user
mysql -u root -p
```

```sql
CREATE DATABASE food_menu_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'menu_app'@'localhost' IDENTIFIED BY 'secure_password_here';
GRANT ALL PRIVILEGES ON food_menu_system.* TO 'menu_app'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

```bash
# Import database schema
mysql -u menu_app -p food_menu_system < database/schema.sql
```

### Step 3: Application Deployment

```bash
# Create web directory
sudo mkdir -p /var/www/menu-system

# Upload application files
# (Use scp, rsync, or git clone)
sudo cp -r /path/to/food-menu-system/* /var/www/menu-system/

# Set proper ownership
sudo chown -R www-data:www-data /var/www/menu-system

# Set proper permissions
sudo find /var/www/menu-system -type d -exec chmod 755 {} \;
sudo find /var/www/menu-system -type f -exec chmod 644 {} \;

# Make specific directories writable
sudo chmod 775 /var/www/menu-system/logs
sudo chmod 775 /var/www/menu-system/backups
sudo chmod 775 /var/www/menu-system/uploads
sudo chmod 775 /var/www/menu-system/cache
```

### Step 4: Web Server Configuration

#### Apache Configuration

Create virtual host file: `/etc/apache2/sites-available/menu-system.conf`

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    DocumentRoot /var/www/menu-system
    
    <Directory /var/www/menu-system>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/menu-system_error.log
    CustomLog ${APACHE_LOG_DIR}/menu-system_access.log combined
    
    # Redirect to HTTPS
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
</VirtualHost>

<VirtualHost *:443>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    DocumentRoot /var/www/menu-system
    
    SSLEngine on
    SSLCertificateFile /path/to/certificate.crt
    SSLCertificateKeyFile /path/to/private.key
    SSLCertificateChainFile /path/to/chain.crt
    
    <Directory /var/www/menu-system>
        AllowOverride All
        Require all granted
    </Directory>
    
    # Security headers
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Strict-Transport-Security "max-age=63072000; includeSubDomains; preload"
    
    ErrorLog ${APACHE_LOG_DIR}/menu-system_ssl_error.log
    CustomLog ${APACHE_LOG_DIR}/menu-system_ssl_access.log combined
</VirtualHost>
```

```bash
# Enable site and restart Apache
sudo a2ensite menu-system.conf
sudo systemctl restart apache2
```

#### Nginx Configuration

Create configuration file: `/etc/nginx/sites-available/menu-system`

```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;
    
    root /var/www/menu-system;
    index index.html index.php;
    
    ssl_certificate /path/to/certificate.crt;
    ssl_certificate_key /path/to/private.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512;
    
    # Security headers
    add_header X-Content-Type-Options nosniff;
    add_header X-Frame-Options DENY;
    add_header X-XSS-Protection "1; mode=block";
    add_header Strict-Transport-Security "max-age=63072000; includeSubDomains; preload";
    
    location / {
        try_files $uri $uri/ /index.html;
    }
    
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    location ~ /\. {
        deny all;
    }
    
    location ~* \.(log|sql|env)$ {
        deny all;
    }
}
```

```bash
# Enable site and restart Nginx
sudo ln -s /etc/nginx/sites-available/menu-system /etc/nginx/sites-enabled/
sudo systemctl restart nginx
```

### Step 5: Application Configuration

```bash
# Navigate to application directory
cd /var/www/menu-system

# Create environment configuration
sudo cp .env.example .env
sudo nano .env
```

Update `.env` with production values:

```env
# Application
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database
DB_HOST=localhost
DB_NAME=food_menu_system
DB_USER=menu_app
DB_PASS=secure_password_here

# Security
JWT_SECRET=your-very-secure-random-key-here
SESSION_LIFETIME=3600

# Email (configure for notifications)
MAIL_HOST=smtp.yourdomain.com
MAIL_PORT=587
MAIL_USERNAME=noreply@yourdomain.com
MAIL_PASSWORD=email_password
MAIL_ENCRYPTION=tls
```

```bash
# Run setup script
sudo -u www-data php setup.php

# Test the installation
sudo -u www-data php test_api.php
```

### Step 6: Security Hardening

#### File Permissions
```bash
# Secure sensitive files
sudo chmod 600 /var/www/menu-system/.env
sudo chmod 600 /var/www/menu-system/config/database.php

# Remove setup files (optional)
sudo rm /var/www/menu-system/setup.php
sudo rm /var/www/menu-system/test_api.php
```

#### Firewall Configuration
```bash
# UFW (Ubuntu)
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable

# Firewalld (CentOS)
sudo firewall-cmd --permanent --add-service=ssh
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --permanent --add-service=https
sudo firewall-cmd --reload
```

#### Database Security
```bash
# Secure MySQL configuration
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf
```

Add these security settings:
```ini
[mysqld]
bind-address = 127.0.0.1
skip-networking = false
local-infile = 0
```

### Step 7: Monitoring and Maintenance

#### Log Rotation
Create `/etc/logrotate.d/menu-system`:

```
/var/www/menu-system/logs/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
}
```

#### Backup Script
Create `/usr/local/bin/backup-menu-system.sh`:

```bash
#!/bin/bash
cd /var/www/menu-system
sudo -u www-data php deploy.php backup
```

```bash
# Make executable
sudo chmod +x /usr/local/bin/backup-menu-system.sh

# Add to crontab for daily backups
sudo crontab -e
# Add: 0 2 * * * /usr/local/bin/backup-menu-system.sh
```

#### Health Check Script
Create `/usr/local/bin/health-check-menu-system.sh`:

```bash
#!/bin/bash
cd /var/www/menu-system
sudo -u www-data php deploy.php check
```

## Docker Deployment

For containerized deployment, create `docker-compose.yml`:

```yaml
version: '3.8'

services:
  web:
    build: .
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./:/var/www/html
      - ./logs:/var/www/html/logs
      - ./uploads:/var/www/html/uploads
    depends_on:
      - db
    environment:
      - DB_HOST=db
      - DB_NAME=food_menu_system
      - DB_USER=menu_app
      - DB_PASS=secure_password

  db:
    image: mysql:8.0
    environment:
      - MYSQL_ROOT_PASSWORD=root_password
      - MYSQL_DATABASE=food_menu_system
      - MYSQL_USER=menu_app
      - MYSQL_PASSWORD=secure_password
    volumes:
      - db_data:/var/lib/mysql
      - ./database/schema.sql:/docker-entrypoint-initdb.d/schema.sql

volumes:
  db_data:
```

## Troubleshooting

### Common Issues

1. **Permission Denied Errors**
   ```bash
   sudo chown -R www-data:www-data /var/www/menu-system
   sudo chmod -R 755 /var/www/menu-system
   ```

2. **Database Connection Failed**
   - Check database credentials in `.env`
   - Verify MySQL service is running
   - Test connection: `mysql -u menu_app -p food_menu_system`

3. **API Endpoints Not Working**
   - Check web server error logs
   - Verify .htaccess rules (Apache)
   - Check PHP error logs

4. **SSL Certificate Issues**
   - Verify certificate files exist and are readable
   - Check certificate validity: `openssl x509 -in certificate.crt -text -noout`

### Performance Optimization

1. **Enable PHP OPcache**
   ```ini
   ; In php.ini
   opcache.enable=1
   opcache.memory_consumption=128
   opcache.max_accelerated_files=4000
   ```

2. **Database Optimization**
   ```bash
   # Run optimization
   sudo -u www-data php deploy.php optimize
   ```

3. **Web Server Caching**
   - Enable gzip compression
   - Set appropriate cache headers
   - Use CDN for static assets

## Maintenance

### Regular Tasks

1. **Daily**
   - Monitor application logs
   - Check system resources
   - Verify backups

2. **Weekly**
   - Review security logs
   - Update system packages
   - Clean temporary files

3. **Monthly**
   - Review user activity
   - Update application dependencies
   - Test backup restoration

### Commands

```bash
# Create backup
sudo -u www-data php deploy.php backup

# Health check
sudo -u www-data php deploy.php check

# Clean temporary files
sudo -u www-data php deploy.php clean

# Optimize database
sudo -u www-data php deploy.php optimize
```

This deployment guide ensures a secure, scalable, and maintainable production environment for the Food Menu Management System.
