<?php
class PickupRequest {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    public function create($requestData) {
        try {
            // Calculate delivery fee
            $delivery_fee = calculateDeliveryFee(
                $requestData['package_weight'],
                $requestData['pickup_city_id'],
                $requestData['delivery_city_id'],
                $this->db
            );
            
            // Calculate COD fee if applicable
            $cod_fee = $requestData['cod_amount'] > 0 ? 
                ($requestData['cod_amount'] * getSystemSetting('cod_fee_percentage', 2.5, $this->db) / 100) : 0;
            
            $total_amount = $delivery_fee + $cod_fee;
            
            // Generate QR code data
            $qr_data = json_encode([
                'type' => 'pickup_request',
                'id' => uniqid(),
                'timestamp' => time()
            ]);
            
            $qr_filename = generateQRCode($qr_data);
            
            $stmt = $this->db->prepare("
                INSERT INTO pickup_requests (
                    vendor_id, pickup_address, delivery_address, pickup_city_id, delivery_city_id,
                    package_type, package_weight, package_dimensions, package_description,
                    recipient_name, recipient_phone, cod_amount, delivery_fee, total_amount,
                    special_instructions, qr_code, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $requestData['vendor_id'],
                $requestData['pickup_address'],
                $requestData['delivery_address'],
                $requestData['pickup_city_id'],
                $requestData['delivery_city_id'],
                $requestData['package_type'],
                $requestData['package_weight'],
                $requestData['package_dimensions'],
                $requestData['package_description'],
                $requestData['recipient_name'],
                $requestData['recipient_phone'],
                $requestData['cod_amount'],
                $delivery_fee,
                $total_amount,
                $requestData['special_instructions'],
                $qr_filename,
                STATUS_EN_ATTENTE
            ]);
            
            if ($result) {
                $pickup_id = $this->db->lastInsertId();
                
                // Update QR code with actual pickup request ID
                $qr_data_updated = json_encode([
                    'type' => 'pickup_request',
                    'id' => $pickup_id,
                    'tracking' => generateTrackingNumber(),
                    'timestamp' => time()
                ]);
                
                $qr_filename_updated = generateQRCode($qr_data_updated);
                
                $stmt = $this->db->prepare("UPDATE pickup_requests SET qr_code = ? WHERE id = ?");
                $stmt->execute([$qr_filename_updated, $pickup_id]);
                
                // Create delivery package entry
                $this->createDeliveryPackage($pickup_id);
                
                // Send notification to vendor
                sendNotification(
                    $requestData['vendor_id'],
                    'Pickup Request Created',
                    'Your pickup request has been created successfully.',
                    'success',
                    $this->db
                );
                
                return $pickup_id;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Create pickup request error: " . $e->getMessage());
            return false;
        }
    }
    
    private function createDeliveryPackage($pickup_request_id) {
        try {
            $tracking_number = generateTrackingNumber();
            
            $stmt = $this->db->prepare("
                INSERT INTO delivery_packages (pickup_request_id, tracking_number, current_status)
                VALUES (?, ?, ?)
            ");
            
            return $stmt->execute([$pickup_request_id, $tracking_number, STATUS_EN_ATTENTE]);
        } catch (Exception $e) {
            error_log("Create delivery package error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getById($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT pr.*, 
                       u.first_name as vendor_first_name, u.last_name as vendor_last_name,
                       c1.name as pickup_city_name, c2.name as delivery_city_name,
                       dp.tracking_number, dp.current_status as package_status
                FROM pickup_requests pr
                JOIN users u ON pr.vendor_id = u.id
                JOIN cities c1 ON pr.pickup_city_id = c1.id
                JOIN cities c2 ON pr.delivery_city_id = c2.id
                LEFT JOIN delivery_packages dp ON pr.id = dp.pickup_request_id
                WHERE pr.id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Get pickup request by ID error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getByVendor($vendor_id, $limit = null) {
        try {
            $limit_clause = $limit ? "LIMIT $limit" : "";
            
            $stmt = $this->db->prepare("
                SELECT pr.*, 
                       c1.name as pickup_city_name, c2.name as delivery_city_name,
                       dp.tracking_number, dp.current_status as package_status
                FROM pickup_requests pr
                JOIN cities c1 ON pr.pickup_city_id = c1.id
                JOIN cities c2 ON pr.delivery_city_id = c2.id
                LEFT JOIN delivery_packages dp ON pr.id = dp.pickup_request_id
                WHERE pr.vendor_id = ?
                ORDER BY pr.created_at DESC
                $limit_clause
            ");
            $stmt->execute([$vendor_id]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get pickup requests by vendor error: " . $e->getMessage());
            return [];
        }
    }
    
    public function getAll($filters = []) {
        try {
            $where_conditions = [];
            $params = [];
            
            if (isset($filters['status'])) {
                $where_conditions[] = "pr.status = ?";
                $params[] = $filters['status'];
            }
            
            if (isset($filters['vendor_id'])) {
                $where_conditions[] = "pr.vendor_id = ?";
                $params[] = $filters['vendor_id'];
            }
            
            if (isset($filters['city_id'])) {
                $where_conditions[] = "(pr.pickup_city_id = ? OR pr.delivery_city_id = ?)";
                $params[] = $filters['city_id'];
                $params[] = $filters['city_id'];
            }
            
            if (isset($filters['date_from'])) {
                $where_conditions[] = "DATE(pr.created_at) >= ?";
                $params[] = $filters['date_from'];
            }
            
            if (isset($filters['date_to'])) {
                $where_conditions[] = "DATE(pr.created_at) <= ?";
                $params[] = $filters['date_to'];
            }
            
            $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
            
            $stmt = $this->db->prepare("
                SELECT pr.*, 
                       u.first_name as vendor_first_name, u.last_name as vendor_last_name,
                       c1.name as pickup_city_name, c2.name as delivery_city_name,
                       dp.tracking_number, dp.current_status as package_status
                FROM pickup_requests pr
                JOIN users u ON pr.vendor_id = u.id
                JOIN cities c1 ON pr.pickup_city_id = c1.id
                JOIN cities c2 ON pr.delivery_city_id = c2.id
                LEFT JOIN delivery_packages dp ON pr.id = dp.pickup_request_id
                $where_clause
                ORDER BY pr.created_at DESC
            ");
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get all pickup requests error: " . $e->getMessage());
            return [];
        }
    }
    
    public function updateStatus($id, $new_status, $updated_by = null) {
        try {
            // Get current status
            $stmt = $this->db->prepare("SELECT status FROM pickup_requests WHERE id = ?");
            $stmt->execute([$id]);
            $current_status = $stmt->fetchColumn();
            
            // Update pickup request status
            $stmt = $this->db->prepare("UPDATE pickup_requests SET status = ? WHERE id = ?");
            $result = $stmt->execute([$new_status, $id]);
            
            if ($result && $current_status !== $new_status) {
                // Update delivery package status as well
                $stmt = $this->db->prepare("
                    UPDATE delivery_packages 
                    SET current_status = ? 
                    WHERE pickup_request_id = ?
                ");
                $stmt->execute([$new_status, $id]);
                
                // Log status change
                $stmt = $this->db->prepare("SELECT id FROM delivery_packages WHERE pickup_request_id = ?");
                $stmt->execute([$id]);
                $package_id = $stmt->fetchColumn();
                
                if ($package_id) {
                    logStatusChange($package_id, $current_status, $new_status, $updated_by, null, null, $this->db);
                }
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Update pickup request status error: " . $e->getMessage());
            return false;
        }
    }
    
    public function delete($id) {
        try {
            // Only allow deletion if status is 'en_attente'
            $stmt = $this->db->prepare("SELECT status FROM pickup_requests WHERE id = ?");
            $stmt->execute([$id]);
            $status = $stmt->fetchColumn();
            
            if ($status !== STATUS_EN_ATTENTE) {
                return false; // Cannot delete processed requests
            }
            
            // Delete associated delivery package first
            $stmt = $this->db->prepare("DELETE FROM delivery_packages WHERE pickup_request_id = ?");
            $stmt->execute([$id]);
            
            // Delete pickup request
            $stmt = $this->db->prepare("DELETE FROM pickup_requests WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            error_log("Delete pickup request error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getStats($vendor_id = null) {
        try {
            $where = $vendor_id ? "WHERE vendor_id = ?" : "";
            $params = $vendor_id ? [$vendor_id] : [];
            
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_requests,
                    COUNT(CASE WHEN status = 'en_attente' THEN 1 END) as pending_requests,
                    COUNT(CASE WHEN status = 'delivered' THEN 1 END) as delivered_requests,
                    SUM(total_amount) as total_revenue,
                    AVG(delivery_fee) as avg_delivery_fee,
                    COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as today_requests
                FROM pickup_requests $where
            ");
            $stmt->execute($params);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Get pickup request stats error: " . $e->getMessage());
            return false;
        }
    }
    
    public function searchRequests($query, $vendor_id = null) {
        try {
            $where = "WHERE (pr.recipient_name LIKE ? OR pr.recipient_phone LIKE ? OR dp.tracking_number LIKE ?)";
            $params = ["%$query%", "%$query%", "%$query%"];
            
            if ($vendor_id) {
                $where .= " AND pr.vendor_id = ?";
                $params[] = $vendor_id;
            }
            
            $stmt = $this->db->prepare("
                SELECT pr.*, 
                       u.first_name as vendor_first_name, u.last_name as vendor_last_name,
                       c1.name as pickup_city_name, c2.name as delivery_city_name,
                       dp.tracking_number, dp.current_status as package_status
                FROM pickup_requests pr
                JOIN users u ON pr.vendor_id = u.id
                JOIN cities c1 ON pr.pickup_city_id = c1.id
                JOIN cities c2 ON pr.delivery_city_id = c2.id
                LEFT JOIN delivery_packages dp ON pr.id = dp.pickup_request_id
                $where
                ORDER BY pr.created_at DESC
            ");
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Search pickup requests error: " . $e->getMessage());
            return [];
        }
    }
}
?>