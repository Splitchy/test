# 🚚 Multi-Role Delivery Management System

A comprehensive delivery management system built in PHP with MySQL, featuring multi-role authentication, real-time tracking, QR code scanning, and advanced reporting capabilities designed for the Moroccan delivery market.

## 🌟 Features

### 👥 Multi-Role System
- **Admin Role**: Complete system management and oversight
- **Vendor Role**: Package pickup request management
- **Delivery Agent Role**: Package delivery and tracking

### 📦 Core Functionality
- **Pickup Request Management**: Create and track package pickups
- **QR Code System**: Automatic generation and mobile scanning
- **Real-time Tracking**: Complete package lifecycle tracking
- **Delivery Slip Management**: Bulk assignment and PDF generation
- **Payment Processing**: COD (Cash on Delivery) support
- **Multi-city Support**: Configurable delivery zones and fees

### 🔧 Technical Features
- **Secure Authentication**: Password hashing and session management
- **CSRF Protection**: Form security and validation
- **PDF Generation**: Professional delivery slips and reports
- **Email/SMS Notifications**: Real-time status updates
- **Responsive Design**: Mobile-friendly interface
- **RESTful API**: AJAX endpoints for dynamic updates

## 🏗️ System Architecture

### Database Schema
```sql
- users (Multi-role user management)
- cities (Delivery zones with fees)
- pickup_requests (Package pickup requests)
- delivery_packages (Active deliveries)
- delivery_slips (Agent assignments)
- delivery_status_logs (Complete tracking history)
- package_tracking (Real-time location updates)
- notifications (System notifications)
- system_settings (Configuration)
```

### Status Flow
```
en_attente → pret_pour_preparation → ready → en_preparation → 
ramasse → in_delivery_slip → mise_en_distribution → delivered
```

## 🚀 Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Composer (for dependencies)

### Setup Steps

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd delivery-system
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Database setup**
   ```bash
   # Create database
   mysql -u root -p -e "CREATE DATABASE delivery_system;"
   
   # Import schema
   mysql -u root -p delivery_system < sql/database_schema.sql
   ```

4. **Configure database connection**
   Edit `config/database.php`:
   ```php
   private $host = 'localhost';
   private $db_name = 'delivery_system';
   private $username = 'your_username';
   private $password = 'your_password';
   ```

5. **Set up file permissions**
   ```bash
   chmod 755 uploads/
   chmod 755 uploads/qr_codes/
   ```

6. **Configure email/SMS settings**
   Edit `config/config.php` for SMTP and Twilio settings.

## 🔑 Default Login Credentials

- **Admin**: `admin` / `admin123`
- **Note**: Additional users can be registered through the registration page

## 📱 User Interfaces

### Admin Dashboard
- Overview of system operations
- User management (vendors, delivery agents)
- City and fee management
- Comprehensive reporting
- Delivery slip creation

### Vendor Interface
- Create pickup requests
- Track package status
- View delivery history
- Manage profile settings
- Real-time notifications

### Delivery Agent Interface
- View assigned delivery slips
- Scan QR codes for updates
- Update package status
- Mobile-friendly design
- Performance tracking

## 🔧 API Endpoints

### Authentication Required
- `POST /api/scan_qr` - Update package status via QR scan
- `POST /api/update_status` - Manual status updates
- `GET /api/track_package` - Package tracking information
- `GET /api/notifications` - User notifications

### Public Endpoints
- `GET /api/track/{tracking_number}` - Public package tracking

## 📊 Features in Detail

### QR Code System
- Automatic generation for each package
- Contains tracking information and package ID
- Mobile-optimized scanning interface
- Real-time status updates

### PDF Generation
- Professional delivery slip formatting
- Company branding support
- Batch printing capabilities
- Digital signatures support

### Notification System
- Real-time status updates
- Email notifications (SMTP)
- SMS support (Twilio)
- In-app notification center

### Reporting & Analytics
- Dashboard metrics
- Performance tracking
- Delivery statistics
- Revenue reporting
- Exportable data (PDF/Excel)

## 🛠️ Configuration

### Email Settings (config/config.php)
```php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
```

### SMS Settings (Twilio)
```php
define('TWILIO_SID', 'your-twilio-sid');
define('TWILIO_TOKEN', 'your-twilio-token');
define('TWILIO_FROM', 'your-twilio-number');
```

### City Configuration
Add new cities through the admin interface or directly in the database:
```sql
INSERT INTO cities (name, code, delivery_fee) VALUES 
('New City', 'NC', 30.00);
```

## 🔒 Security Features

- **Password Hashing**: Secure password storage
- **CSRF Protection**: Form security tokens
- **Input Sanitization**: XSS prevention
- **Session Management**: Secure session handling
- **Role-based Access**: Granular permission control

## 📱 Mobile Optimization

- Responsive Bootstrap design
- Touch-friendly interfaces
- Mobile QR code scanning
- Optimized for delivery agents on-the-go

## 🚨 Known Issues & Fixes

### Fixed Issues ✅
- Missing view files for zones and agents
- Controller method implementations
- Database column references
- Routing patterns
- Model method alignments

### Pending Fix ⚠️
- Syntax error in ApiController.php line 165 (missing closing parenthesis)
  **Fix**: Add `)` to complete the `new DeliverySlip($this->db)` statement

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## 📞 Support

For support or questions:
- Email: admin@delivery.com
- Documentation: Check the `docs/` folder
- Issues: Create a GitHub issue

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 🙏 Acknowledgments

- Bootstrap for responsive UI components
- Font Awesome for icons
- TCPDF for PDF generation
- PHPMailer for email functionality
- QR Code library for QR generation

---

**Built with ❤️ for the Moroccan delivery market**