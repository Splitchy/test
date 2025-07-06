<?php

namespace App\Models;

class City extends BaseModel
{
    protected $table = 'cities';
    protected $fillable = ['name', 'zone_id'];

    public function getCitiesWithZones(): array
    {
        $sql = "SELECT c.*, z.name as zone_name 
                FROM cities c 
                LEFT JOIN zones z ON c.zone_id = z.id 
                ORDER BY c.name";
        
        return $this->fetchAll($sql);
    }

    public function getCitiesByZone(int $zoneId): array
    {
        return $this->findAll(['zone_id' => $zoneId], 'name ASC');
    }

    public function getCityWithZone(int $cityId): ?array
    {
        $sql = "SELECT c.*, z.name as zone_name 
                FROM cities c 
                LEFT JOIN zones z ON c.zone_id = z.id 
                WHERE c.id = ?";
        
        return $this->fetch($sql, [$cityId]);
    }

    public function createCity(string $name, int $zoneId): int
    {
        return $this->create([
            'name' => $name,
            'zone_id' => $zoneId
        ]);
    }

    public function updateCity(int $cityId, string $name, int $zoneId): bool
    {
        return $this->update($cityId, [
            'name' => $name,
            'zone_id' => $zoneId
        ]);
    }

    public function searchCities(string $query): array
    {
        $sql = "SELECT c.*, z.name as zone_name 
                FROM cities c 
                LEFT JOIN zones z ON c.zone_id = z.id 
                WHERE c.name LIKE ? OR z.name LIKE ?
                ORDER BY c.name";
        
        $searchTerm = "%{$query}%";
        return $this->fetchAll($sql, [$searchTerm, $searchTerm]);
    }

    public function getCityStats(): array
    {
        $stats = [];
        
        // Get city counts by zone
        $sql = "SELECT z.name as zone_name, COUNT(c.id) as count 
                FROM cities c 
                JOIN zones z ON c.zone_id = z.id 
                GROUP BY z.id, z.name 
                ORDER BY z.name";
        
        $results = $this->fetchAll($sql);
        
        foreach ($results as $row) {
            $stats['by_zone'][$row['zone_name']] = (int) $row['count'];
        }
        
        // Get total cities
        $stats['total'] = $this->count();
        
        return $stats;
    }

    public function canDelete(int $cityId): bool
    {
        // Check if city has orders
        $sql = "SELECT COUNT(*) as count FROM orders WHERE city_id = ?";
        $result = $this->fetch($sql, [$cityId]);
        
        return $result['count'] == 0;
    }

    public function deleteCity(int $cityId): bool
    {
        if (!$this->canDelete($cityId)) {
            return false;
        }
        
        $this->beginTransaction();
        
        try {
            // Delete related tariffs
            $this->query('DELETE FROM tariffs WHERE city_id = ?', [$cityId]);
            
            // Delete city
            $this->delete($cityId);
            
            $this->commit();
            return true;
            
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function getTariff(int $cityId): ?array
    {
        $sql = "SELECT * FROM tariffs WHERE city_id = ? ORDER BY created_at DESC LIMIT 1";
        return $this->fetch($sql, [$cityId]);
    }

    public function updateTariff(int $cityId, array $tariffData): bool
    {
        $this->beginTransaction();
        
        try {
            // Delete existing tariff
            $this->query('DELETE FROM tariffs WHERE city_id = ?', [$cityId]);
            
            // Insert new tariff
            $tariffData['city_id'] = $cityId;
            $this->query(
                'INSERT INTO tariffs (city_id, zone_id, delivery_price, refusal_price, return_price, standard_delivery_time) 
                 VALUES (?, ?, ?, ?, ?, ?)',
                [
                    $cityId,
                    $tariffData['zone_id'],
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

    public function getCitiesWithTariffs(): array
    {
        $sql = "SELECT c.*, z.name as zone_name, t.delivery_price, t.refusal_price, 
                       t.return_price, t.standard_delivery_time 
                FROM cities c 
                LEFT JOIN zones z ON c.zone_id = z.id 
                LEFT JOIN tariffs t ON c.id = t.city_id 
                ORDER BY c.name";
        
        return $this->fetchAll($sql);
    }

    public function findByName(string $name): ?array
    {
        return $this->findBy(['name' => $name]);
    }

    public function getOrdersCount(int $cityId): int
    {
        $sql = "SELECT COUNT(*) as count FROM orders WHERE city_id = ?";
        $result = $this->fetch($sql, [$cityId]);
        return (int) $result['count'];
    }

    public function getDeliveryStats(int $cityId): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN status = 'LIVRE' THEN 1 ELSE 0 END) as delivered,
                    SUM(CASE WHEN status = 'REFUSE' THEN 1 ELSE 0 END) as refused,
                    SUM(CASE WHEN status = 'ANNULE' THEN 1 ELSE 0 END) as cancelled
                FROM orders WHERE city_id = ?";
        
        $result = $this->fetch($sql, [$cityId]);
        return $result ?: [];
    }
}