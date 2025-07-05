<?php
require_once 'vendor/autoload.php'; // For Composer packages

// QR Code generation using phpqrcode library
function generateQRCode($data, $filename = null) {
    require_once 'libs/phpqrcode/qrlib.php';
    
    if (!$filename) {
        $filename = 'qr_' . uniqid() . '.png';
    }
    
    $filepath = UPLOAD_PATH . 'qr_codes/' . $filename;
    
    // Create directory if not exists
    if (!file_exists(dirname($filepath))) {
        mkdir(dirname($filepath), 0755, true);
    }
    
    QRcode::png($data, $filepath, QR_ECLEVEL_L, 4);
    
    return $filename;
}

// Generate tracking number
function generateTrackingNumber() {
    return 'TRK' . date('Ymd') . strtoupper(substr(uniqid(), -6));
}

// Generate delivery slip number
function generateSlipNumber() {
    return 'SLP' . date('Ymd') . strtoupper(substr(uniqid(), -4));
}

// Sanitize input data
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Validate email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Validate phone number (basic Moroccan format)
function isValidPhone($phone) {
    return preg_match('/^(\+212|0)[5-7][0-9]{8}$/', $phone);
}

// Format currency
function formatCurrency($amount) {
    return number_format($amount, 2) . ' MAD';
}

// Format date
function formatDate($date, $format = 'd/m/Y H:i') {
    return date($format, strtotime($date));
}

// Calculate delivery fee based on weight and distance
function calculateDeliveryFee($weight, $pickup_city_id, $delivery_city_id, $db) {
    try {
        // Base fee from delivery city
        $stmt = $db->prepare("SELECT delivery_fee FROM cities WHERE id = ?");
        $stmt->execute([$delivery_city_id]);
        $base_fee = $stmt->fetchColumn();
        
        // Weight surcharge (per kg above 1kg)
        $weight_surcharge = max(0, ($weight - 1)) * 5; // 5 MAD per extra kg
        
        // Inter-city surcharge
        $inter_city_surcharge = 0;
        if ($pickup_city_id != $delivery_city_id) {
            $inter_city_surcharge = 10; // 10 MAD for inter-city
        }
        
        return $base_fee + $weight_surcharge + $inter_city_surcharge;
    } catch (Exception $e) {
        error_log("Calculate delivery fee error: " . $e->getMessage());
        return 25; // Default fee
    }
}

// Send notification
function sendNotification($user_id, $title, $message, $type = 'info', $db) {
    try {
        $stmt = $db->prepare("
            INSERT INTO notifications (user_id, title, message, type)
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([$user_id, $title, $message, $type]);
    } catch (Exception $e) {
        error_log("Send notification error: " . $e->getMessage());
        return false;
    }
}

// Send email notification
function sendEmailNotification($to, $subject, $message, $isHTML = true) {
    // Using PHPMailer for email sending
    require_once 'libs/PHPMailer/PHPMailer.php';
    require_once 'libs/PHPMailer/SMTP.php';
    require_once 'libs/PHPMailer/Exception.php';
    
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\SMTP;
    use PHPMailer\PHPMailer\Exception;
    
    try {
        $mail = new PHPMailer(true);
        
        // SMTP configuration
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        
        // Email settings
        $mail->setFrom(ADMIN_EMAIL, SITE_NAME);
        $mail->addAddress($to);
        $mail->Subject = $subject;
        
        if ($isHTML) {
            $mail->isHTML(true);
            $mail->Body = $message;
        } else {
            $mail->Body = $message;
        }
        
        return $mail->send();
    } catch (Exception $e) {
        error_log("Email error: " . $e->getMessage());
        return false;
    }
}

// Send SMS notification using Twilio
function sendSMSNotification($to, $message) {
    if (empty(TWILIO_SID) || empty(TWILIO_TOKEN)) {
        return false;
    }
    
    require_once 'libs/twilio/autoload.php';
    
    use Twilio\Rest\Client;
    
    try {
        $client = new Client(TWILIO_SID, TWILIO_TOKEN);
        
        $client->messages->create(
            $to,
            [
                'from' => TWILIO_FROM,
                'body' => $message
            ]
        );
        
        return true;
    } catch (Exception $e) {
        error_log("SMS error: " . $e->getMessage());
        return false;
    }
}

// Generate PDF using TCPDF
function generatePDF($html, $filename, $orientation = 'P') {
    require_once 'libs/tcpdf/tcpdf.php';
    
    $pdf = new TCPDF($orientation, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator(SITE_NAME);
    $pdf->SetAuthor(SITE_NAME);
    $pdf->SetTitle($filename);
    
    // Set margins
    $pdf->SetMargins(15, 20, 15);
    $pdf->SetHeaderMargin(10);
    $pdf->SetFooterMargin(10);
    
    // Set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, 20);
    
    // Add a page
    $pdf->AddPage();
    
    // Set font
    $pdf->SetFont('helvetica', '', 12);
    
    // Output HTML content
    $pdf->writeHTML($html, true, false, true, false, '');
    
    return $pdf->Output($filename, 'S'); // Return as string
}

// Log delivery status change
function logStatusChange($package_id, $old_status, $new_status, $changed_by, $location = null, $notes = null, $db) {
    try {
        $stmt = $db->prepare("
            INSERT INTO delivery_status_logs (package_id, old_status, new_status, changed_by, change_location, notes)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$package_id, $old_status, $new_status, $changed_by, $location, $notes]);
    } catch (Exception $e) {
        error_log("Log status change error: " . $e->getMessage());
        return false;
    }
}

// Get status display name
function getStatusDisplayName($status) {
    $status_names = [
        'en_attente' => 'En Attente',
        'pret_pour_preparation' => 'Prêt pour Préparation',
        'ready' => 'Prêt',
        'en_preparation' => 'En Préparation',
        'ramasse' => 'Ramassé',
        'in_delivery_slip' => 'En Bordereau',
        'mise_en_distribution' => 'Mise en Distribution',
        'delivered' => 'Livré'
    ];
    
    return $status_names[$status] ?? ucfirst(str_replace('_', ' ', $status));
}

// Get status color class for UI
function getStatusColor($status) {
    $colors = [
        'en_attente' => 'warning',
        'pret_pour_preparation' => 'info',
        'ready' => 'primary',
        'en_preparation' => 'info',
        'ramasse' => 'secondary',
        'in_delivery_slip' => 'secondary',
        'mise_en_distribution' => 'primary',
        'delivered' => 'success'
    ];
    
    return $colors[$status] ?? 'secondary';
}

// Check if user can access resource
function canUserAccess($user_role, $required_roles) {
    if (is_string($required_roles)) {
        $required_roles = [$required_roles];
    }
    
    return in_array($user_role, $required_roles);
}

// Generate CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

// Validate CSRF token
function validateCSRFToken($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

// Upload file with validation
function uploadFile($file, $allowed_types = ['jpg', 'jpeg', 'png', 'pdf'], $max_size = MAX_FILE_SIZE) {
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['success' => false, 'error' => 'No file uploaded'];
    }
    
    // Check file size
    if ($file['size'] > $max_size) {
        return ['success' => false, 'error' => 'File too large'];
    }
    
    // Check file type
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_ext, $allowed_types)) {
        return ['success' => false, 'error' => 'File type not allowed'];
    }
    
    // Generate unique filename
    $filename = uniqid() . '.' . $file_ext;
    $filepath = UPLOAD_PATH . $filename;
    
    // Create upload directory if not exists
    if (!file_exists(UPLOAD_PATH)) {
        mkdir(UPLOAD_PATH, 0755, true);
    }
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $filename, 'filepath' => $filepath];
    } else {
        return ['success' => false, 'error' => 'Failed to move uploaded file'];
    }
}

// Compress image
function compressImage($source, $destination, $quality = 75) {
    $info = getimagesize($source);
    
    if ($info['mime'] == 'image/jpeg') {
        $image = imagecreatefromjpeg($source);
    } elseif ($info['mime'] == 'image/gif') {
        $image = imagecreatefromgif($source);
    } elseif ($info['mime'] == 'image/png') {
        $image = imagecreatefrompng($source);
    } else {
        return false;
    }
    
    return imagejpeg($image, $destination, $quality);
}

// Get system setting
function getSystemSetting($key, $default = null, $db) {
    try {
        $stmt = $db->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $value = $stmt->fetchColumn();
        
        return $value !== false ? $value : $default;
    } catch (Exception $e) {
        error_log("Get system setting error: " . $e->getMessage());
        return $default;
    }
}

// Set system setting
function setSystemSetting($key, $value, $description = null, $db) {
    try {
        $stmt = $db->prepare("
            INSERT INTO system_settings (setting_key, setting_value, description)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE
            setting_value = VALUES(setting_value),
            description = COALESCE(VALUES(description), description)
        ");
        return $stmt->execute([$key, $value, $description]);
    } catch (Exception $e) {
        error_log("Set system setting error: " . $e->getMessage());
        return false;
    }
}
?>