<?php
class User {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    public function create($userData) {
        try {
            // Check if username or email already exists
            $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$userData['username'], $userData['email']]);
            if ($stmt->fetch()) {
                return false; // User already exists
            }
            
            $stmt = $this->db->prepare("
                INSERT INTO users (username, email, password_hash, role, first_name, last_name, phone, address, city_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            return $stmt->execute([
                $userData['username'],
                $userData['email'],
                password_hash($userData['password'], PASSWORD_DEFAULT),
                $userData['role'],
                $userData['first_name'],
                $userData['last_name'],
                $userData['phone'] ?? null,
                $userData['address'] ?? null,
                $userData['city_id'] ?? null
            ]);
        } catch (Exception $e) {
            error_log("User creation error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getById($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT u.*, c.name as city_name
                FROM users u
                LEFT JOIN cities c ON u.city_id = c.id
                WHERE u.id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Get user by ID error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getByUsername($username) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Get user by username error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getByEmail($email) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Get user by email error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getByRole($role) {
        try {
            $stmt = $this->db->prepare("
                SELECT u.*, c.name as city_name
                FROM users u
                LEFT JOIN cities c ON u.city_id = c.id
                WHERE u.role = ? AND u.is_active = 1
                ORDER BY u.first_name, u.last_name
            ");
            $stmt->execute([$role]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get users by role error: " . $e->getMessage());
            return [];
        }
    }
    
    public function getAll() {
        try {
            $stmt = $this->db->prepare("
                SELECT u.*, c.name as city_name
                FROM users u
                LEFT JOIN cities c ON u.city_id = c.id
                ORDER BY u.created_at DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get all users error: " . $e->getMessage());
            return [];
        }
    }
    
    public function update($userData) {
        try {
            $fields = [];
            $values = [];
            
            // Build dynamic update query
            if (isset($userData['first_name'])) {
                $fields[] = "first_name = ?";
                $values[] = $userData['first_name'];
            }
            
            if (isset($userData['last_name'])) {
                $fields[] = "last_name = ?";
                $values[] = $userData['last_name'];
            }
            
            if (isset($userData['email'])) {
                $fields[] = "email = ?";
                $values[] = $userData['email'];
            }
            
            if (isset($userData['phone'])) {
                $fields[] = "phone = ?";
                $values[] = $userData['phone'];
            }
            
            if (isset($userData['address'])) {
                $fields[] = "address = ?";
                $values[] = $userData['address'];
            }
            
            if (isset($userData['city_id'])) {
                $fields[] = "city_id = ?";
                $values[] = $userData['city_id'];
            }
            
            if (isset($userData['password'])) {
                $fields[] = "password_hash = ?";
                $values[] = password_hash($userData['password'], PASSWORD_DEFAULT);
            }
            
            if (isset($userData['role'])) {
                $fields[] = "role = ?";
                $values[] = $userData['role'];
            }
            
            if (isset($userData['is_active'])) {
                $fields[] = "is_active = ?";
                $values[] = $userData['is_active'];
            }
            
            if (empty($fields)) {
                return false;
            }
            
            $values[] = $userData['id'];
            
            $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute($values);
        } catch (Exception $e) {
            error_log("User update error: " . $e->getMessage());
            return false;
        }
    }
    
    public function delete($id) {
        try {
            // Soft delete - just deactivate the user
            $stmt = $this->db->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            error_log("User delete error: " . $e->getMessage());
            return false;
        }
    }
    
    public function activate($id) {
        try {
            $stmt = $this->db->prepare("UPDATE users SET is_active = 1 WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            error_log("User activate error: " . $e->getMessage());
            return false;
        }
    }
    
    public function changePassword($id, $newPassword) {
        try {
            $stmt = $this->db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            return $stmt->execute([password_hash($newPassword, PASSWORD_DEFAULT), $id]);
        } catch (Exception $e) {
            error_log("Change password error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getStats($role = null) {
        try {
            $where = $role ? "WHERE role = ?" : "";
            $params = $role ? [$role] : [];
            
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_users,
                    COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_users,
                    COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as today_registrations
                FROM users $where
            ");
            $stmt->execute($params);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Get user stats error: " . $e->getMessage());
            return false;
        }
    }
    
    public function searchUsers($query, $role = null) {
        try {
            $where = "WHERE (u.first_name LIKE ? OR u.last_name LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
            $params = ["%$query%", "%$query%", "%$query%", "%$query%"];
            
            if ($role) {
                $where .= " AND u.role = ?";
                $params[] = $role;
            }
            
            $stmt = $this->db->prepare("
                SELECT u.*, c.name as city_name
                FROM users u
                LEFT JOIN cities c ON u.city_id = c.id
                $where
                ORDER BY u.first_name, u.last_name
            ");
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Search users error: " . $e->getMessage());
            return [];
        }
    }
    
    public function getUserPickupStats($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_requests,
                    COUNT(CASE WHEN status = 'delivered' THEN 1 END) as delivered_requests,
                    SUM(total_amount) as total_revenue,
                    AVG(delivery_fee) as avg_delivery_fee
                FROM pickup_requests
                WHERE vendor_id = ?
            ");
            $stmt->execute([$userId]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Get user pickup stats error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getUserDeliveryStats($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(DISTINCT ds.id) as total_slips,
                    COUNT(DISTINCT dp.id) as total_packages,
                    COUNT(CASE WHEN dp.current_status = 'delivered' THEN 1 END) as delivered_packages,
                    AVG(TIMESTAMPDIFF(HOUR, ds.created_at, dp.actual_delivery)) as avg_delivery_time
                FROM delivery_slips ds
                LEFT JOIN delivery_slip_packages dsp ON ds.id = dsp.delivery_slip_id
                LEFT JOIN delivery_packages dp ON dsp.delivery_package_id = dp.id
                WHERE ds.delivery_agent_id = ?
            ");
            $stmt->execute([$userId]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Get user delivery stats error: " . $e->getMessage());
            return false;
        }
    }
}
?>