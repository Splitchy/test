<?php

namespace App\Models;

class Zone extends BaseModel
{
    protected $table = 'zones';
    protected $fillable = ['name'];

    public function getZonesWithCities(): array
    {
        $sql = "SELECT z.*, COUNT(c.id) as city_count 
                FROM zones z 
                LEFT JOIN cities c ON z.id = c.zone_id 
                GROUP BY z.id 
                ORDER BY z.name";
        
        return $this->fetchAll($sql);
    }

    public function getZoneWithCities(int $zoneId): ?array
    {
        $zone = $this->find($zoneId);
        if (!$zone) {
            return null;
        }
        
        $sql = "SELECT * FROM cities WHERE zone_id = ? ORDER BY name";
        $cities = $this->fetchAll($sql, [$zoneId]);
        
        $zone['cities'] = $cities;
        return $zone;
    }

    public function createZone(string $name): int
    {
        return $this->create(['name' => $name]);
    }

    public function updateZone(int $zoneId, string $name): bool
    {
        return $this->update($zoneId, ['name' => $name]);
    }

    public function searchZones(string $query): array
    {
        $sql = "SELECT z.*, COUNT(c.id) as city_count 
                FROM zones z 
                LEFT JOIN cities c ON z.id = c.zone_id 
                WHERE z.name LIKE ?
                GROUP BY z.id 
                ORDER BY z.name";
        
        $searchTerm = "%{$query}%";
        return $this->fetchAll($sql, [$searchTerm]);
    }

    public function getZoneStats(): array
    {
        $stats = [];
        
        // Get zone counts
        $stats['total'] = $this->count();
        
        // Get cities per zone
        $sql = "SELECT z.name as zone_name, COUNT(c.id) as city_count 
                FROM zones z 
                LEFT JOIN cities c ON z.id = c.zone_id 
                GROUP BY z.id, z.name 
                ORDER BY z.name";
        
        $results = $this->fetchAll($sql);
        
        foreach ($results as $row) {
            $stats['cities_per_zone'][$row['zone_name']] = (int) $row['city_count'];
        }
        
        return $stats;
    }

    public function canDelete(int $zoneId): bool
    {
        // Check if zone has cities
        $sql = "SELECT COUNT(*) as count FROM cities WHERE zone_id = ?";
        $result = $this->fetch($sql, [$zoneId]);
        
        return $result['count'] == 0;
    }

    public function deleteZone(int $zoneId): bool
    {
        if (!$this->canDelete($zoneId)) {
            return false;
        }
        
        $this->beginTransaction();
        
        try {
            // Delete related tariffs
            $this->query('DELETE FROM tariffs WHERE zone_id = ?', [$zoneId]);
            
            // Delete zone
            $this->delete($zoneId);
            
            $this->commit();
            return true;
            
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function getTariff(int $zoneId): ?array
    {
        $sql = "SELECT * FROM tariffs WHERE zone_id = ? ORDER BY created_at DESC LIMIT 1";
        return $this->fetch($sql, [$zoneId]);
    }

    public function updateTariff(int $zoneId, array $tariffData): bool
    {
        $this->beginTransaction();
        
        try {
            // Delete existing tariff
            $this->query('DELETE FROM tariffs WHERE zone_id = ?', [$zoneId]);
            
            // Insert new tariff
            $tariffData['zone_id'] = $zoneId;
            $this->query(
                'INSERT INTO tariffs (zone_id, delivery_price, refusal_price, return_price, standard_delivery_time) 
                 VALUES (?, ?, ?, ?, ?)',
                [
                    $zoneId,
                    $tariffData['delivery_price'],
                    $tariffData['refusal_price'],
                    $tariffData['return_price'],
                    $tariffData['standard_delivery_time']
                ]
            );
            
            $this->commit();
            return true;
            
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function getZonesWithTariffs(): array
    {
        $sql = "SELECT z.*, t.delivery_price, t.refusal_price, t.return_price, t.standard_delivery_time 
                FROM zones z 
                LEFT JOIN tariffs t ON z.id = t.zone_id 
                ORDER BY z.name";
        
        return $this->fetchAll($sql);
    }

    public function findByName(string $name): ?array
    {
        return $this->findBy(['name' => $name]);
    }

    public function getOrdersCount(int $zoneId): int
    {
        $sql = "SELECT COUNT(*) as count 
                FROM orders o 
                JOIN cities c ON o.city_id = c.id 
                WHERE c.zone_id = ?";
        
        $result = $this->fetch($sql, [$zoneId]);
        return (int) $result['count'];
    }

    public function getDeliveryStats(int $zoneId): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN o.status = 'LIVRE' THEN 1 ELSE 0 END) as delivered,
                    SUM(CASE WHEN o.status = 'REFUSE' THEN 1 ELSE 0 END) as refused,
                    SUM(CASE WHEN o.status = 'ANNULE' THEN 1 ELSE 0 END) as cancelled
                FROM orders o 
                JOIN cities c ON o.city_id = c.id 
                WHERE c.zone_id = ?";
        
        $result = $this->fetch($sql, [$zoneId]);
        return $result ?: [];
    }

    public function getZoneOrdersForDistribution(int $zoneId): array
    {
        $sql = "SELECT o.*, c.name as city_name, u.first_name, u.last_name, u.store_name 
                FROM orders o 
                JOIN cities c ON o.city_id = c.id 
                JOIN users u ON o.client_id = u.id 
                WHERE c.zone_id = ? AND o.status = 'RAMASSE' 
                ORDER BY o.created_at DESC";
        
        return $this->fetchAll($sql, [$zoneId]);
    }

    public function getLivreursInZone(int $zoneId): array
    {
        // Get livreurs who have delivered in this zone
        $sql = "SELECT DISTINCT u.id, u.first_name, u.last_name, u.email 
                FROM users u 
                JOIN orders o ON u.id = o.livreur_id 
                JOIN cities c ON o.city_id = c.id 
                WHERE c.zone_id = ? AND u.role = 'livreur' AND u.status = 'approved'
                ORDER BY u.first_name, u.last_name";
        
        return $this->fetchAll($sql, [$zoneId]);
    }

    public function getAllZones(): array
    {
        return $this->findAll([], 'name ASC');
    }
}