<?php
class City {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    public function create($cityData) {
        try {
            // Check if city code already exists
            $stmt = $this->db->prepare("SELECT id FROM cities WHERE code = ?");
            $stmt->execute([$cityData['code']]);
            if ($stmt->fetch()) {
                return false; // City code already exists
            }
            
            $stmt = $this->db->prepare("
                INSERT INTO cities (name, code, delivery_fee, is_active)
                VALUES (?, ?, ?, 1)
            ");
            
            return $stmt->execute([
                $cityData['name'],
                strtoupper($cityData['code']),
                $cityData['delivery_fee']
            ]);
        } catch (Exception $e) {
            error_log("Create city error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getById($id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM cities WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Get city by ID error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getByCode($code) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM cities WHERE code = ?");
            $stmt->execute([strtoupper($code)]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Get city by code error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getAll($active_only = true) {
        try {
            $where = $active_only ? "WHERE is_active = 1" : "";
            
            $stmt = $this->db->prepare("
                SELECT * FROM cities 
                $where 
                ORDER BY name ASC
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get all cities error: " . $e->getMessage());
            return [];
        }
    }
    
    public function update($cityData) {
        try {
            $fields = [];
            $values = [];
            
            // Build dynamic update query
            if (isset($cityData['name'])) {
                $fields[] = "name = ?";
                $values[] = $cityData['name'];
            }
            
            if (isset($cityData['code'])) {
                $fields[] = "code = ?";
                $values[] = strtoupper($cityData['code']);
            }
            
            if (isset($cityData['delivery_fee'])) {
                $fields[] = "delivery_fee = ?";
                $values[] = $cityData['delivery_fee'];
            }
            
            if (isset($cityData['is_active'])) {
                $fields[] = "is_active = ?";
                $values[] = $cityData['is_active'];
            }
            
            if (empty($fields)) {
                return false;
            }
            
            $values[] = $cityData['id'];
            
            $sql = "UPDATE cities SET " . implode(', ', $fields) . " WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute($values);
        } catch (Exception $e) {
            error_log("Update city error: " . $e->getMessage());
            return false;
        }
    }
    
    public function delete($id) {
        try {
            // Check if city is being used
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM pickup_requests 
                WHERE pickup_city_id = ? OR delivery_city_id = ?
            ");
            $stmt->execute([$id, $id]);
            $usage_count = $stmt->fetchColumn();
            
            if ($usage_count > 0) {
                // Soft delete - just deactivate
                return $this->update(['id' => $id, 'is_active' => 0]);
            } else {
                // Hard delete if not used
                $stmt = $this->db->prepare("DELETE FROM cities WHERE id = ?");
                return $stmt->execute([$id]);
            }
        } catch (Exception $e) {
            error_log("Delete city error: " . $e->getMessage());
            return false;
        }
    }
    
    public function activate($id) {
        try {
            $stmt = $this->db->prepare("UPDATE cities SET is_active = 1 WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            error_log("Activate city error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getUsageStats($city_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_pickups,
                    COUNT(CASE WHEN pickup_city_id = ? THEN 1 END) as pickup_count,
                    COUNT(CASE WHEN delivery_city_id = ? THEN 1 END) as delivery_count,
                    SUM(CASE WHEN pickup_city_id = ? OR delivery_city_id = ? THEN delivery_fee ELSE 0 END) as total_revenue
                FROM pickup_requests
                WHERE pickup_city_id = ? OR delivery_city_id = ?
            ");
            $stmt->execute([$city_id, $city_id, $city_id, $city_id, $city_id, $city_id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Get city usage stats error: " . $e->getMessage());
            return false;
        }
    }
    
    public function searchCities($query) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM cities 
                WHERE name LIKE ? OR code LIKE ?
                ORDER BY name ASC
            ");
            $stmt->execute(["%$query%", "%$query%"]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Search cities error: " . $e->getMessage());
            return [];
        }
    }
    
    public function getPopularCities($limit = 10) {
        try {
            $stmt = $this->db->prepare("
                SELECT c.*, COUNT(pr.id) as usage_count
                FROM cities c
                LEFT JOIN pickup_requests pr ON (c.id = pr.pickup_city_id OR c.id = pr.delivery_city_id)
                WHERE c.is_active = 1
                GROUP BY c.id
                ORDER BY usage_count DESC, c.name ASC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get popular cities error: " . $e->getMessage());
            return [];
        }
    }
    
    public function calculateDistance($city1_id, $city2_id) {
        // This is a simplified distance calculation
        // In a real application, you would use actual coordinates and distance algorithms
        try {
            if ($city1_id === $city2_id) {
                return 0; // Same city
            }
            
            $stmt = $this->db->prepare("
                SELECT c1.name as city1_name, c2.name as city2_name
                FROM cities c1, cities c2
                WHERE c1.id = ? AND c2.id = ?
            ");
            $stmt->execute([$city1_id, $city2_id]);
            $result = $stmt->fetch();
            
            if ($result) {
                // Simplified distance based on city names (for demo purposes)
                // In reality, you would use GPS coordinates
                $distances = [
                    'Casablanca-Rabat' => 91,
                    'Casablanca-Marrakech' => 243,
                    'Rabat-Fès' => 206,
                    'Marrakech-Agadir' => 254,
                    // Add more distance pairs as needed
                ];
                
                $key1 = $result['city1_name'] . '-' . $result['city2_name'];
                $key2 = $result['city2_name'] . '-' . $result['city1_name'];
                
                return $distances[$key1] ?? $distances[$key2] ?? 100; // Default 100km
            }
            
            return 100; // Default distance
        } catch (Exception $e) {
            error_log("Calculate distance error: " . $e->getMessage());
            return 100; // Default distance
        }
    }
    
    public function getDeliveryZones() {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    c.*,
                    COUNT(pr.id) as total_deliveries,
                    AVG(pr.delivery_fee) as avg_fee,
                    SUM(pr.total_amount) as total_revenue
                FROM cities c
                LEFT JOIN pickup_requests pr ON c.id = pr.delivery_city_id
                WHERE c.is_active = 1
                GROUP BY c.id
                ORDER BY total_deliveries DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get delivery zones error: " . $e->getMessage());
            return [];
        }
    }
}
?>