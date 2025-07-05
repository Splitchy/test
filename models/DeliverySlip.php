<?php
class DeliverySlip {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    public function create($slipData) {
        try {
            $slip_number = generateSlipNumber();
            
            $stmt = $this->db->prepare("
                INSERT INTO delivery_slips (slip_number, delivery_agent_id, created_by, slip_date, notes)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $slip_number,
                $slipData['delivery_agent_id'],
                $slipData['created_by'],
                $slipData['slip_date'],
                $slipData['notes']
            ]);
            
            if ($result) {
                $slip_id = $this->db->lastInsertId();
                
                // Add packages to the slip
                if (!empty($slipData['package_ids'])) {
                    $this->addPackagesToSlip($slip_id, $slipData['package_ids']);
                }
                
                // Update slip package count
                $this->updatePackageCount($slip_id);
                
                // Assign packages to delivery agent
                $this->assignPackagesToAgent($slipData['package_ids'], $slipData['delivery_agent_id']);
                
                // Send notification to delivery agent
                sendNotification(
                    $slipData['delivery_agent_id'],
                    'New Delivery Slip Assigned',
                    "You have been assigned delivery slip #$slip_number with " . count($slipData['package_ids']) . " packages.",
                    'info',
                    $this->db
                );
                
                return $slip_id;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Create delivery slip error: " . $e->getMessage());
            return false;
        }
    }
    
    private function addPackagesToSlip($slip_id, $package_ids) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO delivery_slip_packages (delivery_slip_id, delivery_package_id)
                VALUES (?, ?)
            ");
            
            foreach ($package_ids as $package_id) {
                $stmt->execute([$slip_id, $package_id]);
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Add packages to slip error: " . $e->getMessage());
            return false;
        }
    }
    
    private function assignPackagesToAgent($package_ids, $agent_id) {
        try {
            $packageModel = new DeliveryPackage($this->db);
            
            foreach ($package_ids as $package_id) {
                $packageModel->assignToAgent($package_id, $agent_id);
                $packageModel->updateStatus($package_id, STATUS_IN_DELIVERY_SLIP);
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Assign packages to agent error: " . $e->getMessage());
            return false;
        }
    }
    
    private function updatePackageCount($slip_id) {
        try {
            $stmt = $this->db->prepare("
                UPDATE delivery_slips 
                SET total_packages = (
                    SELECT COUNT(*) FROM delivery_slip_packages WHERE delivery_slip_id = ?
                )
                WHERE id = ?
            ");
            return $stmt->execute([$slip_id, $slip_id]);
        } catch (Exception $e) {
            error_log("Update package count error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getById($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT ds.*, 
                       u.first_name as agent_first_name, u.last_name as agent_last_name,
                       c.first_name as creator_first_name, c.last_name as creator_last_name
                FROM delivery_slips ds
                JOIN users u ON ds.delivery_agent_id = u.id
                JOIN users c ON ds.created_by = c.id
                WHERE ds.id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Get delivery slip by ID error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getByAgent($agent_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT ds.*, 
                       u.first_name as creator_first_name, u.last_name as creator_last_name
                FROM delivery_slips ds
                JOIN users u ON ds.created_by = u.id
                WHERE ds.delivery_agent_id = ?
                ORDER BY ds.created_at DESC
            ");
            $stmt->execute([$agent_id]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get delivery slips by agent error: " . $e->getMessage());
            return [];
        }
    }
    
    public function getAll() {
        try {
            $stmt = $this->db->prepare("
                SELECT ds.*, 
                       u.first_name as agent_first_name, u.last_name as agent_last_name,
                       c.first_name as creator_first_name, c.last_name as creator_last_name
                FROM delivery_slips ds
                JOIN users u ON ds.delivery_agent_id = u.id
                JOIN users c ON ds.created_by = c.id
                ORDER BY ds.created_at DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get all delivery slips error: " . $e->getMessage());
            return [];
        }
    }
    
    public function getSlipPackages($slip_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT dp.*, pr.recipient_name, pr.delivery_address, pr.recipient_phone,
                       c.name as delivery_city_name
                FROM delivery_slip_packages dsp
                JOIN delivery_packages dp ON dsp.delivery_package_id = dp.id
                JOIN pickup_requests pr ON dp.pickup_request_id = pr.id
                JOIN cities c ON pr.delivery_city_id = c.id
                WHERE dsp.delivery_slip_id = ?
                ORDER BY pr.delivery_address
            ");
            $stmt->execute([$slip_id]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get slip packages error: " . $e->getMessage());
            return [];
        }
    }
    
    public function updateStatus($slip_id, $new_status) {
        try {
            $stmt = $this->db->prepare("UPDATE delivery_slips SET status = ? WHERE id = ?");
            $result = $stmt->execute([$new_status, $slip_id]);
            
            if ($result && $new_status === 'completed') {
                // Mark all packages as completed
                $packages = $this->getSlipPackages($slip_id);
                $packageModel = new DeliveryPackage($this->db);
                
                foreach ($packages as $package) {
                    if ($package['current_status'] !== STATUS_DELIVERED) {
                        $packageModel->updateStatus($package['id'], STATUS_DELIVERED);
                    }
                }
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Update delivery slip status error: " . $e->getMessage());
            return false;
        }
    }
    
    public function delete($slip_id) {
        try {
            // Only allow deletion if status is 'pending'
            $stmt = $this->db->prepare("SELECT status FROM delivery_slips WHERE id = ?");
            $stmt->execute([$slip_id]);
            $status = $stmt->fetchColumn();
            
            if ($status !== 'pending') {
                return false; // Cannot delete processed slips
            }
            
            // Remove packages from slip
            $stmt = $this->db->prepare("DELETE FROM delivery_slip_packages WHERE delivery_slip_id = ?");
            $stmt->execute([$slip_id]);
            
            // Delete delivery slip
            $stmt = $this->db->prepare("DELETE FROM delivery_slips WHERE id = ?");
            return $stmt->execute([$slip_id]);
        } catch (Exception $e) {
            error_log("Delete delivery slip error: " . $e->getMessage());
            return false;
        }
    }
    
    public function generateSlipPDF($slip_id) {
        try {
            $slip = $this->getById($slip_id);
            $packages = $this->getSlipPackages($slip_id);
            
            if (!$slip) {
                return false;
            }
            
            $html = $this->generateSlipHTML($slip, $packages);
            return generatePDF($html, "delivery_slip_{$slip['slip_number']}.pdf");
        } catch (Exception $e) {
            error_log("Generate slip PDF error: " . $e->getMessage());
            return false;
        }
    }
    
    private function generateSlipHTML($slip, $packages) {
        $html = '
        <style>
            table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
            th { background-color: #f2f2f2; }
            .header { text-align: center; margin-bottom: 30px; }
            .slip-info { margin-bottom: 20px; }
        </style>
        
        <div class="header">
            <h1>' . SITE_NAME . '</h1>
            <h2>Delivery Slip</h2>
        </div>
        
        <div class="slip-info">
            <p><strong>Slip Number:</strong> ' . $slip['slip_number'] . '</p>
            <p><strong>Date:</strong> ' . formatDate($slip['slip_date']) . '</p>
            <p><strong>Delivery Agent:</strong> ' . $slip['agent_first_name'] . ' ' . $slip['agent_last_name'] . '</p>
            <p><strong>Total Packages:</strong> ' . count($packages) . '</p>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Tracking #</th>
                    <th>Recipient</th>
                    <th>Address</th>
                    <th>Phone</th>
                    <th>City</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>';
        
        foreach ($packages as $package) {
            $html .= '<tr>
                <td>' . $package['tracking_number'] . '</td>
                <td>' . $package['recipient_name'] . '</td>
                <td>' . $package['delivery_address'] . '</td>
                <td>' . $package['recipient_phone'] . '</td>
                <td>' . $package['delivery_city_name'] . '</td>
                <td>' . getStatusDisplayName($package['current_status']) . '</td>
            </tr>';
        }
        
        $html .= '</tbody></table>
        
        <div style="margin-top: 50px;">
            <p>Agent Signature: _________________________</p>
            <p>Date: _________________________</p>
        </div>';
        
        return $html;
    }
    
    public function getStats($agent_id = null) {
        try {
            $where = $agent_id ? "WHERE delivery_agent_id = ?" : "";
            $params = $agent_id ? [$agent_id] : [];
            
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_slips,
                    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_slips,
                    COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as active_slips,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_slips,
                    SUM(total_packages) as total_packages_handled,
                    COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as today_slips
                FROM delivery_slips $where
            ");
            $stmt->execute($params);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Get delivery slip stats error: " . $e->getMessage());
            return false;
        }
    }
    
    public function searchSlips($query, $agent_id = null) {
        try {
            $where = "WHERE ds.slip_number LIKE ?";
            $params = ["%$query%"];
            
            if ($agent_id) {
                $where .= " AND ds.delivery_agent_id = ?";
                $params[] = $agent_id;
            }
            
            $stmt = $this->db->prepare("
                SELECT ds.*, 
                       u.first_name as agent_first_name, u.last_name as agent_last_name,
                       c.first_name as creator_first_name, c.last_name as creator_last_name
                FROM delivery_slips ds
                JOIN users u ON ds.delivery_agent_id = u.id
                JOIN users c ON ds.created_by = c.id
                $where
                ORDER BY ds.created_at DESC
            ");
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Search delivery slips error: " . $e->getMessage());
            return [];
        }
    }
}
?>