<?php

return [
    'app_name' => 'Logistics Management System',
    'app_version' => '1.0.0',
    'debug' => $_ENV['APP_DEBUG'] ?? false,
    'timezone' => $_ENV['APP_TIMEZONE'] ?? 'UTC',
    'encryption_key' => $_ENV['APP_KEY'] ?? 'default-key-please-change',
    
    // JWT Configuration
    'jwt' => [
        'secret' => $_ENV['JWT_SECRET'] ?? 'your-secret-key',
        'expire' => 86400, // 24 hours
        'algorithm' => 'HS256'
    ],
    
    // File Upload Configuration
    'uploads' => [
        'max_size' => 5242880, // 5MB
        'allowed_types' => ['jpg', 'jpeg', 'png', 'pdf'],
        'path' => __DIR__ . '/../uploads/'
    ],
    
    // Email Configuration
    'email' => [
        'smtp_host' => $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com',
        'smtp_port' => $_ENV['SMTP_PORT'] ?? 587,
        'smtp_username' => $_ENV['SMTP_USERNAME'] ?? '',
        'smtp_password' => $_ENV['SMTP_PASSWORD'] ?? '',
        'from_email' => $_ENV['FROM_EMAIL'] ?? 'noreply@logistics.com',
        'from_name' => $_ENV['FROM_NAME'] ?? 'Logistics System'
    ],
    
    // SMS Configuration (Twilio)
    'sms' => [
        'account_sid' => $_ENV['TWILIO_SID'] ?? '',
        'auth_token' => $_ENV['TWILIO_TOKEN'] ?? '',
        'from_number' => $_ENV['TWILIO_FROM'] ?? ''
    ],
    
    // PDF Configuration
    'pdf' => [
        'logo_path' => __DIR__ . '/../assets/logo.png',
        'company_name' => $_ENV['COMPANY_NAME'] ?? 'Logistics Company',
        'company_address' => $_ENV['COMPANY_ADDRESS'] ?? '123 Main St, City, Country',
        'company_phone' => $_ENV['COMPANY_PHONE'] ?? '+1234567890',
        'company_email' => $_ENV['COMPANY_EMAIL'] ?? 'info@logistics.com'
    ]
];