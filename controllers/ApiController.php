<?php
class ApiController {
    private $db;
    private $auth;
    
    public function __construct($database, $auth) {
        $this->db = $database;
        $this->auth = $auth;
    }
    
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['api_action'] ?? '';
        
        header('Content-Type: application/json');
        
        try {
            switch ($action) {
                case 'scan_qr':
                    $this->handleQRScan();
                    break;
                    
                case 'update_status':
                    $this->handleStatusUpdate();
                    break;
                    
                case 'track_package':
                    $this->handlePackageTracking();
                    break;
                    
                case 'get_notifications':
                    $this->handleGetNotifications();
                    break;
                    
                case 'mark_notification_read':
                    $this->handleMarkNotificationRead();
                    break;
                    
                case 'get_delivery_slip':
                    $this->handleGetDeliverySlip();
                    break;
                    
                case 'update_slip_status':
                    $this->handleUpdateSlipStatus();
                    break;
                    
                case 'get_dashboard_data':
                    $this->handleGetDashboardData();
                    break;
                    
                case 'search':
                    $this->handleSearch();
                    break;
                    
                case 'get_cities':
                    $this->handleGetCities();
                    break;
                    
                case 'calculate_fee':
                    $this->handleCalculateFee();
                    break;
                    
                case 'upload_file':
                    $this->handleFileUpload();
                    break;
                    
                case 'generate_report':
                    $this->handleGenerateReport();
                    break;
                    
                default:
                    $this->sendError('Invalid API action', 400);
            }
        } catch (Exception $e) {
            error_log("API Error: " . $e->getMessage());
            $this->sendError('Internal server error', 500);
        }
    }
    
    private function handleQRScan() {
        if (!$this->auth->hasRole(ROLE_DELIVERY_AGENT)) {
            $this->sendError('Unauthorized', 403);
            return;
        }
        
        $qr_code = $_POST['qr_code'] ?? '';
        $new_status = $_POST['new_status'] ?? '';
        $location = $_POST['location'] ?? '';
        $notes = $_POST['notes'] ?? '';
        
        if (empty($qr_code) || empty($new_status)) {
            $this->sendError('QR code and status are required', 400);
            return;
        }
        
        $user = $this->auth->getCurrentUser();
        $packageModel = new DeliveryPackage($this->db);
        
        if ($packageModel->updateStatusByQR($qr_code, $new_status, $user['id'], $location, $notes)) {
            $this->sendSuccess(['message' => 'Package status updated successfully']);
        } else {
            $this->sendError('Failed to update package status', 400);
        }
    }
    
    private function handleStatusUpdate() {
        $user = $this->auth->getCurrentUser();
        if (!$user) {
            $this->sendError('Unauthorized', 401);
            return;
        }
        
        $package_id = $_POST['package_id'] ?? '';
        $new_status = $_POST['new_status'] ?? '';
        $location = $_POST['location'] ?? '';
        $notes = $_POST['notes'] ?? '';
        
        if (empty($package_id) || empty($new_status)) {
            $this->sendError('Package ID and status are required', 400);
            return;
        }
        
        $packageModel = new DeliveryPackage($this->db);
        
        if ($packageModel->updateStatus($package_id, $new_status, $user['id'], $location, $notes)) {
            $this->sendSuccess(['message' => 'Status updated successfully']);
        } else {
            $this->sendError('Failed to update status', 400);
        }
    }
    
    private function handlePackageTracking() {
        $tracking_number = $_GET['tracking_number'] ?? '';
        
        if (empty($tracking_number)) {
            $this->sendError('Tracking number is required', 400);
            return;
        }
        
        $packageModel = new DeliveryPackage($this->db);
        $package = $packageModel->getByTrackingNumber($tracking_number);
        
        if ($package) {
            $tracking_history = $packageModel->getTrackingHistory($package['id']);
            $this->sendSuccess([
                'package' => $package,
                'tracking_history' => $tracking_history
            ]);
        } else {
            $this->sendError('Package not found', 404);
        }
    }
    
    private function handleGetNotifications() {
        $user = $this->auth->getCurrentUser();
        if (!$user) {
            $this->sendError('Unauthorized', 401);
            return;
        }
        
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM notifications 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT 50
            ");
            $stmt->execute([$user['id']]);
            $notifications = $stmt->fetchAll();
            
            $this->sendSuccess(['notifications' => $notifications]);
        } catch (Exception $e) {
            $this->sendError('Failed to get notifications', 500);
        }
    }
    
    private function handleMarkNotificationRead() {
        $user = $this->auth->getCurrentUser();
        if (!$user) {
            $this->sendError('Unauthorized', 401);
            return;
        }
        
        $notification_id = $_POST['notification_id'] ?? '';
        
        if (empty($notification_id)) {
            $this->sendError('Notification ID is required', 400);
            return;
        }
        
        try {
            $stmt = $this->db->prepare("
                UPDATE notifications 
                SET is_read = 1 
                WHERE id = ? AND user_id = ?
            ");
            $result = $stmt->execute([$notification_id, $user['id']]);
            
            if ($result) {
                $this->sendSuccess(['message' => 'Notification marked as read']);
            } else {
                $this->sendError('Failed to mark notification as read', 400);
            }
        } catch (Exception $e) {
            $this->sendError('Database error', 500);
        }
    }
    
    private function handleGetDeliverySlip() {
        if (!$this->auth->hasAnyRole([ROLE_ADMIN, ROLE_DELIVERY_AGENT])) {
            $this->sendError('Unauthorized', 403);
            return;
        }
        
        $slip_id = $_GET['slip_id'] ?? '';
        
        if (empty($slip_id)) {
            $this->sendError('Slip ID is required', 400);
            return;
        }
        
        $slipModel = new DeliverySlip($this->db);
        
        $slip = $slipModel->getById($slip_id);
        
        if ($slip) {
            $packages = $slipModel->getSlipPackages($slip_id);
            $this->sendSuccess([
                'slip' => $slip,
                'packages' => $packages
            ]);
        } else {
            $this->sendError('Delivery slip not found', 404);
        }
    }
    
    private function handleUpdateSlipStatus() {
        if (!$this->auth->hasRole(ROLE_DELIVERY_AGENT)) {
            $this->sendError('Unauthorized', 403);
            return;
        }
        
        $slip_id = $_POST['slip_id'] ?? '';
        $new_status = $_POST['new_status'] ?? '';
        
        if (empty($slip_id) || empty($new_status)) {
            $this->sendError('Slip ID and status are required', 400);
            return;
        }
        
        $slipModel = new DeliverySlip($this->db);
        
        if ($slipModel->updateStatus($slip_id, $new_status)) {
            $this->sendSuccess(['message' => 'Slip status updated successfully']);
        } else {
            $this->sendError('Failed to update slip status', 400);
        }
    }
    
    private function handleGetDashboardData() {
        $user = $this->auth->getCurrentUser();
        if (!$user) {
            $this->sendError('Unauthorized', 401);
            return;
        }
        
        $data = getDashboardData($this->db, $user);
        $this->sendSuccess($data);
    }
    
    private function handleSearch() {
        $user = $this->auth->getCurrentUser();
        if (!$user) {
            $this->sendError('Unauthorized', 401);
            return;
        }
        
        $query = $_GET['q'] ?? '';
        $type = $_GET['type'] ?? 'packages';
        
        if (empty($query)) {
            $this->sendError('Search query is required', 400);
            return;
        }
        
        $results = [];
        
        switch ($type) {
            case 'packages':
                $packageModel = new DeliveryPackage($this->db);
                $agent_id = $user['role'] === ROLE_DELIVERY_AGENT ? $user['id'] : null;
                $results = $packageModel->searchPackages($query, $agent_id);
                break;
                
            case 'requests':
                $pickupModel = new PickupRequest($this->db);
                $vendor_id = $user['role'] === ROLE_VENDOR ? $user['id'] : null;
                $results = $pickupModel->searchRequests($query, $vendor_id);
                break;
                
            case 'users':
                if ($user['role'] === ROLE_ADMIN) {
                    $userModel = new User($this->db);
                    $results = $userModel->searchUsers($query);
                }
                break;
        }
        
        $this->sendSuccess(['results' => $results]);
    }
    
    private function handleGetCities() {
        $cityModel = new City($this->db);
        $cities = $cityModel->getAll();
        $this->sendSuccess(['cities' => $cities]);
    }
    
    private function handleCalculateFee() {
        $weight = $_GET['weight'] ?? 0;
        $pickup_city_id = $_GET['pickup_city_id'] ?? 0;
        $delivery_city_id = $_GET['delivery_city_id'] ?? 0;
        
        if ($weight <= 0 || !$pickup_city_id || !$delivery_city_id) {
            $this->sendError('Weight and city IDs are required', 400);
            return;
        }
        
        $fee = calculateDeliveryFee($weight, $pickup_city_id, $delivery_city_id, $this->db);
        $this->sendSuccess(['delivery_fee' => $fee]);
    }
    
    private function handleFileUpload() {
        $user = $this->auth->getCurrentUser();
        if (!$user) {
            $this->sendError('Unauthorized', 401);
            return;
        }
        
        if (!isset($_FILES['file'])) {
            $this->sendError('No file uploaded', 400);
            return;
        }
        
        $result = uploadFile($_FILES['file']);
        
        if ($result['success']) {
            $this->sendSuccess($result);
        } else {
            $this->sendError($result['error'], 400);
        }
    }
    
    private function handleGenerateReport() {
        if (!$this->auth->hasRole(ROLE_ADMIN)) {
            $this->sendError('Unauthorized', 403);
            return;
        }
        
        $type = $_GET['type'] ?? 'summary';
        $format = $_GET['format'] ?? 'json';
        
        $reportData = getReportData($this->db, $_GET);
        
        if ($format === 'pdf') {
            // Generate PDF report
            $html = $this->generateReportHTML($reportData, $type);
            $pdf = generatePDF($html, "report_$type.pdf");
            
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="report_' . $type . '.pdf"');
            echo $pdf;
            exit;
        } else {
            $this->sendSuccess($reportData);
        }
    }
    
    private function generateReportHTML($data, $type) {
        // Generate HTML for PDF reports
        $html = '<h1>' . ucfirst($type) . ' Report</h1>';
        $html .= '<p>Generated on: ' . date('Y-m-d H:i:s') . '</p>';
        
        // Add report content based on type
        switch ($type) {
            case 'summary':
                if (isset($data['summary'])) {
                    $summary = $data['summary'];
                    $html .= '<table border="1" cellpadding="5">';
                    $html .= '<tr><th>Metric</th><th>Value</th></tr>';
                    $html .= '<tr><td>Total Requests</td><td>' . ($summary['total_requests'] ?? 0) . '</td></tr>';
                    $html .= '<tr><td>Delivered Count</td><td>' . ($summary['delivered_count'] ?? 0) . '</td></tr>';
                    $html .= '<tr><td>Total Revenue</td><td>' . formatCurrency($summary['total_revenue'] ?? 0) . '</td></tr>';
                    $html .= '<tr><td>Average Delivery Fee</td><td>' . formatCurrency($summary['avg_delivery_fee'] ?? 0) . '</td></tr>';
                    $html .= '</table>';
                }
                break;
        }
        
        return $html;
    }
    
    private function sendSuccess($data, $code = 200) {
        http_response_code($code);
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }
    
    private function sendError($message, $code = 400) {
        http_response_code($code);
        echo json_encode(['success' => false, 'error' => $message]);
        exit;
    }
}
?>