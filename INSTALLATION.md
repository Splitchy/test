# Logistics Management System - Installation Guide

## Prerequisites

- PHP 8.0 or higher
- MySQL 8.0 or MariaDB 10.4+
- Composer (PHP package manager)
- Web server (Apache/Nginx)
- Node.js and npm (for frontend build tools)

## Installation Steps

### 1. Clone and Setup Project

```bash
# Clone the repository
git clone <repository-url> logistics-system
cd logistics-system

# Install PHP dependencies
composer install

# Create environment file
cp .env.example .env
```

### 2. Database Setup

```bash
# Create database
mysql -u root -p
CREATE DATABASE logistics_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'logistics_user'@'localhost' IDENTIFIED BY 'strong_password';
GRANT ALL PRIVILEGES ON logistics_db.* TO 'logistics_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Import database schema
mysql -u logistics_user -p logistics_db < database/schema.sql
```

### 3. Configure Environment

Edit `.env` file with your settings:

```env
# Database Configuration
DB_HOST=localhost
DB_NAME=logistics_db
DB_USER=logistics_user
DB_PASS=strong_password

# Application Configuration
APP_DEBUG=false
APP_TIMEZONE=Africa/Casablanca
APP_KEY=generate-32-character-random-key
JWT_SECRET=generate-64-character-jwt-secret

# Email Configuration (Gmail example)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=your-email@gmail.com
SMTP_PASSWORD=your-app-password
FROM_EMAIL=noreply@logistics.com
FROM_NAME=Logistics System

# SMS Configuration (Twilio)
TWILIO_SID=your-twilio-account-sid
TWILIO_TOKEN=your-twilio-auth-token
TWILIO_FROM=+1234567890

# Company Information
COMPANY_NAME=Your Logistics Company
COMPANY_ADDRESS=123 Main St, City, Country
COMPANY_PHONE=+212123456789
COMPANY_EMAIL=info@logistics.com
```

### 4. Create Required Directories

```bash
# Create upload directories
mkdir -p uploads/{pdfs,temp,cin_documents,rib_documents,stock_photos,payment_proofs}
chmod 755 uploads
chmod -R 755 uploads/

# Create logs directory
mkdir -p logs
chmod 755 logs
```

### 5. Web Server Configuration

#### Apache (.htaccess)

Create `.htaccess` in project root:

```apache
RewriteEngine On

# Handle Angular HTML5 mode
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_URI} !^/api/
RewriteRule ^.*$ /index.html [L]

# API routes
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^api/(.*)$ /api/index.php [L,QSA]

# Security headers
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"
```

#### Nginx Configuration

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/logistics-system;
    index index.html index.php;

    # Security headers
    add_header X-Content-Type-Options nosniff;
    add_header X-Frame-Options DENY;
    add_header X-XSS-Protection "1; mode=block";

    # API routes
    location /api/ {
        try_files $uri $uri/ /api/index.php?$query_string;
    }

    # PHP processing
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Frontend routes (Angular)
    location / {
        try_files $uri $uri/ /index.html;
    }

    # Static files
    location /uploads/ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

### 6. API Entry Point

Create `api/index.php`:

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && substr($line, 0, 1) !== '#') {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[$key] = $value;
        }
    }
}

// Simple router
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Remove /api prefix
$route = preg_replace('#^/api/#', '', $requestUri);
$segments = explode('/', trim($route, '/'));

try {
    // Route to appropriate controller
    switch ($segments[0]) {
        case 'auth':
            $controller = new \App\Controllers\AuthController();
            break;
        case 'users':
            $controller = new \App\Controllers\UserController();
            break;
        case 'orders':
            $controller = new \App\Controllers\OrderController();
            break;
        case 'stock':
            $controller = new \App\Controllers\StockController();
            break;
        case 'delivery':
            $controller = new \App\Controllers\DeliveryController();
            break;
        case 'admin':
            $controller = new \App\Controllers\AdminController();
            break;
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Route not found']);
            exit;
    }

    // Call appropriate method based on HTTP method and route
    $method = strtolower($requestMethod);
    $action = $segments[1] ?? 'index';
    
    $methodName = $method . ucfirst($action);
    
    if (method_exists($controller, $methodName)) {
        $controller->$methodName();
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Method not found']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error: ' . $e->getMessage()]);
}
```

### 7. Security Configuration

```bash
# Generate secure keys
php -r "echo bin2hex(random_bytes(32)) . PHP_EOL;" # For APP_KEY
php -r "echo bin2hex(random_bytes(64)) . PHP_EOL;" # For JWT_SECRET

# Set proper permissions
chmod 600 .env
chmod -R 750 config/
chmod -R 755 src/
```

### 8. Cron Jobs Setup

Add to crontab (`crontab -e`):

```bash
# Cleanup temporary files daily at 2 AM
0 2 * * * /usr/bin/php /path/to/logistics-system/scripts/cleanup.php

# Send daily reports at 8 AM
0 8 * * * /usr/bin/php /path/to/logistics-system/scripts/daily_reports.php
```

### 9. SSL Certificate (Production)

```bash
# Using Let's Encrypt
certbot --nginx -d your-domain.com
```

## Default Login Credentials

- **Admin Account:**
  - Email: `admin@logistics.com`
  - Password: `admin123`

**⚠️ Important: Change the default admin password immediately after installation!**

## Testing the Installation

1. **Database Connection:**
```bash
php -r "
try {
    \$pdo = new PDO('mysql:host=localhost;dbname=logistics_db', 'logistics_user', 'password');
    echo 'Database connection successful!' . PHP_EOL;
} catch(PDOException \$e) {
    echo 'Database connection failed: ' . \$e->getMessage() . PHP_EOL;
}
"
```

2. **API Test:**
```bash
curl -X POST http://your-domain.com/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@logistics.com","password":"admin123"}'
```

3. **File Permissions:**
```bash
ls -la uploads/
# Should show proper ownership and permissions
```

## Troubleshooting

### Common Issues

1. **Database Connection Failed:**
   - Check database credentials in `.env`
   - Ensure MySQL service is running
   - Verify user permissions

2. **File Upload Issues:**
   - Check uploads directory permissions
   - Verify PHP `upload_max_filesize` setting
   - Check disk space

3. **Email Not Sending:**
   - Verify SMTP credentials
   - Check if less secure apps are enabled (Gmail)
   - Test with a different email provider

4. **Permission Denied:**
   - Check file/directory ownership
   - Verify web server user permissions
   - Review SELinux settings (if applicable)

### Log Files

- **Application Logs:** `logs/app.log`
- **PHP Errors:** Check your web server error logs
- **Database Errors:** MySQL error log

## Performance Optimization

1. **Enable PHP OPcache:**
```ini
; In php.ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.revalidate_freq=2
```

2. **Database Indexing:**
```sql
-- Additional indexes for better performance
CREATE INDEX idx_orders_status_date ON orders(status, created_at);
CREATE INDEX idx_users_role_status ON users(role, status);
```

3. **Enable Gzip Compression:**
```apache
# In .htaccess
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
</IfModule>
```

## Support

For technical support or questions:
- Documentation: See README.md for usage guide
- Issues: Create issues in the project repository
- Email: support@logistics.com

---

**Next Steps:** After installation, see [README.md](README.md) for user guide and system features.