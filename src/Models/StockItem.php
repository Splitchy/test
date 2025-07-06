<?php

namespace App\Models;

class StockItem extends BaseModel
{
    protected $table = 'stock_items';
    protected $fillable = [
        'client_id', 'reference', 'quantity', 'description', 'photo_path', 'status'
    ];

    public function createStockItem(int $clientId, array $data): int
    {
        $data['client_id'] = $clientId;
        $data['reference'] = $this->generateReference('STK');
        $data['status'] = 'EN_ATTENTE';
        
        return $this->create($data);
    }

    public function getStockItemsByClient(int $clientId, string $status = ''): array
    {
        $criteria = ['client_id' => $clientId];
        
        if (!empty($status)) {
            $criteria['status'] = $status;
        }
        
        return $this->findAll($criteria, 'created_at DESC');
    }

    public function getPendingStockItems(): array
    {
        return $this->findAll(['status' => 'EN_ATTENTE'], 'created_at DESC');
    }

    public function getApprovedStockItems(): array
    {
        return $this->findAll(['status' => 'APPROUVE'], 'created_at DESC');
    }

    public function approveStockItem(int $itemId): bool
    {
        return $this->update($itemId, ['status' => 'APPROUVE']);
    }

    public function refuseStockItem(int $itemId): bool
    {
        return $this->update($itemId, ['status' => 'REFUSE']);
    }

    public function updateQuantity(int $itemId, int $quantity): bool
    {
        return $this->update($itemId, ['quantity' => $quantity]);
    }

    public function uploadPhoto(int $itemId, string $photoPath): bool
    {
        return $this->update($itemId, ['photo_path' => $photoPath]);
    }

    public function getStockItemWithClient(int $itemId): ?array
    {
        $sql = "SELECT si.*, u.first_name, u.last_name, u.store_name, u.email 
                FROM stock_items si 
                JOIN users u ON si.client_id = u.id 
                WHERE si.id = ?";
        
        return $this->fetch($sql, [$itemId]);
    }

    public function getStockItemsWithClient(string $status = ''): array
    {
        $sql = "SELECT si.*, u.first_name, u.last_name, u.store_name, u.email 
                FROM stock_items si 
                JOIN users u ON si.client_id = u.id";
        
        $params = [];
        
        if (!empty($status)) {
            $sql .= " WHERE si.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY si.created_at DESC";
        
        return $this->fetchAll($sql, $params);
    }

    public function searchStockItems(string $query): array
    {
        $sql = "SELECT si.*, u.first_name, u.last_name, u.store_name, u.email 
                FROM stock_items si 
                JOIN users u ON si.client_id = u.id 
                WHERE si.reference LIKE ? OR si.description LIKE ? OR u.store_name LIKE ?
                ORDER BY si.created_at DESC";
        
        $searchTerm = "%{$query}%";
        return $this->fetchAll($sql, [$searchTerm, $searchTerm, $searchTerm]);
    }

    public function getStockStats(): array
    {
        $stats = [];
        
        // Get stock counts by status
        $sql = "SELECT status, COUNT(*) as count FROM stock_items GROUP BY status";
        $results = $this->fetchAll($sql);
        
        foreach ($results as $row) {
            $stats['by_status'][$row['status']] = (int) $row['count'];
        }
        
        // Get total quantity
        $sql = "SELECT SUM(quantity) as total_quantity FROM stock_items WHERE status = 'APPROUVE'";
        $result = $this->fetch($sql);
        $stats['total_quantity'] = (int) $result['total_quantity'];
        
        return $stats;
    }

    public function findByReference(string $reference): ?array
    {
        return $this->findBy(['reference' => $reference]);
    }

    public function getAvailableStock(int $itemId): int
    {
        $item = $this->find($itemId);
        if (!$item || $item['status'] !== 'APPROUVE') {
            return 0;
        }
        
        // Check how many are already in orders
        $sql = "SELECT COALESCE(SUM(quantity), 0) as used_quantity 
                FROM orders 
                WHERE stock_item_id = ? 
                AND status NOT IN ('ANNULE', 'REFUSE')";
        
        $result = $this->fetch($sql, [$itemId]);
        $usedQuantity = (int) $result['used_quantity'];
        
        return max(0, $item['quantity'] - $usedQuantity);
    }

    public function canCreateOrder(int $itemId, int $requestedQuantity): bool
    {
        $availableQuantity = $this->getAvailableStock($itemId);
        return $availableQuantity >= $requestedQuantity;
    }

    public function getStockMovements(int $itemId): array
    {
        $sql = "SELECT o.*, u.first_name, u.last_name 
                FROM orders o 
                JOIN users u ON o.livreur_id = u.id 
                WHERE o.stock_item_id = ? 
                ORDER BY o.created_at DESC";
        
        return $this->fetchAll($sql, [$itemId]);
    }

    public function deleteStockItem(int $itemId): bool
    {
        // Check if there are any orders linked to this stock item
        $sql = "SELECT COUNT(*) as count FROM orders WHERE stock_item_id = ?";
        $result = $this->fetch($sql, [$itemId]);
        
        if ($result['count'] > 0) {
            return false; // Cannot delete if orders exist
        }
        
        return $this->delete($itemId);
    }
}