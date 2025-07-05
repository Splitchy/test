# PHP Website Installation System

A complete, self-installing PHP website with automatic database setup, user management, and a modern responsive interface.

## 🚀 Features

- **One-Click Installation**: Complete website setup through a guided web interface
- **Automatic Database Setup**: Creates all necessary tables and initial data
- **User Management**: Admin, vendor, and user roles with proper authentication
- **Security Features**: Password hashing, SQL injection protection, secure sessions
- **Modern UI**: Bootstrap 5 with custom CSS and responsive design
- **Self-Disabling**: Installation script automatically disables after completion
- **Error Handling**: Comprehensive error handling and user feedback

## 📋 Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher (or MariaDB 10.2+)
- Apache/Nginx web server
- PDO MySQL extension enabled
- mod_rewrite enabled (for pretty URLs)

## 🛠️ Installation

### Step 1: Upload Files
Upload all files to your web server's document root or a subdirectory.

### Step 2: Set Permissions
Ensure your web server can write to the directory:
```bash
chmod 755 /path/to/your/website
```

### Step 3: Access Installation
Navigate to your website in a browser:
```
http://yourdomain.com/install.php
```

### Step 4: Follow the Installation Wizard

#### Database Configuration (Step 1)
- **Database Host**: Usually `localhost`
- **Database Name**: Name of your MySQL database
- **Database Username**: MySQL username with database privileges
- **Database Password**: Password for the MySQL user
- **Database Port**: MySQL port (default: 3306)

#### Site Configuration (Step 2)
- **Site Name**: Name of your website
- **Site URL**: Full URL to your website
- **Admin Email**: Email for the administrator account
- **Admin Password**: Password for the administrator (minimum 6 characters)
- **Vendor Email**: Email for vendor notifications

#### Installation Confirmation (Step 3)
Review your settings and click "Install Website" to complete the setup.

#### Completion (Step 4)
The installation script will automatically disable itself and redirect you to your new website.

## 📁 File Structure

```
/
├── install.php              # Installation script (auto-disabled after setup)
├── index.php               # Main application router
├── config.php              # Database and site configuration (created during install)
├── .htaccess               # URL rewriting and security rules (created during install)
├── .installed              # Installation marker file (created during install)
│
├── includes/
│   ├── header.php          # Website header template
│   └── footer.php          # Website footer template
│
├── pages/
│   ├── home.php            # Homepage content
│   ├── login.php           # User login page
│   ├── dashboard.php       # User dashboard
│   ├── admin.php           # Admin panel
│   └── 404.php             # Error page
│
└── assets/
    ├── css/
    │   └── style.css       # Custom stylesheet
    └── js/
        └── script.js       # Custom JavaScript
```

## 🗄️ Database Schema

The installation creates the following tables:

### `users`
- User accounts with roles (admin, vendor, user)
- Secure password hashing
- Account status management

### `products`
- Product catalog with vendor associations
- Price and description fields
- Active/inactive status

### `orders`
- Order management system
- User associations and status tracking
- Order totals and timestamps

### `settings`
- Dynamic site configuration
- Key-value storage for settings
- Easy configuration updates

## 👤 User Roles

### Administrator
- Full access to admin panel
- User management capabilities
- Site configuration control
- Product and order management

### Vendor
- Product management access
- Order fulfillment capabilities
- Limited administrative functions

### User
- Standard user account
- Dashboard access
- Order placement and tracking

## 🔐 Security Features

- **Password Hashing**: Uses PHP's `password_hash()` with strong algorithms
- **SQL Injection Protection**: All queries use prepared statements
- **Session Management**: Secure session handling with proper regeneration
- **File Access Control**: Sensitive files protected via `.htaccess`
- **Input Validation**: Client-side and server-side validation
- **CSRF Protection**: Anti-forgery tokens for forms (can be extended)

## 🎨 Customization

### Styling
Edit `assets/css/style.css` to customize the appearance:
- CSS custom properties for easy color scheme changes
- Responsive design with Bootstrap 5
- Custom animations and transitions

### Functionality
Extend `assets/js/script.js` for additional features:
- Form validation and enhancement
- AJAX functionality
- Interactive components

### Templates
Modify files in `includes/` and `pages/` to change:
- Page layouts and structure
- Navigation menus
- Content presentation

## ⚙️ Configuration

After installation, site settings are stored in:
- `config.php` - Database and core configuration
- `settings` table - Dynamic site preferences

### Common Settings
```php
// Access via helper functions
$siteName = getSetting('site_name');
$adminEmail = getSetting('admin_email');

// Update settings
setSetting('maintenance_mode', 'enabled');
```

## 🔧 Troubleshooting

### Installation Issues

**Database Connection Failed**
- Verify database credentials
- Ensure MySQL server is running
- Check user permissions

**Permission Denied**
- Verify web server write permissions
- Check file ownership
- Ensure PHP has necessary extensions

**Install Page Not Loading**
- Check for PHP syntax errors
- Verify server configuration
- Review web server error logs

### Post-Installation Issues

**Pages Not Loading (404 Errors)**
- Ensure mod_rewrite is enabled
- Verify `.htaccess` file exists
- Check virtual host configuration

**Login Issues**
- Verify database connection
- Check user credentials
- Review session configuration

## 🚀 Deployment Tips

### Production Setup
1. Use HTTPS for secure connections
2. Set proper file permissions (644 for files, 755 for directories)
3. Configure proper backup procedures
4. Set up regular security updates
5. Monitor error logs regularly

### Performance Optimization
- Enable PHP OPcache
- Use proper MySQL indexing
- Implement caching strategies
- Optimize images and assets
- Consider CDN for static content

## 📝 Development

### Adding New Pages
1. Create PHP file in `pages/` directory
2. Add route in `index.php` router
3. Include navigation links in header
4. Test functionality and permissions

### Database Changes
- Use migration scripts for schema changes
- Update installation script for new installations
- Maintain backward compatibility when possible

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## 📄 License

This project is open source and available under the [MIT License](LICENSE).

## 🆘 Support

For support and questions:
- Check the troubleshooting section above
- Review server error logs
- Ensure all requirements are met
- Verify file permissions and configuration

## 🔄 Updates

To update the website:
1. Backup your database and files
2. Replace core files (preserve `config.php`)
3. Run any necessary migration scripts
4. Test functionality thoroughly

---

## Quick Start Commands

```bash
# Download and extract files
wget https://github.com/your-repo/website.zip
unzip website.zip

# Set permissions
chmod -R 755 website/
chmod 644 website/install.php

# Navigate to installation
# http://yourdomain.com/install.php
```

**Note**: The `install.php` file will automatically disable itself after successful installation to prevent security issues. If you need to reinstall, delete the `.installed` file and restore the `install.php` file.