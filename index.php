<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'models/User.php';
require_once 'models/PickupRequest.php';
require_once 'models/DeliveryPackage.php';
require_once 'models/DeliverySlip.php';
require_once 'models/City.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Initialize authentication
$auth = new Auth($db);

// Check if user is logged in for protected routes
$is_authenticated = $auth->isLoggedIn();
$current_user = $is_authenticated ? $auth->getCurrentUser() : null;

// Get the requested action
$action = $_GET['action'] ?? 'dashboard';
$ajax = $_GET['ajax'] ?? false;

// CSRF protection for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST[CSRF_TOKEN_NAME]) || !hash_equals($_SESSION[CSRF_TOKEN_NAME], $_POST[CSRF_TOKEN_NAME])) {
        if ($ajax) {
            http_response_code(403);
            echo json_encode(['error' => 'CSRF token mismatch']);
            exit;
        } else {
            die('CSRF token mismatch');
        }
    }
}

// Generate CSRF token if not exists
if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
    $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
}

// Public routes (no authentication required)
$public_routes = ['login', 'register', 'track', 'api'];

// Check authentication for protected routes
if (!in_array($action, $public_routes) && !$is_authenticated) {
    if ($ajax) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        exit;
    } else {
        header('Location: ?action=login');
        exit;
    }
}

// Handle AJAX requests
if ($ajax) {
    require_once 'controllers/ApiController.php';
    $apiController = new ApiController($db, $auth);
    $apiController->handleRequest();
    exit;
}

// Route handling
switch ($action) {
    case 'login':
        if ($is_authenticated) {
            header('Location: ?action=dashboard');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            
            if ($auth->login($username, $password)) {
                $user = $auth->getCurrentUser();
                $_SESSION['success_message'] = 'Welcome back, ' . $user['first_name'] . '!';
                header('Location: ?action=dashboard');
                exit;
            } else {
                $error_message = 'Invalid username or password';
            }
        }
        
        include 'views/auth/login.php';
        break;
        
    case 'logout':
        $auth->logout();
        header('Location: ?action=login');
        exit;
        
    case 'register':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userData = [
                'username' => $_POST['username'] ?? '',
                'email' => $_POST['email'] ?? '',
                'password' => $_POST['password'] ?? '',
                'role' => $_POST['role'] ?? ROLE_VENDOR,
                'first_name' => $_POST['first_name'] ?? '',
                'last_name' => $_POST['last_name'] ?? '',
                'phone' => $_POST['phone'] ?? '',
                'address' => $_POST['address'] ?? '',
                'city_id' => $_POST['city_id'] ?? null
            ];
            
            $userModel = new User($db);
            if ($userModel->create($userData)) {
                $_SESSION['success_message'] = 'Account created successfully. Please login.';
                header('Location: ?action=login');
                exit;
            } else {
                $error_message = 'Failed to create account';
            }
        }
        
        $cityModel = new City($db);
        $cities = $cityModel->getAll();
        include 'views/auth/register.php';
        break;
        
    case 'dashboard':
        $dashboardData = getDashboardData($db, $current_user);
        include 'views/dashboard.php';
        break;
        
    case 'pickup-requests':
        $pickupModel = new PickupRequest($db);
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $requestData = [
                'vendor_id' => $current_user['id'],
                'pickup_address' => $_POST['pickup_address'] ?? '',
                'delivery_address' => $_POST['delivery_address'] ?? '',
                'pickup_city_id' => $_POST['pickup_city_id'] ?? '',
                'delivery_city_id' => $_POST['delivery_city_id'] ?? '',
                'package_type' => $_POST['package_type'] ?? PACKAGE_TYPE_PARCEL,
                'package_weight' => $_POST['package_weight'] ?? 0,
                'package_dimensions' => $_POST['package_dimensions'] ?? '',
                'package_description' => $_POST['package_description'] ?? '',
                'recipient_name' => $_POST['recipient_name'] ?? '',
                'recipient_phone' => $_POST['recipient_phone'] ?? '',
                'cod_amount' => $_POST['cod_amount'] ?? 0,
                'special_instructions' => $_POST['special_instructions'] ?? ''
            ];
            
            if ($pickupModel->create($requestData)) {
                $_SESSION['success_message'] = 'Pickup request created successfully';
                header('Location: ?action=pickup-requests');
                exit;
            } else {
                $error_message = 'Failed to create pickup request';
            }
        }
        
        $requests = $pickupModel->getByVendor($current_user['id']);
        $cityModel = new City($db);
        $cities = $cityModel->getAll();
        include 'views/pickup_requests.php';
        break;
        
    case 'delivery-slips':
        if ($current_user['role'] !== ROLE_ADMIN && $current_user['role'] !== ROLE_DELIVERY_AGENT) {
            header('Location: ?action=dashboard');
            exit;
        }
        
        $slipModel = new DeliverySlip($db);
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $current_user['role'] === ROLE_ADMIN) {
            $slipData = [
                'delivery_agent_id' => $_POST['delivery_agent_id'] ?? '',
                'created_by' => $current_user['id'],
                'slip_date' => $_POST['slip_date'] ?? date('Y-m-d'),
                'notes' => $_POST['notes'] ?? '',
                'package_ids' => $_POST['package_ids'] ?? []
            ];
            
            if ($slipModel->create($slipData)) {
                $_SESSION['success_message'] = 'Delivery slip created successfully';
                header('Location: ?action=delivery-slips');
                exit;
            } else {
                $error_message = 'Failed to create delivery slip';
            }
        }
        
        if ($current_user['role'] === ROLE_ADMIN) {
            $slips = $slipModel->getAll();
            $userModel = new User($db);
            $delivery_agents = $userModel->getByRole(ROLE_DELIVERY_AGENT);
            $packageModel = new DeliveryPackage($db);
            $available_packages = $packageModel->getAvailableForSlip();
        } else {
            $slips = $slipModel->getByAgent($current_user['id']);
        }
        
        include 'views/delivery_slips.php';
        break;
        
    case 'package-tracking':
        $packageModel = new DeliveryPackage($db);
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $tracking_number = $_POST['tracking_number'] ?? '';
            $package = $packageModel->getByTrackingNumber($tracking_number);
            
            if ($package) {
                $tracking_history = $packageModel->getTrackingHistory($package['id']);
            } else {
                $error_message = 'Package not found';
            }
        }
        
        include 'views/package_tracking.php';
        break;
        
    case 'scan-qr':
        if ($current_user['role'] !== ROLE_DELIVERY_AGENT) {
            header('Location: ?action=dashboard');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $qr_code = $_POST['qr_code'] ?? '';
            $new_status = $_POST['new_status'] ?? '';
            $location = $_POST['location'] ?? '';
            $notes = $_POST['notes'] ?? '';
            
            $packageModel = new DeliveryPackage($db);
            if ($packageModel->updateStatusByQR($qr_code, $new_status, $current_user['id'], $location, $notes)) {
                $_SESSION['success_message'] = 'Package status updated successfully';
            } else {
                $error_message = 'Failed to update package status';
            }
        }
        
        include 'views/qr_scanner.php';
        break;
        
    case 'reports':
        if ($current_user['role'] !== ROLE_ADMIN) {
            header('Location: ?action=dashboard');
            exit;
        }
        
        $reportData = getReportData($db, $_GET);
        include 'views/reports.php';
        break;
        
    case 'users':
        if ($current_user['role'] !== ROLE_ADMIN) {
            header('Location: ?action=dashboard');
            exit;
        }
        
        $userModel = new User($db);
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userData = [
                'username' => $_POST['username'] ?? '',
                'email' => $_POST['email'] ?? '',
                'password' => $_POST['password'] ?? '',
                'role' => $_POST['role'] ?? ROLE_VENDOR,
                'first_name' => $_POST['first_name'] ?? '',
                'last_name' => $_POST['last_name'] ?? '',
                'phone' => $_POST['phone'] ?? '',
                'address' => $_POST['address'] ?? '',
                'city_id' => $_POST['city_id'] ?? null
            ];
            
            if ($userModel->create($userData)) {
                $_SESSION['success_message'] = 'User created successfully';
                header('Location: ?action=users');
                exit;
            } else {
                $error_message = 'Failed to create user';
            }
        }
        
        $users = $userModel->getAll();
        $cityModel = new City($db);
        $cities = $cityModel->getAll();
        include 'views/users.php';
        break;
        
    case 'cities':
        if ($current_user['role'] !== ROLE_ADMIN) {
            header('Location: ?action=dashboard');
            exit;
        }
        
        $cityModel = new City($db);
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $cityData = [
                'name' => $_POST['name'] ?? '',
                'code' => $_POST['code'] ?? '',
                'delivery_fee' => $_POST['delivery_fee'] ?? 0
            ];
            
            if ($cityModel->create($cityData)) {
                $_SESSION['success_message'] = 'City created successfully';
                header('Location: ?action=cities');
                exit;
            } else {
                $error_message = 'Failed to create city';
            }
        }
        
        $cities = $cityModel->getAll();
        include 'views/cities.php';
        break;
        
    case 'profile':
        $userModel = new User($db);
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userData = [
                'id' => $current_user['id'],
                'first_name' => $_POST['first_name'] ?? '',
                'last_name' => $_POST['last_name'] ?? '',
                'phone' => $_POST['phone'] ?? '',
                'address' => $_POST['address'] ?? '',
                'city_id' => $_POST['city_id'] ?? null
            ];
            
            if (!empty($_POST['new_password'])) {
                $userData['password'] = $_POST['new_password'];
            }
            
            if ($userModel->update($userData)) {
                $_SESSION['success_message'] = 'Profile updated successfully';
                // Refresh current user data
                $auth->refreshCurrentUser();
                $current_user = $auth->getCurrentUser();
            } else {
                $error_message = 'Failed to update profile';
            }
        }
        
        $cityModel = new City($db);
        $cities = $cityModel->getAll();
        include 'views/profile.php';
        break;
        
    case 'track':
        // Public tracking page
        $tracking_number = $_GET['tracking'] ?? '';
        $package = null;
        $tracking_history = [];
        
        if ($tracking_number) {
            $packageModel = new DeliveryPackage($db);
            $package = $packageModel->getByTrackingNumber($tracking_number);
            
            if ($package) {
                $tracking_history = $packageModel->getTrackingHistory($package['id']);
            }
        }
        
        include 'views/public_tracking.php';
        break;
        
    default:
        header('Location: ?action=dashboard');
        exit;
}

// Helper functions
function getDashboardData($db, $user) {
    $data = [];
    
    switch ($user['role']) {
        case ROLE_ADMIN:
            // Admin dashboard data
            $stmt = $db->prepare("
                SELECT 
                    (SELECT COUNT(*) FROM pickup_requests WHERE DATE(created_at) = CURDATE()) as today_pickups,
                    (SELECT COUNT(*) FROM pickup_requests WHERE status = 'en_attente') as pending_pickups,
                    (SELECT COUNT(*) FROM delivery_packages WHERE current_status = 'in_delivery_slip') as in_transit,
                    (SELECT COUNT(*) FROM delivery_packages WHERE current_status = 'delivered' AND DATE(actual_delivery) = CURDATE()) as today_delivered,
                    (SELECT COUNT(*) FROM users WHERE role = 'vendor' AND is_active = 1) as active_vendors,
                    (SELECT COUNT(*) FROM users WHERE role = 'delivery_agent' AND is_active = 1) as active_agents
            ");
            $stmt->execute();
            $data['stats'] = $stmt->fetch();
            
            // Recent activity
            $stmt = $db->prepare("
                SELECT pr.*, u.first_name, u.last_name, c1.name as pickup_city, c2.name as delivery_city
                FROM pickup_requests pr
                JOIN users u ON pr.vendor_id = u.id
                JOIN cities c1 ON pr.pickup_city_id = c1.id
                JOIN cities c2 ON pr.delivery_city_id = c2.id
                ORDER BY pr.created_at DESC
                LIMIT 10
            ");
            $stmt->execute();
            $data['recent_requests'] = $stmt->fetchAll();
            break;
            
        case ROLE_VENDOR:
            // Vendor dashboard data
            $stmt = $db->prepare("
                SELECT 
                    (SELECT COUNT(*) FROM pickup_requests WHERE vendor_id = ? AND DATE(created_at) = CURDATE()) as today_requests,
                    (SELECT COUNT(*) FROM pickup_requests WHERE vendor_id = ? AND status = 'en_attente') as pending_requests,
                    (SELECT COUNT(*) FROM pickup_requests pr JOIN delivery_packages dp ON pr.id = dp.pickup_request_id WHERE pr.vendor_id = ? AND dp.current_status = 'in_delivery_slip') as in_transit,
                    (SELECT COUNT(*) FROM pickup_requests pr JOIN delivery_packages dp ON pr.id = dp.pickup_request_id WHERE pr.vendor_id = ? AND dp.current_status = 'delivered') as total_delivered
            ");
            $stmt->execute([$user['id'], $user['id'], $user['id'], $user['id']]);
            $data['stats'] = $stmt->fetch();
            
            // Recent requests
            $stmt = $db->prepare("
                SELECT pr.*, c1.name as pickup_city, c2.name as delivery_city
                FROM pickup_requests pr
                JOIN cities c1 ON pr.pickup_city_id = c1.id
                JOIN cities c2 ON pr.delivery_city_id = c2.id
                WHERE pr.vendor_id = ?
                ORDER BY pr.created_at DESC
                LIMIT 10
            ");
            $stmt->execute([$user['id']]);
            $data['recent_requests'] = $stmt->fetchAll();
            break;
            
        case ROLE_DELIVERY_AGENT:
            // Delivery agent dashboard data
            $stmt = $db->prepare("
                SELECT 
                    (SELECT COUNT(*) FROM delivery_slips WHERE delivery_agent_id = ? AND status = 'pending') as pending_slips,
                    (SELECT COUNT(*) FROM delivery_slips WHERE delivery_agent_id = ? AND status = 'in_progress') as active_slips,
                    (SELECT COUNT(*) FROM delivery_slips ds JOIN delivery_slip_packages dsp ON ds.id = dsp.delivery_slip_id JOIN delivery_packages dp ON dsp.delivery_package_id = dp.id WHERE ds.delivery_agent_id = ? AND dp.current_status = 'delivered' AND DATE(dp.actual_delivery) = CURDATE()) as today_delivered,
                    (SELECT COUNT(*) FROM delivery_slips WHERE delivery_agent_id = ? AND status = 'completed') as total_completed
            ");
            $stmt->execute([$user['id'], $user['id'], $user['id'], $user['id']]);
            $data['stats'] = $stmt->fetch();
            
            // Active delivery slips
            $stmt = $db->prepare("
                SELECT ds.*, COUNT(dsp.delivery_package_id) as package_count
                FROM delivery_slips ds
                LEFT JOIN delivery_slip_packages dsp ON ds.id = dsp.delivery_slip_id
                WHERE ds.delivery_agent_id = ? AND ds.status IN ('pending', 'in_progress')
                GROUP BY ds.id
                ORDER BY ds.created_at DESC
            ");
            $stmt->execute([$user['id']]);
            $data['active_slips'] = $stmt->fetchAll();
            break;
    }
    
    return $data;
}

function getReportData($db, $filters) {
    // Implementation for various reports
    $data = [];
    
    $date_from = $filters['date_from'] ?? date('Y-m-01');
    $date_to = $filters['date_to'] ?? date('Y-m-d');
    $report_type = $filters['type'] ?? 'summary';
    
    switch ($report_type) {
        case 'summary':
            $stmt = $db->prepare("
                SELECT 
                    COUNT(DISTINCT pr.id) as total_requests,
                    COUNT(DISTINCT CASE WHEN dp.current_status = 'delivered' THEN dp.id END) as delivered_count,
                    SUM(pr.total_amount) as total_revenue,
                    AVG(pr.delivery_fee) as avg_delivery_fee
                FROM pickup_requests pr
                LEFT JOIN delivery_packages dp ON pr.id = dp.pickup_request_id
                WHERE pr.created_at BETWEEN ? AND ?
            ");
            $stmt->execute([$date_from, $date_to]);
            $data['summary'] = $stmt->fetch();
            break;
            
        case 'performance':
            $stmt = $db->prepare("
                SELECT 
                    u.first_name, u.last_name,
                    COUNT(DISTINCT ds.id) as total_slips,
                    COUNT(DISTINCT CASE WHEN dp.current_status = 'delivered' THEN dp.id END) as delivered_packages,
                    AVG(TIMESTAMPDIFF(HOUR, ds.created_at, dp.actual_delivery)) as avg_delivery_time
                FROM users u
                LEFT JOIN delivery_slips ds ON u.id = ds.delivery_agent_id
                LEFT JOIN delivery_slip_packages dsp ON ds.id = dsp.delivery_slip_id
                LEFT JOIN delivery_packages dp ON dsp.delivery_package_id = dp.id
                WHERE u.role = 'delivery_agent' AND ds.created_at BETWEEN ? AND ?
                GROUP BY u.id
                ORDER BY delivered_packages DESC
            ");
            $stmt->execute([$date_from, $date_to]);
            $data['performance'] = $stmt->fetchAll();
            break;
    }
    
    return $data;
}
?>