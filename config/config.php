<?php
// System Configuration
define('SITE_URL', 'http://localhost');
define('SITE_NAME', 'Delivery Management System');
define('ADMIN_EMAIL', 'admin@delivery.com');

// Upload settings
define('UPLOAD_PATH', 'uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// Email settings (SMTP)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');

// Twilio SMS settings
define('TWILIO_SID', '');
define('TWILIO_TOKEN', '');
define('TWILIO_FROM', '');

// Security settings
define('CSRF_TOKEN_NAME', 'csrf_token');
define('SESSION_TIMEOUT', 3600); // 1 hour

// Status definitions
define('STATUS_EN_ATTENTE', 'en_attente');
define('STATUS_PRET_PREPARATION', 'pret_pour_preparation');
define('STATUS_READY', 'ready');
define('STATUS_EN_PREPARATION', 'en_preparation');
define('STATUS_RAMASSE', 'ramasse');
define('STATUS_IN_DELIVERY_SLIP', 'in_delivery_slip');
define('STATUS_MISE_EN_DISTRIBUTION', 'mise_en_distribution');
define('STATUS_DELIVERED', 'delivered');

// Package types
define('PACKAGE_TYPE_PARCEL', 'parcel');
define('PACKAGE_TYPE_DOCUMENT', 'document');
define('PACKAGE_TYPE_FRAGILE', 'fragile');

// User roles
define('ROLE_ADMIN', 'admin');
define('ROLE_VENDOR', 'vendor');
define('ROLE_DELIVERY_AGENT', 'delivery_agent');

session_start();
?>