<?php

namespace App\Models;

class BonEnvoi extends BaseModel
{
    protected $table = 'bon_envoi';
    protected $fillable = ['code', 'livreur_id', 'zone_id', 'status'];

    public function createBonEnvoi(int $livreurId, int $zoneId, array $orderIds): int
    {
        $this->beginTransaction();
        
        try {
            // Generate unique code
            $code = 'BD' . date('Ymd') . sprintf('%04d', rand(1, 9999));
            
            // Create bon d'envoi
            $beId = $this->create([
                'code' => $code,
                'livreur_id' => $livreurId,
                'zone_id' => $zoneId,
                'status' => 'PRET_POUR_DISTRIBUTION'
            ]);
            
            // Link orders to bon d'envoi
            foreach ($orderIds as $orderId) {
                $this->query(
                    'INSERT INTO bon_envoi_orders (bon_envoi_id, order_id) VALUES (?, ?)',
                    [$beId, $orderId]
                );
                
                // Update order status
                $this->query(
                    'UPDATE orders SET status = ? WHERE id = ?',
                    ['PRET_POUR_DISTRIBUTION', $orderId]
                );
            }
            
            $this->commit();
            return $beId;
            
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function scanBonEnvoi(int $beId): bool
    {
        $this->beginTransaction();
        
        try {
            // Update bon d'envoi status
            $this->update($beId, ['status' => 'RECU']);
            
            // Get all orders in this bon d'envoi
            $sql = "SELECT order_id FROM bon_envoi_orders WHERE bon_envoi_id = ?";
            $orders = $this->fetchAll($sql, [$beId]);
            
            // Update all orders status and assign to livreur
            $be = $this->find($beId);
            foreach ($orders as $order) {
                $this->query(
                    'UPDATE orders SET status = ?, livreur_id = ? WHERE id = ?',
                    ['MISE_EN_DISTRIBUTION', $be['livreur_id'], $order['order_id']]
                );
            }
            
            $this->commit();
            return true;
            
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function getBonEnvoisByLivreur(int $livreurId): array
    {
        $sql = "SELECT be.*, z.name as zone_name, COUNT(beo.order_id) as order_count 
                FROM bon_envoi be 
                JOIN zones z ON be.zone_id = z.id 
                LEFT JOIN bon_envoi_orders beo ON be.id = beo.bon_envoi_id 
                WHERE be.livreur_id = ? 
                GROUP BY be.id 
                ORDER BY be.created_at DESC";
        
        return $this->fetchAll($sql, [$livreurId]);
    }

    public function getAllBonEnvois(): array
    {
        $sql = "SELECT be.*, u.first_name, u.last_name, z.name as zone_name, 
                       COUNT(beo.order_id) as order_count 
                FROM bon_envoi be 
                JOIN users u ON be.livreur_id = u.id 
                JOIN zones z ON be.zone_id = z.id 
                LEFT JOIN bon_envoi_orders beo ON be.id = beo.bon_envoi_id 
                GROUP BY be.id 
                ORDER BY be.created_at DESC";
        
        return $this->fetchAll($sql);
    }

    public function getBonEnvoiOrders(int $beId): array
    {
        $sql = "SELECT o.*, c.name as city_name, u.first_name, u.last_name, u.store_name 
                FROM bon_envoi_orders beo 
                JOIN orders o ON beo.order_id = o.id 
                JOIN cities c ON o.city_id = c.id 
                JOIN users u ON o.client_id = u.id 
                WHERE beo.bon_envoi_id = ? 
                ORDER BY beo.id";
        
        return $this->fetchAll($sql, [$beId]);
    }

    public function getBonEnvoiDetails(int $beId): ?array
    {
        $sql = "SELECT be.*, u.first_name, u.last_name, u.email, z.name as zone_name 
                FROM bon_envoi be 
                JOIN users u ON be.livreur_id = u.id 
                JOIN zones z ON be.zone_id = z.id 
                WHERE be.id = ?";
        
        return $this->fetch($sql, [$beId]);
    }

    public function findByCode(string $code): ?array
    {
        return $this->findBy(['code' => $code]);
    }

    public function getReadyForDistribution(): array
    {
        $sql = "SELECT be.*, u.first_name, u.last_name, z.name as zone_name, 
                       COUNT(beo.order_id) as order_count 
                FROM bon_envoi be 
                JOIN users u ON be.livreur_id = u.id 
                JOIN zones z ON be.zone_id = z.id 
                LEFT JOIN bon_envoi_orders beo ON be.id = beo.bon_envoi_id 
                WHERE be.status = 'PRET_POUR_DISTRIBUTION' 
                GROUP BY be.id 
                ORDER BY be.created_at DESC";
        
        return $this->fetchAll($sql);
    }

    public function getReceivedBonEnvois(): array
    {
        $sql = "SELECT be.*, u.first_name, u.last_name, z.name as zone_name, 
                       COUNT(beo.order_id) as order_count 
                FROM bon_envoi be 
                JOIN users u ON be.livreur_id = u.id 
                JOIN zones z ON be.zone_id = z.id 
                LEFT JOIN bon_envoi_orders beo ON be.id = beo.bon_envoi_id 
                WHERE be.status = 'RECU' 
                GROUP BY be.id 
                ORDER BY be.created_at DESC";
        
        return $this->fetchAll($sql);
    }

    public function getOrdersForLivreur(int $livreurId): array
    {
        $sql = "SELECT o.*, c.name as city_name, u.first_name, u.last_name, u.store_name 
                FROM orders o 
                JOIN cities c ON o.city_id = c.id 
                JOIN users u ON o.client_id = u.id 
                WHERE o.livreur_id = ? 
                AND o.status = 'MISE_EN_DISTRIBUTION' 
                ORDER BY o.created_at DESC";
        
        return $this->fetchAll($sql, [$livreurId]);
    }

    public function getBonEnvoiStats(): array
    {
        $stats = [];
        
        // Get counts by status
        $sql = "SELECT status, COUNT(*) as count FROM bon_envoi GROUP BY status";
        $results = $this->fetchAll($sql);
        
        foreach ($results as $row) {
            $stats['by_status'][$row['status']] = (int) $row['count'];
        }
        
        // Get total orders in BE
        $sql = "SELECT COUNT(*) as total_orders FROM bon_envoi_orders";
        $result = $this->fetch($sql);
        $stats['total_orders'] = (int) $result['total_orders'];
        
        // Get stats by zone
        $sql = "SELECT z.name as zone_name, COUNT(be.id) as count 
                FROM bon_envoi be 
                JOIN zones z ON be.zone_id = z.id 
                GROUP BY z.id, z.name";
        $results = $this->fetchAll($sql);
        
        foreach ($results as $row) {
            $stats['by_zone'][$row['zone_name']] = (int) $row['count'];
        }
        
        return $stats;
    }

    public function canDelete(int $beId): bool
    {
        $be = $this->find($beId);
        return $be && $be['status'] === 'PRET_POUR_DISTRIBUTION';
    }

    public function deleteBonEnvoi(int $beId): bool
    {
        if (!$this->canDelete($beId)) {
            return false;
        }
        
        $this->beginTransaction();
        
        try {
            // Get orders in this BE
            $orders = $this->getBonEnvoiOrders($beId);
            
            // Delete BE orders junction
            $this->query('DELETE FROM bon_envoi_orders WHERE bon_envoi_id = ?', [$beId]);
            
            // Reset order statuses
            foreach ($orders as $order) {
                $this->query('UPDATE orders SET status = ? WHERE id = ?', ['RAMASSE', $order['id']]);
            }
            
            // Delete BE
            $this->delete($beId);
            
            $this->commit();
            return true;
            
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function getOrdersByBonEnvoi(int $beId): array
    {
        return $this->getBonEnvoiOrders($beId);
    }

    public function assignOrdersToLivreur(array $orderIds, int $livreurId): bool
    {
        $this->beginTransaction();
        
        try {
            foreach ($orderIds as $orderId) {
                $this->query(
                    'UPDATE orders SET livreur_id = ?, status = ? WHERE id = ?',
                    [$livreurId, 'MISE_EN_DISTRIBUTION', $orderId]
                );
            }
            
            $this->commit();
            return true;
            
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }
}