<?php

namespace App\Models;

class Order extends BaseModel
{
    protected $table = 'orders';
    protected $fillable = [
        'client_id', 'livreur_id', 'type', 'reference', 'product_name', 'quantity', 'price',
        'phone', 'city_id', 'address', 'note', 'status', 'tracking_code', 'delivery_date',
        'delivery_note', 'delivery_location', 'stock_item_id'
    ];

    public function createRamassageOrder(int $clientId, array $data): int
    {
        $data['client_id'] = $clientId;
        $data['type'] = 'ramassage';
        $data['status'] = 'EN_ATTENTE_RAMASSAGE';
        $data['reference'] = $this->generateReference('RAM');
        
        return $this->create($data);
    }

    public function createStockOrder(int $clientId, int $stockItemId, array $data): int
    {
        $data['client_id'] = $clientId;
        $data['stock_item_id'] = $stockItemId;
        $data['type'] = 'stock';
        $data['status'] = 'EN_ATTENTE_PREPARATION';
        $data['reference'] = $this->generateReference('STK');
        
        return $this->create($data);
    }

    public function getOrdersByClient(int $clientId, string $status = '', string $type = ''): array
    {
        $criteria = ['client_id' => $clientId];
        
        if (!empty($status)) {
            $criteria['status'] = $status;
        }
        
        if (!empty($type)) {
            $criteria['type'] = $type;
        }
        
        return $this->findAll($criteria, 'created_at DESC');
    }

    public function getOrdersByLivreur(int $livreurId, string $status = ''): array
    {
        $criteria = ['livreur_id' => $livreurId];
        
        if (!empty($status)) {
            $criteria['status'] = $status;
        }
        
        return $this->findAll($criteria, 'created_at DESC');
    }

    public function getOrdersForRamassage(int $clientId = 0): array
    {
        $sql = "SELECT o.*, c.name as city_name, u.first_name, u.last_name, u.store_name 
                FROM orders o 
                JOIN cities c ON o.city_id = c.id 
                JOIN users u ON o.client_id = u.id 
                WHERE o.status IN ('EN_ATTENTE_RAMASSAGE', 'EN_ATTENTE_PREPARATION')";
        
        $params = [];
        
        if ($clientId > 0) {
            $sql .= " AND o.client_id = ?";
            $params[] = $clientId;
        }
        
        $sql .= " ORDER BY o.created_at DESC";
        
        return $this->fetchAll($sql, $params);
    }

    public function getOrdersForDistribution(int $zoneId = 0): array
    {
        $sql = "SELECT o.*, c.name as city_name, z.name as zone_name, u.first_name, u.last_name, u.store_name 
                FROM orders o 
                JOIN cities c ON o.city_id = c.id 
                JOIN zones z ON c.zone_id = z.id 
                JOIN users u ON o.client_id = u.id 
                WHERE o.status = 'RAMASSE'";
        
        $params = [];
        
        if ($zoneId > 0) {
            $sql .= " AND z.id = ?";
            $params[] = $zoneId;
        }
        
        $sql .= " ORDER BY o.created_at DESC";
        
        return $this->fetchAll($sql, $params);
    }

    public function updateStatus(int $orderId, string $status, array $extraData = []): bool
    {
        $data = ['status' => $status];
        
        if (!empty($extraData)) {
            $data = array_merge($data, $extraData);
        }
        
        return $this->update($orderId, $data);
    }

    public function assignToLivreur(int $orderId, int $livreurId): bool
    {
        return $this->update($orderId, [
            'livreur_id' => $livreurId,
            'status' => 'MISE_EN_DISTRIBUTION'
        ]);
    }

    public function markAsDelivered(int $orderId, string $note = '', array $location = []): bool
    {
        $data = [
            'status' => 'LIVRE',
            'delivery_date' => date('Y-m-d H:i:s'),
            'delivery_note' => $note
        ];
        
        if (!empty($location)) {
            $data['delivery_location'] = json_encode($location);
        }
        
        return $this->update($orderId, $data);
    }

    public function markAsRefused(int $orderId, string $note = ''): bool
    {
        return $this->update($orderId, [
            'status' => 'REFUSE',
            'delivery_date' => date('Y-m-d H:i:s'),
            'delivery_note' => $note
        ]);
    }

    public function markAsCancelled(int $orderId, string $note = ''): bool
    {
        return $this->update($orderId, [
            'status' => 'ANNULE',
            'delivery_note' => $note
        ]);
    }

    public function findByTrackingCode(string $trackingCode): ?array
    {
        return $this->findBy(['tracking_code' => $trackingCode]);
    }

    public function generateTrackingCode(): string
    {
        do {
            $code = 'TRK' . strtoupper(bin2hex(random_bytes(4)));
        } while ($this->findByTrackingCode($code));
        
        return $code;
    }

    public function getOrderStats(): array
    {
        $stats = [];
        
        // Get order counts by status
        $sql = "SELECT status, COUNT(*) as count FROM orders GROUP BY status";
        $results = $this->fetchAll($sql);
        
        foreach ($results as $row) {
            $stats['by_status'][$row['status']] = (int) $row['count'];
        }
        
        // Get order counts by type
        $sql = "SELECT type, COUNT(*) as count FROM orders GROUP BY type";
        $results = $this->fetchAll($sql);
        
        foreach ($results as $row) {
            $stats['by_type'][$row['type']] = (int) $row['count'];
        }
        
        // Get delivery stats
        $sql = "SELECT 
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN status = 'LIVRE' THEN 1 ELSE 0 END) as delivered,
                    SUM(CASE WHEN status = 'REFUSE' THEN 1 ELSE 0 END) as refused,
                    SUM(CASE WHEN status = 'ANNULE' THEN 1 ELSE 0 END) as cancelled,
                    SUM(price) as total_revenue
                FROM orders";
        
        $result = $this->fetch($sql);
        $stats['overview'] = $result;
        
        return $stats;
    }

    public function getOrdersByDateRange(string $startDate, string $endDate): array
    {
        $sql = "SELECT o.*, c.name as city_name, u.first_name, u.last_name, u.store_name 
                FROM orders o 
                JOIN cities c ON o.city_id = c.id 
                JOIN users u ON o.client_id = u.id 
                WHERE o.created_at BETWEEN ? AND ?
                ORDER BY o.created_at DESC";
        
        return $this->fetchAll($sql, [$startDate, $endDate]);
    }

    public function searchOrders(string $query): array
    {
        $sql = "SELECT o.*, c.name as city_name, u.first_name, u.last_name, u.store_name 
                FROM orders o 
                JOIN cities c ON o.city_id = c.id 
                JOIN users u ON o.client_id = u.id 
                WHERE o.reference LIKE ? OR o.tracking_code LIKE ? OR o.product_name LIKE ? OR o.phone LIKE ?
                ORDER BY o.created_at DESC";
        
        $searchTerm = "%{$query}%";
        return $this->fetchAll($sql, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }

    public function getOrderWithDetails(int $orderId): ?array
    {
        $sql = "SELECT o.*, c.name as city_name, z.name as zone_name, 
                       client.first_name as client_first_name, client.last_name as client_last_name, 
                       client.store_name, client.email as client_email,
                       livreur.first_name as livreur_first_name, livreur.last_name as livreur_last_name,
                       livreur.email as livreur_email,
                       si.reference as stock_reference, si.description as stock_description
                FROM orders o 
                JOIN cities c ON o.city_id = c.id 
                JOIN zones z ON c.zone_id = z.id 
                JOIN users client ON o.client_id = client.id 
                LEFT JOIN users livreur ON o.livreur_id = livreur.id 
                LEFT JOIN stock_items si ON o.stock_item_id = si.id 
                WHERE o.id = ?";
        
        return $this->fetch($sql, [$orderId]);
    }
}