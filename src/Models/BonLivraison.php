<?php

namespace App\Models;

class BonLivraison extends BaseModel
{
    protected $table = 'bon_livraison';
    protected $fillable = ['code', 'client_id', 'status'];

    public function createBonLivraison(int $clientId, array $orderIds): int
    {
        $this->beginTransaction();
        
        try {
            // Generate unique code
            $code = 'BL' . date('Ymd') . sprintf('%04d', rand(1, 9999));
            
            // Create bon de livraison
            $blId = $this->create([
                'code' => $code,
                'client_id' => $clientId,
                'status' => 'EN_PREPARATION'
            ]);
            
            // Link orders to bon de livraison
            foreach ($orderIds as $orderId) {
                $this->query(
                    'INSERT INTO bon_livraison_orders (bon_livraison_id, order_id) VALUES (?, ?)',
                    [$blId, $orderId]
                );
                
                // Update order status
                $this->query(
                    'UPDATE orders SET status = ? WHERE id = ?',
                    ['EN_PREPARATION', $orderId]
                );
            }
            
            $this->commit();
            return $blId;
            
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function getBonLivraisonsByClient(int $clientId): array
    {
        $sql = "SELECT bl.*, COUNT(blo.order_id) as order_count 
                FROM bon_livraison bl 
                LEFT JOIN bon_livraison_orders blo ON bl.id = blo.bon_livraison_id 
                WHERE bl.client_id = ? 
                GROUP BY bl.id 
                ORDER BY bl.created_at DESC";
        
        return $this->fetchAll($sql, [$clientId]);
    }

    public function getAllBonLivraisons(): array
    {
        $sql = "SELECT bl.*, u.first_name, u.last_name, u.store_name, 
                       COUNT(blo.order_id) as order_count 
                FROM bon_livraison bl 
                JOIN users u ON bl.client_id = u.id 
                LEFT JOIN bon_livraison_orders blo ON bl.id = blo.bon_livraison_id 
                GROUP BY bl.id 
                ORDER BY bl.created_at DESC";
        
        return $this->fetchAll($sql);
    }

    public function getBonLivraisonOrders(int $blId): array
    {
        $sql = "SELECT o.*, blo.scanned_at, c.name as city_name 
                FROM bon_livraison_orders blo 
                JOIN orders o ON blo.order_id = o.id 
                JOIN cities c ON o.city_id = c.id 
                WHERE blo.bon_livraison_id = ? 
                ORDER BY blo.id";
        
        return $this->fetchAll($sql, [$blId]);
    }

    public function scanOrder(int $blId, int $orderId): bool
    {
        $this->beginTransaction();
        
        try {
            // Check if order is already scanned
            $sql = "SELECT scanned_at FROM bon_livraison_orders 
                    WHERE bon_livraison_id = ? AND order_id = ?";
            $result = $this->fetch($sql, [$blId, $orderId]);
            
            if ($result && $result['scanned_at']) {
                throw new \Exception('Order already scanned');
            }
            
            // Mark as scanned
            $this->query(
                'UPDATE bon_livraison_orders SET scanned_at = NOW() 
                 WHERE bon_livraison_id = ? AND order_id = ?',
                [$blId, $orderId]
            );
            
            // Update order status
            $this->query(
                'UPDATE orders SET status = ? WHERE id = ?',
                ['RAMASSE', $orderId]
            );
            
            // Check if all orders are scanned
            $sql = "SELECT COUNT(*) as total, 
                           SUM(CASE WHEN scanned_at IS NOT NULL THEN 1 ELSE 0 END) as scanned 
                    FROM bon_livraison_orders 
                    WHERE bon_livraison_id = ?";
            
            $result = $this->fetch($sql, [$blId]);
            
            if ($result['total'] == $result['scanned']) {
                // All orders scanned, update BL status
                $this->update($blId, ['status' => 'RECU']);
            }
            
            $this->commit();
            return true;
            
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function getBonLivraisonDetails(int $blId): ?array
    {
        $sql = "SELECT bl.*, u.first_name, u.last_name, u.store_name, u.email 
                FROM bon_livraison bl 
                JOIN users u ON bl.client_id = u.id 
                WHERE bl.id = ?";
        
        return $this->fetch($sql, [$blId]);
    }

    public function findByCode(string $code): ?array
    {
        return $this->findBy(['code' => $code]);
    }

    public function getScannedCount(int $blId): array
    {
        $sql = "SELECT COUNT(*) as total, 
                       SUM(CASE WHEN scanned_at IS NOT NULL THEN 1 ELSE 0 END) as scanned 
                FROM bon_livraison_orders 
                WHERE bon_livraison_id = ?";
        
        return $this->fetch($sql, [$blId]);
    }

    public function getPendingOrders(int $blId): array
    {
        $sql = "SELECT o.* 
                FROM bon_livraison_orders blo 
                JOIN orders o ON blo.order_id = o.id 
                WHERE blo.bon_livraison_id = ? 
                AND blo.scanned_at IS NULL 
                ORDER BY o.created_at";
        
        return $this->fetchAll($sql, [$blId]);
    }

    public function getScannedOrders(int $blId): array
    {
        $sql = "SELECT o.*, blo.scanned_at 
                FROM bon_livraison_orders blo 
                JOIN orders o ON blo.order_id = o.id 
                WHERE blo.bon_livraison_id = ? 
                AND blo.scanned_at IS NOT NULL 
                ORDER BY blo.scanned_at DESC";
        
        return $this->fetchAll($sql, [$blId]);
    }

    public function getBonLivraisonStats(): array
    {
        $stats = [];
        
        // Get counts by status
        $sql = "SELECT status, COUNT(*) as count FROM bon_livraison GROUP BY status";
        $results = $this->fetchAll($sql);
        
        foreach ($results as $row) {
            $stats['by_status'][$row['status']] = (int) $row['count'];
        }
        
        // Get total orders in BL
        $sql = "SELECT COUNT(*) as total_orders FROM bon_livraison_orders";
        $result = $this->fetch($sql);
        $stats['total_orders'] = (int) $result['total_orders'];
        
        return $stats;
    }

    public function canDelete(int $blId): bool
    {
        $bl = $this->find($blId);
        return $bl && $bl['status'] === 'EN_PREPARATION';
    }

    public function deleteBonLivraison(int $blId): bool
    {
        if (!$this->canDelete($blId)) {
            return false;
        }
        
        $this->beginTransaction();
        
        try {
            // Get orders in this BL
            $orders = $this->getBonLivraisonOrders($blId);
            
            // Delete BL orders junction
            $this->query('DELETE FROM bon_livraison_orders WHERE bon_livraison_id = ?', [$blId]);
            
            // Reset order statuses
            foreach ($orders as $order) {
                $originalStatus = $order['type'] === 'ramassage' ? 'EN_ATTENTE_RAMASSAGE' : 'EN_ATTENTE_PREPARATION';
                $this->query('UPDATE orders SET status = ? WHERE id = ?', [$originalStatus, $order['id']]);
            }
            
            // Delete BL
            $this->delete($blId);
            
            $this->commit();
            return true;
            
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }
}