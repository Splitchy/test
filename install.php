<?php
session_start();

// Check if already installed
if (file_exists('config.php') && file_exists('.installed')) {
    header('Location: index.php');
    exit('Website is already installed!');
}

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$error = '';
$success = '';

// Process form submissions
if ($_POST) {
    switch ($step) {
        case 1:
            // Database configuration
            if (isset($_POST['db_host'])) {
                $_SESSION['install_data'] = [
                    'db_host' => $_POST['db_host'],
                    'db_name' => $_POST['db_name'],
                    'db_user' => $_POST['db_user'],
                    'db_pass' => $_POST['db_pass'],
                    'db_port' => $_POST['db_port'] ?: 3306
                ];
                
                // Test database connection
                try {
                    $dsn = "mysql:host={$_POST['db_host']};port={$_POST['db_port']};charset=utf8mb4";
                    $pdo = new PDO($dsn, $_POST['db_user'], $_POST['db_pass']);
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    
                    // Create database if it doesn't exist
                    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$_POST['db_name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                    
                    $success = 'Database connection successful!';
                    header('Location: install.php?step=2');
                    exit;
                } catch (PDOException $e) {
                    $error = 'Database connection failed: ' . $e->getMessage();
                }
            }
            break;
            
        case 2:
            // Admin and email configuration
            if (isset($_POST['admin_email'])) {
                $_SESSION['install_data'] = array_merge($_SESSION['install_data'], [
                    'admin_email' => $_POST['admin_email'],
                    'admin_password' => $_POST['admin_password'],
                    'vendor_email' => $_POST['vendor_email'],
                    'site_name' => $_POST['site_name'],
                    'site_url' => $_POST['site_url']
                ]);
                
                header('Location: install.php?step=3');
                exit;
            }
            break;
            
        case 3:
            // Final installation
            if (isset($_POST['confirm_install'])) {
                try {
                    $data = $_SESSION['install_data'];
                    
                    // Create database connection
                    $dsn = "mysql:host={$data['db_host']};dbname={$data['db_name']};port={$data['db_port']};charset=utf8mb4";
                    $pdo = new PDO($dsn, $data['db_user'], $data['db_pass']);
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    
                    // Create tables
                    createTables($pdo);
                    
                    // Create admin user
                    createAdminUser($pdo, $data);
                    
                    // Create configuration file
                    createConfigFile($data);
                    
                    // Create .htaccess for security
                    createHtaccess();
                    
                    // Create basic website files
                    createWebsiteFiles($data);
                    
                    // Mark as installed
                    file_put_contents('.installed', date('Y-m-d H:i:s'));
                    
                    // Clean up session
                    unset($_SESSION['install_data']);
                    
                    header('Location: install.php?step=4');
                    exit;
                    
                } catch (Exception $e) {
                    $error = 'Installation failed: ' . $e->getMessage();
                }
            }
            break;
    }
}

function createTables($pdo) {
    $sql = "
    CREATE TABLE IF NOT EXISTS `users` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `email` varchar(255) NOT NULL,
        `password` varchar(255) NOT NULL,
        `role` enum('admin','vendor','user') NOT NULL DEFAULT 'user',
        `name` varchar(255) NOT NULL,
        `status` enum('active','inactive') NOT NULL DEFAULT 'active',
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `email` (`email`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS `products` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(255) NOT NULL,
        `description` text,
        `price` decimal(10,2) NOT NULL,
        `vendor_id` int(11) NOT NULL,
        `status` enum('active','inactive') NOT NULL DEFAULT 'active',
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `vendor_id` (`vendor_id`),
        FOREIGN KEY (`vendor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS `orders` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `total` decimal(10,2) NOT NULL,
        `status` enum('pending','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`),
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS `settings` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `setting_key` varchar(255) NOT NULL,
        `setting_value` text,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `setting_key` (`setting_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    
    $pdo->exec($sql);
}

function createAdminUser($pdo, $data) {
    $hashedPassword = password_hash($data['admin_password'], PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("INSERT INTO users (email, password, role, name) VALUES (?, ?, 'admin', 'Administrator')");
    $stmt->execute([$data['admin_email'], $hashedPassword]);
    
    // Insert settings
    $settings = [
        ['site_name', $data['site_name']],
        ['site_url', $data['site_url']],
        ['admin_email', $data['admin_email']],
        ['vendor_email', $data['vendor_email']]
    ];
    
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    foreach ($settings as $setting) {
        $stmt->execute($setting);
    }
}

function createConfigFile($data) {
    $config = "<?php
// Database Configuration
define('DB_HOST', '{$data['db_host']}');
define('DB_NAME', '{$data['db_name']}');
define('DB_USER', '{$data['db_user']}');
define('DB_PASS', '{$data['db_pass']}');
define('DB_PORT', '{$data['db_port']}');

// Site Configuration
define('SITE_NAME', '{$data['site_name']}');
define('SITE_URL', '{$data['site_url']}');
define('ADMIN_EMAIL', '{$data['admin_email']}');
define('VENDOR_EMAIL', '{$data['vendor_email']}');

// Security
define('SECRET_KEY', '" . bin2hex(random_bytes(32)) . "');

// Database Connection
try {
    \$dsn = \"mysql:host=\" . DB_HOST . \";dbname=\" . DB_NAME . \";port=\" . DB_PORT . \";charset=utf8mb4\";
    \$pdo = new PDO(\$dsn, DB_USER, DB_PASS);
    \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    \$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException \$e) {
    die('Database connection failed: ' . \$e->getMessage());
}

// Helper Functions
function getSetting(\$key, \$default = null) {
    global \$pdo;
    \$stmt = \$pdo->prepare(\"SELECT setting_value FROM settings WHERE setting_key = ?\");
    \$stmt->execute([\$key]);
    \$result = \$stmt->fetchColumn();
    return \$result !== false ? \$result : \$default;
}

function setSetting(\$key, \$value) {
    global \$pdo;
    \$stmt = \$pdo->prepare(\"INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)\");
    return \$stmt->execute([\$key, \$value]);
}
?>";
    
    file_put_contents('config.php', $config);
}

function createHtaccess() {
    $htaccess = "RewriteEngine On

# Security Headers
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection \"1; mode=block\"

# Hide sensitive files
<Files \"config.php\">
    Order allow,deny
    Deny from all
</Files>

<Files \".installed\">
    Order allow,deny
    Deny from all
</Files>

<Files \"install.php\">
    Order allow,deny
    Deny from all
</Files>

# Pretty URLs
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php?url=$1 [QSA,L]

# Prevent access to sensitive directories
RedirectMatch 403 ^/\\.git.*$
";
    
    file_put_contents('.htaccess', $htaccess);
}

function createWebsiteFiles($data) {
    // Create index.php
    $index = "<?php
require_once 'config.php';
session_start();

// Simple router
\$url = isset(\$_GET['url']) ? \$_GET['url'] : '';
\$url = rtrim(\$url, '/');
\$url = filter_var(\$url, FILTER_SANITIZE_URL);
\$url = explode('/', \$url);

\$page = \$url[0] ?: 'home';

// Check if user is logged in
\$isLoggedIn = isset(\$_SESSION['user_id']);
\$user = null;
if (\$isLoggedIn) {
    \$stmt = \$pdo->prepare(\"SELECT * FROM users WHERE id = ?\");
    \$stmt->execute([\$_SESSION['user_id']]);
    \$user = \$stmt->fetch();
}

// Handle logout
if (\$page === 'logout') {
    session_destroy();
    header('Location: /');
    exit;
}

// Handle login
if (\$page === 'login' && \$_POST) {
    \$email = \$_POST['email'] ?? '';
    \$password = \$_POST['password'] ?? '';
    
    \$stmt = \$pdo->prepare(\"SELECT * FROM users WHERE email = ? AND status = 'active'\");
    \$stmt->execute([\$email]);
    \$loginUser = \$stmt->fetch();
    
    if (\$loginUser && password_verify(\$password, \$loginUser['password'])) {
        \$_SESSION['user_id'] = \$loginUser['id'];
        header('Location: /dashboard');
        exit;
    } else {
        \$error = 'Invalid credentials';
    }
}

// Include header
include 'includes/header.php';

// Route to appropriate page
switch (\$page) {
    case 'home':
    case '':
        include 'pages/home.php';
        break;
    case 'login':
        if (!\$isLoggedIn) {
            include 'pages/login.php';
        } else {
            header('Location: /dashboard');
            exit;
        }
        break;
    case 'dashboard':
        if (\$isLoggedIn) {
            include 'pages/dashboard.php';
        } else {
            header('Location: /login');
            exit;
        }
        break;
    case 'admin':
        if (\$isLoggedIn && \$user['role'] === 'admin') {
            include 'pages/admin.php';
        } else {
            header('Location: /login');
            exit;
        }
        break;
    default:
        http_response_code(404);
        include 'pages/404.php';
        break;
}

// Include footer
include 'includes/footer.php';
?>";
    
    file_put_contents('index.php', $index);
    
    // Create directories
    @mkdir('includes', 0755, true);
    @mkdir('pages', 0755, true);
    @mkdir('assets/css', 0755, true);
    @mkdir('assets/js', 0755, true);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Website Installation</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header h1 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .progress {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            position: relative;
        }
        
        .progress::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            height: 2px;
            background: #e0e0e0;
            z-index: 1;
        }
        
        .progress::after {
            content: '';
            position: absolute;
            top: 20px;
            left: 0;
            height: 2px;
            background: #667eea;
            z-index: 2;
            width: <?php echo (($step - 1) / 3) * 100; ?>%;
            transition: width 0.3s ease;
        }
        
        .progress-step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e0e0e0;
            color: #999;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            position: relative;
            z-index: 3;
        }
        
        .progress-step.active {
            background: #667eea;
            color: white;
        }
        
        .progress-step.completed {
            background: #4caf50;
            color: white;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 500;
        }
        
        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="number"],
        input[type="url"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        
        input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            background: #667eea;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            transition: background 0.3s ease;
        }
        
        .btn:hover {
            background: #5a6fd8;
        }
        
        .btn-success {
            background: #4caf50;
        }
        
        .btn-success:hover {
            background: #45a049;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .alert-error {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }
        
        .alert-success {
            background: #e8f5e8;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }
        
        .step-title {
            font-size: 24px;
            margin-bottom: 10px;
            color: #333;
        }
        
        .step-description {
            color: #666;
            margin-bottom: 30px;
        }
        
        .success-icon {
            font-size: 64px;
            color: #4caf50;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .text-center {
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Website Installation</h1>
            <p>Easy setup in just a few steps</p>
        </div>
        
        <div class="progress">
            <div class="progress-step <?php echo $step >= 1 ? 'active' : ''; ?>">1</div>
            <div class="progress-step <?php echo $step >= 2 ? ($step > 2 ? 'completed' : 'active') : ''; ?>">2</div>
            <div class="progress-step <?php echo $step >= 3 ? ($step > 3 ? 'completed' : 'active') : ''; ?>">3</div>
            <div class="progress-step <?php echo $step >= 4 ? 'active' : ''; ?>">✓</div>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if ($step == 1): ?>
            <h2 class="step-title">Database Configuration</h2>
            <p class="step-description">Enter your database connection details</p>
            
            <form method="POST">
                <div class="form-group">
                    <label for="db_host">Database Host</label>
                    <input type="text" id="db_host" name="db_host" value="localhost" required>
                </div>
                
                <div class="form-group">
                    <label for="db_name">Database Name</label>
                    <input type="text" id="db_name" name="db_name" required>
                </div>
                
                <div class="form-group">
                    <label for="db_user">Database Username</label>
                    <input type="text" id="db_user" name="db_user" required>
                </div>
                
                <div class="form-group">
                    <label for="db_pass">Database Password</label>
                    <input type="password" id="db_pass" name="db_pass">
                </div>
                
                <div class="form-group">
                    <label for="db_port">Database Port</label>
                    <input type="number" id="db_port" name="db_port" value="3306">
                </div>
                
                <button type="submit" class="btn">Test Connection & Continue</button>
            </form>
            
        <?php elseif ($step == 2): ?>
            <h2 class="step-title">Site Configuration</h2>
            <p class="step-description">Configure your website settings and admin account</p>
            
            <form method="POST">
                <div class="form-group">
                    <label for="site_name">Site Name</label>
                    <input type="text" id="site_name" name="site_name" required>
                </div>
                
                <div class="form-group">
                    <label for="site_url">Site URL</label>
                    <input type="url" id="site_url" name="site_url" value="<?php echo 'http://' . $_SERVER['HTTP_HOST']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="admin_email">Admin Email</label>
                    <input type="email" id="admin_email" name="admin_email" required>
                </div>
                
                <div class="form-group">
                    <label for="admin_password">Admin Password</label>
                    <input type="password" id="admin_password" name="admin_password" required minlength="6">
                </div>
                
                <div class="form-group">
                    <label for="vendor_email">Vendor Email</label>
                    <input type="email" id="vendor_email" name="vendor_email" required>
                </div>
                
                <button type="submit" class="btn">Continue</button>
            </form>
            
        <?php elseif ($step == 3): ?>
            <h2 class="step-title">Ready to Install</h2>
            <p class="step-description">Review your settings and complete the installation</p>
            
            <div style="background: #f5f5f5; padding: 20px; border-radius: 5px; margin-bottom: 20px;">
                <strong>Installation Summary:</strong><br>
                Site Name: <?php echo htmlspecialchars($_SESSION['install_data']['site_name']); ?><br>
                Admin Email: <?php echo htmlspecialchars($_SESSION['install_data']['admin_email']); ?><br>
                Vendor Email: <?php echo htmlspecialchars($_SESSION['install_data']['vendor_email']); ?><br>
                Database: <?php echo htmlspecialchars($_SESSION['install_data']['db_name']); ?>
            </div>
            
            <form method="POST">
                <input type="hidden" name="confirm_install" value="1">
                <button type="submit" class="btn btn-success">Install Website</button>
            </form>
            
        <?php elseif ($step == 4): ?>
            <div class="text-center">
                <div class="success-icon">🎉</div>
                <h2 class="step-title">Installation Complete!</h2>
                <p class="step-description">Your website has been successfully installed.</p>
                
                <div style="background: #e8f5e8; padding: 20px; border-radius: 5px; margin: 20px 0; text-align: left;">
                    <strong>Next Steps:</strong><br>
                    • The installation file will be automatically disabled<br>
                    • You can now access your website<br>
                    • Login with your admin credentials<br>
                    • Start configuring your website
                </div>
                
                <a href="index.php" class="btn" style="display: inline-block; text-decoration: none;">Go to Website</a>
            </div>
            
            <?php
            // Disable install.php by renaming it
            if (file_exists('install.php')) {
                rename('install.php', 'install_disabled_' . date('Y_m_d_H_i_s') . '.php');
            }
            ?>
        <?php endif; ?>
    </div>
</body>
</html>