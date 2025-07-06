<?php
/**
 * Logistics Management System - API Entry Point
 * Routes requests to appropriate controllers
 */

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

// Set timezone
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'UTC');

// Enable error reporting in debug mode
if (($_ENV['APP_DEBUG'] ?? false) === 'true') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Set CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Get request information
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Remove /api prefix and split route
$route = preg_replace('#^/api/?#', '', $requestUri);
$segments = explode('/', trim($route, '/'));

// Get controller and action
$controllerName = $segments[0] ?? '';
$action = $segments[1] ?? 'index';
$id = $segments[2] ?? null;

// Rate limiting
$clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (!rateLimitCheck($clientIp)) {
    http_response_code(429);
    echo json_encode(['error' => 'Too many requests. Please try again later.']);
    exit;
}

try {
    // Route to appropriate controller
    $controller = null;
    
    switch ($controllerName) {
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
            
        case 'reports':
            $controller = new \App\Controllers\ReportsController();
            break;
            
        case 'cities':
            $controller = new \App\Controllers\CityController();
            break;
            
        case 'zones':
            $controller = new \App\Controllers\ZoneController();
            break;
            
        case 'invoices':
            $controller = new \App\Controllers\InvoiceController();
            break;
            
        case 'upload':
            $controller = new \App\Controllers\UploadController();
            break;
            
        case 'scan':
            $controller = new \App\Controllers\ScanController();
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
            exit;
    }

    // Build method name based on HTTP method and action
    $methodName = strtolower($requestMethod);
    
    // Handle different HTTP methods
    switch ($requestMethod) {
        case 'GET':
            if ($id) {
                $methodName = 'show'; // GET /api/orders/123
            } elseif ($action !== 'index') {
                $methodName = 'get' . ucfirst($action); // GET /api/orders/pending
            } else {
                $methodName = 'index'; // GET /api/orders
            }
            break;
            
        case 'POST':
            if ($action === 'index') {
                $methodName = 'store'; // POST /api/orders
            } else {
                $methodName = 'post' . ucfirst($action); // POST /api/orders/scan
            }
            break;
            
        case 'PUT':
            if ($id) {
                $methodName = 'update'; // PUT /api/orders/123
            } else {
                $methodName = 'put' . ucfirst($action); // PUT /api/orders/status
            }
            break;
            
        case 'DELETE':
            if ($id) {
                $methodName = 'destroy'; // DELETE /api/orders/123
            } else {
                $methodName = 'delete' . ucfirst($action); // DELETE /api/orders/bulk
            }
            break;
    }

    // Call the controller method
    if (method_exists($controller, $methodName)) {
        // Pass ID as parameter if available
        if ($id && in_array($methodName, ['show', 'update', 'destroy'])) {
            $controller->$methodName($id);
        } else {
            $controller->$methodName();
        }
    } else {
        http_response_code(404);
        echo json_encode(['error' => "Method {$methodName} not found in controller"]);
    }

} catch (Exception $e) {
    // Log the error
    error_log("API Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    
    // Return appropriate error response
    if ($e instanceof \App\Exceptions\AuthenticationException) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
    } elseif ($e instanceof \App\Exceptions\AuthorizationException) {
        http_response_code(403);
        echo json_encode(['error' => 'Insufficient permissions']);
    } elseif ($e instanceof \App\Exceptions\ValidationException) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    } elseif ($e instanceof \App\Exceptions\NotFoundException) {
        http_response_code(404);
        echo json_encode(['error' => 'Resource not found']);
    } else {
        http_response_code(500);
        $errorMessage = ($_ENV['APP_DEBUG'] ?? false) === 'true' 
            ? $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine()
            : 'Internal server error';
        echo json_encode(['error' => $errorMessage]);
    }
}

/**
 * Simple rate limiting function
 */
function rateLimitCheck(string $identifier, int $maxRequests = 60, int $timeWindow = 3600): bool
{
    $cacheFile = sys_get_temp_dir() . '/rate_limit_' . md5($identifier);
    $requests = [];
    
    if (file_exists($cacheFile)) {
        $content = file_get_contents($cacheFile);
        $requests = $content ? json_decode($content, true) : [];
    }
    
    $now = time();
    
    // Remove old requests outside the time window
    $requests = array_filter($requests, function($timestamp) use ($now, $timeWindow) {
        return ($now - $timestamp) < $timeWindow;
    });
    
    // Check if limit exceeded
    if (count($requests) >= $maxRequests) {
        return false;
    }
    
    // Add current request
    $requests[] = $now;
    file_put_contents($cacheFile, json_encode($requests));
    
    return true;
}

/**
 * Log API request for analytics
 */
function logApiRequest(): void
{
    $logData = [
        'timestamp' => date('Y-m-d H:i:s'),
        'method' => $_SERVER['REQUEST_METHOD'],
        'uri' => $_SERVER['REQUEST_URI'],
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ];
    
    $logFile = __DIR__ . '/../logs/api.log';
    file_put_contents($logFile, json_encode($logData) . PHP_EOL, FILE_APPEND | LOCK_EX);
}

// Log the request (optional - comment out if not needed)
// logApiRequest();