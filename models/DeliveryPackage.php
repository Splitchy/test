<?php
class DeliveryPackage {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    public function getById($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT dp.*, pr.*, 
                       u.first_name as vendor_first_name, u.last_name as vendor_last_name,
                       c1.name as pickup_city_name, c2.name as delivery_city_name,
                       da.first_name as agent_first_name, da.last_name as agent_last_name
                FROM delivery_packages dp
                JOIN pickup_requests pr ON dp.pickup_request_id = pr.id
                JOIN users u ON pr.vendor_id = u.id
                JOIN cities c1 ON pr.pickup_city_id = c1.id
                JOIN cities c2 ON pr.delivery_city_id = c2.id
                LEFT JOIN users da ON dp.delivery_agent_id = da.id
                WHERE dp.id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Get delivery package by ID error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getByTrackingNumber($tracking_number) {
        try {
            $stmt = $this->db->prepare("
                SELECT dp.*, pr.*, 
                       u.first_name as vendor_first_name, u.last_name as vendor_last_name,
                       c1.name as pickup_city_name, c2.name as delivery_city_name,
                       da.first_name as agent_first_name, da.last_name as agent_last_name
                FROM delivery_packages dp
                JOIN pickup_requests pr ON dp.pickup_request_id = pr.id
                JOIN users u ON pr.vendor_id = u.id
                JOIN cities c1 ON pr.pickup_city_id = c1.id
                JOIN cities c2 ON pr.delivery_city_id = c2.id
                LEFT JOIN users da ON dp.delivery_agent_id = da.id
                WHERE dp.tracking_number = ?
            ");
            $stmt->execute([$tracking_number]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Get delivery package by tracking number error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getAvailableForSlip() {
        try {
            $stmt = $this->db->prepare("
                SELECT dp.*, pr.recipient_name, pr.delivery_address,
                       c.name as delivery_city_name
                FROM delivery_packages dp
                JOIN pickup_requests pr ON dp.pickup_request_id = pr.id
                JOIN cities c ON pr.delivery_city_id = c.id
                WHERE dp.delivery_agent_id IS NULL 
                AND dp.current_status IN ('ready', 'en_preparation', 'ramasse')
                AND dp.id NOT IN (
                    SELECT delivery_package_id FROM delivery_slip_packages
                )
                ORDER BY dp.created_at ASC
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get available packages for slip error: " . $e->getMessage());
            return [];
        }
    }
    
    public function getByAgent($agent_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT dp.*, pr.recipient_name, pr.delivery_address,
                       c.name as delivery_city_name
                FROM delivery_packages dp
                JOIN pickup_requests pr ON dp.pickup_request_id = pr.id
                JOIN cities c ON pr.delivery_city_id = c.id
                WHERE dp.delivery_agent_id = ?
                ORDER BY dp.created_at DESC
            ");
            $stmt->execute([$agent_id]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get packages by agent error: " . $e->getMessage());
            return [];
        }
    }
    
    public function updateStatus($id, $new_status, $updated_by = null, $location = null, $notes = null) {
        try {
            // Get current status
            $stmt = $this->db->prepare("SELECT current_status FROM delivery_packages WHERE id = ?");
            $stmt->execute([$id]);
            $current_status = $stmt->fetchColumn();
            
            // Update package status
            $fields = ["current_status = ?"];
            $params = [$new_status];
            
            if ($new_status === STATUS_DELIVERED) {
                $fields[] = "actual_delivery = CURRENT_TIMESTAMP";
            }
            
            if ($notes) {
                $fields[] = "delivery_notes = ?";
                $params[] = $notes;
            }
            
            $params[] = $id;
            
            $stmt = $this->db->prepare("
                UPDATE delivery_packages 
                SET " . implode(", ", $fields) . " 
                WHERE id = ?
            ");
            $result = $stmt->execute($params);
            
            if ($result && $current_status !== $new_status) {
                // Log status change
                logStatusChange($id, $current_status, $new_status, $updated_by, $location, $notes, $this->db);
                
                // Update pickup request status
                $stmt = $this->db->prepare("
                    UPDATE pickup_requests 
                    SET status = ? 
                    WHERE id = (SELECT pickup_request_id FROM delivery_packages WHERE id = ?)
                ");
                $stmt->execute([$new_status, $id]);
                
                // Add package tracking entry
                $this->addTrackingEntry($id, $new_status, $location, $updated_by);
                
                // Send notifications
                $this->sendStatusUpdateNotifications($id, $new_status);
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Update package status error: " . $e->getMessage());
            return false;
        }
    }
    
    public function updateStatusByQR($qr_code, $new_status, $updated_by = null, $location = null, $notes = null) {
        try {
            // Get package ID from QR code
            $stmt = $this->db->prepare("
                SELECT dp.id 
                FROM delivery_packages dp
                JOIN pickup_requests pr ON dp.pickup_request_id = pr.id
                WHERE pr.qr_code = ?
            ");
            $stmt->execute([$qr_code]);
            $package_id = $stmt->fetchColumn();
            
            if (!$package_id) {
                return false;
            }
            
            return $this->updateStatus($package_id, $new_status, $updated_by, $location, $notes);
        } catch (Exception $e) {
            error_log("Update package status by QR error: " . $e->getMessage());
            return false;
        }
    }
    
    private function addTrackingEntry($package_id, $status, $location = null, $scanned_by = null) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO package_tracking (package_id, status, location, scanned_by)
                VALUES (?, ?, ?, ?)
            ");
            return $stmt->execute([$package_id, $status, $location, $scanned_by]);
        } catch (Exception $e) {
            error_log("Add tracking entry error: " . $e->getMessage());
            return false;
        }
    }
    
    private function sendStatusUpdateNotifications($package_id, $new_status) {
        try {
            // Get package and vendor info
            $stmt = $this->db->prepare("
                SELECT dp.*, pr.vendor_id, pr.recipient_name, dp.tracking_number
                FROM delivery_packages dp
                JOIN pickup_requests pr ON dp.pickup_request_id = pr.id
                WHERE dp.id = ?
            ");
            $stmt->execute([$package_id]);
            $package = $stmt->fetch();
            
            if ($package) {
                $status_name = getStatusDisplayName($new_status);
                $message = "Package for {$package['recipient_name']} (#{$package['tracking_number']}) is now: $status_name";
                
                // Notify vendor
                sendNotification(
                    $package['vendor_id'],
                    'Package Status Updated',
                    $message,
                    'info',
                    $this->db
                );
                
                // Send email if enabled
                if (getSystemSetting('email_notifications', 1, $this->db)) {
                    $stmt = $this->db->prepare("SELECT email FROM users WHERE id = ?");
                    $stmt->execute([$package['vendor_id']]);
                    $vendor_email = $stmt->fetchColumn();
                    
                    if ($vendor_email) {
                        sendEmailNotification(
                            $vendor_email,
                            'Package Status Update - ' . SITE_NAME,
                            $message
                        );
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Send status update notifications error: " . $e->getMessage());
        }
    }
    
    public function assignToAgent($package_id, $agent_id) {
        try {
            $stmt = $this->db->prepare("
                UPDATE delivery_packages 
                SET delivery_agent_id = ? 
                WHERE id = ?
            ");
            return $stmt->execute([$agent_id, $package_id]);
        } catch (Exception $e) {
            error_log("Assign package to agent error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getTrackingHistory($package_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT pt.*, u.first_name, u.last_name
                FROM package_tracking pt
                LEFT JOIN users u ON pt.scanned_by = u.id
                WHERE pt.package_id = ?
                ORDER BY pt.scan_time DESC
            ");
            $stmt->execute([$package_id]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get tracking history error: " . $e->getMessage());
            return [];
        }
    }
    
    public function getStatusLogs($package_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT dsl.*, u.first_name, u.last_name
                FROM delivery_status_logs dsl
                LEFT JOIN users u ON dsl.changed_by = u.id
                WHERE dsl.package_id = ?
                ORDER BY dsl.created_at DESC
            ");
            $stmt->execute([$package_id]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get status logs error: " . $e->getMessage());
            return [];
        }
    }
    
    public function getStats($agent_id = null) {
        try {
            $where = $agent_id ? "WHERE delivery_agent_id = ?" : "";
            $params = $agent_id ? [$agent_id] : [];
            
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_packages,
                    COUNT(CASE WHEN current_status = 'delivered' THEN 1 END) as delivered_packages,
                    COUNT(CASE WHEN current_status = 'in_delivery_slip' THEN 1 END) as in_transit_packages,
                    COUNT(CASE WHEN current_status = 'delivered' AND DATE(actual_delivery) = CURDATE() THEN 1 END) as today_delivered,
                    AVG(CASE WHEN current_status = 'delivered' THEN TIMESTAMPDIFF(HOUR, created_at, actual_delivery) END) as avg_delivery_time
                FROM delivery_packages $where
            ");
            $stmt->execute($params);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Get delivery package stats error: " . $e->getMessage());
            return false;
        }
    }
    
    public function searchPackages($query, $agent_id = null) {
        try {
            $where = "WHERE (dp.tracking_number LIKE ? OR pr.recipient_name LIKE ? OR pr.recipient_phone LIKE ?)";
            $params = ["%$query%", "%$query%", "%$query%"];
            
            if ($agent_id) {
                $where .= " AND dp.delivery_agent_id = ?";
                $params[] = $agent_id;
            }
            
            $stmt = $this->db->prepare("
                SELECT dp.*, pr.recipient_name, pr.delivery_address,
                       c.name as delivery_city_name
                FROM delivery_packages dp
                JOIN pickup_requests pr ON dp.pickup_request_id = pr.id
                JOIN cities c ON pr.delivery_city_id = c.id
                $where
                ORDER BY dp.created_at DESC
            ");
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Search packages error: " . $e->getMessage());
            return [];
        }
    }
}
?>