# Logistics Management System

A comprehensive logistics and delivery management system with role-based access control, real-time tracking, and automated workflows.

## 🚀 Features

### User Management & Authentication
- **Multi-role system**: Client, Livreur (Delivery Person), Admin
- **Registration with approval workflow**
- **JWT-based authentication**
- **Document upload** (CIN, RIB) for verification
- **Admin approval/rejection** with notifications

### Stock Management
- **Stock item creation** with photo upload
- **Admin approval workflow** for stock items
- **Quantity tracking** and availability checks
- **Integration with order system**

### Order Management
- **Ramassage orders** (pickup requests)
- **Stock-based orders** (from approved inventory)
- **Real-time status tracking** with 9 status levels
- **Barcode and QR code generation**
- **Address and contact management**

### Delivery Workflow
- **Bon de Livraison** (Pickup Bonds) with scanning
- **Bon d'Envoi** (Distribution Bonds) by zone
- **Livreur assignment** and tracking
- **Sound feedback** for scanning operations
- **GPS location logging** for deliveries

### Invoicing & Payments
- **Automated client invoicing** with delivery fees
- **Livreur commission tracking**
- **Payment proof upload**
- **PDF generation** for all documents

### Reporting & Analytics
- **Real-time dashboard** with KPIs
- **Delivery performance metrics**
- **Revenue and commission reports**
- **CSV/Excel exports**
- **Email summaries**

### Technical Features
- **RESTful API** architecture
- **Barcode/QR code scanning** support
- **Email and SMS** notifications
- **PDF generation** for documents
- **File upload** with validation
- **Rate limiting** and security
- **Comprehensive logging**

## 🏗️ System Architecture

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Frontend      │    │   Backend API   │    │   Database      │
│   (Web App)     │◄──►│   (PHP/MySQL)   │◄──►│   (MySQL)       │
└─────────────────┘    └─────────────────┘    └─────────────────┘
         │                       │                       │
         ▼                       ▼                       ▼
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Mobile App    │    │   Services      │    │   File Storage  │
│   (Optional)    │    │   Email/SMS/PDF │    │   Uploads       │
└─────────────────┘    └─────────────────┘    └─────────────────┘
```

## 📋 User Roles & Permissions

### 👤 Client
- Register with store information
- Manage stock items (if enabled)
- Create ramassage orders
- View order status and tracking
- Receive invoices and notifications

### 🚚 Livreur (Delivery Person)
- Register with delivery fees
- Upload required documents
- View assigned deliveries
- Scan and update order status
- Manage delivery invoices

### 🔧 Admin
- Approve/reject user registrations
- Manage cities, zones, and tariffs
- Create delivery and distribution bonds
- Generate reports and analytics
- Configure system settings

## 📱 Order Status Flow

```
Ramassage Orders:
EN_ATTENTE_RAMASSAGE → EN_PREPARATION → RAMASSE → PRET_POUR_DISTRIBUTION → MISE_EN_DISTRIBUTION → LIVRE/REFUSE/ANNULE

Stock Orders:
EN_ATTENTE_PREPARATION → EN_PREPARATION → RAMASSE → PRET_POUR_DISTRIBUTION → MISE_EN_DISTRIBUTION → LIVRE/REFUSE/ANNULE
```

## 🔧 Installation

See [INSTALLATION.md](INSTALLATION.md) for detailed setup instructions.

### Quick Start

```bash
# Clone repository
git clone <repository-url>
cd logistics-system

# Install dependencies
composer install

# Setup environment
cp .env.example .env
# Edit .env with your configuration

# Setup database
mysql -u root -p < database/schema.sql

# Configure web server
# See INSTALLATION.md for Apache/Nginx configuration
```

## 🎯 Usage Guide

### Initial Setup

1. **Admin Login:**
   - Email: `admin@logistics.com`
   - Password: `admin123` (change immediately!)

2. **Configure System:**
   - Add cities and zones
   - Set delivery tariffs
   - Configure email/SMS settings

3. **User Management:**
   - Review pending registrations
   - Approve clients and livreurs
   - Set delivery fees and permissions

### Daily Operations

#### For Clients:
1. **Create Orders:**
   ```
   Dashboard → Colis → Colis de Ramassage → Ajouter Colis
   ```

2. **Manage Stock** (if enabled):
   ```
   Dashboard → Colis → Colis de Stock → Ajouter Colis
   ```

3. **Track Orders:**
   ```
   Dashboard → Colis → Liste Colis
   ```

#### For Admins:
1. **Create Pickup Bonds:**
   ```
   Orders → Select Orders → Créer Bon de Livraison
   ```

2. **Scan Orders:**
   ```
   Bon de Livraison → Scan Modal → Use Barcode Scanner
   ```

3. **Create Distribution Bonds:**
   ```
   Liste Colis → Filter by Zone → Créer Bon d'Envoi
   ```

#### For Livreurs:
1. **View Assignments:**
   ```
   Dashboard → Mes Bons d'Envoi
   ```

2. **Deliver Orders:**
   ```
   Mes Colis → Scan/Enter Tracking Code → Update Status
   ```

3. **Manage Invoices:**
   ```
   Factures → Upload Payment Proof
   ```

## 📊 API Documentation

### Authentication
```http
POST /api/auth/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password"
}
```

### Orders
```http
GET /api/orders
Authorization: Bearer <token>

POST /api/orders
Authorization: Bearer <token>
Content-Type: application/json

{
  "product_name": "Product Name",
  "quantity": 1,
  "price": 100.00,
  "phone": "0612345678",
  "city_id": 1,
  "address": "Delivery Address",
  "note": "Special instructions"
}
```

### Scanning
```http
POST /api/delivery/scan
Authorization: Bearer <token>
Content-Type: application/json

{
  "bon_livraison_id": 1,
  "order_id": 1
}
```

## 🔒 Security Features

- **JWT Authentication** with expiration
- **Role-based access control**
- **Input validation** and sanitization
- **CSRF protection**
- **Rate limiting**
- **File upload restrictions**
- **SQL injection protection**
- **XSS prevention**

## 📈 Performance

- **Database indexing** for fast queries
- **File caching** for uploaded documents
- **Optimized PDF generation**
- **Efficient barcode processing**
- **Pagination** for large datasets
- **Background job processing**

## 🌐 Multi-language Support

- **French** (Primary)
- **Arabic** (RTL support)
- **English** (International)

## 📱 Mobile Compatibility

- **Responsive design** for all devices
- **Touch-friendly** scanning interface
- **Mobile-optimized** forms
- **Offline capability** (planned)

## 🔧 Configuration

### Email Settings
```env
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=your-email@gmail.com
SMTP_PASSWORD=your-app-password
```

### SMS Settings (Twilio)
```env
TWILIO_SID=your-account-sid
TWILIO_TOKEN=your-auth-token
TWILIO_FROM=+1234567890
```

### Company Information
```env
COMPANY_NAME=Your Company
COMPANY_ADDRESS=Your Address
COMPANY_PHONE=+212123456789
COMPANY_EMAIL=info@company.com
```

## 📋 System Requirements

### Minimum Requirements
- **PHP:** 8.0+
- **MySQL:** 8.0+ or MariaDB 10.4+
- **Memory:** 512MB RAM
- **Storage:** 10GB available space
- **Bandwidth:** 100Mbps

### Recommended Requirements
- **PHP:** 8.1+ with OPcache
- **MySQL:** 8.0+ with InnoDB
- **Memory:** 2GB RAM
- **Storage:** 50GB SSD
- **Bandwidth:** 1Gbps

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests
5. Submit a pull request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 📞 Support

- **Documentation:** [Wiki](https://github.com/your-repo/wiki)
- **Issues:** [GitHub Issues](https://github.com/your-repo/issues)
- **Email:** support@logistics.com
- **Phone:** +212 123 456 789

## 🚀 Roadmap

### Version 2.0 (Planned)
- [ ] Real-time notifications with WebSockets
- [ ] Mobile app (React Native)
- [ ] Advanced analytics dashboard
- [ ] Integration with payment gateways
- [ ] Route optimization
- [ ] Multi-tenant support

### Version 1.1 (In Progress)
- [ ] Enhanced reporting
- [ ] Bulk operations
- [ ] API rate limiting improvements
- [ ] Performance optimizations

## 👥 Team

- **Backend Developer:** System architecture and API
- **Frontend Developer:** User interface and experience
- **DevOps Engineer:** Deployment and infrastructure
- **QA Engineer:** Testing and quality assurance

---

**Built with ❤️ for efficient logistics management**